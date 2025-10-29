<?php
/**
 * Database Migration: Add sync metadata to applications table
 * - Adds columns: etag (VARCHAR 255), last_synced_at (DATETIME)
 * - Adds index: idx_app_is_active on (is_active)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Require admin access
requireAdmin();

$success = false;
$message = '';
$error = '';

try {
    $database = new Database();
    $db = $database->getConnection();

    $messages = [];

    // Columns check
    $columns = $db->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('etag', $columns, true)) {
        $db->exec("ALTER TABLE applications ADD COLUMN etag VARCHAR(255) NULL AFTER visibility");
        $messages[] = "✅ Added column 'etag'";
    } else {
        $messages[] = "✅ Column 'etag' already exists";
    }

    if (!in_array('last_synced_at', $columns, true)) {
        $db->exec("ALTER TABLE applications ADD COLUMN last_synced_at DATETIME NULL AFTER updated_at");
        $messages[] = "✅ Added column 'last_synced_at'";
    } else {
        $messages[] = "✅ Column 'last_synced_at' already exists";
    }

    // Index check
    $indexExists = false;
    $indexes = $db->query("SHOW INDEX FROM applications WHERE Key_name = 'idx_app_is_active'");
    if ($indexes && $indexes->rowCount() > 0) {
        $indexExists = true;
    }
    if (!$indexExists) {
        $db->exec("CREATE INDEX idx_app_is_active ON applications(is_active)");
        $messages[] = "✅ Created index 'idx_app_is_active'";
    } else {
        $messages[] = "✅ Index 'idx_app_is_active' already exists";
    }

    $success = true;
    $message = implode("\n", $messages);
} catch (Exception $e) {
    $error = "❌ ERROR: " . $e->getMessage();
    error_log("Migration error (syncmeta): " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Database Migration - Sync Metadata</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #FDF6F3; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; padding: 3rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #DD5A3A; margin-bottom: 1.5rem; font-size: 2rem; }
        .success { background: #D4EDDA; color: #155724; padding: 1.5rem; border-radius: 8px; border: 1px solid #C3E6CB; margin-bottom: 1.5rem; white-space: pre-line; }
        .error { background: #F8D7DA; color: #721C24; padding: 1.5rem; border-radius: 8px; border: 1px solid #F5C6CB; margin-bottom: 1.5rem; }
        .actions { margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #E8D5CF; }
        .btn { display: inline-block; background: #DD5A3A; color: white; padding: 0.875rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.2s; margin-right: 1rem; }
        .btn:hover { background: #C14D30; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
    </style>
    </head>
<body>
    <div class="container">
        <h1>🔧 Database Migration: Sync Metadata</h1>
        <?php if ($success && $message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <div class="actions">
            <a href="check-database.php" class="btn">Back to DB Check</a>
            <a href="import-repos.php" class="btn btn-secondary">Go to Import</a>
        </div>
    </div>
</body>
</html>


