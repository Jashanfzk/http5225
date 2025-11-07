<?php
/**
 * Contributor Profile
 * Log hours and view contribution history
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Require login
requireLogin();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get user information
    $user_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user = $user_stmt->fetch();
    
    // Fetch active applications from database
    $apps_stmt = $db->prepare("
        SELECT id, name, description, language 
        FROM applications 
        WHERE is_active = 1 
        ORDER BY name ASC
    ");
    $apps_stmt->execute();
    $applications = $apps_stmt->fetchAll();
    
    // Process form submission
    $message = '';
    $error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_hours'])) {
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            // Validate and sanitize input
            $application_id = intval($_POST['application_id'] ?? 0);
            $work_date = sanitizeInput($_POST['work_date'] ?? '');
            $duration = floatval($_POST['duration'] ?? 0);
            $description = sanitizeInput($_POST['description'] ?? '');
            
            // Validate required fields
            if ($application_id <= 0) {
                $error = 'Please select a repository.';
            } elseif (empty($work_date)) {
                $error = 'Please select a work date.';
            } elseif ($duration < MIN_HOURS_PER_ENTRY || $duration > MAX_HOURS_PER_DAY) {
                $error = 'Duration must be between ' . MIN_HOURS_PER_ENTRY . ' and ' . MAX_HOURS_PER_DAY . ' hours.';
            } elseif (strtotime($work_date) > time()) {
                $error = 'Work date cannot be in the future.';
            } else {
                // Check if application exists and is active
                $app_stmt = $db->prepare("SELECT id FROM applications WHERE id = ? AND is_active = 1");
                $app_stmt->execute([$application_id]);
                if (!$app_stmt->fetch()) {
                    $error = 'Invalid repository selected.';
                } else {
                    // Insert time entry
                    $insert_stmt = $db->prepare("
                        INSERT INTO hours (user_id, application_id, work_date, duration, description) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    if ($insert_stmt->execute([$_SESSION['user_id'], $application_id, $work_date, $duration, $description])) {
                        $message = 'Hours logged successfully!';
                    } else {
                        $error = 'Failed to log hours. Please try again.';
                    }
                }
            }
        }
    }
    
    // Get user's recent time entries
    $recent_stmt = $db->prepare("
        SELECT 
            h.*,
            a.name as app_name,
            a.html_url as app_url
        FROM hours h
        JOIN applications a ON h.application_id = a.id
        WHERE h.user_id = ?
        ORDER BY h.work_date DESC, h.created_at DESC
        LIMIT 10
    ");
    $recent_stmt->execute([$_SESSION['user_id']]);
    $recent_entries = $recent_stmt->fetchAll();
    
    // Get user's total statistics
    $stats_stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_entries,
            SUM(duration) as total_hours,
            COUNT(DISTINCT application_id) as projects_worked_on,
            MIN(work_date) as first_entry,
            MAX(work_date) as last_entry
        FROM hours 
        WHERE user_id = ?
    ");
    $stats_stmt->execute([$_SESSION['user_id']]);
    $user_stats = $stats_stmt->fetch();
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = 'An error occurred. Please try again later.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Contributor Profile - BrickMMO Timesheets</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/w3-theme.css">
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
            color: #333;
        }
        
        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #E8D5CF;
        }
        
        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2C3E50;
            margin: 0;
        }
        
        .dashboard-subtitle {
            font-size: 0.95rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 2px solid #DD5A3A;
        }
        
        .user-details {
            text-align: left;
        }
        
        .user-name {
            font-weight: 600;
            color: #2C3E50;
            font-size: 0.95rem;
            margin-bottom: 0.1rem;
        }
        
        .user-logout {
            color: #DD5A3A;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .user-logout:hover {
            text-decoration: underline;
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: minmax(300px, 1fr) minmax(600px, 2fr);
            gap: 1.5rem;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.65rem 0.9rem;
            border: 1px solid #DDD;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.2s;
            background-color: white;
        }
        
        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
            padding-right: 2.5rem;
        }
        
        select.form-control option {
            padding: 0.5rem;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #DD5A3A;
            box-shadow: 0 0 0 3px rgba(221, 90, 58, 0.1);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn-submit {
            width: 100%;
            background: #DD5A3A;
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 0.5rem;
        }
        
        .btn-submit:hover {
            background: #C44A2A;
        }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .alert-success {
            background: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }
        
        .alert-error {
            background: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }
        
        .history-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .history-table thead {
            background: #FDF6F3;
        }
        
        .history-table th {
            padding: 0.9rem 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #E8D5CF;
        }
        
        .history-table td {
            padding: 1rem;
            border-bottom: 1px solid #F0F0F0;
            font-size: 0.9rem;
        }
        
        .history-table tbody tr:hover {
            background: #FEFBFA;
        }
        
        .repo-name {
            font-weight: 600;
            color: #DD5A3A;
        }
        
        .date-text {
            color: #666;
        }
        
        .hours-text {
            font-weight: 600;
            color: #2C3E50;
        }
        
        .description-text {
            color: #666;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .btn-edit {
            color: #DD5A3A;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .btn-edit:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #999;
            background: #FAFAFA;
            border-radius: 8px;
        }
        
        @media (max-width: 1200px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .dashboard-container {
                padding: 1rem;
                max-width: 95%;
            }
        }
        
        @media (max-width: 640px) {
            .dashboard-container {
                padding: 0.75rem;
                max-width: 100%;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .dashboard-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <header style="background: white; border-bottom: 1px solid #E8D5CF; padding: 1.5rem 0; margin-bottom: 2rem;">
        <div style="max-width: 1600px; margin: 0 auto; padding: 0 1rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <a href="index.php">
                    <img src="./assets/BrickMMO_Logo_Coloured.png" alt="BrickMMO" style="height: 48px;">
                </a>
            </div>
            <nav style="display: flex; gap: 2rem; align-items: center;">
                <a href="<?= BASE_URL ?>" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">Home</a>
                <a href="<?= BASE_URL ?>dashboard.php" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">Dashboard</a>
                <a href="<?= BASE_URL ?>personal-history.php" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">My History</a>
                <a href="<?= BASE_URL ?>auth/logout.php" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">Logout</a>
            </nav>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Contributor Profile</h1>
                <p class="dashboard-subtitle">Log your hours and view your contribution history.</p>
            </div>
            <div class="user-profile">
                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="User avatar" class="user-avatar"/>
                <div class="user-details">
                    <div class="user-name"><?= htmlspecialchars($user['name'] ?? $user['login']) ?></div>
                    <a href="auth/logout.php" class="user-logout">Logout</a>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <!-- Main Content -->
        <div class="dashboard-content">
            <!-- Left: Log New Hours -->
            <div class="card">
                <h2 class="card-title">Log New Hours</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="application_id">Repository</label>
                        <select class="form-control" id="application_id" name="application_id" required>
                            <option value="">Select a repository...</option>
                            <?php foreach ($applications as $app): ?>
                                <option value="<?= (int)$app['id'] ?>"
                                        <?= (isset($_POST['application_id']) && $_POST['application_id'] == $app['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($app['name']) ?>
                                    <?php if (!empty($app['language']) && $app['language'] !== 'N/A'): ?>
                                        (<?= htmlspecialchars($app['language']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="work_date">Date</label>
                        <input class="form-control" id="work_date" name="work_date" type="date" 
                               value="<?= isset($_POST['work_date']) ? htmlspecialchars($_POST['work_date']) : date('Y-m-d') ?>"
                               max="<?= date('Y-m-d') ?>" required/>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="duration">Hours</label>
                        <input class="form-control" id="duration" name="duration" min="<?= MIN_HOURS_PER_ENTRY ?>" max="<?= MAX_HOURS_PER_DAY ?>" 
                               step="0.25" type="number" 
                               value="<?= isset($_POST['duration']) ? htmlspecialchars($_POST['duration']) : '' ?>"
                               placeholder="0.00" required/>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  placeholder="Describe your work..."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                    </div>
                    
                    <button type="submit" name="log_hours" class="btn-submit">
                        Log Hours
                    </button>
                </form>
            </div>

            <!-- Right: Personal History -->
            <div class="card">
                <h2 class="card-title">Personal History</h2>
                
                <?php if (!empty($recent_entries)): ?>
                    <div class="w3-responsive">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Repository</th>
                                    <th>Date</th>
                                    <th>Hours</th>
                                    <th>Description</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_entries as $entry): ?>
                                    <tr>
                                        <td class="repo-name"><?= htmlspecialchars($entry['app_name']) ?></td>
                                        <td class="date-text"><?= date('Y-m-d', strtotime($entry['work_date'])) ?></td>
                                        <td class="hours-text"><?= number_format($entry['duration'], 1) ?></td>
                                        <td class="description-text">
                                            <?php if ($entry['description']): ?>
                                                <?= htmlspecialchars($entry['description']) ?>
                                            <?php else: ?>
                                                <span style="color: #CCC;">No description</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="#" class="btn-edit">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No entries yet. Start logging your hours!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
