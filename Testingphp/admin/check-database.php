<?php
/**
 * Database Diagnostic Tool
 * Check if database and tables are set up correctly
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Require admin access
requireAdmin();

$checks = [];
$errors = [];

try {
    $database = new Database();
    $db = $database->getConnection();
    $checks[] = "✅ Database connection successful";
    
    // Check if applications table exists
    $table_check = $db->query("SHOW TABLES LIKE 'applications'");
    if ($table_check->rowCount() > 0) {
        $checks[] = "✅ Applications table exists";
        
        // Check columns
        $columns = $db->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_COLUMN);
        $checks[] = "✅ Table columns: " . implode(', ', $columns);
        
        // Check if visibility column exists
        if (in_array('visibility', $columns)) {
            $checks[] = "✅ Visibility column exists";
        } else {
            $errors[] = "❌ Visibility column is MISSING! Run migrate-visibility.php";
        }
        
        // Sync metadata columns
        if (in_array('etag', $columns)) {
            $checks[] = "✅ Sync column 'etag' exists";
        } else {
            $errors[] = "❌ Sync column 'etag' is MISSING! Run migrate-syncmeta.php";
        }
        if (in_array('last_synced_at', $columns)) {
            $checks[] = "✅ Sync column 'last_synced_at' exists";
        } else {
            $errors[] = "❌ Sync column 'last_synced_at' is MISSING! Run migrate-syncmeta.php";
        }

        // Count repositories
        $count = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
        $checks[] = "📊 Total repositories in database: $count";
        
        if ($count > 0) {
            // Show sample data
            $sample = $db->query("SELECT id, name, visibility, is_active FROM applications LIMIT 5")->fetchAll();
            $checks[] = "📋 Sample repositories:";
            foreach ($sample as $repo) {
                $checks[] = "  - ID: {$repo['id']}, Name: {$repo['name']}, Visibility: {$repo['visibility']}, Active: " . ($repo['is_active'] ? 'Yes' : 'No');
            }
        } else {
            $errors[] = "⚠️ No repositories found - Click 'Import from GitHub' to fetch them";
        }
        
    } else {
        $errors[] = "❌ Applications table does NOT exist! Run database schema.sql";
    }
    
} catch (Exception $e) {
    $errors[] = "❌ ERROR: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Database Diagnostic</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #FDF6F3;
            padding: 2rem;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            color: #DD5A3A;
            margin-bottom: 2rem;
        }
        .check-list {
            list-style: none;
            margin-bottom: 2rem;
        }
        .check-list li {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 6px;
            font-family: monospace;
        }
        .error {
            color: #dc3545;
            font-weight: 600;
        }
        .success {
            color: #28a745;
        }
        .btn {
            display: inline-block;
            background: #DD5A3A;
            color: white;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-right: 1rem;
            transition: all 0.2s;
        }
        .btn:hover {
            background: #C14D30;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Diagnostic</h1>
        
        <h3>System Checks:</h3>
        <ul class="check-list">
            <?php foreach ($checks as $check): ?>
                <li class="<?= strpos($check, '✅') !== false ? 'success' : '' ?>">
                    <?= htmlspecialchars($check) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <?php if (!empty($errors)): ?>
            <h3 style="color: #dc3545;">Issues Found:</h3>
            <ul class="check-list">
                <?php foreach ($errors as $error): ?>
                    <li class="error"><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #E8D5CF;">
            <h3>Quick Actions:</h3>
            <div style="margin-top: 1rem;">
                <a href="migrate-visibility.php" class="btn">1. Add Visibility Column</a>
                <a href="migrate-syncmeta.php" class="btn">2. Add Sync Metadata</a>
                <a href="dashboard-new.php" class="btn btn-secondary">3. Go to Dashboard</a>
            </div>
            <p style="margin-top: 1rem; color: #666;">
                After fixing any issues, go to Dashboard and click "Import from GitHub"
            </p>
        </div>
    </div>
</body>
</html>

