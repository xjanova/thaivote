<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_th',
        'website',
        'logo',
        'rss_url',
        'api_endpoint',
        'scrape_config',
        'type',
        'priority',
        'fetch_interval',
        'is_active',
        'last_fetched_at',
    ];

    protected $casts = [
        'scrape_config' => 'array',
        'is_active' => 'boolean',
        'last_fetched_at' => 'datetime',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(NewsArticle::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNeedsFetch($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_fetched_at')
                    ->orWhereRaw('TIMESTAMPDIFF(SECOND, last_fetched_at, NOW()) >= fetch_interval');
            });
    }
}
