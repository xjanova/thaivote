<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Party extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_th',
        'name_en',
        'abbreviation',
        'logo',
        'color',
        'secondary_color',
        'description',
        'slogan',
        'website',
        'facebook_page',
        'twitter_handle',
        'leader_name',
        'leader_photo',
        'founded_year',
        'headquarters',
        'party_number',
        'is_active',
        'api_key',
        'api_secret',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    protected $hidden = [
        'api_secret',
    ];

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function nationalResults(): HasMany
    {
        return $this->hasMany(NationalResult::class);
    }

    public function provinceResults(): HasMany
    {
        return $this->hasMany(ProvinceResult::class);
    }

    public function feeds(): HasMany
    {
        return $this->hasMany(PartyFeed::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(PartyPost::class);
    }

    public function newsArticles()
    {
        return $this->belongsToMany(NewsArticle::class, 'news_article_party');
    }

    public function generateApiKey(): void
    {
        $this->api_key = 'pk_' . Str::random(32);
        $this->api_secret = 'sk_' . Str::random(48);
        $this->save();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
