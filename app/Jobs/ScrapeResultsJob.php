<?php

namespace App\Jobs;

use App\Models\Election;
use App\Services\ResultScraperService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        protected Election $election,
    ) {}

    public function handle(ResultScraperService $service): void
    {
        try {
            $results = $service->scrapeAllSources($this->election);

            Log::info("Results scrape completed for election {$this->election->id}", $results);
        } catch (Exception $e) {
            Log::error("Results scrape failed for election {$this->election->id}: " . $e->getMessage());

            throw $e;
        }
    }
}
