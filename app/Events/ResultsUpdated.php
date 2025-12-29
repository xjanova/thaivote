<?php

namespace App\Events;

use App\Models\Election;
use App\Models\NationalResult;
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

    public function __construct(Election $election)
    {
        $this->election = $election;

        // Get latest results
        $this->results = [
            'national' => NationalResult::with('party')
                ->where('election_id', $election->id)
                ->orderBy('total_seats', 'desc')
                ->get()
                ->toArray(),
            'stats' => $election->stats?->toArray(),
            'updated_at' => now()->toIso8601String(),
        ];
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
            'results' => $this->results,
        ];
    }
}
