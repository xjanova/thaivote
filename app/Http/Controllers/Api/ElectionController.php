<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionStats;
use App\Models\NationalResult;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        if (!$election) {
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
        // Get historical snapshots of results
        $timeline = cache()->remember(
            "election.{$election->id}.timeline",
            60,
            function () use ($election) {
                // This would fetch from a timeline table if we had one
                return [];
            }
        );

        return response()->json($timeline);
    }
}
