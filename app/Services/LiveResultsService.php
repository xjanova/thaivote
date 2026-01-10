<?php

namespace App\Services;

use App\Models\Constituency;
use App\Models\Election;
use App\Models\NationalResult;
use App\Models\Province;
use App\Models\ProvinceResult;
use Illuminate\Support\Facades\Cache;

/**
 * Service สำหรับดึงและรวบรวมผลเลือกตั้งแบบ real-time
 */
class LiveResultsService
{
    protected array $regions = [
        'north' => ['name_th' => 'ภาคเหนือ', 'color' => '#22C55E'],
        'northeast' => ['name_th' => 'ภาคตะวันออกเฉียงเหนือ', 'color' => '#EF4444'],
        'central' => ['name_th' => 'ภาคกลาง', 'color' => '#F59E0B'],
        'east' => ['name_th' => 'ภาคตะวันออก', 'color' => '#3B82F6'],
        'west' => ['name_th' => 'ภาคตะวันตก', 'color' => '#8B5CF6'],
        'south' => ['name_th' => 'ภาคใต้', 'color' => '#EC4899'],
    ];

    /**
     * ดึงผลเลือกตั้งทั้งหมดแบบ real-time
     */
    public function getLiveResults(int $electionId): array
    {
        $cacheKey = "live_results_{$electionId}";

        return Cache::remember($cacheKey, 10, function () use ($electionId) {
            return [
                'election' => $this->getElectionInfo($electionId),
                'national' => $this->getNationalResults($electionId),
                'provinces' => $this->getAllProvincesResults($electionId),
                'regions' => $this->getRegionalResults($electionId),
            ];
        });
    }

    /**
     * ข้อมูลการเลือกตั้ง
     */
    public function getElectionInfo(int $electionId): array
    {
        $election = Election::find($electionId);

        if (! $election) {
            return [];
        }

        return [
            'id' => $election->id,
            'name' => $election->name,
            'date' => $election->election_date->format('d/m/Y'),
            'status' => $election->status,
            'total_eligible_voters' => $election->total_eligible_voters,
            'total_votes_cast' => $election->total_votes_cast,
            'voter_turnout' => $election->voter_turnout,
            'settings' => $election->settings,
        ];
    }

    /**
     * ผลระดับชาติ
     */
    public function getNationalResults(int $electionId): array
    {
        $results = NationalResult::where('election_id', $electionId)
            ->with('party')
            ->orderByDesc('total_seats')
            ->get();

        $totalVotes = $results->sum('total_votes');
        $totalSeats = $results->sum('total_seats');
        $stationsCounted = ProvinceResult::where('election_id', $electionId)
            ->sum('stations_counted');
        $totalStations = ProvinceResult::where('election_id', $electionId)
            ->sum('total_stations');

        return [
            'total_votes' => $totalVotes,
            'total_seats' => $totalSeats,
            'constituency_seats' => $results->sum('constituency_seats'),
            'party_list_seats' => $results->sum('party_list_seats'),
            'counting_progress' => $totalStations > 0
                ? round(($stationsCounted / $totalStations) * 100, 1)
                : 0,
            'stations_counted' => $stationsCounted,
            'total_stations' => $totalStations,
            'invalid_votes' => Election::find($electionId)?->settings['invalid_votes'] ?? 0,
            'voter_turnout' => Election::find($electionId)?->voter_turnout ?? 0,
            'parties' => $results->map(function ($result) use ($totalVotes) {
                return [
                    'id' => $result->party_id,
                    'name_th' => $result->party->name_th,
                    'name_en' => $result->party->name_en,
                    'abbreviation' => $result->party->abbreviation,
                    'color' => $result->party->color,
                    'party_number' => $result->party->party_number,
                    'seats' => $result->total_seats,
                    'constituency_seats' => $result->constituency_seats,
                    'party_list_seats' => $result->party_list_seats,
                    'total_votes' => $result->total_votes,
                    'vote_percentage' => $totalVotes > 0
                        ? round(($result->total_votes / $totalVotes) * 100, 2)
                        : 0,
                ];
            })->toArray(),
        ];
    }

    /**
     * ผลทุกจังหวัด
     */
    public function getAllProvincesResults(int $electionId): array
    {
        $provinces = Province::with(['results' => function ($query) use ($electionId) {
            $query->where('election_id', $electionId)->with('party');
        }])->get();

        $results = [];

        foreach ($provinces as $province) {
            $provinceResults = $province->results->sortByDesc('seats_won');
            $totalVotes = $provinceResults->sum('total_votes');

            $results[$province->id] = [
                'province_id' => $province->id,
                'name_th' => $province->name_th,
                'name_en' => $province->name_en,
                'region' => $province->region ?? 'central',
                'total_constituencies' => $province->total_constituencies,
                'counting_progress' => $provinceResults->first()?->counting_progress ?? 0,
                'total_votes' => $totalVotes,
                'parties' => $provinceResults->map(function ($result) use ($totalVotes) {
                    return [
                        'party_id' => $result->party_id,
                        'party' => [
                            'id' => $result->party->id,
                            'name_th' => $result->party->name_th,
                            'abbreviation' => $result->party->abbreviation,
                            'color' => $result->party->color,
                        ],
                        'seats_won' => $result->seats_won,
                        'total_votes' => $result->total_votes,
                        'vote_percentage' => $totalVotes > 0
                            ? round(($result->total_votes / $totalVotes) * 100, 2)
                            : 0,
                    ];
                })->values()->toArray(),
            ];
        }

        return $results;
    }

    /**
     * ผลรายจังหวัด
     */
    public function getProvinceResults(int $electionId, int $provinceId): array
    {
        $province = Province::with(['results' => function ($query) use ($electionId) {
            $query->where('election_id', $electionId)->with('party');
        }, 'constituencies.results' => function ($query) use ($electionId) {
            $query->where('election_id', $electionId)->with(['candidate', 'party']);
        }])->find($provinceId);

        if (! $province) {
            return [];
        }

        $results = $province->results->sortByDesc('seats_won');
        $totalVotes = $results->sum('total_votes');

        return [
            'province' => [
                'id' => $province->id,
                'name_th' => $province->name_th,
                'name_en' => $province->name_en,
                'total_constituencies' => $province->total_constituencies,
                'population' => $province->population,
            ],
            'counting_progress' => $results->first()?->counting_progress ?? 0,
            'total_votes' => $totalVotes,
            'parties' => $results->map(function ($result) use ($totalVotes) {
                return [
                    'party_id' => $result->party_id,
                    'party' => [
                        'id' => $result->party->id,
                        'name_th' => $result->party->name_th,
                        'abbreviation' => $result->party->abbreviation,
                        'color' => $result->party->color,
                    ],
                    'seats_won' => $result->seats_won,
                    'total_votes' => $result->total_votes,
                    'vote_percentage' => $totalVotes > 0
                        ? round(($result->total_votes / $totalVotes) * 100, 2)
                        : 0,
                ];
            })->values()->toArray(),
            'constituencies' => $province->constituencies->map(function ($constituency) {
                $winner = $constituency->results->sortByDesc('votes')->first();

                return [
                    'id' => $constituency->id,
                    'number' => $constituency->number,
                    'name' => $constituency->name,
                    'winner' => $winner ? [
                        'candidate' => [
                            'id' => $winner->candidate_id,
                            'full_name' => $winner->candidate?->full_name,
                        ],
                        'party' => [
                            'id' => $winner->party_id,
                            'name_th' => $winner->party?->name_th,
                            'abbreviation' => $winner->party?->abbreviation,
                            'color' => $winner->party?->color,
                        ],
                        'votes' => $winner->votes,
                    ] : null,
                ];
            })->toArray(),
        ];
    }

    /**
     * ผลรายเขตเลือกตั้ง
     */
    public function getConstituencyResults(int $electionId, int $constituencyId): array
    {
        $constituency = Constituency::with(['province', 'results' => function ($query) use ($electionId) {
            $query->where('election_id', $electionId)
                ->with(['candidate', 'party'])
                ->orderByDesc('votes');
        }])->find($constituencyId);

        if (! $constituency) {
            return [];
        }

        $totalVotes = $constituency->results->sum('votes');

        return [
            'constituency' => [
                'id' => $constituency->id,
                'number' => $constituency->number,
                'name' => $constituency->name,
                'province' => [
                    'id' => $constituency->province->id,
                    'name_th' => $constituency->province->name_th,
                ],
            ],
            'total_votes' => $totalVotes,
            'counting_progress' => $constituency->results->first()?->counting_progress ?? 0,
            'candidates' => $constituency->results->map(function ($result) use ($totalVotes) {
                return [
                    'candidate' => [
                        'id' => $result->candidate_id,
                        'title' => $result->candidate?->title,
                        'first_name' => $result->candidate?->first_name,
                        'last_name' => $result->candidate?->last_name,
                        'full_name' => $result->candidate?->full_name,
                        'photo' => $result->candidate?->photo,
                        'candidate_number' => $result->candidate?->candidate_number,
                    ],
                    'party' => [
                        'id' => $result->party_id,
                        'name_th' => $result->party?->name_th,
                        'abbreviation' => $result->party?->abbreviation,
                        'color' => $result->party?->color,
                        'party_number' => $result->party?->party_number,
                    ],
                    'votes' => $result->votes,
                    'vote_percentage' => $totalVotes > 0
                        ? round(($result->votes / $totalVotes) * 100, 2)
                        : 0,
                    'is_winner' => $result->is_winner ?? false,
                ];
            })->toArray(),
        ];
    }

    /**
     * ผลแยกตามภาค
     */
    public function getRegionalResults(int $electionId): array
    {
        $provinces = Province::with(['results' => function ($query) use ($electionId) {
            $query->where('election_id', $electionId)->with('party');
        }])->get();

        $regionResults = [];

        foreach ($this->regions as $regionKey => $regionInfo) {
            $regionProvinces = $provinces->filter(function ($province) use ($regionKey) {
                return ($province->region ?? 'central') === $regionKey;
            });

            $partySeats = [];

            foreach ($regionProvinces as $province) {
                foreach ($province->results as $result) {
                    $partyId = $result->party_id;

                    if (! isset($partySeats[$partyId])) {
                        $partySeats[$partyId] = [
                            'id' => $result->party->id,
                            'name_th' => $result->party->name_th,
                            'abbreviation' => $result->party->abbreviation,
                            'color' => $result->party->color,
                            'seats' => 0,
                            'votes' => 0,
                        ];
                    }
                    $partySeats[$partyId]['seats'] += $result->seats_won;
                    $partySeats[$partyId]['votes'] += $result->total_votes;
                }
            }

            // Sort by seats
            usort($partySeats, fn ($a, $b) => $b['seats'] - $a['seats']);

            $regionResults[$regionKey] = [
                'key' => $regionKey,
                'name_th' => $regionInfo['name_th'],
                'color' => $regionInfo['color'],
                'total_provinces' => $regionProvinces->count(),
                'total_seats' => collect($partySeats)->sum('seats'),
                'total_votes' => collect($partySeats)->sum('votes'),
                'parties' => array_values($partySeats),
            ];
        }

        return $regionResults;
    }

    /**
     * ล้าง cache
     */
    public function clearCache(int $electionId): void
    {
        Cache::forget("live_results_{$electionId}");
    }
}
