<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use App\Models\NewsSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = NewsArticle::with('source')
            ->approved()
            ->orderBy('published_at', 'desc');

        if ($request->has('party_id')) {
            $query->whereHas('parties', function ($q) use ($request) {
                $q->where('party_id', $request->party_id);
            });
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('content', 'like', "%{$request->search}%");
            });
        }

        $news = $query->paginate($request->get('per_page', 20));

        return response()->json($news);
    }

    public function show(NewsArticle $article): JsonResponse
    {
        $article->incrementViews();
        $article->load(['source', 'parties']);

        return response()->json($article);
    }

    public function breaking(): JsonResponse
    {
        $news = NewsArticle::with('source')
            ->approved()
            ->where('is_featured', true)
            ->orWhere('published_at', '>=', now()->subHours(2))
            ->orderBy('published_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($news);
    }

    public function featured(): JsonResponse
    {
        $news = NewsArticle::with('source')
            ->approved()
            ->featured()
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json($news);
    }

    public function sources(): JsonResponse
    {
        $sources = NewsSource::active()
            ->orderBy('priority', 'desc')
            ->get();

        return response()->json($sources);
    }

    public function bySource(NewsSource $source): JsonResponse
    {
        $news = NewsArticle::where('news_source_id', $source->id)
            ->approved()
            ->orderBy('published_at', 'desc')
            ->paginate(20);

        return response()->json($news);
    }
}
