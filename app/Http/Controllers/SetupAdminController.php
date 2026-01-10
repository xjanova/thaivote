<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class SetupAdminController extends Controller
{
    /**
     * Show the setup admin form.
     * This is used when no admin exists in the system after installation.
     */
    public function show()
    {
        try {
            // Check if database is ready
            if (! Schema::hasTable('users')) {
                // Redirect to installation if database not ready
                return redirect('/install');
            }

            // If admin already exists, redirect to home
            $hasAdmin = DB::table('users')->where('is_admin', true)->exists();
            if ($hasAdmin) {
                return redirect('/');
            }
        } catch (Exception $e) {
            // Database error - redirect to install
            return redirect('/install');
        }

        return view('setup-admin');
    }

    /**
     * Create the super admin user.
     */
    public function store(Request $request)
    {
        // Double check no admin exists
        $hasAdmin = DB::table('users')->where('is_admin', true)->exists();
        if ($hasAdmin) {
            return redirect('/');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'กรุณากรอกชื่อ',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
            'password.confirmed' => 'ยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Create admin user using Model for proper login
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);
        } catch (Exception $e) {
            // Try alternative approach using DB facade
            try {
                DB::table('users')->insert([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'is_admin' => true,
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $user = User::where('email', $request->email)->first();
            } catch (Exception $e2) {
                return back()->withErrors([
                    'user' => 'ไม่สามารถสร้างผู้ใช้งานได้: '.$e2->getMessage(),
                ])->withInput();
            }
        }

        // Create installed file to mark installation as complete
        $installedPath = storage_path('app/installed');
        if (! File::exists($installedPath)) {
            File::put($installedPath, now()->toIso8601String());
        }

        // Login the admin user
        if ($user) {
            Auth::login($user);
        }

        return redirect('/admin')->with('success', 'สร้างบัญชีแอดมินเรียบร้อยแล้ว');
    }
}
