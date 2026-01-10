<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class TrackPageViews
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only track GET requests (not API, admin, or asset requests)
        if ($request->isMethod('GET') &&
            !$request->is('api/*') &&
            !$request->is('admin/*') &&
            !$request->is('build/*') &&
            !str_ends_with($request->path(), '.js') &&
            !str_ends_with($request->path(), '.css')
        ) {
            $this->trackView();
        }

        return $next($request);
    }

    /**
     * Track the page view in cache.
     */
    protected function trackView(): void
    {
        $now = Carbon::now();

        // Round to nearest 15 minutes
        $minutes = $now->minute;
        $roundedMinutes = floor($minutes / 15) * 15;
        $time = $now->copy()->minute($roundedMinutes)->second(0);

        // Cache key format: traffic.2024-01-10-14-30
        $cacheKey = 'traffic.' . $time->format('Y-m-d-H-i');

        // Increment view count (cache for 2 hours)
        $views = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $views + 1, now()->addHours(2));

        // Also track daily total
        $dailyKey = 'traffic.daily.' . $time->format('Y-m-d');
        $dailyViews = Cache::get($dailyKey, 0);
        Cache::put($dailyKey, $dailyViews + 1, now()->addDay());
    }
}
