<?php

use App\Http\Controllers\Install\InstallController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Installation Wizard Routes
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'checkInstallation']);
    Route::get('/welcome', [InstallController::class, 'welcome'])->name('welcome');
    Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');
    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database', [InstallController::class, 'databaseStore'])->name('database.store');
    Route::get('/application', [InstallController::class, 'application'])->name('application');
    Route::post('/application', [InstallController::class, 'applicationStore'])->name('application.store');
    Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('/admin', [InstallController::class, 'adminStore'])->name('admin.store');
    Route::get('/complete', [InstallController::class, 'complete'])->name('complete');
});

// Public Routes
Route::get('/', function () {
    return Inertia::render('Dashboard', [
        'electionId' => 1, // Default to active election
    ]);
})->name('home');

Route::get('/election/{election}', function ($election) {
    return Inertia::render('Dashboard', [
        'electionId' => $election,
    ]);
})->name('election.show');

Route::get('/results', function () {
    return Inertia::render('Results/Index');
})->name('results');

Route::get('/results/{province}', function ($province) {
    return Inertia::render('Results/Province', [
        'provinceId' => $province,
    ]);
})->name('results.province');

Route::get('/news', function () {
    return Inertia::render('News/Index');
})->name('news');

Route::get('/news/{article}', function ($article) {
    return Inertia::render('News/Show', [
        'articleId' => $article,
    ]);
})->name('news.show');

Route::get('/parties', function () {
    return Inertia::render('Parties/Index');
})->name('parties');

Route::get('/parties/{party}', function ($party) {
    return Inertia::render('Parties/Show', [
        'partyId' => $party,
    ]);
})->name('parties.show');

Route::get('/map', function () {
    return Inertia::render('Map/FullScreen');
})->name('map');

// Blockchain Voting (Future)
Route::prefix('vote')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Vote/Index');
    })->name('vote');

    Route::get('/verify', function () {
        return Inertia::render('Vote/Verify');
    })->name('vote.verify');
});

// API Documentation
Route::get('/api-docs', function () {
    return Inertia::render('ApiDocs');
})->name('api-docs');

// Admin Routes (Protected)
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('admin.dashboard');

    Route::get('/elections', function () {
        return Inertia::render('Admin/Elections/Index');
    })->name('admin.elections');

    Route::get('/parties', function () {
        return Inertia::render('Admin/Parties/Index');
    })->name('admin.parties');

    Route::get('/news', function () {
        return Inertia::render('Admin/News/Index');
    })->name('admin.news');

    Route::get('/sources', function () {
        return Inertia::render('Admin/Sources/Index');
    })->name('admin.sources');

    Route::get('/keywords', function () {
        return Inertia::render('Admin/Keywords/Index');
    })->name('admin.keywords');

    Route::get('/settings', function () {
        return Inertia::render('Admin/Settings');
    })->name('admin.settings');
});

// Auth routes
require __DIR__.'/auth.php';
