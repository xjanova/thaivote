<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_id',
        'election_id',
        'constituency_id',
        'candidate_number',
        'title',
        'first_name',
        'last_name',
        'nickname',
        'photo',
        'biography',
        'birth_date',
        'education',
        'occupation',
        'type',
        'party_list_order',
        'is_pm_candidate',
        'social_media',
        'policies',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_pm_candidate' => 'boolean',
        'social_media' => 'array',
        'policies' => 'array',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function constituency(): BelongsTo
    {
        return $this->belongsTo(Constituency::class);
    }

    public function constituencyResults(): HasMany
    {
        return $this->hasMany(ConstituencyResult::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->title . ' ' . $this->first_name . ' ' . $this->last_name);
    }

    public function scopePartyList($query)
    {
        return $query->where('type', 'party_list');
    }

    public function scopeConstituency($query)
    {
        return $query->where('type', 'constituency');
    }

    public function scopePmCandidates($query)
    {
        return $query->where('is_pm_candidate', true);
    }
}
