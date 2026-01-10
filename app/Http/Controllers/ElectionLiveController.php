<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Services\LiveResultsService;
use Inertia\Inertia;
use Inertia\Response;

class ElectionLiveController extends Controller
{
    public function __construct(
        private LiveResultsService $liveResultsService,
    ) {}

    /**
     * แสดงหน้า Live Results สำหรับการเลือกตั้งที่ active
     */
    public function index(): Response
    {
        // หาการเลือกตั้งที่กำลังดำเนินอยู่ หรือล่าสุด
        $election = Election::where('status', 'active')
            ->orWhere('status', 'counting')
            ->orderBy('election_date', 'desc')
            ->first();

        if (! $election) {
            // ถ้าไม่มีการเลือกตั้ง active ให้ใช้การเลือกตั้งล่าสุด
            $election = Election::orderBy('election_date', 'desc')->first();
        }

        if (! $election) {
            return Inertia::render('ElectionLive', [
                'election' => null,
                'initialResults' => null,
                'message' => 'ไม่พบข้อมูลการเลือกตั้ง',
            ]);
        }

        return $this->renderLivePage($election);
    }

    /**
     * แสดงหน้า Live Results สำหรับการเลือกตั้งที่ระบุ
     */
    public function show(Election $election): Response
    {
        return $this->renderLivePage($election);
    }

    /**
     * Render หน้า Live Results
     */
    private function renderLivePage(Election $election): Response
    {
        // ดึงผลเลือกตั้งเริ่มต้น
        $nationalResults = $this->liveResultsService->getNationalResults($election->id);
        $regionalResults = $this->liveResultsService->getRegionalResults($election->id);

        return Inertia::render('ElectionLive', [
            'election' => [
                'id' => $election->id,
                'name' => $election->name,
                'election_date' => $election->election_date->format('Y-m-d'),
                'election_date_thai' => $election->election_date->thaidate('j F พ.ศ. Y'),
                'status' => $election->status,
                'total_eligible_voters' => $election->total_eligible_voters,
                'settings' => $election->settings,
            ],
            'initialResults' => [
                'national' => $nationalResults,
                'regional' => $regionalResults,
                'lastUpdated' => now()->toIso8601String(),
            ],
        ]);
    }
}
