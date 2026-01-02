<?php

namespace App\Jobs;

use App\Models\Election;
use App\Models\ResultSnapshot;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreateElectionSnapshot implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $electionId = null,
    ) {}

    public function handle(): void
    {
        $query = Election::query()
            ->whereIn('status', ['ongoing', 'counting']);

        if ($this->electionId) {
            $query->where('id', $this->electionId);
        }

        $elections = $query->get();

        foreach ($elections as $election) {
            try {
                ResultSnapshot::createFromElection($election);
                Log::info("Created snapshot for election {$election->id}");
            } catch (Exception $e) {
                Log::error("Failed to create snapshot for election {$election->id}: {$e->getMessage()}");
            }
        }
    }
}
