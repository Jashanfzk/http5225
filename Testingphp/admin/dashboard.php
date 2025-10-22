<?php
/**
 * Admin Dashboard
 * Repository management and system analytics
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Require admin access
requireAdmin();

try {
    $database = new Database();
    $db = $database->getConnection();
    
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
    
    // Get top contributors
    $contributors_stmt = $db->prepare("
        SELECT 
            u.name,
            u.login,
            u.avatar_url,
            COUNT(h.id) as total_entries,
            SUM(h.duration) as total_hours
        FROM users u
        JOIN hours h ON u.id = h.user_id
        GROUP BY u.id, u.name, u.login, u.avatar_url
        ORDER BY total_hours DESC
        LIMIT 10
    ");
    $contributors_stmt->execute();
    $top_contributors = $contributors_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $system_stats = [];
    $recent_users = [];
    $repository_stats = [];
    $top_contributors = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Dashboard - BrickMMO Timesheets</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="../css/w3-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-light dark:text-text-dark transition-colors duration-300">
    <div id="app">
        <header class="py-4 px-8 border-b border-gray-200 dark:border-gray-700">
            <div class="container mx-auto flex justify-between items-center">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-primary">BrickMMO</span>
                    <span class="text-sm text-subtext-light dark:text-subtext-dark ml-2">Admin Panel</span>
                </div>
                <nav class="flex items-center space-x-6">
                    <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="../index.php">Public Site</a>
                    <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="../dashboard.php">User Dashboard</a>
                    <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="import-repos.php">Import Repos</a>
                    <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="../github-token-setup.php">GitHub Token</a>
                    <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="../auth/logout.php">Logout</a>
                    <button class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors" id="theme-toggle">
                        <span class="material-icons">brightness_6</span>
                    </button>
                </nav>
            </div>
        </header>
        
        <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-primary mb-2">Admin Dashboard</h1>
                <p class="text-subtext-light dark:text-subtext-dark">Manage repositories and view system analytics.</p>
            </div>
            
            <!-- System Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md text-center">
                    <h3 class="text-2xl font-bold text-primary"><?= $system_stats['total_users'] ?? 0 ?></h3>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">Total Users</p>
                </div>
                <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md text-center">
                    <h3 class="text-2xl font-bold text-primary"><?= $system_stats['active_apps'] ?? 0 ?></h3>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">Active Apps</p>
                </div>
                <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md text-center">
                    <h3 class="text-2xl font-bold text-primary"><?= $system_stats['total_apps'] ?? 0 ?></h3>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">Total Apps</p>
                </div>
                <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md text-center">
                    <h3 class="text-2xl font-bold text-primary"><?= $system_stats['total_entries'] ?? 0 ?></h3>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">Time Entries</p>
                </div>
                <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md text-center">
                    <h3 class="text-2xl font-bold text-primary"><?= number_format($system_stats['total_hours'] ?? 0, 0) ?></h3>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">Total Hours</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Repository Management -->
                <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Repository Management</h2>
                        <a href="import-repos.php" class="bg-primary hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-colors text-sm">
                            Import Repos
                        </a>
                    </div>
                    
                    <div class="space-y-3">
                        <?php foreach ($repository_stats as $repo): ?>
                            <div class="bg-background-light dark:bg-background-dark p-4 rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2">
                                            <h3 class="font-medium text-primary"><?= htmlspecialchars($repo['name']) ?></h3>
                                            <span class="ml-2 px-2 py-1 text-xs rounded-full <?= $repo['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= $repo['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </div>
                                        <?php if ($repo['description']): ?>
                                            <p class="text-sm text-subtext-light dark:text-subtext-dark mb-2">
                                                <?= htmlspecialchars($repo['description']) ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="flex space-x-4 text-xs text-subtext-light dark:text-subtext-dark">
                                            <span><?= $repo['contributors'] ?> contributors</span>
                                            <span><?= number_format($repo['total_hours'], 1) ?>h total</span>
                                            <span><?= $repo['time_entries'] ?> entries</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <button class="text-xs bg-gray-500 hover:bg-gray-600 text-white py-1 px-2 rounded transition-colors toggle-repo" 
                                                data-repo-id="<?= $repo['id'] ?>" 
                                                data-current-status="<?= $repo['is_active'] ? '1' : '0' ?>">
                                            <?= $repo['is_active'] ? 'Disable' : 'Enable' ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Top Contributors -->
                <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-bold mb-4">Top Contributors</h2>
                    <div class="space-y-3">
                        <?php foreach ($top_contributors as $contributor): ?>
                            <div class="flex items-center justify-between bg-background-light dark:bg-background-dark p-3 rounded-lg">
                                <div class="flex items-center">
                                    <img alt="Contributor avatar" class="h-8 w-8 rounded-full mr-3" src="<?= htmlspecialchars($contributor['avatar_url']) ?>"/>
                                    <div>
                                        <p class="font-medium text-sm"><?= htmlspecialchars($contributor['name'] ?? $contributor['login']) ?></p>
                                        <p class="text-xs text-subtext-light dark:text-subtext-dark"><?= $contributor['total_entries'] ?> entries</p>
                                    </div>
                                </div>
                                <span class="font-bold text-primary text-sm"><?= number_format($contributor['total_hours'], 1) ?>h</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md mt-8">
                <h2 class="text-xl font-bold mb-4">Recent Users</h2>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <?php foreach ($recent_users as $user): ?>
                        <div class="bg-background-light dark:bg-background-dark p-4 rounded-lg text-center">
                            <img alt="User avatar" class="h-12 w-12 rounded-full mx-auto mb-2" src="<?= htmlspecialchars($user['avatar_url']) ?>"/>
                            <p class="font-medium text-sm"><?= htmlspecialchars($user['name'] ?? $user['login']) ?></p>
                            <p class="text-xs text-subtext-light dark:text-subtext-dark"><?= date('M j, Y', strtotime($user['created_at'])) ?></p>
                            <?php if ($user['is_admin']): ?>
                                <span class="inline-block mt-1 px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Admin</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        document.getElementById('theme-toggle').addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
        });
        
        // Repository toggle functionality
        document.querySelectorAll('.toggle-repo').forEach(button => {
            button.addEventListener('click', async function() {
                const repoId = this.dataset.repoId;
                const currentStatus = this.dataset.currentStatus;
                const newStatus = currentStatus === '1' ? '0' : '1';
                
                try {
                    const response = await fetch('toggle-repository.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `repo_id=${repoId}&status=${newStatus}&csrf_token=<?= generateCSRFToken() ?>`
                    });
                    
                    if (response.ok) {
                        location.reload();
                    } else {
                        alert('Failed to update repository status');
                    }
                } catch (error) {
                    alert('Error updating repository status');
                }
            });
        });
    </script>
</body>
</html>
