<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Constituency;
use App\Models\ConstituencyResult;
use App\Models\Province;
use App\Models\ProvinceResult;
use Illuminate\Http\JsonResponse;

class ProvinceController extends Controller
{
    public function index(): JsonResponse
    {
        $provinces = Province::with(['districts'])
            ->orderBy('name_th')
            ->get();

        return response()->json($provinces);
    }

    public function show(Province $province): JsonResponse
    {
        $province->load(['districts', 'constituencies']);

        return response()->json($province);
    }

    public function geoJson(): JsonResponse
    {
        $provinces = Province::select(['id', 'code', 'name_th', 'name_en', 'geo_data'])
            ->get()
            ->map(function ($province) {
                return [
                    'type' => 'Feature',
                    'properties' => [
                        'id' => $province->id,
                        'code' => $province->code,
                        'name_th' => $province->name_th,
                        'name_en' => $province->name_en,
                    ],
                    'geometry' => $province->geo_data,
                ];
            });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $provinces,
        ]);
    }

    public function results(int $electionId, Province $province): JsonResponse
    {
        $results = ProvinceResult::with('party')
            ->where('election_id', $electionId)
            ->where('province_id', $province->id)
            ->orderBy('seats_won', 'desc')
            ->get();

        $constituencies = Constituency::where('province_id', $province->id)->get();

        $constituencyResults = ConstituencyResult::with(['candidate', 'party'])
            ->where('election_id', $electionId)
            ->whereIn('constituency_id', $constituencies->pluck('id'))
            ->get()
            ->groupBy('constituency_id');

        return response()->json([
            'province' => $province,
            'results' => $results,
            'constituencies' => $constituencies->map(function ($c) use ($constituencyResults) {
                $results = $constituencyResults->get($c->id, collect());

                return [
                    ...$c->toArray(),
                    'results' => $results,
                    'winner' => $results->firstWhere('is_winner', true),
                ];
            }),
        ]);
    }

    public function byRegion(string $region): JsonResponse
    {
        $provinces = Province::where('region', $region)
            ->orderBy('name_th')
            ->get();

        return response()->json($provinces);
    }
}
