<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Constituency;
use App\Models\Election;
use App\Models\Party;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminCandidateController extends Controller
{
    /**
     * แสดงรายการผู้สมัครทั้งหมด
     */
    public function index(Request $request)
    {
        $query = Candidate::with(['party', 'election', 'constituency.province']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%")
                    ->orWhere('candidate_number', 'like', "%{$search}%");
            });
        }

        // Filter by party
        if ($partyId = $request->input('party_id')) {
            $query->where('party_id', $partyId);
        }

        // Filter by election
        if ($electionId = $request->input('election_id')) {
            $query->where('election_id', $electionId);
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Filter by province
        if ($provinceId = $request->input('province_id')) {
            $query->whereHas('constituency', function ($q) use ($provinceId) {
                $q->where('province_id', $provinceId);
            });
        }

        $candidates = $query->orderBy('candidate_number')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Candidates/Index', [
            'candidates' => $candidates,
            'filters' => $request->only(['search', 'party_id', 'election_id', 'type', 'province_id']),
            'parties' => Party::active()->orderBy('name_th')->get(['id', 'name_th', 'color']),
            'elections' => Election::orderByDesc('election_date')->get(['id', 'name', 'election_date']),
            'provinces' => Province::orderBy('name_th')->get(['id', 'name_th']),
        ]);
    }

    /**
     * แสดงฟอร์มสร้างผู้สมัครใหม่
     */
    public function create(Request $request)
    {
        $constituencies = [];

        if ($provinceId = $request->input('province_id')) {
            $constituencies = Constituency::where('province_id', $provinceId)
                ->orderBy('number')
                ->get(['id', 'number', 'name']);
        }

        return Inertia::render('Admin/Candidates/Create', [
            'parties' => Party::active()->orderBy('name_th')->get(['id', 'name_th', 'color', 'logo']),
            'elections' => Election::orderByDesc('election_date')->get(['id', 'name', 'election_date']),
            'provinces' => Province::orderBy('name_th')->get(['id', 'name_th', 'code']),
            'constituencies' => $constituencies,
        ]);
    }

    /**
     * บันทึกผู้สมัครใหม่
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'party_id' => 'nullable|exists:parties,id',
            'election_id' => 'required|exists:elections,id',
            'constituency_id' => 'nullable|exists:constituencies,id',
            'candidate_number' => 'required|string|max:10',
            'title' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'photo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'biography' => 'nullable|string|max:5000',
            'birth_date' => 'nullable|date|before:today',
            'education' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:255',
            'type' => 'required|in:constituency,party_list',
            'party_list_order' => 'nullable|integer|min:1',
            'is_pm_candidate' => 'boolean',
            'social_media' => 'nullable|array',
            'social_media.facebook' => 'nullable|string|max:255',
            'social_media.twitter' => 'nullable|string|max:255',
            'social_media.instagram' => 'nullable|string|max:255',
            'social_media.line' => 'nullable|string|max:255',
            'policies' => 'nullable|array',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $validated['photo'] = $this->uploadImage($request->file('photo'), 'candidates');
        }

        $candidate = Candidate::create($validated);

        return redirect()
            ->route('admin.candidates.index')
            ->with('success', "เพิ่มผู้สมัคร {$candidate->full_name} เรียบร้อยแล้ว");
    }

    /**
     * แสดงรายละเอียดผู้สมัคร
     */
    public function show(Candidate $candidate)
    {
        $candidate->load(['party', 'election', 'constituency.province', 'constituencyResults']);

        return Inertia::render('Admin/Candidates/Show', [
            'candidate' => $candidate,
        ]);
    }

    /**
     * แสดงฟอร์มแก้ไขผู้สมัคร
     */
    public function edit(Candidate $candidate)
    {
        $candidate->load(['party', 'election', 'constituency.province']);

        $constituencies = [];

        if ($candidate->constituency) {
            $constituencies = Constituency::where('province_id', $candidate->constituency->province_id)
                ->orderBy('number')
                ->get(['id', 'number', 'name']);
        }

        return Inertia::render('Admin/Candidates/Edit', [
            'candidate' => $candidate,
            'parties' => Party::active()->orderBy('name_th')->get(['id', 'name_th', 'color', 'logo']),
            'elections' => Election::orderByDesc('election_date')->get(['id', 'name', 'election_date']),
            'provinces' => Province::orderBy('name_th')->get(['id', 'name_th', 'code']),
            'constituencies' => $constituencies,
        ]);
    }

    /**
     * อัปเดตข้อมูลผู้สมัคร
     */
    public function update(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'party_id' => 'nullable|exists:parties,id',
            'election_id' => 'required|exists:elections,id',
            'constituency_id' => 'nullable|exists:constituencies,id',
            'candidate_number' => 'required|string|max:10',
            'title' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'photo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'biography' => 'nullable|string|max:5000',
            'birth_date' => 'nullable|date|before:today',
            'education' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:255',
            'type' => 'required|in:constituency,party_list',
            'party_list_order' => 'nullable|integer|min:1',
            'is_pm_candidate' => 'boolean',
            'social_media' => 'nullable|array',
            'policies' => 'nullable|array',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($candidate->photo) {
                Storage::disk('public')->delete($candidate->photo);
            }
            $validated['photo'] = $this->uploadImage($request->file('photo'), 'candidates');
        }

        $candidate->update($validated);

        return redirect()
            ->route('admin.candidates.index')
            ->with('success', "อัปเดตผู้สมัคร {$candidate->full_name} เรียบร้อยแล้ว");
    }

    /**
     * ลบผู้สมัคร
     */
    public function destroy(Candidate $candidate)
    {
        $candidateName = $candidate->full_name;

        // Delete associated photo
        if ($candidate->photo) {
            Storage::disk('public')->delete($candidate->photo);
        }

        $candidate->delete();

        return redirect()
            ->route('admin.candidates.index')
            ->with('success', "ลบผู้สมัคร {$candidateName} เรียบร้อยแล้ว");
    }

    /**
     * ดึงรายการเขตเลือกตั้งตามจังหวัด (API)
     */
    public function getConstituencies(Province $province)
    {
        return response()->json([
            'constituencies' => Constituency::where('province_id', $province->id)
                ->orderBy('number')
                ->get(['id', 'number', 'name']),
        ]);
    }

    /**
     * Import ผู้สมัครจาก CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'election_id' => 'required|exists:elections,id',
        ]);

        // TODO: Implement CSV import logic

        return back()->with('success', 'นำเข้าข้อมูลผู้สมัครเรียบร้อยแล้ว');
    }

    /**
     * Export ผู้สมัครเป็น CSV
     */
    public function export(Request $request)
    {
        // TODO: Implement CSV export logic

        return response()->download('candidates.csv');
    }

    /**
     * อัปโหลดรูปภาพ
     */
    private function uploadImage($file, string $folder): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs("images/{$folder}", $filename, 'public');
    }
}
