<?php

namespace App\Services;

use App\Models\Election;
use App\Models\ElectionStats;
use App\Models\NationalResult;
use App\Models\Party;
use App\Models\Province;
use App\Models\ProvinceResult;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service สำหรับดึงข้อมูลจาก ECT Report 69
 * แหล่งข้อมูล: https://ectreport69.ect.go.th/
 */
class ECTReport69Service
{
    protected string $baseUrl = 'https://ectreport69.ect.go.th';

    protected array $apiEndpoints = [
        'overview' => '/api/overview',
        'summary' => '/api/summary',
        'national' => '/api/national',
        'provinces' => '/api/provinces',
        'constituency' => '/api/constituency',
        'party_list' => '/api/party-list',
        'results' => '/api/results',
        'stats' => '/api/stats',
    ];

    protected array $headers = [
        'Accept' => 'application/json',
        'Accept-Language' => 'th-TH,th;q=0.9,en;q=0.8',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Referer' => 'https://ectreport69.ect.go.th/overview',
    ];

    /**
     * ดึงข้อมูลจาก ECT Report 69 API
     */
    public function fetchOverview(): ?array
    {
        $endpoints = [
            '/api/overview',
            '/api/v1/overview',
            '/api/election/overview',
            '/api/election/summary',
            '/api/summary',
        ];

        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::withHeaders($this->headers)
                    ->timeout(30)
                    ->get($this->baseUrl . $endpoint);

                if ($response->successful()) {
                    Log::info("ECT Report 69: Successfully fetched from {$endpoint}");

                    return $response->json();
                }
            } catch (Exception $e) {
                Log::debug("ECT Report 69: Failed {$endpoint}: {$e->getMessage()}");
            }
        }

        Log::warning('ECT Report 69: All API endpoints failed, using cached/manual data');

        return null;
    }

    /**
     * ดึงผลรายจังหวัด
     */
    public function fetchProvinceResults(): ?array
    {
        $endpoints = [
            '/api/provinces',
            '/api/v1/provinces',
            '/api/election/provinces',
        ];

        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::withHeaders($this->headers)
                    ->timeout(30)
                    ->get($this->baseUrl . $endpoint);

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (Exception $e) {
                Log::debug("ECT Report 69 provinces: Failed {$endpoint}: {$e->getMessage()}");
            }
        }

        return null;
    }

    /**
     * ดึงผลรายเขต
     */
    public function fetchConstituencyResults(int $provinceId): ?array
    {
        $endpoints = [
            "/api/constituency/{$provinceId}",
            "/api/v1/constituency/{$provinceId}",
            "/api/provinces/{$provinceId}/constituencies",
        ];

        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::withHeaders($this->headers)
                    ->timeout(30)
                    ->get($this->baseUrl . $endpoint);

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (Exception $e) {
                Log::debug("ECT Report 69 constituency: Failed {$endpoint}");
            }
        }

        return null;
    }

    /**
     * Scrape และอัพเดทข้อมูลทั้งหมด
     */
    public function scrapeAndUpdate(int $electionId): array
    {
        $stats = [
            'source' => 'ectreport69.ect.go.th',
            'fetched_at' => now()->toIso8601String(),
            'api_success' => false,
            'parties_updated' => 0,
            'provinces_updated' => 0,
        ];

        // พยายามดึงจาก API
        $data = $this->fetchOverview();

        if ($data) {
            $stats['api_success'] = true;
            $stats = array_merge($stats, $this->processApiData($electionId, $data));
        }

        // อัพเดท election stats
        $this->updateElectionStats($electionId);

        // Clear cache
        Cache::forget("live_results_{$electionId}");

        return $stats;
    }

    /**
     * Manually update results (for use when API is not available)
     */
    public function manualUpdate(int $electionId, array $partyResults): int
    {
        $updated = 0;

        foreach ($partyResults as $result) {
            $party = Party::where('party_number', $result['party_number'])->first();

            if (! $party) {
                continue;
            }

            NationalResult::updateOrCreate(
                [
                    'election_id' => $electionId,
                    'party_id' => $party->id,
                ],
                [
                    'constituency_votes' => $result['constituency_votes'] ?? 0,
                    'party_list_votes' => $result['party_list_votes'] ?? 0,
                    'total_votes' => $result['total_votes'] ?? 0,
                    'constituency_seats' => $result['constituency_seats'] ?? 0,
                    'party_list_seats' => $result['party_list_seats'] ?? 0,
                    'total_seats' => $result['total_seats'] ?? 0,
                    'rank' => $result['rank'] ?? $updated + 1,
                ],
            );

            $updated++;
        }

        // Update stats
        $this->updateElectionStats($electionId);

        // Clear cache
        Cache::forget("live_results_{$electionId}");

        return $updated;
    }

    /**
     * Schedule สำหรับ real-time update (ทุก 30 วินาที)
     */
    public function scheduledUpdate(): void
    {
        $election = Election::where('status', 'counting')
            ->orWhere('status', 'ongoing')
            ->first();

        if (! $election) {
            return;
        }

        $this->scrapeAndUpdate($election->id);
    }

    /**
     * Process API response data and store in database
     */
    protected function processApiData(int $electionId, array $data): array
    {
        $partiesUpdated = 0;
        $provincesUpdated = 0;

        // Process national results
        if (isset($data['parties']) || isset($data['results'])) {
            $partyResults = $data['parties'] ?? $data['results'] ?? [];

            foreach ($partyResults as $result) {
                $party = $this->findParty($result);

                if ($party) {
                    NationalResult::updateOrCreate(
                        [
                            'election_id' => $electionId,
                            'party_id' => $party->id,
                        ],
                        [
                            'constituency_votes' => $result['constituency_votes'] ?? $result['votes'] ?? 0,
                            'party_list_votes' => $result['party_list_votes'] ?? 0,
                            'total_votes' => $result['total_votes'] ?? $result['votes'] ?? 0,
                            'constituency_seats' => $result['constituency_seats'] ?? $result['seats'] ?? 0,
                            'party_list_seats' => $result['party_list_seats'] ?? 0,
                            'total_seats' => $result['total_seats'] ?? $result['seats'] ?? 0,
                            'rank' => $result['rank'] ?? 0,
                        ],
                    );
                    $partiesUpdated++;
                }
            }
        }

        // Process province results
        if (isset($data['provinces'])) {
            foreach ($data['provinces'] as $provinceData) {
                $provincesUpdated += $this->processProvinceData($electionId, $provinceData);
            }
        }

        return [
            'parties_updated' => $partiesUpdated,
            'provinces_updated' => $provincesUpdated,
        ];
    }

    /**
     * หาพรรคจากข้อมูล
     */
    protected function findParty(array $data): ?Party
    {
        if (isset($data['party_number'])) {
            $party = Party::where('party_number', $data['party_number'])->first();

            if ($party) {
                return $party;
            }
        }

        if (isset($data['party_name']) || isset($data['name_th'])) {
            $name = $data['party_name'] ?? $data['name_th'];

            return Party::where('name_th', $name)
                ->orWhere('name_th', 'LIKE', "%{$name}%")
                ->first();
        }

        return null;
    }

    /**
     * Process province-level data
     */
    protected function processProvinceData(int $electionId, array $data): int
    {
        $province = Province::where('name_th', $data['province_name'] ?? '')
            ->orWhere('name_en', $data['province_name_en'] ?? '')
            ->first();

        if (! $province) {
            return 0;
        }

        $updated = 0;

        foreach ($data['parties'] ?? [] as $partyResult) {
            $party = $this->findParty($partyResult);

            if ($party) {
                ProvinceResult::updateOrCreate(
                    [
                        'election_id' => $electionId,
                        'province_id' => $province->id,
                        'party_id' => $party->id,
                    ],
                    [
                        'total_votes' => $partyResult['votes'] ?? 0,
                        'seats_won' => $partyResult['seats'] ?? 0,
                        'vote_percentage' => $partyResult['percentage'] ?? 0,
                        'counting_progress' => $data['counting_progress'] ?? 0,
                    ],
                );
                $updated++;
            }
        }

        return $updated > 0 ? 1 : 0;
    }

    /**
     * อัพเดทสถิติการเลือกตั้ง
     */
    protected function updateElectionStats(int $electionId): void
    {
        $election = Election::find($electionId);

        if (! $election) {
            return;
        }

        $totalVotes = NationalResult::where('election_id', $electionId)->sum('total_votes');
        $totalSeats = NationalResult::where('election_id', $electionId)->sum('total_seats');

        ElectionStats::updateOrCreate(
            ['election_id' => $electionId],
            [
                'total_votes_cast' => $totalVotes,
                'voter_turnout' => $election->total_eligible_voters > 0
                    ? round(($totalVotes / $election->total_eligible_voters) * 100, 2)
                    : 0,
                'counting_progress' => $this->calculateCountingProgress($electionId),
                'constituencies_counted' => ProvinceResult::where('election_id', $electionId)
                    ->where('counting_progress', 100)
                    ->count(),
                'constituencies_total' => 400,
            ],
        );
    }

    /**
     * คำนวณ progress การนับคะแนน
     */
    protected function calculateCountingProgress(int $electionId): float
    {
        $provinces = ProvinceResult::where('election_id', $electionId)
            ->select('province_id')
            ->distinct()
            ->get();

        if ($provinces->isEmpty()) {
            return 0;
        }

        $avgProgress = ProvinceResult::where('election_id', $electionId)
            ->avg('counting_progress');

        return round($avgProgress ?? 0, 1);
    }
}
