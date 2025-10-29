<?php
/**
 * Database Migration: Add visibility column to applications table
 * Run this file once to add the missing visibility column
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
    
    // Check if column already exists
    $check = $db->query("SHOW COLUMNS FROM applications LIKE 'visibility'");
    
    if ($check->rowCount() > 0) {
        $message = "✅ The 'visibility' column already exists in the applications table. No migration needed!";
        $success = true;
    } else {
        // Add the visibility column
        $sql = "ALTER TABLE applications 
                ADD COLUMN visibility VARCHAR(20) DEFAULT 'public' 
                AFTER languages";
        
        $db->exec($sql);
        
        $message = "✅ SUCCESS! The 'visibility' column has been added to the applications table.";
        $success = true;
    }
    
} catch (Exception $e) {
    $error = "❌ ERROR: " . $e->getMessage();
    error_log("Migration error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Database Migration - Add Visibility Column</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #FDF6F3;
            color: #2C3E50;
            padding: 2rem;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #DD5A3A;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }
        
        .success {
            background: #D4EDDA;
            color: #155724;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #C3E6CB;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        
        .error {
            background: #F8D7DA;
            color: #721C24;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #F5C6CB;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        
        .info {
            background: #E8F4FD;
            color: #004085;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #B8DAFF;
            margin-bottom: 1.5rem;
        }
        
        .info h3 {
            margin-bottom: 0.75rem;
            color: #004085;
        }
        
        .info ul {
            list-style-position: inside;
            line-height: 1.8;
        }
        
        .btn {
            display: inline-block;
            background: #DD5A3A;
            color: white;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            margin-right: 1rem;
        }
        
        .btn:hover {
            background: #C14D30;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .actions {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #E8D5CF;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Migration</h1>
        <p style="margin-bottom: 2rem; color: #666;">Add visibility column to applications table</p>
        
        <?php if ($success && $message): ?>
            <div class="success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="info">
                <h3>✅ Migration Complete!</h3>
                <p>Next steps:</p>
                <ul>
                    <li>Go to Import Repositories page</li>
                    <li>Click "Import Repositories" button</li>
                    <li>All repositories will be fetched from GitHub API</li>
                    <li>Check your Admin Dashboard to see the results</li>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="import-repos.php" class="btn">Import Repositories</a>
            <a href="dashboard-new.php" class="btn btn-secondary">Admin Dashboard</a>
        </div>
    </div>
</body>
</html>

