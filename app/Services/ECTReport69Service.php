<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Constituency;
use App\Models\ConstituencyResult;
use App\Models\Election;
use App\Models\ElectionStats;
use App\Models\NationalResult;
use App\Models\Party;
use App\Models\Province;
use App\Models\ProvinceResult;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service สำหรับดึงข้อมูลจาก ECT Report 69 (ectreport69.ect.go.th)
 *
 * แหล่งข้อมูล:
 * - Reference data: static-ectreport69.ect.go.th/data/data/refs/
 * - Live stats: stats-ectreport69.ect.go.th/data/records/
 */
class ECTReport69Service
{
    protected string $staticBase = 'https://static-ectreport69.ect.go.th/data/data/refs';

    protected string $statsBase = 'https://stats-ectreport69.ect.go.th/data/records';

    protected string $logoBase = 'https://static-ectreport69.ect.go.th';

    /**
     * ECT API endpoints
     */
    protected array $endpoints = [
        // ข้อมูลอ้างอิง (Reference Data)
        'provinces' => '/info_province.json',
        'constituencies' => '/info_constituency.json',
        'parties' => '/info_party_overview.json',
        'mp_candidates' => '/info_mp_candidate.json',
        'party_candidates' => '/info_party_candidate.json',

        // ข้อมูลสถิติ (Live Stats)
        'stats_constituency' => '/stats_cons.json',
        'stats_party' => '/stats_party.json',
    ];

    protected array $headers = [
        'Accept' => 'application/json',
        'Accept-Language' => 'th-TH,th;q=0.9,en;q=0.8',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Referer' => 'https://ectreport69.ect.go.th/',
        'Origin' => 'https://ectreport69.ect.go.th',
    ];

    // =============================================
    // Fetch Methods - ดึงข้อมูลจาก ECT API
    // =============================================

    /**
     * ดึงข้อมูลจาก static reference endpoint
     */
    public function fetchReference(string $key): ?array
    {
        $endpoint = $this->endpoints[$key] ?? null;

        if (! $endpoint) {
            Log::warning("ECT69: Unknown reference endpoint: {$key}");

            return null;
        }

        $url = $this->staticBase . $endpoint;

        return $this->fetchJson($url, "reference:{$key}");
    }

    /**
     * ดึงข้อมูลจาก stats endpoint
     */
    public function fetchStats(string $key): ?array
    {
        $statsKey = "stats_{$key}";
        $endpoint = $this->endpoints[$statsKey] ?? $this->endpoints[$key] ?? null;

        if (! $endpoint) {
            Log::warning("ECT69: Unknown stats endpoint: {$key}");

            return null;
        }

        $url = $this->statsBase . $endpoint;

        return $this->fetchJson($url, "stats:{$key}");
    }

    /**
     * ดึง JSON จาก URL
     */
    protected function fetchJson(string $url, string $label): ?array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("ECT69: Fetched {$label} successfully", [
                    'url' => $url,
                    'count' => is_array($data) ? count($data) : 'object',
                ]);

                return $data;
            }

            Log::warning("ECT69: HTTP {$response->status()} for {$label}", [
                'url' => $url,
            ]);
        } catch (Exception $e) {
            Log::error("ECT69: Failed to fetch {$label}: {$e->getMessage()}", [
                'url' => $url,
            ]);
        }

        return null;
    }

    // =============================================
    // Sync Methods - ซิงค์ข้อมูลเข้า Database
    // =============================================

    /**
     * ซิงค์ข้อมูลพรรคการเมืองจาก ECT
     */
    public function syncParties(): array
    {
        $data = $this->fetchReference('parties');

        if (! $data) {
            return ['success' => false, 'synced' => 0, 'message' => 'ไม่สามารถดึงข้อมูลพรรคได้'];
        }

        $synced = 0;
        $parties = $this->normalizeArray($data);

        foreach ($parties as $item) {
            $partyNumber = $this->extractField($item, ['party_id', 'partyId', 'party_no', 'partyNo', 'id', 'no']);
            $nameTh = $this->extractField($item, ['party_name', 'partyName', 'party_name_th', 'partyNameTh', 'name_th', 'name']);
            $nameEn = $this->extractField($item, ['party_name_en', 'partyNameEn', 'name_en', 'nameEn']);
            $abbreviation = $this->extractField($item, ['party_abbr', 'partyAbbr', 'abbreviation', 'abbr', 'short_name', 'shortName']);
            $color = $this->extractField($item, ['party_color', 'partyColor', 'color']);
            $logo = $this->extractField($item, ['party_logo', 'partyLogo', 'logo', 'logo_url', 'logoUrl', 'img', 'image']);
            $leader = $this->extractField($item, ['leader', 'leader_name', 'leaderName', 'pm_candidate', 'pmCandidate']);

            if (! $partyNumber || ! $nameTh) {
                continue;
            }

            // สร้าง logo URL ที่สมบูรณ์
            if ($logo && ! str_starts_with($logo, 'http')) {
                $logo = $this->logoBase . '/' . ltrim($logo, '/');
            }

            Party::updateOrCreate(
                ['party_number' => $partyNumber],
                array_filter([
                    'name_th' => $nameTh,
                    'name_en' => $nameEn ?: null,
                    'abbreviation' => $abbreviation ?: mb_substr($nameTh, 0, 4),
                    'color' => $color ?: '#6b7280',
                    'logo' => $logo ?: null,
                    'leader_name' => $leader ?: null,
                    'is_active' => true,
                ], fn ($v) => $v !== null),
            );

            $synced++;
        }

        Log::info("ECT69: Synced {$synced} parties");

        return ['success' => true, 'synced' => $synced];
    }

    /**
     * ซิงค์ข้อมูลจังหวัดจาก ECT
     */
    public function syncProvinces(): array
    {
        $data = $this->fetchReference('provinces');

        if (! $data) {
            return ['success' => false, 'synced' => 0, 'message' => 'ไม่สามารถดึงข้อมูลจังหวัดได้'];
        }

        $synced = 0;
        $provinces = $this->normalizeArray($data);

        foreach ($provinces as $item) {
            $code = $this->extractField($item, ['province_id', 'provinceId', 'prov_id', 'provId', 'id', 'code']);
            $nameTh = $this->extractField($item, ['province_name', 'provinceName', 'province_name_th', 'provinceNameTh', 'name_th', 'name']);
            $nameEn = $this->extractField($item, ['province_name_en', 'provinceNameEn', 'name_en', 'nameEn']);
            $region = $this->extractField($item, ['region', 'region_name', 'regionName', 'area']);
            $totalConst = $this->extractField($item, ['total_constituency', 'totalConstituency', 'constituency_count', 'constituencyCount', 'num_cons', 'numCons', 'cons_count']);

            if (! $code || ! $nameTh) {
                continue;
            }

            Province::updateOrCreate(
                ['code' => (string) $code],
                array_filter([
                    'name_th' => $nameTh,
                    'name_en' => $nameEn ?: null,
                    'region' => $this->mapRegion($region),
                    'total_constituencies' => $totalConst ?: null,
                ], fn ($v) => $v !== null),
            );

            $synced++;
        }

        Log::info("ECT69: Synced {$synced} provinces");

        return ['success' => true, 'synced' => $synced];
    }

    /**
     * ซิงค์ข้อมูลเขตเลือกตั้งจาก ECT
     */
    public function syncConstituencies(): array
    {
        $data = $this->fetchReference('constituencies');

        if (! $data) {
            return ['success' => false, 'synced' => 0, 'message' => 'ไม่สามารถดึงข้อมูลเขตได้'];
        }

        $synced = 0;
        $constituencies = $this->normalizeArray($data);

        // Cache provinces by code for lookup
        $provinceMap = Province::pluck('id', 'code')->toArray();

        foreach ($constituencies as $item) {
            $consId = $this->extractField($item, ['constituency_id', 'constituencyId', 'cons_id', 'consId', 'id']);
            $provCode = $this->extractField($item, ['province_id', 'provinceId', 'prov_id', 'provId']);
            $number = $this->extractField($item, ['constituency_no', 'constituencyNo', 'cons_no', 'consNo', 'number', 'no']);
            $name = $this->extractField($item, ['constituency_name', 'constituencyName', 'cons_name', 'consName', 'name']);
            $voters = $this->extractField($item, ['total_eligible_voters', 'totalEligibleVoters', 'eligible_voters', 'eligibleVoters', 'voters']);
            $stations = $this->extractField($item, ['total_polling_stations', 'totalPollingStations', 'polling_stations', 'pollingStations', 'stations', 'num_station', 'numStation']);

            if (! $provCode || ! $number) {
                continue;
            }

            $provinceId = $provinceMap[(string) $provCode] ?? null;

            if (! $provinceId) {
                continue;
            }

            Constituency::updateOrCreate(
                ['province_id' => $provinceId, 'number' => $number],
                array_filter([
                    'name' => $name ?: null,
                    'total_eligible_voters' => $voters ?: null,
                    'total_polling_stations' => $stations ?: null,
                ], fn ($v) => $v !== null),
            );

            $synced++;
        }

        Log::info("ECT69: Synced {$synced} constituencies");

        return ['success' => true, 'synced' => $synced];
    }

    /**
     * ซิงค์ผู้สมัคร ส.ส. แบ่งเขต จาก ECT
     */
    public function syncMpCandidates(int $electionId): array
    {
        $data = $this->fetchReference('mp_candidates');

        if (! $data) {
            return ['success' => false, 'synced' => 0, 'message' => 'ไม่สามารถดึงข้อมูลผู้สมัครได้'];
        }

        $synced = 0;
        $candidates = $this->normalizeArray($data);

        // Cache lookups
        $provinceMap = Province::pluck('id', 'code')->toArray();
        $partyMap = Party::pluck('id', 'party_number')->toArray();

        foreach ($candidates as $item) {
            $provCode = $this->extractField($item, ['province_id', 'provinceId', 'prov_id']);
            $consNo = $this->extractField($item, ['constituency_no', 'constituencyNo', 'cons_no', 'consNo']);
            $candNo = $this->extractField($item, ['candidate_no', 'candidateNo', 'cand_no', 'candNo', 'number', 'no']);
            $partyNo = $this->extractField($item, ['party_id', 'partyId', 'party_no', 'partyNo']);
            $title = $this->extractField($item, ['title', 'prefix']);
            $firstName = $this->extractField($item, ['first_name', 'firstName', 'fname']);
            $lastName = $this->extractField($item, ['last_name', 'lastName', 'lname']);

            if (! $provCode || ! $consNo) {
                continue;
            }

            $provinceId = $provinceMap[(string) $provCode] ?? null;
            $partyId = $partyMap[(int) $partyNo] ?? null;

            if (! $provinceId) {
                continue;
            }

            $constituency = Constituency::where('province_id', $provinceId)
                ->where('number', $consNo)
                ->first();

            if (! $constituency) {
                continue;
            }

            Candidate::updateOrCreate(
                [
                    'election_id' => $electionId,
                    'constituency_id' => $constituency->id,
                    'candidate_number' => $candNo ?: 0,
                ],
                array_filter([
                    'party_id' => $partyId,
                    'title' => $title ?: null,
                    'first_name' => $firstName ?: null,
                    'last_name' => $lastName ?: null,
                    'type' => 'constituency',
                ], fn ($v) => $v !== null),
            );

            $synced++;
        }

        Log::info("ECT69: Synced {$synced} MP candidates");

        return ['success' => true, 'synced' => $synced];
    }

    /**
     * ซิงค์ผู้สมัคร ส.ส. บัญชีรายชื่อ จาก ECT
     */
    public function syncPartyCandidates(int $electionId): array
    {
        $data = $this->fetchReference('party_candidates');

        if (! $data) {
            return ['success' => false, 'synced' => 0, 'message' => 'ไม่สามารถดึงข้อมูลผู้สมัครบัญชีรายชื่อได้'];
        }

        $synced = 0;
        $candidates = $this->normalizeArray($data);
        $partyMap = Party::pluck('id', 'party_number')->toArray();

        foreach ($candidates as $item) {
            $partyNo = $this->extractField($item, ['party_id', 'partyId', 'party_no', 'partyNo']);
            $order = $this->extractField($item, ['order', 'list_order', 'listOrder', 'no', 'number', 'candidate_no']);
            $title = $this->extractField($item, ['title', 'prefix']);
            $firstName = $this->extractField($item, ['first_name', 'firstName', 'fname']);
            $lastName = $this->extractField($item, ['last_name', 'lastName', 'lname']);

            if (! $partyNo) {
                continue;
            }

            $partyId = $partyMap[(int) $partyNo] ?? null;

            if (! $partyId) {
                continue;
            }

            Candidate::updateOrCreate(
                [
                    'election_id' => $electionId,
                    'party_id' => $partyId,
                    'type' => 'party_list',
                    'party_list_order' => $order ?: 0,
                ],
                array_filter([
                    'title' => $title ?: null,
                    'first_name' => $firstName ?: null,
                    'last_name' => $lastName ?: null,
                    'candidate_number' => $order ?: 0,
                ], fn ($v) => $v !== null),
            );

            $synced++;
        }

        Log::info("ECT69: Synced {$synced} party-list candidates");

        return ['success' => true, 'synced' => $synced];
    }

    // =============================================
    // Live Results - ซิงค์ผลคะแนนเรียลไทม์
    // =============================================

    /**
     * ซิงค์ผลคะแนนรายพรรค (ระดับชาติ)
     */
    public function syncPartyStats(int $electionId): array
    {
        $data = $this->fetchStats('stats_party');

        if (! $data) {
            return ['success' => false, 'synced' => 0, 'message' => 'ไม่สามารถดึงสถิติรายพรรคได้'];
        }

        $synced = 0;
        $records = $this->normalizeArray($data);
        $partyMap = Party::pluck('id', 'party_number')->toArray();

        // รวมผลรายพรรค
        $partyTotals = [];

        foreach ($records as $item) {
            $partyNo = $this->extractField($item, ['party_id', 'partyId', 'party_no', 'partyNo', 'id']);

            if (! $partyNo) {
                continue;
            }

            $partyId = $partyMap[(int) $partyNo] ?? null;

            if (! $partyId) {
                continue;
            }

            // ดึงข้อมูลคะแนนและที่นั่ง
            $consVotes = (int) $this->extractField($item, ['constituency_votes', 'constituencyVotes', 'cons_votes', 'consVotes', 'mp_score', 'mpScore', 'votes_cons']);
            $partyListVotes = (int) $this->extractField($item, ['party_list_votes', 'partyListVotes', 'party_votes', 'partyVotes', 'pl_score', 'plScore', 'votes_party']);
            $totalVotes = (int) $this->extractField($item, ['total_votes', 'totalVotes', 'total_score', 'totalScore', 'score']);
            $consSeats = (int) $this->extractField($item, ['constituency_seats', 'constituencySeats', 'cons_seats', 'consSeats', 'mp_seat', 'mpSeat', 'seats_cons']);
            $partyListSeats = (int) $this->extractField($item, ['party_list_seats', 'partyListSeats', 'pl_seats', 'plSeats', 'pl_seat', 'plSeat', 'seats_party']);
            $totalSeats = (int) $this->extractField($item, ['total_seats', 'totalSeats', 'total_seat', 'totalSeat', 'seats']);
            $rank = (int) $this->extractField($item, ['rank', 'order', 'no']);

            // ถ้าไม่มี total ให้รวมจาก constituency + party_list
            if (! $totalVotes && ($consVotes || $partyListVotes)) {
                $totalVotes = $consVotes + $partyListVotes;
            }

            if (! $totalSeats && ($consSeats || $partyListSeats)) {
                $totalSeats = $consSeats + $partyListSeats;
            }

            // สะสมถ้ามีข้อมูลซ้ำ (กรณีข้อมูลเป็นรายเขต)
            if (isset($partyTotals[$partyId])) {
                $partyTotals[$partyId]['constituency_votes'] += $consVotes;
                $partyTotals[$partyId]['party_list_votes'] += $partyListVotes;
                $partyTotals[$partyId]['total_votes'] += $totalVotes;
                $partyTotals[$partyId]['constituency_seats'] += $consSeats;
                $partyTotals[$partyId]['party_list_seats'] += $partyListSeats;
                $partyTotals[$partyId]['total_seats'] += $totalSeats;
            } else {
                $partyTotals[$partyId] = [
                    'constituency_votes' => $consVotes,
                    'party_list_votes' => $partyListVotes,
                    'total_votes' => $totalVotes,
                    'constituency_seats' => $consSeats,
                    'party_list_seats' => $partyListSeats,
                    'total_seats' => $totalSeats,
                    'rank' => $rank,
                ];
            }
        }

        // จัดลำดับตาม total_seats
        uasort($partyTotals, fn ($a, $b) => $b['total_seats'] - $a['total_seats']);
        $rank = 1;

        foreach ($partyTotals as $partyId => $totals) {
            NationalResult::updateOrCreate(
                [
                    'election_id' => $electionId,
                    'party_id' => $partyId,
                ],
                [
                    'constituency_votes' => $totals['constituency_votes'],
                    'party_list_votes' => $totals['party_list_votes'],
                    'total_votes' => $totals['total_votes'],
                    'constituency_seats' => $totals['constituency_seats'],
                    'party_list_seats' => $totals['party_list_seats'],
                    'total_seats' => $totals['total_seats'],
                    'rank' => $totals['rank'] ?: $rank,
                ],
            );

            $rank++;
            $synced++;
        }

        Log::info("ECT69: Synced party stats for {$synced} parties");

        return ['success' => true, 'synced' => $synced];
    }

    /**
     * ซิงค์ผลคะแนนรายเขต (Constituency stats)
     */
    public function syncConstituencyStats(int $electionId): array
    {
        $data = $this->fetchStats('stats_constituency');

        if (! $data) {
            return ['success' => false, 'synced' => 0, 'message' => 'ไม่สามารถดึงสถิติรายเขตได้'];
        }

        $synced = 0;
        $records = $this->normalizeArray($data);

        // Cache lookups
        $provinceMap = Province::pluck('id', 'code')->toArray();
        $partyMap = Party::pluck('id', 'party_number')->toArray();

        // Province-level aggregation for ProvinceResult
        $provinceAgg = [];

        foreach ($records as $item) {
            $provCode = $this->extractField($item, ['province_id', 'provinceId', 'prov_id', 'provId']);
            $consNo = $this->extractField($item, ['constituency_no', 'constituencyNo', 'cons_no', 'consNo', 'cons_id', 'consId']);
            $partyNo = $this->extractField($item, ['party_id', 'partyId', 'party_no', 'partyNo']);
            $candNo = $this->extractField($item, ['candidate_no', 'candidateNo', 'cand_no', 'candNo']);
            $votes = (int) $this->extractField($item, ['score', 'votes', 'total_score', 'totalScore', 'vote_count', 'voteCount']);
            $rank = (int) $this->extractField($item, ['rank', 'order']);
            $stationsCounted = (int) $this->extractField($item, ['station_count', 'stationCount', 'stations_counted', 'stationsCounted', 'count_station']);
            $stationsTotal = (int) $this->extractField($item, ['station_total', 'stationTotal', 'stations_total', 'stationsTotal', 'total_station', 'num_station']);
            $isWinner = $this->extractField($item, ['winner', 'is_winner', 'isWinner']);

            if (! $provCode || ! $consNo) {
                continue;
            }

            $provinceId = $provinceMap[(string) $provCode] ?? null;
            $partyId = $partyMap[(int) $partyNo] ?? null;

            if (! $provinceId) {
                continue;
            }

            $constituency = Constituency::where('province_id', $provinceId)
                ->where('number', $consNo)
                ->first();

            if (! $constituency) {
                continue;
            }

            // Find candidate
            $candidateId = null;

            if ($candNo && $partyId) {
                $candidate = Candidate::where('election_id', $electionId)
                    ->where('constituency_id', $constituency->id)
                    ->where('candidate_number', $candNo)
                    ->first();
                $candidateId = $candidate?->id;
            }

            $countingProgress = $stationsTotal > 0
                ? round(($stationsCounted / $stationsTotal) * 100, 1)
                : 0;

            // Save constituency result
            if ($partyId && $votes > 0) {
                ConstituencyResult::updateOrCreate(
                    [
                        'election_id' => $electionId,
                        'constituency_id' => $constituency->id,
                        'party_id' => $partyId,
                    ],
                    array_filter([
                        'candidate_id' => $candidateId,
                        'votes' => $votes,
                        'rank' => $rank ?: 0,
                        'is_winner' => $isWinner === true || $isWinner === 1 || $rank === 1,
                        'stations_counted' => $stationsCounted ?: 0,
                        'stations_total' => $stationsTotal ?: 0,
                        'counting_progress' => $countingProgress,
                    ]),
                );

                // Province aggregation
                $provPartyKey = "{$provinceId}_{$partyId}";

                if (! isset($provinceAgg[$provPartyKey])) {
                    $provinceAgg[$provPartyKey] = [
                        'province_id' => $provinceId,
                        'party_id' => $partyId,
                        'total_votes' => 0,
                        'seats_won' => 0,
                        'cons_counted' => 0,
                        'cons_total' => 0,
                    ];
                }

                $provinceAgg[$provPartyKey]['total_votes'] += $votes;

                if ($isWinner === true || $isWinner === 1 || $rank === 1) {
                    $provinceAgg[$provPartyKey]['seats_won']++;
                }

                $synced++;
            }
        }

        // Save province-level results
        $totalVotesByProvince = [];

        foreach ($provinceAgg as $agg) {
            $provId = $agg['province_id'];

            if (! isset($totalVotesByProvince[$provId])) {
                $totalVotesByProvince[$provId] = 0;
            }

            $totalVotesByProvince[$provId] += $agg['total_votes'];
        }

        foreach ($provinceAgg as $agg) {
            $provTotalVotes = $totalVotesByProvince[$agg['province_id']] ?? 1;
            $totalConst = Province::find($agg['province_id'])?->total_constituencies ?? 0;

            ProvinceResult::updateOrCreate(
                [
                    'election_id' => $electionId,
                    'province_id' => $agg['province_id'],
                    'party_id' => $agg['party_id'],
                ],
                [
                    'total_votes' => $agg['total_votes'],
                    'seats_won' => $agg['seats_won'],
                    'vote_percentage' => $provTotalVotes > 0
                        ? round(($agg['total_votes'] / $provTotalVotes) * 100, 2)
                        : 0,
                    'constituencies_counted' => $agg['cons_counted'],
                    'constituencies_total' => $totalConst,
                ],
            );
        }

        Log::info("ECT69: Synced constituency stats: {$synced} records");

        return ['success' => true, 'synced' => $synced];
    }

    // =============================================
    // Full Sync - ซิงค์ข้อมูลทั้งหมด
    // =============================================

    /**
     * ซิงค์ข้อมูลอ้างอิงทั้งหมด (พรรค, จังหวัด, เขต, ผู้สมัคร)
     */
    public function syncReferenceData(int $electionId): array
    {
        $results = [];

        $results['parties'] = $this->syncParties();
        $results['provinces'] = $this->syncProvinces();
        $results['constituencies'] = $this->syncConstituencies();
        $results['mp_candidates'] = $this->syncMpCandidates($electionId);
        $results['party_candidates'] = $this->syncPartyCandidates($electionId);

        return $results;
    }

    /**
     * ซิงค์ผลคะแนนเรียลไทม์ (สถิติรายพรรค + รายเขต)
     */
    public function syncLiveResults(int $electionId): array
    {
        $results = [];

        $results['party_stats'] = $this->syncPartyStats($electionId);
        $results['constituency_stats'] = $this->syncConstituencyStats($electionId);

        // อัปเดต election stats
        $this->updateElectionStats($electionId);

        // Clear cache
        Cache::forget("live_results_{$electionId}");

        return $results;
    }

    /**
     * ซิงค์ทุกอย่าง (reference + live)
     */
    public function fullSync(int $electionId): array
    {
        return [
            'source' => 'ectreport69.ect.go.th',
            'fetched_at' => now()->toIso8601String(),
            'reference' => $this->syncReferenceData($electionId),
            'live' => $this->syncLiveResults($electionId),
        ];
    }

    /**
     * Scrape และอัพเดทข้อมูล (backward compatible)
     */
    public function scrapeAndUpdate(int $electionId): array
    {
        $liveResults = $this->syncLiveResults($electionId);

        $partySynced = $liveResults['party_stats']['synced'] ?? 0;
        $consSynced = $liveResults['constituency_stats']['synced'] ?? 0;

        return [
            'source' => 'ectreport69.ect.go.th',
            'fetched_at' => now()->toIso8601String(),
            'api_success' => ($partySynced > 0 || $consSynced > 0),
            'parties_updated' => $partySynced,
            'provinces_updated' => $consSynced,
        ];
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

        $this->updateElectionStats($electionId);
        Cache::forget("live_results_{$electionId}");

        return $updated;
    }

    /**
     * Schedule สำหรับ real-time update
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

    // =============================================
    // Helper Methods
    // =============================================

    /**
     * อัปเดทสถิติการเลือกตั้ง
     */
    protected function updateElectionStats(int $electionId): void
    {
        $election = Election::find($electionId);

        if (! $election) {
            return;
        }

        $totalVotes = NationalResult::where('election_id', $electionId)->sum('total_votes');
        $totalSeats = NationalResult::where('election_id', $electionId)->sum('total_seats');

        $stationsCounted = DB::table('constituency_results')
            ->where('election_id', $electionId)
            ->selectRaw('SUM(stations_counted) as counted, SUM(stations_total) as total')
            ->first();

        $counted = (int) ($stationsCounted?->counted ?? 0);
        $total = (int) ($stationsCounted?->total ?? 0);

        $constCounted = ConstituencyResult::where('election_id', $electionId)
            ->where('counting_progress', '>=', 100)
            ->distinct('constituency_id')
            ->count('constituency_id');

        ElectionStats::updateOrCreate(
            ['election_id' => $electionId],
            [
                'total_eligible_voters' => $election->total_eligible_voters ?: 0,
                'total_votes_cast' => $totalVotes,
                'voter_turnout' => $election->total_eligible_voters > 0
                    ? round(($totalVotes / $election->total_eligible_voters) * 100, 2)
                    : 0,
                'counting_progress' => $total > 0
                    ? round(($counted / $total) * 100, 1)
                    : 0,
                'constituencies_counted' => $constCounted,
                'constituencies_total' => 400,
                'stations_counted' => $counted,
                'stations_total' => $total,
                'last_updated_at' => now(),
            ],
        );
    }

    /**
     * Normalize array - handles both indexed arrays and nested objects
     */
    protected function normalizeArray($data): array
    {
        if (! is_array($data)) {
            return [];
        }

        // ถ้าเป็น object ที่มี data key
        if (isset($data['data']) && is_array($data['data'])) {
            return $this->normalizeArray($data['data']);
        }

        // ถ้าเป็น object ที่มี items/records/results key
        foreach (['items', 'records', 'results', 'list', 'rows'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        // ถ้าเป็น indexed array อยู่แล้ว
        if (array_is_list($data)) {
            return $data;
        }

        // ถ้าเป็น associative array (single item) ให้ wrap เป็น array
        return [$data];
    }

    /**
     * ดึงค่าจาก array โดยลอง field names หลายแบบ
     */
    protected function extractField(array $data, array $possibleKeys): mixed
    {
        foreach ($possibleKeys as $key) {
            if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null) {
                return $data[$key];
            }
        }

        return null;
    }

    /**
     * แปลงชื่อภาคเป็น key ที่ใช้ในระบบ
     */
    protected function mapRegion(?string $region): string
    {
        if (! $region) {
            return 'central';
        }

        $regionMap = [
            'เหนือ' => 'north',
            'ภาคเหนือ' => 'north',
            'north' => 'north',
            'อีสาน' => 'northeast',
            'ตะวันออกเฉียงเหนือ' => 'northeast',
            'ภาคตะวันออกเฉียงเหนือ' => 'northeast',
            'northeast' => 'northeast',
            'กลาง' => 'central',
            'ภาคกลาง' => 'central',
            'central' => 'central',
            'ตะวันออก' => 'east',
            'ภาคตะวันออก' => 'east',
            'east' => 'east',
            'ตะวันตก' => 'west',
            'ภาคตะวันตก' => 'west',
            'west' => 'west',
            'ใต้' => 'south',
            'ภาคใต้' => 'south',
            'south' => 'south',
        ];

        return $regionMap[$region] ?? 'central';
    }
}
