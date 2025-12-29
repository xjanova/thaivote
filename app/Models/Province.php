<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_th',
        'name_en',
        'code',
        'region',
        'population',
        'total_districts',
        'total_constituencies',
        'geo_data',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'geo_data' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function constituencies(): HasMany
    {
        return $this->hasMany(Constituency::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ProvinceResult::class);
    }

    public function getResultsForElection(int $electionId)
    {
        return $this->results()->where('election_id', $electionId)->get();
    }
}
