<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'polling_station_id',
        'candidate_id',
        'votes',
        'is_official',
        'counted_at',
        'source',
        'source_url',
    ];

    protected $casts = [
        'is_official' => 'boolean',
        'counted_at' => 'datetime',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function pollingStation(): BelongsTo
    {
        return $this->belongsTo(PollingStation::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
