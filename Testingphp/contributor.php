<?php

require_once 'config/config.php';
require_once 'config/database.php';

$username = isset($_GET['user']) ? sanitizeInput($_GET['user']) : '';

if (empty($username)) {
    redirect(BASE_URL . 'index.php?error=invalid_user');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
    $user_stmt->execute([$username]);
    $user = $user_stmt->fetch();
    
    if (!$user) {
        redirect(BASE_URL . 'index.php?error=user_not_found');
    }
    
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body>
    
    <?php 
    $fromAdmin = isset($_GET['from']) && $_GET['from'] === 'admin';
    ?>
    <header style="background: white; border-bottom: 1px solid #E8D5CF; padding: 1.5rem 0; margin-bottom: 2rem;">
        <div style="max-width: 1600px; margin: 0 auto; padding: 0 1rem; display: flex; justify-content: <?= $fromAdmin ? 'flex-start' : 'space-between' ?>; align-items: center;">
            <div>
                <?php if ($fromAdmin): ?>
                    <a href="admin/contributors.php" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 1.5rem;">←</span> Return to Contributors
                    </a>
                <?php else: ?>
                    <a href="index.php">
                        <img src="./assets/BrickMMO_Logo_Coloured.png" alt="BrickMMO" style="height: 48px;">
                    </a>
                <?php endif; ?>
            </div>
            <?php if (!$fromAdmin): ?>
            <nav style="display: flex; gap: 2rem; align-items: center;">
                <a href="<?= BASE_URL ?>" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">Home</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= BASE_URL ?>dashboard.php" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">Dashboard</a>
                    <a href="<?= BASE_URL ?>personal-history.php" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">My History</a>
                    <a href="<?= BASE_URL ?>auth/logout.php" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">Logout</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>auth/login.php" style="color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem;">Login</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
    </header>

    
    <section id="repository" style="padding: 3rem 1rem;">
        <div class="w3-container" style="max-width: 1600px; margin: 0 auto;">
            <div class="w3-card bg-brick brick-card" style="padding: 2rem;">
                <h2 class="section-title"><?= htmlspecialchars($user['name'] ?? $username) ?></h2>
                <p class="section-subtitle">Detailed view of contributor information and statistics</p>
                <p class="section-subtitle">View-only profile. To log time, use your <a href="<?= BASE_URL ?>dashboard.php">Dashboard</a>.</p>
                
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
