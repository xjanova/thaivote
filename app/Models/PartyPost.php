<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_feed_id',
        'party_id',
        'platform_post_id',
        'content',
        'post_url',
        'media_type',
        'media_urls',
        'likes',
        'shares',
        'comments',
        'posted_at',
        'is_featured',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'is_featured' => 'boolean',
        'posted_at' => 'datetime',
    ];

    public function feed(): BelongsTo
    {
        return $this->belongsTo(PartyFeed::class, 'party_feed_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('posted_at', 'desc');
    }
}
