<?php

namespace App\Console\Commands;

use App\Models\Election;
use App\Models\NationalResult;
use App\Services\ECTReport69Service;
use Illuminate\Console\Command;

class ScrapeECTReport69Command extends Command
{
    protected $signature = 'ect:scrape69
                            {--election-id= : Election ID to update}
                            {--once : Run once instead of continuous}
                            {--interval=30 : Interval in seconds for continuous mode}';

    protected $description = 'Scrape election results from ECT Report 69 (ectreport69.ect.go.th)';

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
        $this->info("Scraping ECT Report 69 for: {$election->name}");
        $this->newLine();

        if ($this->option('once')) {
            return $this->scrapeOnce($electionId);
        }

        return $this->scrapeContinuous($electionId);
    }

    protected function scrapeOnce(int $electionId): int
    {
        $this->info('Fetching data from ectreport69.ect.go.th...');

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
                ['Provinces Updated', $stats['provinces_updated']],
            ],
        );

        // Show current standings
        $this->showStandings($electionId);

        return Command::SUCCESS;
    }

    protected function scrapeContinuous(int $electionId): int
    {
        $interval = (int) $this->option('interval');
        $this->info("Starting continuous scraping (every {$interval}s)...");
        $this->info('Press Ctrl+C to stop.');
        $this->newLine();

        while (true) {
            $this->line('[' . now()->format('H:i:s') . '] Fetching...');
            $stats = $this->service->scrapeAndUpdate($electionId);

            $status = $stats['api_success'] ? '<fg=green>OK</>' : '<fg=yellow>CACHED</>';
            $this->line("  Status: {$status} | Parties: {$stats['parties_updated']} | Provinces: {$stats['provinces_updated']}");

            sleep($interval);
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
