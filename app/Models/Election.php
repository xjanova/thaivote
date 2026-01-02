<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Election extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'type',
        'description',
        'election_date',
        'start_time',
        'end_time',
        'status',
        'total_eligible_voters',
        'total_votes_cast',
        'voter_turnout',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'election_date' => 'date',
        'is_active' => 'boolean',
        'settings' => 'array',
        'voter_turnout' => 'decimal:2',
    ];

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function stats(): HasOne
    {
        return $this->hasOne(ElectionStats::class);
    }

    public function nationalResults(): HasMany
    {
        return $this->hasMany(NationalResult::class);
    }

    public function provinceResults(): HasMany
    {
        return $this->hasMany(ProvinceResult::class);
    }

    public function constituencyResults(): HasMany
    {
        return $this->hasMany(ConstituencyResult::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ResultSnapshot::class)->orderBy('snapshot_at');
    }

    public function blockchainConfig(): HasOne
    {
        return $this->hasOne(BlockchainConfig::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing');
    }

    public function isOngoing(): bool
    {
        return $this->status === 'ongoing';
    }

    public function isCounting(): bool
    {
        return $this->status === 'counting';
    }
}
