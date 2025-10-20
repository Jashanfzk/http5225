<?php
/**
 * User Dashboard
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
    
    // Get active applications
    $apps_stmt = $db->prepare("SELECT * FROM applications WHERE is_active = 1 ORDER BY name ASC");
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
    <title>User Dashboard - BrickMMO Timesheets</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/w3-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
    </style>
</head>
<body class="w3-light-grey">
    <div id="app">
        <header class="brickmmo-nav w3-bar w3-white w3-padding">
            <div class="w3-content w3-display-container">
                <div class="w3-bar-item w3-left">
                    <span class="w3-xxlarge w3-text-theme w3-bold">BrickMMO</span>
                </div>
                <nav class="w3-bar w3-right">
                    <a class="w3-bar-item w3-button w3-hover-theme" href="index.php">Home</a>
                    <a class="w3-bar-item w3-button w3-hover-theme" href="personal-history.php">View History</a>
                    <a class="w3-bar-item w3-button w3-hover-theme" href="auth/logout.php">Logout</a>
                    <button class="w3-bar-item w3-button w3-hover-theme" id="theme-toggle">
                        <span class="material-icons">brightness_6</span>
                    </button>
                </nav>
            </div>
        </header>
        
        <main class="w3-content w3-padding-large">
            <section id="dashboard">
                <div class="brickmmo-card w3-card w3-white w3-padding-large">
                    <div class="w3-row w3-margin-bottom">
                        <div class="w3-col m6">
                            <h2 class="w3-xxxlarge w3-text-theme w3-bold">User Dashboard</h2>
                            <p class="w3-text-grey">Log your hours and view your contribution history.</p>
                        </div>
                        <div class="w3-col m6 w3-right-align">
                            <div class="w3-display-container">
                                <img alt="User avatar" class="w3-circle w3-margin-right" style="width:48px;height:48px;" src="<?= htmlspecialchars($user['avatar_url']) ?>"/>
                                <div class="w3-display-right">
                                    <p class="w3-bold"><?= htmlspecialchars($user['name'] ?? $user['login']) ?></p>
                                    <a class="w3-small w3-text-theme w3-hover-underline" href="auth/logout.php">Logout</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Messages -->
                    <?php if ($message): ?>
                        <div class="w3-panel w3-green w3-padding w3-round w3-margin-bottom">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="w3-panel w3-red w3-padding w3-round w3-margin-bottom">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- User Statistics -->
                    <div class="brickmmo-stats-grid w3-margin-bottom">
                        <div class="brickmmo-stats-item">
                            <h4>Total Hours</h4>
                            <p><?= number_format($user_stats['total_hours'] ?? 0, 1) ?></p>
                        </div>
                        <div class="brickmmo-stats-item">
                            <h4>Total Entries</h4>
                            <p><?= $user_stats['total_entries'] ?? 0 ?></p>
                        </div>
                        <div class="brickmmo-stats-item">
                            <h4>Projects</h4>
                            <p><?= $user_stats['projects_worked_on'] ?? 0 ?></p>
                        </div>
                        <div class="brickmmo-stats-item">
                            <h4>Since</h4>
                            <p class="w3-small">
                                <?= $user_stats['first_entry'] ? date('M j, Y', strtotime($user_stats['first_entry'])) : 'N/A' ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="w3-row">
                        <!-- Log New Hours Form -->
                        <div class="w3-col m4">
                            <h3 class="w3-xlarge w3-bold w3-margin-bottom">Log New Hours</h3>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                
                                <div class="w3-margin-bottom">
                                    <label class="w3-text-grey" for="application_id">Repository</label>
                                    <select class="w3-select w3-border w3-input" id="application_id" name="application_id" required>
                                        <option value="">Select a repository...</option>
                                        <?php foreach ($applications as $app): ?>
                                            <option value="<?= $app['id'] ?>" <?= (isset($_POST['application_id']) && $_POST['application_id'] == $app['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($app['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="w3-margin-bottom">
                                    <label class="w3-text-grey" for="work_date">Date</label>
                                    <input class="w3-input w3-border" id="work_date" name="work_date" type="date" 
                                           value="<?= isset($_POST['work_date']) ? htmlspecialchars($_POST['work_date']) : date('Y-m-d') ?>"
                                           max="<?= date('Y-m-d') ?>" required/>
                                </div>
                                
                                <div class="w3-margin-bottom">
                                    <label class="w3-text-grey" for="duration">Hours</label>
                                    <input class="w3-input w3-border" id="duration" name="duration" min="<?= MIN_HOURS_PER_ENTRY ?>" max="<?= MAX_HOURS_PER_DAY ?>" 
                                           step="0.25" type="number" 
                                           value="<?= isset($_POST['duration']) ? htmlspecialchars($_POST['duration']) : '' ?>"
                                           placeholder="e.g., 2.5" required/>
                                </div>
                                
                                <div class="w3-margin-bottom">
                                    <label class="w3-text-grey" for="description">Description</label>
                                    <textarea class="w3-input w3-border" id="description" name="description" rows="3" 
                                              placeholder="What did you work on?"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                                </div>
                                
                                <button class="w3-button w3-theme w3-block" type="submit" name="log_hours">
                                    Log Hours
                                </button>
                            </form>
                        </div>
                        
                        <!-- Recent Entries -->
                        <div class="w3-col m8">
                            <h3 class="w3-xlarge w3-bold w3-margin-bottom">Recent Entries</h3>
                            <?php if (!empty($recent_entries)): ?>
                                <div class="w3-margin-bottom">
                                    <?php foreach ($recent_entries as $entry): ?>
                                        <div class="w3-card w3-white w3-padding w3-margin-bottom">
                                            <div class="w3-row">
                                                <div class="w3-col m10">
                                                    <div class="w3-row w3-margin-bottom">
                                                        <h4 class="w3-text-theme w3-bold w3-col m6"><?= htmlspecialchars($entry['app_name']) ?></h4>
                                                        <span class="w3-small w3-text-grey w3-col m6 w3-right-align">
                                                            <?= date('M j, Y', strtotime($entry['work_date'])) ?>
                                                        </span>
                                                    </div>
                                                    <?php if ($entry['description']): ?>
                                                        <p class="w3-small w3-text-grey w3-margin-bottom">
                                                            <?= htmlspecialchars($entry['description']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <p class="w3-tiny w3-text-grey">
                                                        Logged <?= date('M j, Y g:i A', strtotime($entry['created_at'])) ?>
                                                    </p>
                                                </div>
                                                <div class="w3-col m2 w3-right-align">
                                                    <span class="w3-bold w3-text-theme"><?= number_format($entry['duration'], 1) ?>h</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="w3-center w3-margin-top">
                                    <a href="personal-history.php" class="w3-button w3-theme">
                                        View All History
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="w3-card w3-white w3-padding-large w3-center w3-border w3-border-dashed">
                                    <h4 class="w3-large w3-bold w3-margin-bottom">No entries yet</h4>
                                    <p class="w3-text-grey w3-margin-bottom">Start logging your hours to see them here.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
    
    <script>
        document.getElementById('theme-toggle').addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
        });
    </script>
</body>
</html>
