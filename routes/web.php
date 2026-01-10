<?php

use App\Http\Controllers\Admin\AdminCandidateController;
use App\Http\Controllers\Admin\AdminElectionController;
use App\Http\Controllers\Admin\AdminNewsController;
use App\Http\Controllers\Admin\AdminPartyController;
use App\Http\Controllers\Admin\AdminSourceController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\SetupAdminController;
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

// Setup Admin Route (when no admin exists after installation)
Route::get('/setup-admin', [SetupAdminController::class, 'show'])->name('setup-admin');
Route::post('/setup-admin', [SetupAdminController::class, 'store'])->name('setup-admin.store');

// Public Routes
Route::get('/', function () {
    return Inertia::render('Dashboard', [
        'electionId' => 1, // Default to active election
    ]);
})->name('home');

// Live Election Results (Real-time Dashboard)
Route::get('/live', [App\Http\Controllers\ElectionLiveController::class, 'index'])->name('live');
Route::get('/live/{election}', [App\Http\Controllers\ElectionLiveController::class, 'show'])->name('live.show');

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
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    // Dashboard API
    Route::prefix('api/dashboard')->name('dashboard.')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');
        Route::get('/sources', [DashboardController::class, 'sources'])->name('sources');
        Route::get('/recent-news', [DashboardController::class, 'recentNews'])->name('recent-news');
        Route::get('/pending', [DashboardController::class, 'pending'])->name('pending');
        Route::get('/logs', [DashboardController::class, 'logs'])->name('logs');
        Route::get('/traffic', [DashboardController::class, 'traffic'])->name('traffic');
        Route::post('/approve', [DashboardController::class, 'approve'])->name('approve');
        Route::post('/reject', [DashboardController::class, 'reject'])->name('reject');
    });

    // พรรคการเมือง (CRUD)
    Route::resource('parties', AdminPartyController::class);
    Route::post('/parties/{party}/api-key', [AdminPartyController::class, 'generateApiKey'])->name('parties.api-key');
    Route::post('/parties/{party}/toggle-active', [AdminPartyController::class, 'toggleActive'])->name('parties.toggle-active');

    // ผู้สมัคร (CRUD)
    Route::resource('candidates', AdminCandidateController::class);
    Route::get('/candidates/constituencies/{province}', [AdminCandidateController::class, 'getConstituencies'])->name('candidates.constituencies');
    Route::post('/candidates/import', [AdminCandidateController::class, 'import'])->name('candidates.import');
    Route::get('/candidates/export', [AdminCandidateController::class, 'export'])->name('candidates.export');

    // การเลือกตั้ง (CRUD)
    Route::resource('elections', AdminElectionController::class);
    Route::post('/elections/{election}/toggle-active', [AdminElectionController::class, 'toggleActive'])->name('elections.toggle-active');
    Route::post('/elections/{election}/status', [AdminElectionController::class, 'updateStatus'])->name('elections.status');
    Route::post('/elections/{election}/duplicate', [AdminElectionController::class, 'duplicate'])->name('elections.duplicate');

    // ข่าว (CRUD)
    Route::resource('news', AdminNewsController::class);
    Route::post('/news/{news}/publish', [AdminNewsController::class, 'publish'])->name('news.publish');
    Route::post('/news/{news}/unpublish', [AdminNewsController::class, 'unpublish'])->name('news.unpublish');

    // แหล่งข้อมูล (CRUD)
    Route::resource('sources', AdminSourceController::class);
    Route::post('/sources/{source}/toggle-active', [AdminSourceController::class, 'toggleActive'])->name('sources.toggle-active');
    Route::post('/sources/{source}/fetch', [AdminSourceController::class, 'fetch'])->name('sources.fetch');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // ผู้ใช้งาน (User Management)
    Route::resource('users', AdminUserController::class);
    Route::post('/users/{user}/toggle-admin', [AdminUserController::class, 'toggleAdmin'])->name('users.toggle-admin');
});


// Auth routes
require __DIR__ . '/auth.php';
