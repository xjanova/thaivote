<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConstituencyResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'constituency_id',
        'candidate_id',
        'party_id',
        'votes',
        'vote_percentage',
        'rank',
        'is_winner',
        'stations_counted',
        'stations_total',
        'counting_progress',
        'is_official',
    ];

    protected $casts = [
        'vote_percentage' => 'decimal:2',
        'counting_progress' => 'decimal:2',
        'is_winner' => 'boolean',
        'is_official' => 'boolean',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function constituency(): BelongsTo
    {
        return $this->belongsTo(Constituency::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function scopeWinners($query)
    {
        return $query->where('is_winner', true);
    }
}
