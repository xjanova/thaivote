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
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            App\Http\Middleware\CheckInstalled::class,
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

            if ($tableNotExists && !File::exists(storage_path('app/installed'))) {
                return redirect('/install');
            }
        });
    })->create();
