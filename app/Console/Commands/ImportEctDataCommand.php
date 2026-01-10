<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\Constituency;
use App\Models\Election;
use App\Models\Party;
use App\Models\Province;
use App\Services\ECTScraperService;
use Illuminate\Console\Command;

class ImportEctDataCommand extends Command
{
    protected $signature = 'ect:import
                            {--parties : Import party data only}
                            {--candidates : Import candidate data only}
                            {--election : Create election 2569}
                            {--constituencies : Update constituency allocation}
                            {--all : Import all data}
                            {--fresh : Clear existing data before import}';

    protected $description = 'Import election data from ECT (กกต.) for 2569 election';

    protected ECTScraperService $scraper;

    public function __construct(ECTScraperService $scraper)
    {
        parent::__construct();
        $this->scraper = $scraper;
    }

    public function handle(): int
    {
        $this->info('Starting ECT data import...');
        $this->newLine();

        $importAll = $this->option('all');

        if ($this->option('fresh')) {
            if ($this->confirm('This will delete existing election 2569 data. Continue?', false)) {
                $this->clearExistingData();
            }
        }

        if ($importAll || $this->option('election')) {
            $this->importElection();
        }

        if ($importAll || $this->option('parties')) {
            $this->importParties();
        }

        if ($importAll || $this->option('constituencies')) {
            $this->updateConstituencies();
        }

        if ($importAll || $this->option('candidates')) {
            $this->importCandidates();
        }

        $this->newLine();
        $this->info('ECT data import completed!');

        return Command::SUCCESS;
    }

    protected function clearExistingData(): void
    {
        $this->warn('Clearing existing 2569 election data...');

        $election = Election::where('name', 'การเลือกตั้ง ส.ส. 2569')->first();

        if ($election) {
            Candidate::where('election_id', $election->id)->delete();
            $election->delete();
        }

        $this->info('Existing data cleared.');
    }

    protected function importElection(): void
    {
        $this->info('Creating Election 2569...');

        $election = $this->scraper->createElection2569();

        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $election->name],
                ['Date', $election->election_date->format('d/m/Y')],
                ['Status', $election->status],
                ['Eligible Voters', number_format($election->total_eligible_voters)],
                ['Total Seats', $election->settings['total_seats'] ?? 500],
            ],
        );
    }

    protected function importParties(): void
    {
        $this->info('Importing political parties for 2569...');

        $parties = $this->scraper->getParties2569();
        $bar = $this->output->createProgressBar(count($parties));

        $created = 0;
        $updated = 0;

        foreach ($parties as $partyData) {
            $party = Party::where('name_th', $partyData['name_th'])->first();

            $data = [
                'name_th' => $partyData['name_th'],
                'name_en' => $partyData['name_en'],
                'abbreviation' => $partyData['abbreviation'],
                'color' => $partyData['color'],
                'secondary_color' => $partyData['secondary_color'] ?? null,
                'party_number' => $partyData['party_number'],
                'is_active' => true,
            ];

            if ($party) {
                $party->update($data);
                $updated++;
            } else {
                Party::create($data);
                $created++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Parties: {$created} created, {$updated} updated");
    }

    protected function updateConstituencies(): void
    {
        $this->info('Updating constituency allocation for 2569...');

        $allocation = $this->scraper->getConstituencyAllocation2569();
        $provinces = Province::all()->keyBy('name_th');

        $updated = 0;

        foreach ($allocation as $provinceName => $seatCount) {
            $province = $provinces->get($provinceName);

            if ($province) {
                $province->update(['total_constituencies' => $seatCount]);

                // สร้างเขตเลือกตั้งถ้ายังไม่มี
                for ($i = 1; $i <= $seatCount; $i++) {
                    Constituency::updateOrCreate(
                        [
                            'province_id' => $province->id,
                            'number' => $i,
                        ],
                        [
                            'name' => "เขตเลือกตั้งที่ {$i}",
                        ],
                    );
                }

                $updated++;
            } else {
                $this->warn("Province not found: {$provinceName}");
            }
        }

        $this->info("Updated {$updated} provinces with constituency allocation");
    }

    protected function importCandidates(): void
    {
        $this->info('Importing PM candidates for 2569...');

        $election = Election::where('name', 'การเลือกตั้ง ส.ส. 2569')->first();

        if (! $election) {
            $this->error('Election 2569 not found. Run with --election first.');

            return;
        }

        $pmCandidates = $this->scraper->getPmCandidates2569();
        $parties = Party::all()->keyBy('name_th');

        $bar = $this->output->createProgressBar(count($pmCandidates));
        $created = 0;
        $skipped = 0;

        foreach ($pmCandidates as $candidateData) {
            $party = $parties->get($candidateData['party_name']);

            if (! $party) {
                $this->newLine();
                $this->warn("Party not found: {$candidateData['party_name']}");
                $skipped++;
                $bar->advance();
                continue;
            }

            Candidate::updateOrCreate(
                [
                    'election_id' => $election->id,
                    'party_id' => $party->id,
                    'first_name' => $candidateData['first_name'],
                    'last_name' => $candidateData['last_name'],
                ],
                [
                    'title' => $candidateData['title'] ?? '',
                    'candidate_number' => $candidateData['party_list_order'] ?? 1,
                    'type' => 'party_list',
                    'party_list_order' => $candidateData['party_list_order'] ?? 1,
                    'is_pm_candidate' => $candidateData['is_pm_candidate'] ?? false,
                ],
            );

            $created++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("PM Candidates: {$created} imported, {$skipped} skipped");

        // แสดงสรุป
        $this->newLine();
        $this->table(
            ['Category', 'Count'],
            [
                ['Total PM Candidates', Candidate::where('election_id', $election->id)->where('is_pm_candidate', true)->count()],
                ['Total Party List', Candidate::where('election_id', $election->id)->where('type', 'party_list')->count()],
                ['Parties with Candidates', Party::whereHas('candidates', fn ($q) => $q->where('election_id', $election->id))->count()],
            ],
        );
    }
}
