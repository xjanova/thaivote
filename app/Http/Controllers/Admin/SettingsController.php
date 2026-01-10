<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
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

        // Clear cache
        Setting::clearCache();

        return back()->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * API endpoint to get settings.
     */
    public function getSettings()
    {
        return response()->json([
            'success' => true,
            'data' => Setting::getAllSettings(),
        ]);
    }
}
