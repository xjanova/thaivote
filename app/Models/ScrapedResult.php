<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapedResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'result_source_id',
        'election_id',
        'scope',
        'scope_id',
        'raw_data',
        'parsed_data',
        'is_processed',
        'is_valid',
        'error_message',
        'scraped_at',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'parsed_data' => 'array',
        'is_processed' => 'boolean',
        'is_valid' => 'boolean',
        'scraped_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(ResultSource::class, 'result_source_id');
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false)->where('is_valid', true);
    }
}
