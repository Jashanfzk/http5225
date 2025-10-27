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
    
    // Get GitHub user data and repositories
    $headers = ["User-Agent: BrickMMO-Timesheets"];
    
    function fetchGitHubData($url, $headers) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        return null;
    }
    
    // Fetch BrickMMO repositories
    $repos_url = "https://api.github.com/users/brickmmo/repos";
    $repositories = fetchGitHubData($repos_url, $headers);
    
    // Sort repositories by name
    if (is_array($repositories)) {
        usort($repositories, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
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
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
                <a href="https://brickmmo.com" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">BrickMMO Main Site</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= BASE_URL ?>dashboard.php" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">Dashboard</a>
                    <a href="<?= BASE_URL ?>personal-history.php" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">My History</a>
                    <a href="<?= BASE_URL ?>auth/logout.php" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">Logout</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>auth/login.php" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Repository Details Section -->
    <section id="repository" style="padding: 3rem 1rem;">
        <div class="w3-container" style="max-width: 1600px; margin: 0 auto;">
            <div class="w3-card bg-brick brick-card" style="padding: 2rem;">
                <h2 class="section-title"><?= htmlspecialchars($user['name'] ?? $username) ?></h2>
                <p class="section-subtitle">Detailed view of contributor information and statistics</p>
                
                <!-- Log New Hours Section -->
                <div style="margin-bottom: 2rem;">
                    <h3>Log New Hours</h3>
                    <p>Log your hours and view your contribution history.</p>
                    
                    <div class="form-group" style="max-width: 400px;">
                        <label for="repository" style="display: block; margin-bottom: 0.5rem;">Repository</label>
                        <select id="repository" class="form-control" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">Select a repository...</option>
                            <?php if (is_array($repositories)): ?>
                                <?php foreach ($repositories as $repo): ?>
                                    <option value="<?= htmlspecialchars($repo['name']) ?>">
                                        <?= strtoupper(htmlspecialchars($repo['name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <button class="brick-btn" style="margin-top: 1rem; padding: 0.5rem 1rem;">Log Hours</button>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stats-card">
                        <h4 class="text-subtext" style="margin: 0;">Total Hours</h4>
                        <p class="stats-num brick-orange" style="margin: 0.5rem 0 0 0;">
                            <?= number_format($user_stats['total_hours'] ?? 0, 0) ?>
                        </p>
                    </div>
                    <div class="stats-card">
                        <h4 class="text-subtext" style="margin: 0;">Projects</h4>
                        <p class="stats-num brick-orange" style="margin: 0.5rem 0 0 0;">
                            <?= $user_stats['projects_worked_on'] ?? 0 ?>
                        </p>
                    </div>
                    <div class="stats-card">
                        <h4 class="text-subtext" style="margin: 0;">Time Entries</h4>
                        <p class="stats-num brick-orange" style="margin: 0.5rem 0 0 0;">
                            <?= $user_stats['total_entries'] ?? 0 ?>
                        </p>
                    </div>
                </div>

                <!-- Repositories Contributed To -->
                <h3 class="subsection-title">Repositories Contributed To</h3>
                <?php if (!empty($contributed_repos)): ?>
                    <div style="max-width: 100%;">
                        <?php foreach ($contributed_repos as $repo): ?>
                            <div class="contributor-item">
                                <div class="contributor-left">
                                    <a href="<?= BASE_URL ?>repository.php?repo=<?= urlencode($repo['name']) ?>" style="text-decoration: none;">
                                        <span class="contributor-name"><?= htmlspecialchars($repo['name']) ?></span>
                                    </a>
                                    <?php if ($repo['language']): ?>
                                        <span style="margin-left: 1rem; font-size: 0.85rem; color: #666;">
                                            <?= htmlspecialchars($repo['language']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="text-align: right;">
                                    <span class="contributor-hours"><?= number_format($repo['hours_contributed'], 1) ?> hours</span>
                                    <br>
                                    <span style="font-size: 0.85rem; color: #999;">
                                        <?= $repo['entries'] ?> <?= $repo['entries'] == 1 ? 'entry' : 'entries' ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="padding: 2rem; text-align: center; color: #999; background: #f8f9fa; border-radius: 0.5rem;">
                        <p>No contributions recorded yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId.length > 1) {
                    document.querySelector(targetId).scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>
