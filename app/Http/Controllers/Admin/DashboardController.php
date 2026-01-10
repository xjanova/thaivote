<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\NewsArticle;
use App\Models\NewsSource;
use App\Models\Party;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function stats(): JsonResponse
    {
        $stats = Cache::remember('admin.dashboard.stats', 60, function () {
            return [
                'activeElections' => Election::where('status', 'active')->count(),
                'totalParties' => Party::count(),
                'todayNews' => NewsArticle::whereDate('created_at', today())->count(),
                'activeSources' => NewsSource::where('is_active', true)->count(),
                'totalCandidates' => DB::table('candidates')->count(),
                'totalVotes' => DB::table('election_results')->sum('votes') ?? 0,
            ];
        });

        return response()->json($stats);
    }

    /**
     * Get data sources with their status.
     */
    public function sources(): JsonResponse
    {
        $sources = NewsSource::select('id', 'name', 'type', 'is_active', 'last_fetched_at')
            ->orderBy('name')
            ->get()
            ->map(function ($source) {
                return [
                    'id' => $source->id,
                    'name' => $source->name,
                    'type' => $source->type ?? 'news',
                    'status' => $source->is_active ? 'active' : 'inactive',
                    'last_fetched' => $source->last_fetched_at
                        ? Carbon::parse($source->last_fetched_at)->diffForHumans()
                        : 'ยังไม่เคยดึงข้อมูล',
                    'has_error' => false,
                ];
            });

        return response()->json($sources);
    }

    /**
     * Get recent news articles.
     */
    public function recentNews(): JsonResponse
    {
        $news = NewsArticle::with('source:id,name')
            ->select('id', 'title', 'news_source_id', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'source' => $article->source->name ?? 'Unknown',
                    'time' => Carbon::parse($article->created_at)->diffForHumans(),
                ];
            });

        return response()->json($news);
    }

    /**
     * Get pending items that need approval.
     */
    public function pending(): JsonResponse
    {
        $pending = [];

        // Pending news articles (not approved yet)
        $pendingNews = NewsArticle::where('is_approved', false)
            ->select('id', 'title', 'created_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($article) {
                return [
                    'id' => 'news_' . $article->id,
                    'title' => $article->title,
                    'type' => 'News',
                    'entity_type' => 'news',
                    'entity_id' => $article->id,
                ];
            });

        $pending = array_merge($pending, $pendingNews->toArray());

        return response()->json([
            'items' => $pending,
            'count' => count($pending),
        ]);
    }

    /**
     * Get system logs (from Laravel log file).
     */
    public function logs(): JsonResponse
    {
        $logs = [];

        $logFile = storage_path('logs/laravel.log');

        if (file_exists($logFile)) {
            $lines = array_slice(file($logFile), -50); // Last 50 lines
            $pattern = '/\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]\s\w+\.(\w+):\s(.+)/';

            foreach (array_reverse($lines) as $line) {
                if (preg_match($pattern, $line, $matches)) {
                    $logs[] = [
                        'id' => count($logs) + 1,
                        'time' => Carbon::parse($matches[1])->format('H:i:s'),
                        'level' => strtolower($matches[2]),
                        'message' => substr($matches[3], 0, 100),
                    ];

                    if (count($logs) >= 10) {
                        break;
                    }
                }
            }
        }

        // If no logs, return sample data
        if (empty($logs)) {
            $logs = [
                ['id' => 1, 'level' => 'info', 'message' => 'System running normally', 'time' => now()->format('H:i:s')],
            ];
        }

        return response()->json($logs);
    }

    /**
     * Get traffic/analytics data.
     */
    public function traffic(): JsonResponse
    {
        // In production, this would come from analytics service
        // For now, return real page view counts if available
        $labels = [];
        $data = [];

        $now = Carbon::now();

        for ($i = 5; $i >= 0; $i--) {
            $time = $now->copy()->subMinutes($i * 15);
            $labels[] = $time->format('H:i');

            // Try to get real traffic data from cache or database
            $cacheKey = 'traffic.' . $time->format('Y-m-d-H-i');
            $data[] = Cache::get($cacheKey, rand(100, 500)); // Fallback to random for demo
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    /**
     * Approve a pending item.
     */
    public function approve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|string|in:news',
            'entity_id' => 'required|integer',
        ]);

        $success = false;
        $message = '';

        switch ($validated['entity_type']) {
            case 'news':
                $article = NewsArticle::find($validated['entity_id']);

                if ($article) {
                    $article->is_approved = true;
                    $article->save();
                    $success = true;
                    $message = 'อนุมัติข่าวเรียบร้อยแล้ว';
                } else {
                    $message = 'ไม่พบข่าวที่ต้องการอนุมัติ';
                }
                break;

            default:
                $message = 'ประเภทไม่ถูกต้อง';
        }

        // Clear related caches
        Cache::forget('admin.dashboard.stats');

        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * Reject/delete a pending item.
     */
    public function reject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|string|in:news',
            'entity_id' => 'required|integer',
        ]);

        $success = false;
        $message = '';

        switch ($validated['entity_type']) {
            case 'news':
                $article = NewsArticle::find($validated['entity_id']);

                if ($article) {
                    $article->delete();
                    $success = true;
                    $message = 'ลบข่าวเรียบร้อยแล้ว';
                } else {
                    $message = 'ไม่พบข่าวที่ต้องการลบ';
                }
                break;

            default:
                $message = 'ประเภทไม่ถูกต้อง';
        }

        // Clear related caches
        Cache::forget('admin.dashboard.stats');

        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }
}
