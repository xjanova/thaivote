<?php

namespace App\Console\Commands;

use App\Events\ResultsUpdated;
use App\Models\Election;
use App\Models\NationalResult;
use App\Services\ECTReport69Service;
use Exception;
use Illuminate\Console\Command;

class ScrapeECTReport69Command extends Command
{
    protected $signature = 'ect:scrape69
                            {--election-id= : Election ID to update}
                            {--refs : Sync reference data only (parties, provinces, constituencies, candidates)}
                            {--live : Sync live results only (party stats, constituency stats)}
                            {--full : Full sync (reference + live data)}
                            {--once : Run once instead of continuous}
                            {--interval=30 : Interval in seconds for continuous mode}
                            {--broadcast : Broadcast results update via WebSocket}';

    protected $description = 'Scrape election data from ECT Report 69 (ectreport69.ect.go.th)';

    public function __construct(
        protected ECTReport69Service $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $electionId = $this->option('election-id')
            ?? Election::where('status', 'counting')
                ->orWhere('status', 'ongoing')
                ->orderBy('election_date', 'desc')
                ->first()?->id;

        if (! $electionId) {
            $this->error('No active election found. Use --election-id to specify.');

            return Command::FAILURE;
        }

        $election = Election::find($electionId);
        $this->info("ECT Report 69 Scraper for: {$election->name}");
        $this->newLine();

        // Determine sync mode
        $syncRefs = $this->option('refs');
        $syncLive = $this->option('live');
        $syncFull = $this->option('full');

        // Default: live only (for continuous polling)
        if (! $syncRefs && ! $syncLive && ! $syncFull) {
            $syncLive = true;
        }

        if ($this->option('once') || $syncRefs || $syncFull) {
            return $this->runOnce($electionId, $syncRefs, $syncLive, $syncFull);
        }

        return $this->runContinuous($electionId);
    }

    protected function runOnce(int $electionId, bool $syncRefs, bool $syncLive, bool $syncFull): int
    {
        if ($syncFull) {
            return $this->syncFull($electionId);
        }

        if ($syncRefs) {
            return $this->syncReferences($electionId);
        }

        return $this->syncLive($electionId);
    }

    protected function syncFull(int $electionId): int
    {
        $this->info('Full sync: reference data + live results...');
        $this->newLine();

        $results = $this->service->fullSync($electionId);

        // Show reference results
        $this->info('Reference Data:');
        $refRows = [];

        foreach ($results['reference'] ?? [] as $key => $result) {
            $status = ($result['success'] ?? false) ? '<fg=green>OK</>' : '<fg=red>FAIL</>';
            $refRows[] = [$key, $status, $result['synced'] ?? 0, $result['message'] ?? ''];
        }

        $this->table(['Type', 'Status', 'Synced', 'Message'], $refRows);
        $this->newLine();

        // Show live results
        $this->info('Live Results:');
        $liveRows = [];

        foreach ($results['live'] ?? [] as $key => $result) {
            $status = ($result['success'] ?? false) ? '<fg=green>OK</>' : '<fg=red>FAIL</>';
            $liveRows[] = [$key, $status, $result['synced'] ?? 0, $result['message'] ?? ''];
        }

        $this->table(['Type', 'Status', 'Synced', 'Message'], $liveRows);

        $this->broadcastIfNeeded($electionId);
        $this->showStandings($electionId);

        return Command::SUCCESS;
    }

    protected function syncReferences(int $electionId): int
    {
        $this->info('Syncing reference data from ECT...');
        $this->newLine();

        $results = $this->service->syncReferenceData($electionId);

        $rows = [];

        foreach ($results as $key => $result) {
            $status = ($result['success'] ?? false) ? '<fg=green>OK</>' : '<fg=red>FAIL</>';
            $rows[] = [$key, $status, $result['synced'] ?? 0, $result['message'] ?? ''];
        }

        $this->table(['Type', 'Status', 'Synced', 'Message'], $rows);

        return Command::SUCCESS;
    }

    protected function syncLive(int $electionId): int
    {
        $this->info('Syncing live results from ECT...');

        $stats = $this->service->scrapeAndUpdate($electionId);

        if ($stats['api_success']) {
            $this->info('API fetch successful!');
        } else {
            $this->warn('API fetch failed. Using cached/manual data.');
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Source', $stats['source']],
                ['Fetched at', $stats['fetched_at']],
                ['API Success', $stats['api_success'] ? 'Yes' : 'No'],
                ['Parties Updated', $stats['parties_updated']],
                ['Constituency Records', $stats['provinces_updated']],
            ],
        );

        $this->broadcastIfNeeded($electionId);
        $this->showStandings($electionId);

        return Command::SUCCESS;
    }

    protected function runContinuous(int $electionId): int
    {
        $interval = (int) $this->option('interval');
        $this->info("Starting continuous scraping (every {$interval}s)...");
        $this->info('Press Ctrl+C to stop.');
        $this->info('Source: stats-ectreport69.ect.go.th');
        $this->newLine();

        while (true) {
            $this->line('[' . now()->format('H:i:s') . '] Fetching live results...');
            $stats = $this->service->scrapeAndUpdate($electionId);

            $status = $stats['api_success'] ? '<fg=green>OK</>' : '<fg=yellow>CACHED</>';
            $this->line("  Status: {$status} | Parties: {$stats['parties_updated']} | Constituencies: {$stats['provinces_updated']}");

            $this->broadcastIfNeeded($electionId);

            sleep($interval);
        }
    }

    protected function broadcastIfNeeded(int $electionId): void
    {
        if (! $this->option('broadcast')) {
            return;
        }

        $election = Election::find($electionId);

        if (! $election) {
            return;
        }

        try {
            $results = NationalResult::where('election_id', $electionId)
                ->with('party')
                ->orderByDesc('total_seats')
                ->get()
                ->toArray();

            event(new ResultsUpdated($election, $results));
            $this->line('  <fg=cyan>Broadcasted results update</>');
        } catch (Exception $e) {
            $this->warn("  Broadcast failed: {$e->getMessage()}");
        }
    }

    protected function showStandings(int $electionId): void
    {
        $this->newLine();
        $this->info('Current Standings:');

        $results = NationalResult::where('election_id', $electionId)
            ->with('party')
            ->orderByDesc('total_seats')
            ->limit(15)
            ->get();

        if ($results->isEmpty()) {
            $this->warn('No results data yet.');

            return;
        }

        $rows = $results->map(fn ($r, $i) => [
            $i + 1,
            $r->party?->name_th ?? 'Unknown',
            $r->party?->party_number ?? '-',
            number_format($r->total_votes),
            $r->constituency_seats,
            $r->party_list_seats,
            $r->total_seats,
        ])->toArray();

        $this->table(
            ['#', 'Party', 'No.', 'Votes', 'Constituency', 'Party List', 'Total Seats'],
            $rows,
        );
    }
}
