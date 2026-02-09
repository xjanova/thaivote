<?php

use App\Http\Controllers\Api\ConstituencyController;
use App\Http\Controllers\Api\ECTReport69Controller;
use App\Http\Controllers\Api\ElectionController;
use App\Http\Controllers\Api\LiveResultsController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\PartyApiController;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\ProvinceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Election Routes
Route::prefix('elections')->group(function () {
    Route::get('/', [ElectionController::class, 'index']);
    Route::get('/active', [ElectionController::class, 'active']);
    Route::get('/{election}', [ElectionController::class, 'show']);
    Route::get('/{election}/stats', [ElectionController::class, 'stats']);
    Route::get('/{election}/national-results', [ElectionController::class, 'nationalResults']);
    Route::get('/{election}/timeline', [ElectionController::class, 'timeline']);
    Route::post('/{election}/snapshots', [ElectionController::class, 'createSnapshot'])
        ->middleware('auth:sanctum');

    // Province results within election
    Route::get('/{electionId}/provinces/{province}/results', [ProvinceController::class, 'results']);

    // Constituency results within election
    Route::get('/{electionId}/constituencies/{constituency}/results', [ConstituencyController::class, 'results']);

    // Party results within election
    Route::get('/{electionId}/parties/{party}/results', [PartyController::class, 'results']);
    Route::get('/{electionId}/parties/{party}/candidates', [PartyController::class, 'candidates']);
});

// Province Routes
Route::prefix('provinces')->group(function () {
    Route::get('/', [ProvinceController::class, 'index']);
    Route::get('/geojson', [ProvinceController::class, 'geoJson']);
    Route::get('/region/{region}', [ProvinceController::class, 'byRegion']);
    Route::get('/{province}', [ProvinceController::class, 'show']);
    Route::get('/{province}/constituencies', [ConstituencyController::class, 'index']);
});

// Party Routes
Route::prefix('parties')->group(function () {
    Route::get('/', [PartyController::class, 'index']);
    Route::get('/trending', [PartyController::class, 'trending']);
    Route::get('/{party}', [PartyController::class, 'show']);
    Route::get('/{party}/posts', [PartyController::class, 'posts']);
    Route::get('/{party}/news', [PartyController::class, 'news']);
});

// News Routes
Route::prefix('news')->group(function () {
    Route::get('/', [NewsController::class, 'index']);
    Route::get('/breaking', [NewsController::class, 'breaking']);
    Route::get('/featured', [NewsController::class, 'featured']);
    Route::get('/sources', [NewsController::class, 'sources']);
    Route::get('/sources/{source}', [NewsController::class, 'bySource']);
    Route::get('/{article}', [NewsController::class, 'show']);
});

// Party API (Authenticated)
Route::prefix('party-api')->group(function () {
    Route::post('/authenticate', [PartyApiController::class, 'authenticate']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [PartyApiController::class, 'profile']);
        Route::put('/profile', [PartyApiController::class, 'updateProfile']);
        Route::post('/results', [PartyApiController::class, 'submitResults']);
        Route::get('/feeds', [PartyApiController::class, 'getFeeds']);
        Route::post('/feeds', [PartyApiController::class, 'addFeed']);
        Route::post('/posts', [PartyApiController::class, 'submitPost']);
        Route::get('/analytics', [PartyApiController::class, 'getAnalytics']);
    });
});

// Live Results (Real-time Dashboard API)
Route::prefix('live')->group(function () {
    Route::get('/', [LiveResultsController::class, 'index']);
    Route::get('/national', [LiveResultsController::class, 'national']);
    Route::get('/regions', [LiveResultsController::class, 'regions']);
    Route::get('/province/{province}', [LiveResultsController::class, 'province']);
    Route::get('/constituency/{constituency}', [LiveResultsController::class, 'constituency']);
    Route::get('/stream', [LiveResultsController::class, 'stream']); // Server-Sent Events
});

// ECT Report 69 Analysis (Public)
Route::prefix('ect69')->group(function () {
    Route::post('/analyze', [ECTReport69Controller::class, 'analyze']);
    Route::post('/anomalies', [ECTReport69Controller::class, 'anomalies']);

    Route::get('/scrape', function () {
        $service = app(App\Services\ECTReport69Service::class);
        $election = App\Models\Election::where('status', 'counting')
            ->orWhere('status', 'ongoing')
            ->orderBy('election_date', 'desc')
            ->first();

        if (! $election) {
            return response()->json(['success' => false, 'message' => 'No active election'], 404);
        }

        $stats = $service->scrapeAndUpdate($election->id);

        return response()->json([
            'success' => true,
            'data' => $stats,
            'timestamp' => now()->toIso8601String(),
        ]);
    })->middleware('auth:sanctum');
});

// Webhook for external data sources
Route::prefix('webhooks')->middleware('throttle:100,1')->group(function () {
    Route::post('/results/{source}', function ($source) {
        // Handle incoming results from external sources
        // This would be validated with a secret key
    });

    Route::post('/news/{source}', function ($source) {
        // Handle incoming news from RSS/API sources
    });
});
