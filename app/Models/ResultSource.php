<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'website',
        'logo',
        'type',
        'scrape_config',
        'api_endpoint',
        'api_key',
        'reliability_score',
        'priority',
        'fetch_interval',
        'is_active',
        'last_fetched_at',
        'last_fetch_stats',
    ];

    protected $casts = [
        'scrape_config' => 'array',
        'is_active' => 'boolean',
        'last_fetched_at' => 'datetime',
        'last_fetch_stats' => 'array',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function scrapedResults(): HasMany
    {
        return $this->hasMany(ScrapedResult::class);
    }

    public function accuracyRecords(): HasMany
    {
        return $this->hasMany(SourceAccuracy::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByReliability($query)
    {
        return $query->orderBy('reliability_score', 'desc');
    }
}
