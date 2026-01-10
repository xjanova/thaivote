<?php
/**
 * Simple script to initialize logo and favicon settings
 * Run this file by accessing: http://your-domain.com/init-settings.php
 * Delete this file after running successfully
 */

// Load Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class);

try {
    // Check if settings table exists
    $pdo = DB::connection()->getPdo();
    $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");

    if ($stmt->rowCount() == 0) {
        die('❌ Error: Settings table does not exist. Please run migrations first.');
    }

    // Check if logo and favicon settings already exist
    $logoExists = DB::table('settings')->where('key', 'site_logo')->exists();
    $faviconExists = DB::table('settings')->where('key', 'site_favicon')->exists();

    if ($logoExists && $faviconExists) {
        echo '✅ Logo and favicon settings already exist!<br>';
        echo '<a href="/admin/settings">Go to Settings Page</a><br>';
        echo '<br><strong>You can safely delete this file (init-settings.php)</strong>';
        exit;
    }

    // Insert settings
    $now = date('Y-m-d H:i:s');

    if (!$logoExists) {
        DB::table('settings')->insert([
            'key' => 'site_logo',
            'value' => '',
            'type' => 'string',
            'group' => 'appearance',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        echo '✅ Added site_logo setting<br>';
    }

    if (!$faviconExists) {
        DB::table('settings')->insert([
            'key' => 'site_favicon',
            'value' => '',
            'type' => 'string',
            'group' => 'appearance',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        echo '✅ Added site_favicon setting<br>';
    }

    // Clear cache
    Cache::forget('settings.all');
    Cache::forget('setting.site_logo');
    Cache::forget('setting.site_favicon');

    echo '<br>🎉 <strong>Success!</strong> Logo and favicon settings have been initialized.<br>';
    echo '<br><a href="/admin/settings">Go to Settings Page</a><br>';
    echo '<br><strong style="color: red;">IMPORTANT: Delete this file (init-settings.php) for security!</strong>';

} catch (Exception $e) {
    echo '❌ <strong>Error:</strong> ' . $e->getMessage() . '<br>';
    echo '<br>Please check your database connection and try again.';
}
