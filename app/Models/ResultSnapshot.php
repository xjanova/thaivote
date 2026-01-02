<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'snapshot_at',
        'counting_progress',
        'constituencies_counted',
        'stations_counted',
        'total_votes_cast',
        'party_results',
        'leading_parties',
    ];

    protected $casts = [
        'snapshot_at' => 'datetime',
        'counting_progress' => 'decimal:2',
        'party_results' => 'array',
        'leading_parties' => 'array',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    /**
     * Create a snapshot from current election state.
     */
    public static function createFromElection(Election $election): self
    {
        $stats = $election->stats;
        $nationalResults = NationalResult::where('election_id', $election->id)
            ->with('party:id,name_th,short_name,color')
            ->orderByDesc('total_seats')
            ->get();

        $partyResults = $nationalResults->map(fn ($r) => [
            'party_id' => $r->party_id,
            'party_name' => $r->party?->short_name ?? $r->party?->name_th,
            'color' => $r->party?->color,
            'constituency_seats' => $r->constituency_seats,
            'party_list_seats' => $r->party_list_seats,
            'total_seats' => $r->total_seats,
            'total_votes' => $r->total_votes,
        ])->toArray();

        $leadingParties = array_slice($partyResults, 0, 5);

        return self::create([
            'election_id' => $election->id,
            'snapshot_at' => now(),
            'counting_progress' => $stats?->counting_progress ?? 0,
            'constituencies_counted' => $stats?->constituencies_counted ?? 0,
            'stations_counted' => $stats?->stations_counted ?? 0,
            'total_votes_cast' => $stats?->total_votes_cast ?? 0,
            'party_results' => $partyResults,
            'leading_parties' => $leadingParties,
        ]);
    }
}
