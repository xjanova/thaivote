<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\NationalResult;
use App\Models\Candidate;
use App\Models\PartyPost;
use App\Models\NewsArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    public function index(): JsonResponse
    {
        $parties = Party::active()
            ->orderBy('name_th')
            ->get();

        return response()->json($parties);
    }

    public function show(Party $party): JsonResponse
    {
        return response()->json($party);
    }

    public function results(int $electionId, Party $party): JsonResponse
    {
        $nationalResult = NationalResult::where('election_id', $electionId)
            ->where('party_id', $party->id)
            ->first();

        $candidates = Candidate::with('constituency.province')
            ->where('election_id', $electionId)
            ->where('party_id', $party->id)
            ->get();

        return response()->json([
            'party' => $party,
            'result' => $nationalResult,
            'candidates' => $candidates,
        ]);
    }

    public function candidates(int $electionId, Party $party): JsonResponse
    {
        $candidates = Candidate::with(['constituency.province', 'constituencyResults'])
            ->where('election_id', $electionId)
            ->where('party_id', $party->id)
            ->get()
            ->map(function ($candidate) use ($electionId) {
                $result = $candidate->constituencyResults
                    ->firstWhere('election_id', $electionId);
                return [
                    ...$candidate->toArray(),
                    'votes' => $result?->votes ?? 0,
                    'is_winner' => $result?->is_winner ?? false,
                ];
            });

        return response()->json($candidates);
    }

    public function posts(Party $party): JsonResponse
    {
        $posts = PartyPost::where('party_id', $party->id)
            ->with('feed')
            ->orderBy('posted_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json($posts);
    }

    public function news(Party $party): JsonResponse
    {
        $news = $party->newsArticles()
            ->with('source')
            ->orderBy('published_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json($news);
    }

    public function trending(): JsonResponse
    {
        // Get parties sorted by recent news/mentions
        $parties = Party::active()
            ->withCount(['newsArticles as recent_mentions' => function ($query) {
                $query->where('published_at', '>=', now()->subHours(24));
            }])
            ->orderBy('recent_mentions', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($party) {
                // Calculate trend (comparison with previous period)
                $previousMentions = $party->newsArticles()
                    ->whereBetween('published_at', [now()->subHours(48), now()->subHours(24)])
                    ->count();

                $trend = $previousMentions > 0
                    ? (($party->recent_mentions - $previousMentions) / $previousMentions) * 100
                    : 0;

                return [
                    ...$party->toArray(),
                    'mentions' => $party->recent_mentions,
                    'trend' => round($trend, 1),
                ];
            });

        return response()->json($parties);
    }
}
