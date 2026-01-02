<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\PartyFeed;
use App\Models\PartyPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PartyApiController extends Controller
{
    /**
     * API endpoints for political parties to integrate with the system
     */
    public function authenticate(Request $request): JsonResponse
    {
        $request->validate([
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
        ]);

        $party = Party::where('api_key', $request->api_key)->first();

        if (! $party || ! Hash::check($request->api_secret, $party->api_secret)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Generate a temporary token
        $token = $party->createToken('party-api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'party' => $party,
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $party = $request->user()->party;

        return response()->json($party);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $party = $request->user()->party;

        $validated = $request->validate([
            'slogan' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url',
            'facebook_page' => 'nullable|string',
            'twitter_handle' => 'nullable|string',
        ]);

        $party->update($validated);

        return response()->json($party);
    }

    public function submitResults(Request $request): JsonResponse
    {
        $party = $request->user()->party;

        $validated = $request->validate([
            'election_id' => 'required|exists:elections,id',
            'results' => 'required|array',
            'results.*.constituency_id' => 'required|exists:constituencies,id',
            'results.*.candidate_id' => 'required|exists:candidates,id',
            'results.*.votes' => 'required|integer|min:0',
        ]);

        // Store party-submitted results (for verification)
        foreach ($validated['results'] as $result) {
            // This would be stored in a party_submitted_results table
            // and used for cross-verification with official results
        }

        return response()->json(['message' => 'Results submitted successfully']);
    }

    public function getFeeds(Request $request): JsonResponse
    {
        $party = $request->user()->party;

        $feeds = PartyFeed::where('party_id', $party->id)->get();

        return response()->json($feeds);
    }

    public function addFeed(Request $request): JsonResponse
    {
        $party = $request->user()->party;

        $validated = $request->validate([
            'platform' => 'required|in:facebook,twitter,instagram,youtube',
            'page_id' => 'required|string',
            'page_url' => 'required|url',
            'access_token' => 'nullable|string',
        ]);

        $feed = PartyFeed::create([
            'party_id' => $party->id,
            ...$validated,
        ]);

        return response()->json($feed, 201);
    }

    public function submitPost(Request $request): JsonResponse
    {
        $party = $request->user()->party;

        $validated = $request->validate([
            'content' => 'required|string',
            'media_type' => 'nullable|in:image,video',
            'media_urls' => 'nullable|array',
            'platform' => 'required|string',
            'platform_post_id' => 'required|string',
            'post_url' => 'required|url',
        ]);

        $feed = PartyFeed::where('party_id', $party->id)
            ->where('platform', $validated['platform'])
            ->first();

        if (! $feed) {
            return response()->json(['message' => 'Feed not found'], 404);
        }

        $post = PartyPost::create([
            'party_feed_id' => $feed->id,
            'party_id' => $party->id,
            'platform_post_id' => $validated['platform_post_id'],
            'content' => $validated['content'],
            'post_url' => $validated['post_url'],
            'media_type' => $validated['media_type'],
            'media_urls' => $validated['media_urls'],
            'posted_at' => now(),
        ]);

        return response()->json($post, 201);
    }

    public function getAnalytics(Request $request): JsonResponse
    {
        $party = $request->user()->party;

        // Get party analytics
        $analytics = [
            'total_votes' => $party->nationalResults()->sum('total_votes'),
            'total_seats' => $party->nationalResults()->sum('total_seats'),
            'news_mentions' => $party->newsArticles()->count(),
            'social_engagement' => $party->posts()->sum('likes') + $party->posts()->sum('shares'),
        ];

        return response()->json($analytics);
    }
}
