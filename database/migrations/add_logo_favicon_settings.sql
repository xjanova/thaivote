-- Add logo and favicon settings to settings table
-- Run this SQL in your database if you can't run artisan migrate

INSERT INTO settings (id, `key`, `value`, `type`, `group`, created_at, updated_at)
VALUES
    (NULL, 'site_logo', '', 'string', 'appearance', NOW(), NOW()),
    (NULL, 'site_favicon', '', 'string', 'appearance', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    updated_at = NOW();
