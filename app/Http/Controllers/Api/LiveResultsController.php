<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Constituency;
use App\Models\Election;
use App\Models\Province;
use App\Services\LiveResultsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LiveResultsController extends Controller
{
    public function __construct(
        protected LiveResultsService $resultsService,
    ) {}

    /**
     * ดึงผลเลือกตั้งแบบ real-time
     */
    public function index(Request $request): JsonResponse
    {
        $electionId = $request->get('election_id')
            ?? Election::where('status', 'active')
                ->orWhere('status', 'counting')
                ->orderBy('election_date', 'desc')
                ->first()?->id
            ?? Election::orderBy('election_date', 'desc')->first()?->id;

        if (! $electionId) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลการเลือกตั้ง',
            ], 404);
        }

        $results = $this->resultsService->getLiveResults($electionId);

        return response()->json([
            'success' => true,
            'data' => $results,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * ดึงผลระดับชาติ
     */
    public function national(Request $request): JsonResponse
    {
        $electionId = $this->getElectionId($request);

        if (! $electionId) {
            return $this->electionNotFound();
        }

        $results = $this->resultsService->getNationalResults($electionId);

        return response()->json([
            'success' => true,
            'data' => $results,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * ดึงผลรายจังหวัด
     */
    public function province(Request $request, Province $province): JsonResponse
    {
        $electionId = $this->getElectionId($request);

        if (! $electionId) {
            return $this->electionNotFound();
        }

        $results = $this->resultsService->getProvinceResults($electionId, $province->id);

        return response()->json([
            'success' => true,
            'data' => $results,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * ดึงผลรายเขต
     */
    public function constituency(Request $request, Constituency $constituency): JsonResponse
    {
        $electionId = $this->getElectionId($request);

        if (! $electionId) {
            return $this->electionNotFound();
        }

        $results = $this->resultsService->getConstituencyResults($electionId, $constituency->id);

        return response()->json([
            'success' => true,
            'data' => $results,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * ดึงผลรายภาค
     */
    public function regions(Request $request): JsonResponse
    {
        $electionId = $this->getElectionId($request);

        if (! $electionId) {
            return $this->electionNotFound();
        }

        $results = $this->resultsService->getRegionalResults($electionId);

        return response()->json([
            'success' => true,
            'data' => $results,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Stream ผลเลือกตั้ง (Server-Sent Events)
     */
    public function stream(Request $request): StreamedResponse
    {
        $electionId = $this->getElectionId($request);

        return response()->stream(function () use ($electionId) {
            $lastData = null;

            while (true) {
                if (! $electionId) {
                    echo "event: error\n";
                    echo 'data: ' . json_encode(['message' => 'ไม่พบข้อมูลการเลือกตั้ง']) . "\n\n";
                    break;
                }

                $results = $this->resultsService->getLiveResults($electionId);
                $currentData = json_encode($results);

                // Only send if data changed
                if ($currentData !== $lastData) {
                    echo "event: update\n";
                    echo 'data: ' . $currentData . "\n\n";
                    $lastData = $currentData;
                } else {
                    // Send heartbeat
                    echo "event: heartbeat\n";
                    echo 'data: ' . json_encode(['timestamp' => now()->toIso8601String()]) . "\n\n";
                }

                ob_flush();
                flush();

                // Update every 5 seconds
                sleep(5);

                // Stop if client disconnected
                if (connection_aborted()) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Get election ID from request or default to active election
     */
    protected function getElectionId(Request $request): ?int
    {
        return $request->get('election_id')
            ?? Election::where('status', 'active')
                ->orWhere('status', 'counting')
                ->orderBy('election_date', 'desc')
                ->first()?->id
            ?? Election::orderBy('election_date', 'desc')->first()?->id;
    }

    /**
     * Return election not found response
     */
    protected function electionNotFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ไม่พบข้อมูลการเลือกตั้ง',
        ], 404);
    }
}
