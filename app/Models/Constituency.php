<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Constituency extends Model
{
    use HasFactory;

    protected $fillable = [
        'province_id',
        'number',
        'name',
        'total_eligible_voters',
        'total_polling_stations',
        'geo_data',
        'district_ids',
    ];

    protected $casts = [
        'geo_data' => 'array',
        'district_ids' => 'array',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function pollingStations(): HasMany
    {
        return $this->hasMany(PollingStation::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ConstituencyResult::class);
    }

    public function getFullName(): string
    {
        return $this->province->name_th . ' เขต ' . $this->number;
    }
}
