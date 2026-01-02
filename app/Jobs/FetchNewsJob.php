<?php

namespace App\Jobs;

use App\Services\NewsAggregatorService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function handle(NewsAggregatorService $service): void
    {
        try {
            $results = $service->fetchAllSources();

            Log::info('News fetch completed', $results);
        } catch (Exception $e) {
            Log::error('News fetch failed: ' . $e->getMessage());

            throw $e;
        }
    }
}
