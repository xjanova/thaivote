<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectionStats extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'total_eligible_voters',
        'total_votes_cast',
        'valid_votes',
        'invalid_votes',
        'no_vote',
        'voter_turnout',
        'constituencies_counted',
        'constituencies_total',
        'stations_counted',
        'stations_total',
        'counting_progress',
        'last_updated_at',
    ];

    protected $casts = [
        'voter_turnout' => 'decimal:2',
        'counting_progress' => 'decimal:2',
        'last_updated_at' => 'datetime',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }
}
