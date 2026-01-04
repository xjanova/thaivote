<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    /**
     * Handle an incoming request.
     *
     * Check if the application is installed. If not, redirect to the installation wizard.
     * This prevents 500 errors when the database isn't set up yet.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for install routes
        if ($request->is('install*')) {
            return $next($request);
        }

        // Check if installed marker file exists
        if (! File::exists(storage_path('app/installed'))) {
            return redirect('/install');
        }

        return $next($request);
    }
}
