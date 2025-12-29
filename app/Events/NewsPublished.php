<?php

namespace App\Events;

use App\Models\NewsArticle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewsPublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public NewsArticle $article;

    public function __construct(NewsArticle $article)
    {
        $this->article = $article->load('source');
    }

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('news'),
        ];

        // Also broadcast to party-specific channels
        foreach ($this->article->parties as $party) {
            $channels[] = new Channel('party.' . $party->id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'NewsPublished';
    }

    public function broadcastWith(): array
    {
        return [
            'news' => [
                'id' => $this->article->id,
                'title' => $this->article->title,
                'excerpt' => $this->article->excerpt,
                'url' => $this->article->url,
                'image_url' => $this->article->image_url,
                'published_at' => $this->article->published_at?->toIso8601String(),
                'source' => [
                    'name' => $this->article->source?->name,
                    'logo' => $this->article->source?->logo,
                ],
            ],
        ];
    }
}
