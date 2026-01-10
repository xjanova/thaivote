<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdminExists
{
    /**
     * Handle an incoming request.
     *
     * Check if at least one super admin exists. If not, redirect to the setup admin page.
     * This ensures the system always has an administrator.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for certain routes
        if ($request->is('install*') || $request->is('setup-admin*')) {
            return $next($request);
        }

        // Skip if not installed yet
        if (! File::exists(storage_path('app/installed'))) {
            return $next($request);
        }

        // Check if users table exists and has admin
        try {
            if (Schema::hasTable('users')) {
                $hasAdmin = DB::table('users')->where('is_admin', true)->exists();

                if (! $hasAdmin) {
                    return redirect('/setup-admin');
                }
            }
        } catch (\Exception $e) {
            // If database error, let other middleware handle it
        }

        return $next($request);
    }
}
