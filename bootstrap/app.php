<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| This project uses 'public_html' instead of 'public' for shared hosting
| compatibility (DirectAdmin, cPanel). The public path is configured
| in app/Providers/AppServiceProvider.php
|
*/

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            App\Http\Middleware\HandleInertiaRequests::class,
            App\Http\Middleware\CheckInstalled::class,
            App\Http\Middleware\EnsureSuperAdminExists::class,
        ]);

        $middleware->alias([
            'admin' => App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Catch database errors when app is not installed (missing tables)
        $exceptions->render(function (QueryException $e) {
            // Check if this is a "table doesn't exist" error and app not installed
            $tableNotExists = str_contains($e->getMessage(), "doesn't exist")
                || str_contains($e->getMessage(), 'no such table')
                || $e->getCode() === '42S02';

            if ($tableNotExists && ! File::exists(storage_path('app/installed'))) {
                // Return simple HTML redirect (avoid middleware)
                return response(
                    '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=/install"></head>' .
                    '<body><p>Redirecting to installer...</p></body></html>',
                    200,
                    ['Content-Type' => 'text/html'],
                );
            }
        });

        // Catch class not found errors (missing packages)
        $exceptions->render(function (Throwable $e) {
            $isClassNotFound = str_contains($e->getMessage(), 'Class')
                && str_contains($e->getMessage(), 'not found');

            if ($isClassNotFound && ! File::exists(storage_path('app/installed'))) {
                return response(
                    '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=/install"></head>' .
                    '<body><p>Redirecting to installer...</p></body></html>',
                    200,
                    ['Content-Type' => 'text/html'],
                );
            }
        });
    })->create();
