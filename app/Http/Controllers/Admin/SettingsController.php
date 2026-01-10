<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        $settings = Setting::getAllSettings();

        return Inertia::render('Admin/Settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_description' => 'nullable|string|max:500',
            'site_logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'site_favicon' => 'nullable|image|mimes:png,jpg,jpeg,svg,ico|max:1024',
            'maintenance_mode' => 'boolean',
            'auto_refresh_interval' => 'required|integer|min:10|max:600',
            'news_fetch_enabled' => 'boolean',
            'news_fetch_interval' => 'required|integer|min:60|max:3600',
            'results_scrape_enabled' => 'boolean',
            'results_scrape_interval' => 'required|integer|min:30|max:600',
        ]);

        // Update each setting
        Setting::set('site_name', $validated['site_name'], 'string', 'general');
        Setting::set('site_description', $validated['site_description'] ?? '', 'string', 'general');
        Setting::set('maintenance_mode', $validated['maintenance_mode'] ?? false, 'boolean', 'general');
        Setting::set('auto_refresh_interval', $validated['auto_refresh_interval'], 'integer', 'general');
        Setting::set('news_fetch_enabled', $validated['news_fetch_enabled'] ?? true, 'boolean', 'news');
        Setting::set('news_fetch_interval', $validated['news_fetch_interval'], 'integer', 'news');
        Setting::set('results_scrape_enabled', $validated['results_scrape_enabled'] ?? true, 'boolean', 'results');
        Setting::set('results_scrape_interval', $validated['results_scrape_interval'], 'integer', 'results');

        // Handle logo upload
        if ($request->hasFile('site_logo')) {
            $oldLogo = Setting::get('site_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            $logoPath = $this->uploadImage($request->file('site_logo'), 'settings');
            Setting::set('site_logo', $logoPath, 'string', 'appearance');
        }

        // Handle favicon upload
        if ($request->hasFile('site_favicon')) {
            $oldFavicon = Setting::get('site_favicon');
            if ($oldFavicon) {
                Storage::disk('public')->delete($oldFavicon);
            }
            $faviconPath = $this->uploadImage($request->file('site_favicon'), 'settings');
            Setting::set('site_favicon', $faviconPath, 'string', 'appearance');
        }

        // Clear cache
        Setting::clearCache();

        return back()->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * API endpoint to get settings.
     */
    public function getSettings()
    {
        try {
            $settings = Setting::getAllSettings();

            return response()->json([
                'success' => true,
                'data' => $settings,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get settings: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load settings',
                'data' => [
                    'site_name' => 'ThaiVote',
                    'site_description' => '',
                    'site_logo' => '',
                    'site_favicon' => '',
                ],
            ]);
        }
    }

    /**
     * Upload image to storage.
     */
    private function uploadImage($file, string $folder): string
    {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();

        return $file->storeAs("images/{$folder}", $filename, 'public');
    }
}
