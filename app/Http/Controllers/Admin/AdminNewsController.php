<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use App\Models\NewsSource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminNewsController extends Controller
{
    /**
     * Display a listing of news articles.
     */
    public function index(Request $request): Response
    {
        $query = NewsArticle::with('source:id,name');

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%");
            });
        }

        // Filter by source
        if ($sourceId = $request->input('source')) {
            $query->where('news_source_id', $sourceId);
        }

        // Filter by featured
        if ($request->has('featured')) {
            $query->where('is_featured', $request->boolean('featured'));
        }

        // Filter by approved
        if ($request->has('approved')) {
            $query->where('is_approved', $request->boolean('approved'));
        }

        // Filter by sentiment
        if ($sentiment = $request->input('sentiment')) {
            $query->where('sentiment', $sentiment);
        }

        $articles = $query
            ->orderByDesc('published_at')
            ->paginate(15)
            ->withQueryString();

        $sources = NewsSource::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/News/Index', [
            'articles' => $articles,
            'sources' => $sources,
            'filters' => $request->only(['search', 'source', 'featured', 'approved', 'sentiment']),
        ]);
    }

    /**
     * Show the form for creating a new article.
     */
    public function create(): Response
    {
        $sources = NewsSource::select('id', 'name')->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Admin/News/Create', [
            'sources' => $sources,
            'sentiments' => ['positive', 'negative', 'neutral'],
        ]);
    }

    /**
     * Store a newly created article.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'news_source_id' => 'required|exists:news_sources,id',
            'title' => 'required|string|max:500',
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'url' => 'nullable|url|max:500',
            'image_url' => 'nullable|url|max:500',
            'author' => 'nullable|string|max:255',
            'published_at' => 'required|date',
            'sentiment' => 'nullable|in:positive,negative,neutral',
            'sentiment_score' => 'nullable|numeric|min:-1|max:1',
            'is_featured' => 'boolean',
            'is_approved' => 'boolean',
        ]);

        $validated['is_featured'] = $validated['is_featured'] ?? false;
        $validated['is_approved'] = $validated['is_approved'] ?? true;

        NewsArticle::create($validated);

        return redirect()
            ->route('admin.news.index')
            ->with('success', 'สร้างข่าวสำเร็จ');
    }

    /**
     * Display the specified article.
     */
    public function show(NewsArticle $news): Response
    {
        $news->load('source', 'parties');

        return Inertia::render('Admin/News/Show', [
            'article' => $news,
        ]);
    }

    /**
     * Show the form for editing the specified article.
     */
    public function edit(NewsArticle $news): Response
    {
        $sources = NewsSource::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/News/Edit', [
            'article' => $news,
            'sources' => $sources,
            'sentiments' => ['positive', 'negative', 'neutral'],
        ]);
    }

    /**
     * Update the specified article.
     */
    public function update(Request $request, NewsArticle $news)
    {
        $validated = $request->validate([
            'news_source_id' => 'required|exists:news_sources,id',
            'title' => 'required|string|max:500',
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'url' => 'nullable|url|max:500',
            'image_url' => 'nullable|url|max:500',
            'author' => 'nullable|string|max:255',
            'published_at' => 'required|date',
            'sentiment' => 'nullable|in:positive,negative,neutral',
            'sentiment_score' => 'nullable|numeric|min:-1|max:1',
            'is_featured' => 'boolean',
            'is_approved' => 'boolean',
        ]);

        $news->update($validated);

        return redirect()
            ->route('admin.news.index')
            ->with('success', 'อัปเดตข่าวสำเร็จ');
    }

    /**
     * Remove the specified article.
     */
    public function destroy(NewsArticle $news)
    {
        $news->delete();

        return redirect()
            ->route('admin.news.index')
            ->with('success', 'ลบข่าวสำเร็จ');
    }

    /**
     * Publish an article.
     */
    public function publish(NewsArticle $news)
    {
        $news->update(['is_approved' => true]);

        return redirect()
            ->back()
            ->with('success', 'เผยแพร่ข่าวสำเร็จ');
    }

    /**
     * Unpublish an article.
     */
    public function unpublish(NewsArticle $news)
    {
        $news->update(['is_approved' => false]);

        return redirect()
            ->back()
            ->with('success', 'ยกเลิกการเผยแพร่ข่าวสำเร็จ');
    }
}
