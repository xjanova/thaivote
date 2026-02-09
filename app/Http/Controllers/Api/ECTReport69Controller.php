<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnomalyDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ECTReport69Controller extends Controller
{
    public function __construct(
        protected AnomalyDetectionService $anomalyService,
    ) {}

    /**
     * ดึงข้อมูล ECT Report 69 ทั้งหมด (frontend จะส่ง data มาวิเคราะห์)
     */
    public function analyze(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provinces' => 'required|array',
            'provinces.*.name_th' => 'required|string',
            'provinces.*.constituencies' => 'required|array',
            'national_summary' => 'required|array',
        ]);

        $anomalyResult = $this->anomalyService->analyzeAll($data);
        $aiAnalysis = $this->anomalyService->generateAIAnalysis($data, $anomalyResult);

        return response()->json([
            'success' => true,
            'anomalies' => $anomalyResult,
            'ai_analysis' => $aiAnalysis,
            'analyzed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * วิเคราะห์ความผิดปกติ (รับ data จาก frontend)
     */
    public function anomalies(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provinces' => 'required|array',
        ]);

        $anomalyResult = $this->anomalyService->analyzeAll($data);

        return response()->json([
            'success' => true,
            'data' => $anomalyResult,
            'analyzed_at' => now()->toIso8601String(),
        ]);
    }
}
