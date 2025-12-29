<?php

namespace App\Jobs;

use App\Models\Party;
use App\Models\PartyFeed;
use App\Models\PartyPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncPartyFeedsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;

    public function handle(): void
    {
        $feeds = PartyFeed::where('is_active', true)->get();

        foreach ($feeds as $feed) {
            try {
                $posts = match ($feed->platform) {
                    'facebook' => $this->fetchFacebookPosts($feed),
                    'twitter' => $this->fetchTwitterPosts($feed),
                    default => [],
                };

                foreach ($posts as $post) {
                    PartyPost::updateOrCreate(
                        [
                            'party_feed_id' => $feed->id,
                            'platform_post_id' => $post['id'],
                        ],
                        [
                            'party_id' => $feed->party_id,
                            'content' => $post['content'],
                            'post_url' => $post['url'],
                            'media_type' => $post['media_type'] ?? null,
                            'media_urls' => $post['media_urls'] ?? null,
                            'likes' => $post['likes'] ?? 0,
                            'shares' => $post['shares'] ?? 0,
                            'comments' => $post['comments'] ?? 0,
                            'posted_at' => $post['posted_at'],
                        ]
                    );
                }

                $feed->update(['last_synced_at' => now()]);

                Log::info("Synced {$feed->platform} feed for party {$feed->party_id}");
            } catch (\Exception $e) {
                Log::error("Failed to sync feed {$feed->id}: {$e->getMessage()}");
            }
        }
    }

    protected function fetchFacebookPosts(PartyFeed $feed): array
    {
        if (!$feed->access_token) {
            return [];
        }

        $response = Http::get("https://graph.facebook.com/v18.0/{$feed->page_id}/posts", [
            'access_token' => $feed->access_token,
            'fields' => 'id,message,created_time,full_picture,shares,reactions.summary(total_count),comments.summary(total_count)',
            'limit' => 20,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Facebook API error: ' . $response->body());
        }

        return collect($response->json('data', []))
            ->map(fn($post) => [
                'id' => $post['id'],
                'content' => $post['message'] ?? '',
                'url' => "https://facebook.com/{$post['id']}",
                'media_type' => isset($post['full_picture']) ? 'image' : null,
                'media_urls' => isset($post['full_picture']) ? [$post['full_picture']] : null,
                'likes' => $post['reactions']['summary']['total_count'] ?? 0,
                'shares' => $post['shares']['count'] ?? 0,
                'comments' => $post['comments']['summary']['total_count'] ?? 0,
                'posted_at' => $post['created_time'],
            ])
            ->toArray();
    }

    protected function fetchTwitterPosts(PartyFeed $feed): array
    {
        // Twitter API v2 implementation would go here
        return [];
    }
}
