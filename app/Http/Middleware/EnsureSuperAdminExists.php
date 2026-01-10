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
        // Skip check for certain routes (install, setup-admin, auth routes)
        if ($request->is('install*') ||
            $request->is('setup-admin*') ||
            $request->is('login') ||
            $request->is('register') ||
            $request->is('logout')) {
            return $next($request);
        }

        // Check if database exists and has users table
        try {
            if (! Schema::hasTable('users')) {
                // No users table yet - let other middleware handle
                return $next($request);
            }

            // Check if any admin exists
            $hasAdmin = DB::table('users')->where('is_admin', true)->exists();

            if (! $hasAdmin) {
                // No admin exists - redirect to setup
                return redirect('/setup-admin');
            }
        } catch (\Exception $e) {
            // Database error - let other middleware handle it
            return $next($request);
        }

        return $next($request);
    }
}
