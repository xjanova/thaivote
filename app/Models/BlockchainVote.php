<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'transaction_hash',
        'block_number',
        'vote_commitment',
        'nullifier',
        'submitted_at',
        'confirmed_at',
        'status',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    protected $hidden = [
        'vote_commitment',
        'nullifier',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
