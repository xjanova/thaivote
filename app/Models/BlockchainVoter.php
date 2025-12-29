<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainVoter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'election_id',
        'wallet_address',
        'voter_id_hash',
        'eligibility_proof',
        'is_verified',
        'has_voted',
        'verified_at',
        'voted_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'has_voted' => 'boolean',
        'verified_at' => 'datetime',
        'voted_at' => 'datetime',
    ];

    protected $hidden = [
        'voter_id_hash',
        'eligibility_proof',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeNotVoted($query)
    {
        return $query->where('has_voted', false);
    }
}
