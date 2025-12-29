<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NationalResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'party_id',
        'constituency_votes',
        'party_list_votes',
        'total_votes',
        'constituency_seats',
        'party_list_seats',
        'total_seats',
        'vote_percentage',
        'rank',
    ];

    protected $casts = [
        'vote_percentage' => 'decimal:2',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
