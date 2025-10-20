<?php
/**
 * Contributor Profile Page
 * Shows detailed information about a specific contributor
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Get GitHub username from URL
$username = isset($_GET['user']) ? sanitizeInput($_GET['user']) : '';

if (empty($username)) {
    redirect(BASE_URL . 'index.php?error=invalid_user');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get user from database
    $user_stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
    $user_stmt->execute([$username]);
    $user = $user_stmt->fetch();
    
    if (!$user) {
        redirect(BASE_URL . 'index.php?error=user_not_found');
    }
    
    // Get GitHub user data
    $headers = ["User-Agent: BrickMMO-Timesheets"];
    
    function fetchGitHubData($url, $headers) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
    
    // Fetch GitHub user profile
    $github_user_url = "https://api.github.com/users/$username";
    $github_user_data = fetchGitHubData($github_user_url, $headers);
    
    // Get user's time tracking statistics
    $stats_stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_entries,
            SUM(duration) as total_hours,
            COUNT(DISTINCT application_id) as projects_worked_on,
            MIN(work_date) as first_entry,
            MAX(work_date) as last_entry,
            AVG(duration) as avg_hours_per_entry
        FROM hours 
        WHERE user_id = ?
    ");
    $stats_stmt->execute([$user['id']]);
    $user_stats = $stats_stmt->fetch();
    
    // Get repositories user has contributed to
    $repos_stmt = $db->prepare("
        SELECT 
            a.id,
            a.name,
            a.description,
            a.html_url,
            a.language,
            COUNT(h.id) as entries,
            SUM(h.duration) as hours_contributed,
            MAX(h.work_date) as last_contribution
        FROM applications a
        JOIN hours h ON a.id = h.application_id
        WHERE h.user_id = ? AND a.is_active = 1
        GROUP BY a.id, a.name, a.description, a.html_url, a.language
        ORDER BY hours_contributed DESC
    ");
    $repos_stmt->execute([$user['id']]);
    $contributed_repos = $repos_stmt->fetchAll();
    
    // Get recent time entries
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
    $recent_stmt->execute([$user['id']]);
    $recent_entries = $recent_stmt->fetchAll();
    
    // Get monthly contribution data for current year
    $monthly_stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(work_date, '%Y-%m') as month,
            SUM(duration) as total_hours,
            COUNT(*) as entries
        FROM hours 
        WHERE user_id = ? AND YEAR(work_date) = YEAR(CURDATE())
        GROUP BY DATE_FORMAT(work_date, '%Y-%m')
        ORDER BY month DESC
    ");
    $monthly_stmt->execute([$user['id']]);
    $monthly_data = $monthly_stmt->fetchAll();
    
    // Get top contributors (for the top contributors section)
    $top_contributors_stmt = $db->prepare("
        SELECT 
            u.name,
            u.login,
            u.avatar_url,
            SUM(h.duration) as total_hours
        FROM users u
        JOIN hours h ON u.id = h.user_id
        GROUP BY u.id, u.name, u.login, u.avatar_url
        ORDER BY total_hours DESC
        LIMIT 3
    ");
    $top_contributors_stmt->execute();
    $top_contributors = $top_contributors_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Contributor page error: " . $e->getMessage());
    redirect(BASE_URL . 'index.php?error=database_error');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= htmlspecialchars($user['name'] ?? $username) ?> - BrickMMO Contributor</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/w3-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
                    <?php if (isLoggedIn()): ?>
                        <a class="w3-bar-item w3-button w3-hover-theme" href="dashboard.php">User Dashboard</a>
                        <a class="w3-bar-item w3-button w3-hover-theme" href="auth/logout.php">Logout</a>
                    <?php else: ?>
                        <a class="w3-bar-item w3-button w3-hover-theme" href="auth/test-login.php">Login</a>
                    <?php endif; ?>
                    <button class="w3-bar-item w3-button w3-hover-theme" id="theme-toggle">
                        <span class="material-icons">brightness_6</span>
                    </button>
                </nav>
            </div>
        </header>
        
        <main class="w3-content w3-padding-large">
            <!-- Page Header -->
            <div class="w3-margin-bottom">
                <h1 class="w3-xxxlarge w3-text-theme w3-bold w3-margin-bottom">APPLICATIONS-V1</h1>
                <p class="w3-text-grey w3-large">Detailed view of repository information and statistics</p>
            </div>
            
            <!-- Summary Statistics -->
            <div class="brickmmo-card w3-card w3-white w3-padding w3-margin-bottom">
                <div class="w3-row">
                    <div class="w3-col m4 w3-center w3-padding">
                        <h4 class="w3-text-grey w3-small w3-margin-bottom">Total Hours</h4>
                        <p class="w3-xxlarge w3-text-theme w3-bold"><?= number_format($user_stats['total_hours'] ?? 0, 0) ?></p>
                    </div>
                    <div class="w3-col m4 w3-center w3-padding">
                        <h4 class="w3-text-grey w3-small w3-margin-bottom">Contributors</h4>
                        <p class="w3-xxlarge w3-text-theme w3-bold"><?= $user_stats['projects_worked_on'] ?? 0 ?></p>
                    </div>
                    <div class="w3-col m4 w3-center w3-padding">
                        <h4 class="w3-text-grey w3-small w3-margin-bottom">Time Entries</h4>
                        <p class="w3-xxlarge w3-text-theme w3-bold"><?= $user_stats['total_entries'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Top Contributors -->
            <div class="brickmmo-card w3-card w3-white w3-padding w3-margin-bottom">
                <h2 class="w3-large w3-bold w3-margin-bottom">Top Contributors</h2>
                <div class="w3-margin-bottom">
                    <?php foreach ($top_contributors as $contributor): ?>
                        <div class="w3-card w3-white w3-padding w3-margin-bottom w3-round">
                            <div class="w3-row w3-display-container">
                                <div class="w3-col m2">
                                    <img src="<?= htmlspecialchars($contributor['avatar_url']) ?>" 
                                         class="w3-circle" style="width:40px;height:40px;" 
                                         alt="<?= htmlspecialchars($contributor['name'] ?? $contributor['login']) ?>">
                                </div>
                                <div class="w3-col m8">
                                    <p class="w3-bold w3-margin-0"><?= htmlspecialchars($contributor['name'] ?? $contributor['login']) ?></p>
                                </div>
                                <div class="w3-col m2 w3-right-align">
                                    <span class="w3-bold w3-text-theme"><?= number_format($contributor['total_hours'], 0) ?> hours</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Monthly Chart -->
            <?php if (!empty($monthly_data)): ?>
                <div class="brickmmo-card w3-card w3-white w3-padding w3-margin-bottom">
                    <h2 class="w3-large w3-bold w3-margin-bottom">Monthly Activity (<?= date('Y') ?>)</h2>
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="w3-row">
                <!-- Contributed Repositories -->
                <div class="w3-col m6">
                    <div class="brickmmo-card w3-card w3-white w3-padding w3-margin-right">
                        <h2 class="w3-large w3-bold w3-margin-bottom">Contributed Repositories</h2>
                        <?php if (!empty($contributed_repos)): ?>
                            <div class="w3-margin-bottom">
                                <?php foreach ($contributed_repos as $repo): ?>
                                    <div class="w3-card w3-white w3-padding w3-margin-bottom w3-round">
                                        <div class="w3-row">
                                            <div class="w3-col m8">
                                                <h3 class="w3-text-theme w3-bold w3-margin-bottom">
                                                    <a href="repository.php?repo=<?= urlencode($repo['name']) ?>" class="w3-hover-underline">
                                                        <?= htmlspecialchars($repo['name']) ?>
                                                    </a>
                                                </h3>
                                                <?php if ($repo['description']): ?>
                                                    <p class="w3-small w3-text-grey w3-margin-bottom">
                                                        <?= htmlspecialchars($repo['description']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div class="w3-small w3-text-grey">
                                                    <span><?= $repo['entries'] ?> entries</span> | 
                                                    <span><?= number_format($repo['hours_contributed'], 1) ?>h contributed</span> | 
                                                    <span>Last: <?= date('M j, Y', strtotime($repo['last_contribution'])) ?></span>
                                                </div>
                                            </div>
                                            <div class="w3-col m4 w3-right-align">
                                                <span class="w3-bold w3-text-theme"><?= number_format($repo['hours_contributed'], 1) ?>h</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="w3-center w3-padding-large">
                                <div class="material-icons w3-xxlarge w3-text-grey w3-margin-bottom">folder_open</div>
                                <p class="w3-text-grey">No contributions recorded yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="w3-col m6">
                    <div class="brickmmo-card w3-card w3-white w3-padding">
                        <h2 class="w3-large w3-bold w3-margin-bottom">Recent Activity</h2>
                        <?php if (!empty($recent_entries)): ?>
                            <div class="w3-margin-bottom">
                                <?php foreach ($recent_entries as $entry): ?>
                                    <div class="w3-card w3-white w3-padding w3-margin-bottom w3-round">
                                        <div class="w3-row">
                                            <div class="w3-col m8">
                                                <div class="w3-row w3-margin-bottom">
                                                    <h4 class="w3-text-theme w3-bold w3-small w3-col m8">
                                                        <a href="repository.php?repo=<?= urlencode($entry['app_name']) ?>" class="w3-hover-underline">
                                                            <?= htmlspecialchars($entry['app_name']) ?>
                                                        </a>
                                                    </h4>
                                                    <span class="w3-tiny w3-text-grey w3-col m4 w3-right-align">
                                                        <?= date('M j', strtotime($entry['work_date'])) ?>
                                                    </span>
                                                </div>
                                                <?php if ($entry['description']): ?>
                                                    <p class="w3-tiny w3-text-grey">
                                                        <?= htmlspecialchars(substr($entry['description'], 0, 80)) ?><?= strlen($entry['description']) > 80 ? '...' : '' ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="w3-col m4 w3-right-align">
                                                <span class="w3-bold w3-text-theme w3-small"><?= number_format($entry['duration'], 1) ?>h</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="w3-center w3-margin-top">
                                <a href="personal-history.php" class="w3-small w3-text-theme w3-hover-underline">
                                    View All History
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="w3-center w3-padding-large">
                                <div class="material-icons w3-xxlarge w3-text-grey w3-margin-bottom">schedule</div>
                                <p class="w3-text-grey">No recent activity.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        document.getElementById('theme-toggle').addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
        });
        
        // Monthly Chart
        const monthlyData = <?= json_encode($monthly_data) ?>;
        
        if (monthlyData && monthlyData.length > 0) {
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            const months = monthlyData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('en-US', { month: 'short' });
            }).reverse();
            const hours = monthlyData.map(item => parseFloat(item.total_hours)).reverse();
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Hours Logged',
                        data: hours,
                        backgroundColor: '#DD5A3A',
                        borderColor: '#ea5302',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
