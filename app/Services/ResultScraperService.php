<?php

namespace App\Services;

use App\Events\ResultsUpdated;
use App\Models\Election;
use App\Models\NationalResult;
use App\Models\Party;
use App\Models\ProvinceResult;
use App\Models\ResultSource;
use App\Models\ScrapedResult;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResultScraperService
{
    protected Election $election;

    /**
     * Scrape results from all active sources
     */
    public function scrapeAllSources(Election $election): array
    {
        $this->election = $election;
        $sources = ResultSource::active()->orderBy('priority', 'desc')->get();
        $results = [];

        foreach ($sources as $source) {
            try {
                $data = match ($source->type) {
                    'official' => $this->scrapeOfficialSource($source),
                    'news' => $this->scrapeNewsSource($source),
                    'api' => $this->fetchApiSource($source),
                    default => [],
                };

                if (! empty($data)) {
                    $this->storeScrapedData($source, $data);
                    $results[$source->name] = 'success';
                }

                $source->update([
                    'last_fetched_at' => now(),
                    'last_fetch_stats' => [
                        'records' => count($data),
                        'timestamp' => now()->toIso8601String(),
                    ],
                ]);
            } catch (Exception $e) {
                Log::error("Failed to scrape {$source->name}: {$e->getMessage()}");
                $results[$source->name] = "Error: {$e->getMessage()}";
            }
        }

        // Process scraped data and update results
        $this->processScrapedData();

        return $results;
    }

    /**
     * Process scraped data and update official results
     */
    public function processScrapedData(): void
    {
        $unprocessed = ScrapedResult::unprocessed()
            ->where('election_id', $this->election->id)
            ->with('source')
            ->get()
            ->groupBy(fn ($r) => $r->scope . '-' . ($r->scope_id ?? 'all'));

        foreach ($unprocessed as $key => $results) {
            // Get consensus from multiple sources
            $consensus = $this->calculateConsensus($results);

            if ($consensus['confidence'] >= 70) {
                $this->updateResults($consensus);
            }

            // Mark as processed
            ScrapedResult::whereIn('id', $results->pluck('id'))->update(['is_processed' => true]);
        }

        // Broadcast updates
        event(new ResultsUpdated($this->election));
    }

    /**
     * Get source accuracy report
     */
    public function getSourceAccuracyReport(Election $election): array
    {
        $sources = ResultSource::active()->get();
        $report = [];

        foreach ($sources as $source) {
            $scraped = ScrapedResult::where('election_id', $election->id)
                ->where('result_source_id', $source->id)
                ->where('is_processed', true)
                ->get();

            // Compare with official results
            $accurate = 0;
            $total = $scraped->count();

            foreach ($scraped as $result) {
                $official = NationalResult::where('election_id', $election->id)
                    ->where('party_id', $result->parsed_data['party_id'] ?? 0)
                    ->first();

                if ($official) {
                    $voteDiff = abs($official->total_votes - ($result->parsed_data['votes'] ?? 0));
                    $tolerance = $official->total_votes * 0.05; // 5% tolerance

                    if ($voteDiff <= $tolerance) {
                        $accurate++;
                    }
                }
            }

            $report[] = [
                'source' => $source->name,
                'total_records' => $total,
                'accurate_records' => $accurate,
                'accuracy_percentage' => $total > 0 ? round(($accurate / $total) * 100, 2) : 0,
            ];
        }

        return $report;
    }

    /**
     * Scrape official ECT source
     */
    protected function scrapeOfficialSource(ResultSource $source): array
    {
        $response = Http::timeout(60)->get($source->api_endpoint ?? $source->website);

        if (! $response->successful()) {
            throw new Exception("HTTP {$response->status()}");
        }

        $config = $source->scrape_config;

        if ($source->api_endpoint) {
            // API response
            return $this->parseApiResponse($response->json(), $config);
        }

        // HTML scraping
        return $this->parseHtmlResults($response->body(), $config);
    }

    /**
     * Scrape news website for results
     */
    protected function scrapeNewsSource(ResultSource $source): array
    {
        $response = Http::timeout(30)->get($source->website);

        if (! $response->successful()) {
            throw new Exception("HTTP {$response->status()}");
        }

        return $this->parseHtmlResults($response->body(), $source->scrape_config);
    }

    /**
     * Fetch from API source
     */
    protected function fetchApiSource(ResultSource $source): array
    {
        $headers = [];

        if ($source->api_key) {
            $headers['Authorization'] = 'Bearer ' . $source->api_key;
        }

        $response = Http::timeout(30)
            ->withHeaders($headers)
            ->get($source->api_endpoint);

        if (! $response->successful()) {
            throw new Exception("API request failed: HTTP {$response->status()}");
        }

        return $this->parseApiResponse($response->json(), $source->scrape_config);
    }

    /**
     * Parse API response
     */
    protected function parseApiResponse(array $data, array $config): array
    {
        $results = [];
        $dataPath = $config['data_path'] ?? 'results';
        $items = data_get($data, $dataPath, $data);

        foreach ($items as $item) {
            $results[] = [
                'scope' => data_get($item, $config['scope_path'] ?? 'scope', 'national'),
                'scope_id' => data_get($item, $config['scope_id_path'] ?? 'scope_id'),
                'party_name' => data_get($item, $config['party_path'] ?? 'party'),
                'party_id' => data_get($item, $config['party_id_path'] ?? 'party_id'),
                'candidate_name' => data_get($item, $config['candidate_path'] ?? 'candidate'),
                'votes' => (int) data_get($item, $config['votes_path'] ?? 'votes', 0),
                'seats' => (int) data_get($item, $config['seats_path'] ?? 'seats', 0),
                'percentage' => (float) data_get($item, $config['percentage_path'] ?? 'percentage', 0),
                'counted' => (int) data_get($item, $config['counted_path'] ?? 'counted', 0),
                'total' => (int) data_get($item, $config['total_path'] ?? 'total', 0),
            ];
        }

        return $results;
    }

    /**
     * Parse HTML results
     */
    protected function parseHtmlResults(string $html, array $config): array
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);

        $results = [];
        $rows = $xpath->query($config['row_selector'] ?? '//tr[@class="result-row"]');

        foreach ($rows as $row) {
            $partyNode = $xpath->query($config['party_selector'] ?? './/td[1]', $row)->item(0);
            $votesNode = $xpath->query($config['votes_selector'] ?? './/td[2]', $row)->item(0);
            $seatsNode = $xpath->query($config['seats_selector'] ?? './/td[3]', $row)->item(0);

            if ($partyNode) {
                $results[] = [
                    'scope' => 'national',
                    'party_name' => trim($partyNode->textContent),
                    'votes' => $this->parseNumber($votesNode?->textContent ?? '0'),
                    'seats' => $this->parseNumber($seatsNode?->textContent ?? '0'),
                ];
            }
        }

        return $results;
    }

    /**
     * Store scraped data
     */
    protected function storeScrapedData(ResultSource $source, array $data): void
    {
        foreach ($data as $item) {
            ScrapedResult::create([
                'result_source_id' => $source->id,
                'election_id' => $this->election->id,
                'scope' => $item['scope'] ?? 'national',
                'scope_id' => $item['scope_id'] ?? null,
                'raw_data' => $item,
                'parsed_data' => $this->normalizeData($item),
                'scraped_at' => now(),
            ]);
        }
    }

    /**
     * Normalize scraped data
     */
    protected function normalizeData(array $item): array
    {
        // Try to match party by name if no ID
        $partyId = $item['party_id'] ?? null;

        if (! $partyId && isset($item['party_name'])) {
            $party = Party::where('name_th', 'like', '%' . $item['party_name'] . '%')
                ->orWhere('name_en', 'like', '%' . $item['party_name'] . '%')
                ->orWhere('abbreviation', $item['party_name'])
                ->first();
            $partyId = $party?->id;
        }

        return [
            'party_id' => $partyId,
            'votes' => $item['votes'] ?? 0,
            'seats' => $item['seats'] ?? 0,
            'percentage' => $item['percentage'] ?? 0,
        ];
    }

    /**
     * Calculate consensus from multiple sources
     */
    protected function calculateConsensus($results): array
    {
        $byParty = $results->groupBy(fn ($r) => $r->parsed_data['party_id'] ?? 'unknown');
        $consensus = [];

        foreach ($byParty as $partyId => $partyResults) {
            if ($partyId === 'unknown') {
                continue;
            }

            $votes = $partyResults->pluck('parsed_data.votes')->filter();
            $seats = $partyResults->pluck('parsed_data.seats')->filter();

            // Calculate weighted average based on source reliability
            $weightedVotes = 0;
            $weightedSeats = 0;
            $totalWeight = 0;

            foreach ($partyResults as $result) {
                $weight = $result->source->reliability_score / 100;
                $weightedVotes += ($result->parsed_data['votes'] ?? 0) * $weight;
                $weightedSeats += ($result->parsed_data['seats'] ?? 0) * $weight;
                $totalWeight += $weight;
            }

            $avgVotes = $totalWeight > 0 ? round($weightedVotes / $totalWeight) : 0;
            $avgSeats = $totalWeight > 0 ? round($weightedSeats / $totalWeight) : 0;

            // Calculate variance to determine confidence
            $votesVariance = $votes->count() > 1 ? $votes->variance() : 0;
            $seatsVariance = $seats->count() > 1 ? $seats->variance() : 0;

            // Higher source count and lower variance = higher confidence
            $confidence = min(100, ($partyResults->count() * 20) + (100 - min(100, $votesVariance / 1000)));

            $consensus[$partyId] = [
                'party_id' => $partyId,
                'votes' => $avgVotes,
                'seats' => $avgSeats,
                'sources_count' => $partyResults->count(),
                'confidence' => $confidence,
            ];
        }

        $firstResult = $results->first();

        return [
            'scope' => $firstResult->scope,
            'scope_id' => $firstResult->scope_id,
            'results' => $consensus,
            'confidence' => collect($consensus)->avg('confidence') ?? 0,
        ];
    }

    /**
     * Update official results tables
     */
    protected function updateResults(array $consensus): void
    {
        foreach ($consensus['results'] as $partyResult) {
            if ($consensus['scope'] === 'national') {
                NationalResult::updateOrCreate(
                    [
                        'election_id' => $this->election->id,
                        'party_id' => $partyResult['party_id'],
                    ],
                    [
                        'total_votes' => $partyResult['votes'],
                        'total_seats' => $partyResult['seats'],
                        'vote_percentage' => $this->calculatePercentage($partyResult['votes']),
                    ],
                );
            } elseif ($consensus['scope'] === 'province') {
                ProvinceResult::updateOrCreate(
                    [
                        'election_id' => $this->election->id,
                        'province_id' => $consensus['scope_id'],
                        'party_id' => $partyResult['party_id'],
                    ],
                    [
                        'total_votes' => $partyResult['votes'],
                        'seats_won' => $partyResult['seats'],
                        'vote_percentage' => $partyResult['percentage'] ?? 0,
                    ],
                );
            }
        }

        // Update election stats
        $this->updateElectionStats();
    }

    /**
     * Update election statistics
     */
    protected function updateElectionStats(): void
    {
        $stats = $this->election->stats;

        if (! $stats) {
            $stats = $this->election->stats()->create([]);
        }

        $totalVotes = NationalResult::where('election_id', $this->election->id)->sum('total_votes');

        $stats->update([
            'total_votes_cast' => $totalVotes,
            'voter_turnout' => $this->election->total_eligible_voters > 0
                ? ($totalVotes / $this->election->total_eligible_voters) * 100
                : 0,
            'last_updated_at' => now(),
        ]);
    }

    /**
     * Calculate vote percentage
     */
    protected function calculatePercentage(int $votes): float
    {
        $total = NationalResult::where('election_id', $this->election->id)->sum('total_votes');

        return $total > 0 ? round(($votes / $total) * 100, 2) : 0;
    }

    /**
     * Parse number from string
     */
    protected function parseNumber(string $value): int
    {
        return (int) preg_replace('/[^0-9]/', '', $value);
    }
}
