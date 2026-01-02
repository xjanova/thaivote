<?php

namespace App\Services;

use App\Models\NewsArticle;
use App\Models\NewsKeyword;
use App\Models\NewsSource;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewsAggregatorService
{
    protected array $aiEndpoint;

    protected array $keywords = [];

    public function __construct()
    {
        $this->aiEndpoint = [
            'url' => config('services.openai.endpoint', 'https://api.openai.com/v1'),
            'key' => config('services.openai.key'),
        ];
    }

    /**
     * Fetch news from all active sources
     */
    public function fetchAllSources(): array
    {
        $sources = NewsSource::active()->needsFetch()->get();
        $results = [];

        foreach ($sources as $source) {
            try {
                $articles = match ($source->type) {
                    'rss' => $this->fetchRss($source),
                    'api' => $this->fetchApi($source),
                    'scrape' => $this->scrapeWebsite($source),
                    default => [],
                };

                $processed = $this->processArticles($articles, $source);
                $results[$source->name] = count($processed);

                $source->update(['last_fetched_at' => now()]);
            } catch (Exception $e) {
                Log::error("Failed to fetch from {$source->name}: {$e->getMessage()}");
                $results[$source->name] = "Error: {$e->getMessage()}";
            }
        }

        return $results;
    }

    /**
     * Fetch articles from RSS feed
     */
    protected function fetchRss(NewsSource $source): array
    {
        $response = Http::timeout(30)->get($source->rss_url);

        if (! $response->successful()) {
            throw new Exception("Failed to fetch RSS: HTTP {$response->status()}");
        }

        $xml = simplexml_load_string($response->body());
        $articles = [];

        foreach ($xml->channel->item as $item) {
            $articles[] = [
                'title' => (string) $item->title,
                'url' => (string) $item->link,
                'content' => strip_tags((string) ($item->description ?? $item->content)),
                'published_at' => isset($item->pubDate) ? date('Y-m-d H:i:s', strtotime($item->pubDate)) : now(),
                'author' => (string) ($item->author ?? $item->creator ?? null),
                'image_url' => $this->extractImageFromContent((string) $item->description),
            ];
        }

        return $articles;
    }

    /**
     * Fetch articles from API endpoint
     */
    protected function fetchApi(NewsSource $source): array
    {
        $response = Http::timeout(30)
            ->withHeaders($source->scrape_config['headers'] ?? [])
            ->get($source->api_endpoint);

        if (! $response->successful()) {
            throw new Exception("API request failed: HTTP {$response->status()}");
        }

        $data = $response->json();
        $config = $source->scrape_config;

        // Map API response to our format using config
        return collect($data[$config['articles_path'] ?? 'articles'] ?? $data)
            ->map(function ($item) use ($config) {
                return [
                    'title' => data_get($item, $config['title_path'] ?? 'title'),
                    'url' => data_get($item, $config['url_path'] ?? 'url'),
                    'content' => data_get($item, $config['content_path'] ?? 'content'),
                    'excerpt' => data_get($item, $config['excerpt_path'] ?? 'excerpt'),
                    'published_at' => data_get($item, $config['date_path'] ?? 'published_at'),
                    'author' => data_get($item, $config['author_path'] ?? 'author'),
                    'image_url' => data_get($item, $config['image_path'] ?? 'image'),
                ];
            })
            ->toArray();
    }

    /**
     * Scrape articles from website
     */
    protected function scrapeWebsite(NewsSource $source): array
    {
        $response = Http::timeout(30)->get($source->website);

        if (! $response->successful()) {
            throw new Exception("Failed to scrape: HTTP {$response->status()}");
        }

        $html = $response->body();
        $config = $source->scrape_config;

        // Use DOMDocument for parsing
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $articles = [];
        $articleNodes = $xpath->query($config['article_selector'] ?? '//article');

        foreach ($articleNodes as $node) {
            $titleNode = $xpath->query($config['title_selector'] ?? './/h2', $node)->item(0);
            $linkNode = $xpath->query($config['link_selector'] ?? './/a', $node)->item(0);
            $excerptNode = $xpath->query($config['excerpt_selector'] ?? './/p', $node)->item(0);
            $imageNode = $xpath->query($config['image_selector'] ?? './/img', $node)->item(0);

            if ($titleNode && $linkNode) {
                $articles[] = [
                    'title' => trim($titleNode->textContent),
                    'url' => $this->resolveUrl($linkNode->getAttribute('href'), $source->website),
                    'excerpt' => $excerptNode ? trim($excerptNode->textContent) : null,
                    'image_url' => $imageNode ? $this->resolveUrl($imageNode->getAttribute('src'), $source->website) : null,
                    'published_at' => now(),
                ];
            }
        }

        return $articles;
    }

    /**
     * Process and store articles
     */
    protected function processArticles(array $articles, NewsSource $source): array
    {
        $keywords = $this->getKeywords();
        $processed = [];

        foreach ($articles as $article) {
            // Skip if article already exists
            if (NewsArticle::where('url', $article['url'])->exists()) {
                continue;
            }

            // Check keyword relevance
            $matchedKeywords = $this->matchKeywords($article['title'] . ' ' . ($article['content'] ?? ''), $keywords);

            if (empty($matchedKeywords) && ! $this->isElectionRelated($article['title'])) {
                continue; // Skip non-relevant articles
            }

            // Analyze sentiment and relevance with AI
            $analysis = $this->analyzeArticle($article);

            // Create article
            $newsArticle = NewsArticle::create([
                'news_source_id' => $source->id,
                'title' => $article['title'],
                'excerpt' => $article['excerpt'] ?? Str::limit(strip_tags($article['content'] ?? ''), 200),
                'content' => $article['content'] ?? null,
                'url' => $article['url'],
                'image_url' => $article['image_url'] ?? null,
                'author' => $article['author'] ?? null,
                'published_at' => $article['published_at'],
                'keywords_matched' => $matchedKeywords,
                'relevance_score' => $analysis['relevance_score'] ?? 50,
                'sentiment' => $analysis['sentiment'] ?? 'neutral',
                'sentiment_score' => $analysis['sentiment_score'] ?? 0,
                'is_approved' => $analysis['relevance_score'] >= 70, // Auto-approve high relevance
            ]);

            // Link to parties mentioned
            $this->linkParties($newsArticle, $matchedKeywords);

            $processed[] = $newsArticle;
        }

        return $processed;
    }

    /**
     * Get active keywords
     */
    protected function getKeywords(): array
    {
        if (empty($this->keywords)) {
            $this->keywords = NewsKeyword::active()
                ->with('party')
                ->get()
                ->toArray();
        }

        return $this->keywords;
    }

    /**
     * Match keywords in text
     */
    protected function matchKeywords(string $text, array $keywords): array
    {
        $matched = [];
        $text = mb_strtolower($text);

        foreach ($keywords as $keyword) {
            if (mb_strpos($text, mb_strtolower($keyword['keyword'])) !== false) {
                $matched[] = [
                    'keyword' => $keyword['keyword'],
                    'category' => $keyword['category'],
                    'party_id' => $keyword['party_id'],
                ];
            }
        }

        return $matched;
    }

    /**
     * Check if article is election-related
     */
    protected function isElectionRelated(string $text): bool
    {
        $electionKeywords = [
            'เลือกตั้ง', 'การเลือกตั้ง', 'ส.ส.', 'สมาชิกสภา', 'พรรค',
            'ลงคะแนน', 'หีบบัตร', 'กกต', 'เขตเลือกตั้ง', 'ผู้สมัคร',
            'election', 'vote', 'ballot', 'candidate', 'constituency',
        ];

        foreach ($electionKeywords as $keyword) {
            if (mb_stripos($text, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Analyze article using AI
     */
    protected function analyzeArticle(array $article): array
    {
        if (! $this->aiEndpoint['key']) {
            return ['relevance_score' => 50, 'sentiment' => 'neutral', 'sentiment_score' => 0];
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->aiEndpoint['key'],
                    'Content-Type' => 'application/json',
                ])
                ->post($this->aiEndpoint['url'] . '/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an election news analyzer. Analyze the following Thai election news article and return JSON with: relevance_score (0-100 for election relevance), sentiment (positive/negative/neutral), sentiment_score (-1 to 1).',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Title: {$article['title']}\n\nContent: " . Str::limit($article['content'] ?? '', 1000),
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->successful()) {
                return json_decode($response->json('choices.0.message.content'), true);
            }
        } catch (Exception $e) {
            Log::warning("AI analysis failed: {$e->getMessage()}");
        }

        return ['relevance_score' => 50, 'sentiment' => 'neutral', 'sentiment_score' => 0];
    }

    /**
     * Link article to mentioned parties
     */
    protected function linkParties(NewsArticle $article, array $matchedKeywords): void
    {
        $partyIds = collect($matchedKeywords)
            ->pluck('party_id')
            ->filter()
            ->unique()
            ->toArray();

        if (! empty($partyIds)) {
            $article->parties()->sync($partyIds);
        }
    }

    /**
     * Helper: Extract image from HTML content
     */
    protected function extractImageFromContent(string $html): ?string
    {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Helper: Resolve relative URL
     */
    protected function resolveUrl(string $url, string $baseUrl): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $base = parse_url($baseUrl);

        if (str_starts_with($url, '//')) {
            return ($base['scheme'] ?? 'https') . ':' . $url;
        }

        if (str_starts_with($url, '/')) {
            return ($base['scheme'] ?? 'https') . '://' . $base['host'] . $url;
        }

        return rtrim($baseUrl, '/') . '/' . $url;
    }
}
