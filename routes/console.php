<?php

use App\Jobs\CreateElectionSnapshot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Create election snapshots every 5 minutes during active counting
Schedule::job(new CreateElectionSnapshot)
    ->everyFiveMinutes()
    ->name('election-snapshots')
    ->withoutOverlapping();
