<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminElectionController extends Controller
{
    /**
     * Display a listing of elections.
     */
    public function index(Request $request): Response
    {
        $query = Election::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $elections = $query
            ->withCount(['candidates', 'provinceResults'])
            ->orderByDesc('election_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Elections/Index', [
            'elections' => $elections,
            'filters' => $request->only(['search', 'status', 'type']),
            'statuses' => ['upcoming', 'ongoing', 'counting', 'completed', 'cancelled'],
            'types' => ['general', 'senate', 'local', 'referendum', 'by-election'],
        ]);
    }

    /**
     * Show the form for creating a new election.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Elections/Create', [
            'statuses' => ['upcoming', 'ongoing', 'counting', 'completed', 'cancelled'],
            'types' => ['general', 'senate', 'local', 'referendum', 'by-election'],
        ]);
    }

    /**
     * Store a newly created election.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'type' => 'required|in:general,senate,local,referendum,by-election',
            'description' => 'nullable|string|max:1000',
            'election_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'status' => 'required|in:upcoming,ongoing,counting,completed,cancelled',
            'total_eligible_voters' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;

        $election = Election::create($validated);

        return redirect()
            ->route('admin.elections.index')
            ->with('success', 'สร้างการเลือกตั้งสำเร็จ');
    }

    /**
     * Display the specified election.
     */
    public function show(Election $election): Response
    {
        $election->load([
            'candidates' => fn ($q) => $q->with('party')->limit(10),
            'stats',
        ]);

        $election->loadCount([
            'candidates',
            'provinceResults',
            'constituencyResults',
        ]);

        return Inertia::render('Admin/Elections/Show', [
            'election' => $election,
        ]);
    }

    /**
     * Show the form for editing the specified election.
     */
    public function edit(Election $election): Response
    {
        return Inertia::render('Admin/Elections/Edit', [
            'election' => $election,
            'statuses' => ['upcoming', 'ongoing', 'counting', 'completed', 'cancelled'],
            'types' => ['general', 'senate', 'local', 'referendum', 'by-election'],
        ]);
    }

    /**
     * Update the specified election.
     */
    public function update(Request $request, Election $election)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'type' => 'required|in:general,senate,local,referendum,by-election',
            'description' => 'nullable|string|max:1000',
            'election_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'status' => 'required|in:upcoming,ongoing,counting,completed,cancelled',
            'total_eligible_voters' => 'nullable|integer|min:0',
            'total_votes_cast' => 'nullable|integer|min:0',
            'voter_turnout' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $election->update($validated);

        return redirect()
            ->route('admin.elections.index')
            ->with('success', 'อัปเดตการเลือกตั้งสำเร็จ');
    }

    /**
     * Remove the specified election.
     */
    public function destroy(Election $election)
    {
        // Check if election has results
        if ($election->provinceResults()->exists() || $election->constituencyResults()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถลบการเลือกตั้งที่มีผลลัพธ์แล้วได้');
        }

        $election->delete();

        return redirect()
            ->route('admin.elections.index')
            ->with('success', 'ลบการเลือกตั้งสำเร็จ');
    }

    /**
     * Toggle active status of election.
     */
    public function toggleActive(Election $election)
    {
        // If activating this election, deactivate others
        if (! $election->is_active) {
            Election::where('id', '!=', $election->id)->update(['is_active' => false]);
        }

        $election->update(['is_active' => ! $election->is_active]);

        return redirect()
            ->back()
            ->with('success', $election->is_active ? 'เปิดใช้งานการเลือกตั้งแล้ว' : 'ปิดใช้งานการเลือกตั้งแล้ว');
    }

    /**
     * Update election status.
     */
    public function updateStatus(Request $request, Election $election)
    {
        $validated = $request->validate([
            'status' => 'required|in:upcoming,ongoing,counting,completed,cancelled',
        ]);

        $election->update($validated);

        return redirect()
            ->back()
            ->with('success', 'อัปเดตสถานะการเลือกตั้งสำเร็จ');
    }

    /**
     * Duplicate an election.
     */
    public function duplicate(Election $election)
    {
        $newElection = $election->replicate();
        $newElection->name = $newElection->name . ' (สำเนา)';
        $newElection->status = 'upcoming';
        $newElection->is_active = false;
        $newElection->total_votes_cast = 0;
        $newElection->voter_turnout = 0;
        $newElection->save();

        return redirect()
            ->route('admin.elections.edit', $newElection)
            ->with('success', 'คัดลอกการเลือกตั้งสำเร็จ');
    }
}
