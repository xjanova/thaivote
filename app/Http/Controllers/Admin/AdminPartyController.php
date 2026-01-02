<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminPartyController extends Controller
{
    /**
     * แสดงรายการพรรคการเมืองทั้งหมด
     */
    public function index(Request $request)
    {
        $query = Party::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_th', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('abbreviation', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $parties = $query->orderBy('name_th')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Parties/Index', [
            'parties' => $parties,
            'filters' => $request->only(['search', 'active']),
        ]);
    }

    /**
     * แสดงฟอร์มสร้างพรรคใหม่
     */
    public function create()
    {
        return Inertia::render('Admin/Parties/Create');
    }

    /**
     * บันทึกพรรคใหม่
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_th' => 'required|string|max:255|unique:parties,name_th',
            'name_en' => 'required|string|max:255',
            'abbreviation' => 'required|string|max:20',
            'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'description' => 'nullable|string|max:2000',
            'slogan' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'facebook_page' => 'nullable|string|max:255',
            'twitter_handle' => 'nullable|string|max:50',
            'leader_name' => 'nullable|string|max:255',
            'leader_photo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'founded_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'headquarters' => 'nullable|string|max:255',
            'party_number' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->uploadImage($request->file('logo'), 'parties');
        }

        // Handle leader photo upload
        if ($request->hasFile('leader_photo')) {
            $validated['leader_photo'] = $this->uploadImage($request->file('leader_photo'), 'leaders');
        }

        $party = Party::create($validated);

        return redirect()
            ->route('admin.parties.index')
            ->with('success', "สร้างพรรค {$party->name_th} เรียบร้อยแล้ว");
    }

    /**
     * แสดงรายละเอียดพรรค
     */
    public function show(Party $party)
    {
        $party->load(['candidates', 'nationalResults', 'provinceResults']);

        return Inertia::render('Admin/Parties/Show', [
            'party' => $party,
        ]);
    }

    /**
     * แสดงฟอร์มแก้ไขพรรค
     */
    public function edit(Party $party)
    {
        return Inertia::render('Admin/Parties/Edit', [
            'party' => $party,
        ]);
    }

    /**
     * อัปเดตข้อมูลพรรค
     */
    public function update(Request $request, Party $party)
    {
        $validated = $request->validate([
            'name_th' => 'required|string|max:255|unique:parties,name_th,' . $party->id,
            'name_en' => 'required|string|max:255',
            'abbreviation' => 'required|string|max:20',
            'color' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'description' => 'nullable|string|max:2000',
            'slogan' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'facebook_page' => 'nullable|string|max:255',
            'twitter_handle' => 'nullable|string|max:50',
            'leader_name' => 'nullable|string|max:255',
            'leader_photo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'founded_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'headquarters' => 'nullable|string|max:255',
            'party_number' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($party->logo) {
                Storage::disk('public')->delete($party->logo);
            }
            $validated['logo'] = $this->uploadImage($request->file('logo'), 'parties');
        }

        // Handle leader photo upload
        if ($request->hasFile('leader_photo')) {
            if ($party->leader_photo) {
                Storage::disk('public')->delete($party->leader_photo);
            }
            $validated['leader_photo'] = $this->uploadImage($request->file('leader_photo'), 'leaders');
        }

        $party->update($validated);

        return redirect()
            ->route('admin.parties.index')
            ->with('success', "อัปเดตพรรค {$party->name_th} เรียบร้อยแล้ว");
    }

    /**
     * ลบพรรค
     */
    public function destroy(Party $party)
    {
        $partyName = $party->name_th;

        // Delete associated files
        if ($party->logo) {
            Storage::disk('public')->delete($party->logo);
        }
        if ($party->leader_photo) {
            Storage::disk('public')->delete($party->leader_photo);
        }

        $party->delete();

        return redirect()
            ->route('admin.parties.index')
            ->with('success', "ลบพรรค {$partyName} เรียบร้อยแล้ว");
    }

    /**
     * สร้าง API Key สำหรับพรรค
     */
    public function generateApiKey(Party $party)
    {
        $party->generateApiKey();

        return back()->with('success', 'สร้าง API Key เรียบร้อยแล้ว');
    }

    /**
     * Toggle สถานะ active
     */
    public function toggleActive(Party $party)
    {
        $party->update(['is_active' => ! $party->is_active]);

        $status = $party->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return back()->with('success', "{$status}พรรค {$party->name_th} เรียบร้อยแล้ว");
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
