<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by admin status
        if ($request->filled('admin_only')) {
            $query->where('is_admin', true);
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search'),
                'admin_only' => $request->boolean('admin_only'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return Inertia::render('Admin/Users/Create');
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'is_admin' => ['boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'สร้างผู้ใช้งานเรียบร้อยแล้ว');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->only(['id', 'name', 'email', 'is_admin', 'created_at']),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'is_admin' => ['boolean'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_admin = $validated['is_admin'] ?? false;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', 'อัปเดตผู้ใช้งานเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'ไม่สามารถลบบัญชีตัวเองได้');
        }

        // Prevent deleting the last admin
        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->with('error', 'ไม่สามารถลบแอดมินคนสุดท้ายได้');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'ลบผู้ใช้งานเรียบร้อยแล้ว');
    }

    /**
     * Toggle admin status.
     */
    public function toggleAdmin(User $user)
    {
        // Prevent removing admin from yourself
        if ($user->id === auth()->id() && $user->is_admin) {
            return back()->with('error', 'ไม่สามารถถอดสิทธิ์แอดมินตัวเองได้');
        }

        // Prevent removing the last admin
        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->with('error', 'ไม่สามารถถอดสิทธิ์แอดมินคนสุดท้ายได้');
        }

        $user->is_admin = ! $user->is_admin;
        $user->save();

        $message = $user->is_admin
            ? 'เพิ่มสิทธิ์แอดมินเรียบร้อยแล้ว'
            : 'ถอดสิทธิ์แอดมินเรียบร้อยแล้ว';

        return back()->with('success', $message);
    }
}
