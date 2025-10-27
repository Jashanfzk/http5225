<?php
/**
 * Admin Dashboard (server-side only)
 * - Shows applications with aggregated timesheet stats
 * - Allows toggling is_active via regular POST forms (no AJAX)
 * - Links to import-repos.php to refresh applications from GitHub
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// If a logged-in user requests to become admin (development helper)
if (isset($_POST['become_admin'])) {
    // Require login first
    if (!function_exists('isLoggedIn') || !isLoggedIn()) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'You must be signed in to become admin.'];
        header('Location: dashboard.php');
        exit;
    }

    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare('UPDATE users SET is_admin = 1 WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        // Mark session as admin
        $_SESSION['is_admin'] = true;
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Your account has been granted admin privileges.'];
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }

    header('Location: dashboard.php');
    exit;
}

// Toggle handling (server-side form)
if (isset($_POST['action']) && $_POST['action'] === 'toggle' && isset($_POST['repo_id'])) {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        $flash = ['type' => 'error', 'message' => 'Unauthorized'];
    } else {
        $repoId = (int) $_POST['repo_id'];
        $newStatus = isset($_POST['status']) && $_POST['status'] === '1' ? 1 : 0;
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare('UPDATE applications SET is_active = ?, updated_at = NOW() WHERE id = ?');
            $ok = $stmt->execute([$newStatus, $repoId]);
            $flash = ['type' => $ok ? 'success' : 'error', 'message' => $ok ? 'Repository status updated.' : 'Failed to update.'];
        } catch (Exception $e) {
            $flash = ['type' => 'error', 'message' => 'DB error: ' . $e->getMessage()];
        }
    }
    // Redirect to avoid form resubmission
    $_SESSION['flash'] = $flash;
    header('Location: dashboard.php');
    exit;
}

// Admin check
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Fetch admin dashboard data if admin
if ($isAdmin) {
    try {
        $db = (new Database())->getConnection();
        
        // Get system statistics
        $stats_stmt = $db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM users) as total_users,
                (SELECT COUNT(*) FROM applications WHERE is_active = 1) as active_apps,
                (SELECT COUNT(*) FROM applications) as total_apps,
                (SELECT COUNT(*) FROM hours) as total_entries,
                (SELECT SUM(duration) FROM hours) as total_hours
        ");
        $stats_stmt->execute();
        $system_stats = $stats_stmt->fetch();
        
        // Get recent users
        $users_stmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
        $users_stmt->execute();
        $recent_users = $users_stmt->fetchAll();
        
        // Get repository statistics
        $repo_stats_stmt = $db->prepare("
            SELECT 
                a.id,
                a.name,
                a.description,
                a.is_active,
                COUNT(DISTINCT h.user_id) as contributors,
                SUM(h.duration) as total_hours,
                COUNT(h.id) as time_entries
            FROM applications a
            LEFT JOIN hours h ON a.id = h.application_id
            GROUP BY a.id, a.name, a.description, a.is_active
            ORDER BY total_hours DESC
        ");
        $repo_stats_stmt->execute();
        $repository_stats = $repo_stats_stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Admin dashboard error: " . $e->getMessage());
        $system_stats = [];
        $recent_users = [];
        $repository_stats = [];
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Dashboard - BrickMMO</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="../css/w3-theme.css">
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

        .flash {
            margin: 1rem 0;
            padding: 1rem;
            border-radius: 4px;
        }
        .flash.success { background: #e6ffed; border: 1px solid #9fe2b6; }
        .flash.error { background: #ffe6e6; border: 1px solid #f3a6a6; }

        .w3-card {
            margin-bottom: 1rem;
            border-radius: 8px;
            overflow: hidden;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        }

        .stat-card h3 {
            font-size: 2rem;
            color: #DD5A3A;
            margin: 0;
        }

        .stat-card p {
            color: #666;
            margin: 0.5rem 0 0;
            font-size: 0.9rem;
        }

        .repo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .repo-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
        }

        .repo-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .repo-name {
            font-weight: 600;
            color: #2C3E50;
        }

        .repo-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: #666;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-active { background: #e6ffed; color: #2da169; }
        .status-inactive { background: #ffe6e6; color: #d73a49; }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .action-buttons button,
        .action-buttons a {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.85rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            background: #f0f0f0;
            color: #333;
        }

        .action-buttons button:hover,
        .action-buttons a:hover {
            background: #e0e0e0;
        }

        .top-bar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2C3E50;
        }

        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
    </style>
</head>
<body>

<!-- Top Navigation -->
<div class="w3-top w3-theme">
    <div class="w3-bar w3-padding">
        <a href="../index.php" class="w3-bar-item w3-button">BrickMMO</a>
        <a href="dashboard.php" class="w3-bar-item w3-button">Admin Dashboard</a>
        <a href="../dashboard.php" class="w3-bar-item w3-button">User Dashboard</a>
        <?php if (!$isAdmin): ?>
            <form method="post" style="display:inline">
                <button name="become_admin" type="submit" class="w3-bar-item w3-button">Make my account admin</button>
            </form>
        <?php endif; ?>
        <a href="../auth/logout.php" class="w3-bar-item w3-button w3-right">Logout</a>
    </div>
</div>

<div style="margin-top:65px">
    <div class="main-content">
        <?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
            <div class="flash <?php echo htmlspecialchars($f['type']); ?>">
                <?php echo htmlspecialchars($f['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (!$isAdmin): ?>
            <div class="w3-panel w3-pale-red w3-leftbar w3-border-red">
                <p>You need admin access to view this dashboard.</p>
                <form method="post" style="margin-top:1rem">
                    <button name="become_admin" type="submit" class="w3-button w3-theme-d3">Make my account admin</button>
                </form>
            </div>
        <?php else: ?>

            <!-- System Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $system_stats['total_users'] ?? 0; ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $system_stats['active_apps'] ?? 0; ?></h3>
                    <p>Active Apps</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $system_stats['total_entries'] ?? 0; ?></h3>
                    <p>Time Entries</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo number_format($system_stats['total_hours'] ?? 0, 1); ?></h3>
                    <p>Total Hours</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="w3-bar w3-margin-bottom">
                <a href="import-repos.php" class="w3-button w3-theme-d2">Import GitHub Repositories</a>
                <a href="../applications-v1/index.php" class="w3-button w3-theme-l4">View Public Site</a>
            </div>

            <!-- Repository Management -->
            <div class="w3-card">
                <header class="w3-container w3-theme-d1">
                    <h2>Repository Management</h2>
                </header>
                
                <div class="w3-container">
                    <div class="repo-grid">
                    <?php foreach ($repository_stats as $repo): ?>
                        <div class="repo-card">
                            <div class="repo-header">
                                <span class="repo-name"><?php echo htmlspecialchars($repo['name']); ?></span>
                                <span class="status-badge <?php echo $repo['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $repo['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            
                            <?php if ($repo['description']): ?>
                                <p class="w3-text-grey" style="font-size:0.9rem">
                                    <?php echo htmlspecialchars($repo['description']); ?>
                                </p>
                            <?php endif; ?>

                            <div class="repo-stats">
                                <span><?php echo (int)$repo['contributors']; ?> contributors</span>
                                <span><?php echo number_format($repo['total_hours'], 1); ?>h total</span>
                                <span><?php echo (int)$repo['time_entries']; ?> entries</span>
                            </div>

                            <div class="action-buttons">
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="repo_id" value="<?php echo (int)$repo['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $repo['is_active'] ? '0' : '1'; ?>">
                                    <button type="submit" class="w3-button w3-theme-l4">
                                        <?php echo $repo['is_active'] ? 'Disable' : 'Enable'; ?>
                                    </button>
                                </form>
                                <a href="../applications-v1/repo_details.php?repo=<?php echo urlencode($repo['name']); ?>" 
                                   class="w3-button w3-theme-l5">Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="w3-card">
                <header class="w3-container w3-theme-d1">
                    <h2>Recent Users</h2>
                </header>
                <div class="w3-container w3-padding">
                    <?php if (empty($recent_users)): ?>
                        <p class="w3-text-grey">No recent users found.</p>
                    <?php else: ?>
                        <div class="w3-row-padding">
                            <?php foreach ($recent_users as $user): ?>
                            <div class="w3-col l2 m3 s6 w3-margin-bottom">
                                <div class="w3-center">
                                    <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" 
                                         alt="" class="w3-circle" style="width:64px;height:64px">
                                    <h3 class="w3-text-dark-grey" style="font-size:1rem;margin:0.5rem 0">
                                        <?php echo htmlspecialchars($user['name'] ?? $user['login']); ?>
                                    </h3>
                                    <p class="w3-text-grey" style="font-size:0.8rem">
                                        Joined <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                    </p>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="w3-tag w3-theme-l4">Admin</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<script>
// Convert any remaining AJAX toggles to regular form submits for consistency
document.querySelectorAll('.toggle-repo').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const form = this.closest('form');
        if (form) form.submit();
    });
});
</script>

</body>
</html>