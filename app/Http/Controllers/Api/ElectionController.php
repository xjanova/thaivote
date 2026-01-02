<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionStats;
use App\Models\NationalResult;
use App\Models\Party;
use App\Models\ResultSnapshot;
use Illuminate\Http\JsonResponse;

class ElectionController extends Controller
{
    public function index(): JsonResponse
    {
        $elections = Election::with('stats')
            ->orderBy('election_date', 'desc')
            ->get();

        return response()->json($elections);
    }

    public function show(Election $election): JsonResponse
    {
        $election->load(['stats']);

        $nationalResults = NationalResult::with('party')
            ->where('election_id', $election->id)
            ->orderBy('total_seats', 'desc')
            ->get();

        $parties = Party::active()->get();

        return response()->json([
            'election' => $election,
            'stats' => $election->stats,
            'national_results' => $nationalResults,
            'parties' => $parties,
        ]);
    }

    public function active(): JsonResponse
    {
        $election = Election::active()->with('stats')->first();

        if (! $election) {
            return response()->json(['message' => 'No active election'], 404);
        }

        return $this->show($election);
    }

    public function stats(Election $election): JsonResponse
    {
        $stats = ElectionStats::where('election_id', $election->id)->first();

        return response()->json($stats);
    }

    public function nationalResults(Election $election): JsonResponse
    {
        $results = NationalResult::with('party')
            ->where('election_id', $election->id)
            ->orderBy('total_seats', 'desc')
            ->get();

        return response()->json($results);
    }

    public function timeline(Election $election): JsonResponse
    {
        $snapshots = ResultSnapshot::where('election_id', $election->id)
            ->orderBy('snapshot_at')
            ->get()
            ->map(fn ($snapshot) => [
                'time' => $snapshot->snapshot_at->format('H:i'),
                'timestamp' => $snapshot->snapshot_at->toIso8601String(),
                'counting_progress' => (float) $snapshot->counting_progress,
                'constituencies_counted' => $snapshot->constituencies_counted,
                'stations_counted' => $snapshot->stations_counted,
                'total_votes_cast' => $snapshot->total_votes_cast,
                'leading_parties' => $snapshot->leading_parties,
            ]);

        return response()->json([
            'election_id' => $election->id,
            'snapshots' => $snapshots,
            'total_snapshots' => $snapshots->count(),
        ]);
    }

    /**
     * Create a new timeline snapshot for the election.
     */
    public function createSnapshot(Election $election): JsonResponse
    {
        $snapshot = ResultSnapshot::createFromElection($election);

        return response()->json([
            'message' => 'Snapshot created successfully',
            'snapshot' => [
                'id' => $snapshot->id,
                'time' => $snapshot->snapshot_at->format('H:i'),
                'counting_progress' => (float) $snapshot->counting_progress,
            ],
        ], 201);
    }
}
