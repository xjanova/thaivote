<?php
/**
 * Simple PHP script to add logo and favicon settings
 *
 * HOW TO USE:
 * 1. Update the database credentials below
 * 2. Access this file: http://your-domain.com/init-settings-simple.php
 * 3. Delete this file after running successfully
 */

// ========================================
// UPDATE THESE SETTINGS WITH YOUR DATABASE
// ========================================
$DB_HOST = 'localhost';
$DB_NAME = 'your_database_name';
$DB_USER = 'your_database_user';
$DB_PASS = 'your_database_password';
// ========================================

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Initialize Settings</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔧 ThaiVote - Initialize Logo & Favicon Settings</h1>

<?php
try {
    // Connect to database
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo '<p class="success">✅ Database connected successfully!</p>';

    // Check if settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
    if ($stmt->rowCount() == 0) {
        echo '<p class="error">❌ Error: <code>settings</code> table does not exist.</p>';
        echo '<p>Please run database migrations first.</p>';
        exit;
    }

    echo '<p class="success">✅ Settings table exists</p>';

    // Check if logo setting exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE `key` = 'site_logo'");
    $stmt->execute();
    $logoExists = $stmt->fetchColumn() > 0;

    // Check if favicon setting exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE `key` = 'site_favicon'");
    $stmt->execute();
    $faviconExists = $stmt->fetchColumn() > 0;

    if ($logoExists && $faviconExists) {
        echo '<p class="warning">⚠️ Logo and favicon settings already exist!</p>';
        echo '<p><a href="/admin/settings">→ Go to Settings Page</a></p>';
        echo '<hr>';
        echo '<p><strong>✅ Everything is ready!</strong></p>';
        echo '<p class="error">⚠️ You can now <strong>delete this file</strong> (init-settings-simple.php) for security.</p>';
        exit;
    }

    // Insert settings
    $now = date('Y-m-d H:i:s');

    if (!$logoExists) {
        $stmt = $pdo->prepare("
            INSERT INTO settings (`key`, `value`, `type`, `group`, created_at, updated_at)
            VALUES ('site_logo', '', 'string', 'appearance', :now1, :now2)
        ");
        $stmt->execute(['now1' => $now, 'now2' => $now]);
        echo '<p class="success">✅ Added <code>site_logo</code> setting</p>';
    } else {
        echo '<p class="success">✓ <code>site_logo</code> already exists</p>';
    }

    if (!$faviconExists) {
        $stmt = $pdo->prepare("
            INSERT INTO settings (`key`, `value`, `type`, `group`, created_at, updated_at)
            VALUES ('site_favicon', '', 'string', 'appearance', :now1, :now2)
        ");
        $stmt->execute(['now1' => $now, 'now2' => $now]);
        echo '<p class="success">✅ Added <code>site_favicon</code> setting</p>';
    } else {
        echo '<p class="success">✓ <code>site_favicon</code> already exists</p>';
    }

    echo '<hr>';
    echo '<h2 class="success">🎉 Success!</h2>';
    echo '<p>Logo and favicon settings have been initialized successfully.</p>';
    echo '<p><a href="/admin/settings"><strong>→ Go to Settings Page</strong></a></p>';
    echo '<hr>';
    echo '<p class="error"><strong>⚠️ IMPORTANT:</strong> Delete this file (init-settings-simple.php) for security!</p>';

} catch (PDOException $e) {
    echo '<p class="error">❌ <strong>Database Error:</strong></p>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '<hr>';
    echo '<h3>How to fix:</h3>';
    echo '<ol>';
    echo '<li>Open this file in a text editor</li>';
    echo '<li>Update the database credentials at the top of the file:';
    echo '<pre>$DB_HOST = \'localhost\';
$DB_NAME = \'your_database_name\';
$DB_USER = \'your_database_user\';
$DB_PASS = \'your_database_password\';</pre>';
    echo '</li>';
    echo '<li>Save and try again</li>';
    echo '</ol>';
}
?>
</body>
</html>
