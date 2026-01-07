<?php

namespace App\Events;

use App\Models\Election;
use App\Models\NationalResult;
use App\Services\LiveResultsService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResultsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Election $election;

    public array $results;

    public ?string $message;

    public ?array $party;

    public string $updateType;

    public function __construct(
        Election $election,
        ?string $message = null,
        ?array $party = null,
        string $updateType = 'general'
    ) {
        $this->election = $election;
        $this->message = $message;
        $this->party = $party;
        $this->updateType = $updateType;

        // Get latest results using service
        $resultsService = app(LiveResultsService::class);
        $this->results = $resultsService->getLiveResults($election->id);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('election.' . $this->election->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ResultsUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'election_id' => $this->election->id,
            'results' => $this->results,
            'message' => $this->message,
            'party' => $this->party,
            'update_type' => $this->updateType,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
