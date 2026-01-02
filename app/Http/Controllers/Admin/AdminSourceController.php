<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsSource;
use App\Services\NewsAggregatorService;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSourceController extends Controller
{
    /**
     * Display a listing of news sources.
     */
    public function index(Request $request): Response
    {
        $query = NewsSource::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_th', 'like', "%{$search}%")
                    ->orWhere('website', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Filter by active
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $sources = $query
            ->withCount('articles')
            ->orderBy('priority')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Sources/Index', [
            'sources' => $sources,
            'filters' => $request->only(['search', 'type', 'active']),
            'types' => ['rss', 'api', 'scrape'],
        ]);
    }

    /**
     * Show the form for creating a new source.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Sources/Create', [
            'types' => ['rss', 'api', 'scrape'],
        ]);
    }

    /**
     * Store a newly created source.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:news_sources,name',
            'name_th' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:500',
            'logo' => 'nullable|url|max:500',
            'rss_url' => 'nullable|url|max:500',
            'api_endpoint' => 'nullable|url|max:500',
            'scrape_config' => 'nullable|array',
            'type' => 'required|in:rss,api,scrape',
            'priority' => 'nullable|integer|min:0|max:100',
            'fetch_interval' => 'nullable|integer|min:60', // minimum 1 minute
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['priority'] = $validated['priority'] ?? 50;
        $validated['fetch_interval'] = $validated['fetch_interval'] ?? 300; // 5 minutes default

        NewsSource::create($validated);

        return redirect()
            ->route('admin.sources.index')
            ->with('success', 'สร้างแหล่งข่าวสำเร็จ');
    }

    /**
     * Display the specified source.
     */
    public function show(NewsSource $source): Response
    {
        $source->loadCount('articles');

        $recentArticles = $source->articles()
            ->select('id', 'title', 'published_at', 'is_approved')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Sources/Show', [
            'source' => $source,
            'recentArticles' => $recentArticles,
        ]);
    }

    /**
     * Show the form for editing the specified source.
     */
    public function edit(NewsSource $source): Response
    {
        return Inertia::render('Admin/Sources/Edit', [
            'source' => $source,
            'types' => ['rss', 'api', 'scrape'],
        ]);
    }

    /**
     * Update the specified source.
     */
    public function update(Request $request, NewsSource $source)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:news_sources,name,' . $source->id,
            'name_th' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:500',
            'logo' => 'nullable|url|max:500',
            'rss_url' => 'nullable|url|max:500',
            'api_endpoint' => 'nullable|url|max:500',
            'scrape_config' => 'nullable|array',
            'type' => 'required|in:rss,api,scrape',
            'priority' => 'nullable|integer|min:0|max:100',
            'fetch_interval' => 'nullable|integer|min:60',
            'is_active' => 'boolean',
        ]);

        $source->update($validated);

        return redirect()
            ->route('admin.sources.index')
            ->with('success', 'อัปเดตแหล่งข่าวสำเร็จ');
    }

    /**
     * Remove the specified source.
     */
    public function destroy(NewsSource $source)
    {
        // Check if source has articles
        if ($source->articles()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถลบแหล่งข่าวที่มีบทความได้ กรุณาลบบทความก่อน');
        }

        $source->delete();

        return redirect()
            ->route('admin.sources.index')
            ->with('success', 'ลบแหล่งข่าวสำเร็จ');
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(NewsSource $source)
    {
        $source->update(['is_active' => ! $source->is_active]);

        return redirect()
            ->back()
            ->with('success', $source->is_active ? 'เปิดใช้งานแหล่งข่าวแล้ว' : 'ปิดใช้งานแหล่งข่าวแล้ว');
    }

    /**
     * Manually trigger a fetch from this source.
     */
    public function fetch(NewsSource $source, NewsAggregatorService $aggregator)
    {
        try {
            $count = $aggregator->fetchFromSource($source);

            return redirect()
                ->back()
                ->with('success', "ดึงข่าวสำเร็จ {$count} รายการ");
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
