<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Constituency;
use App\Models\ConstituencyResult;
use App\Models\StationResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConstituencyController extends Controller
{
    public function index(int $provinceId): JsonResponse
    {
        $constituencies = Constituency::where('province_id', $provinceId)
            ->orderBy('number')
            ->get();

        return response()->json($constituencies);
    }

    public function show(Constituency $constituency): JsonResponse
    {
        $constituency->load(['province', 'pollingStations']);

        return response()->json($constituency);
    }

    public function results(int $electionId, Constituency $constituency): JsonResponse
    {
        $results = ConstituencyResult::with(['candidate', 'party'])
            ->where('election_id', $electionId)
            ->where('constituency_id', $constituency->id)
            ->orderBy('votes', 'desc')
            ->get();

        $stationResults = StationResult::with('candidate')
            ->where('election_id', $electionId)
            ->whereIn('polling_station_id', $constituency->pollingStations->pluck('id'))
            ->get()
            ->groupBy('polling_station_id');

        return response()->json([
            'constituency' => $constituency,
            'results' => $results,
            'winner' => $results->firstWhere('is_winner', true),
            'stations' => $constituency->pollingStations->map(function ($station) use ($stationResults) {
                return [
                    ...$station->toArray(),
                    'results' => $stationResults->get($station->id, []),
                ];
            }),
        ]);
    }
}
