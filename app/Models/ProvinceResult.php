<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProvinceResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'province_id',
        'party_id',
        'total_votes',
        'seats_won',
        'vote_percentage',
        'constituencies_counted',
        'constituencies_total',
    ];

    protected $casts = [
        'vote_percentage' => 'decimal:2',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
