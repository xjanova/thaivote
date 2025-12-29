<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NewsArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'news_source_id',
        'title',
        'excerpt',
        'content',
        'url',
        'image_url',
        'author',
        'published_at',
        'keywords_matched',
        'relevance_score',
        'sentiment',
        'sentiment_score',
        'is_featured',
        'is_approved',
        'views',
    ];

    protected $casts = [
        'keywords_matched' => 'array',
        'relevance_score' => 'decimal:2',
        'sentiment_score' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_approved' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class, 'news_source_id');
    }

    public function parties(): BelongsToMany
    {
        return $this->belongsToMany(Party::class, 'news_article_party');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    public function incrementViews(): void
    {
        $this->increment('views');
    }
}
