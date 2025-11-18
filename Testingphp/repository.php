<?php

require_once 'config/config.php';
require_once 'config/database.php';

$isAuthorizedAdmin = false;
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare('SELECT name, login FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $adminUser = $stmt->fetch();
        if ($adminUser && isset($adminUser['name']) && isset($adminUser['login']) && 
            ($adminUser['name'] === 'Jashanpreet Singh Gill' || 
             $adminUser['name'] === 'Adam Thomas' || 
             $adminUser['login'] === 'codeadamca')) {
            $isAuthorizedAdmin = true;
        }
    } catch (Exception $e) {
    }
}

if (!isset($_GET['repo'])) {
    redirect(BASE_URL . 'index.php?error=repository_not_found');
}

$repoName = sanitizeInput($_GET['repo']);
$owner = GITHUB_ORG;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM applications WHERE name = ? AND is_active = 1");
    $stmt->execute([$repoName]);
    $repo = $stmt->fetch();
    
    if (!$repo) {
        redirect(BASE_URL . 'index.php?error=repository_not_found');
    }
    
    $stats_stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT user_id) as contributors,
            SUM(duration) as total_hours,
            COUNT(*) as time_entries
        FROM hours 
        WHERE application_id = ?
    ");
    $stats_stmt->execute([$repo['id']]);
    $stats = $stats_stmt->fetch();
    $contributors_stmt = $db->prepare("
        SELECT 
            u.id as user_id,
            u.name,
            u.login,
            u.avatar_url,
            u.bio,
            u.email,
            SUM(h.duration) as total_hours,
            COUNT(DISTINCT h.id) as entries,
            COUNT(DISTINCT DATE(h.work_date)) as days_worked,
            MAX(h.work_date) as last_contribution,
            (
                SELECT COUNT(DISTINCT a2.id)
                FROM hours h2 
                JOIN applications a2 ON h2.application_id = a2.id 
                WHERE h2.user_id = u.id
            ) as total_repos
        FROM hours h
        JOIN users u ON h.user_id = u.id
        WHERE h.application_id = ?
        GROUP BY u.id, u.name, u.login, u.avatar_url, u.bio, u.email
        ORDER BY total_hours DESC
        LIMIT 10
    ");
    $contributors_stmt->execute([$repo['id']]);
    $top_contributors = $contributors_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    redirect(BASE_URL . 'index.php?error=database_error');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($repo['name']) ?> - BrickMMO Repository</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/w3-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
        
        .header {
            background: white;
            border-bottom: 1px solid #E8D5CF;
            padding: 1.5rem 0;
        }
        
        .header-content {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            height: 48px;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-link {
            color: #DD5A3A;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: opacity 0.2s;
        }
        
        .nav-link:hover {
            opacity: 0.8;
        }
        
        .page-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #DD5A3A;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            font-size: 1rem;
            color: #666;
            line-height: 1.5;
        }
        
        .github-link {
            display: inline-block;
            margin-top: 0.75rem;
            color: #DD5A3A;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .github-link:hover {
            text-decoration: underline;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #666;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #DD5A3A;
            line-height: 1;
        }
        
        .contributors-section, .repo-stats-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .repo-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .repo-stats-card {
            background: #FAFAFA;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .repo-stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.05);
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            background: #DD5A3A;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.5rem;
        }
        
        .stats-details {
            flex: 1;
        }
        
        .stats-title {
            font-size: 0.9rem;
            color: #718096;
            margin: 0 0 0.25rem 0;
            font-weight: 500;
        }
        
        .stats-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2C3E50;
            margin-bottom: 0.25rem;
        }
        
        .stats-description {
            font-size: 0.8rem;
            color: #718096;
            margin: 0;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 0;
        }
        
        .section-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sort-dropdown {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: white;
            font-size: 0.85rem;
            cursor: pointer;
        }
        
        .contributor-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .contributor-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid #f0f0f0;
        }
        
        .contributor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        }
        
        .contributor-card-header {
            padding: 1.5rem;
            display: flex;
            gap: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .contributor-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #F0F0F0;
            object-fit: cover;
        }
        
        .contributor-header-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .contributor-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2C3E50;
            text-decoration: none;
            transition: color 0.2s;
            margin-bottom: 0.2rem;
        }
        
        .contributor-name:hover {
            color: #DD5A3A;
        }
        
        .contributor-username {
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 0.5rem;
        }
        
        .contributor-bio {
            font-size: 0.85rem;
            color: #718096;
            margin: 0.5rem 0 0 0;
            line-height: 1.4;
        }
        
        .contributor-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            padding: 1rem;
            background-color: #FAFAFA;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .stat-value {
            font-weight: 700;
            font-size: 1.2rem;
            color: #2C3E50;
        }
        
        .stat-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #718096;
            letter-spacing: 0.5px;
            margin-top: 0.2rem;
        }
        
        .contributor-footer {
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
        }
        
        .last-contribution {
            font-size: 0.8rem;
            color: #718096;
        }
        
        .view-profile-btn {
            background-color: #DD5A3A;
            color: white;
            font-size: 0.8rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .view-profile-btn:hover {
            background-color: #C14A2E;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #999;
        }
        
        .github-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .github-stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .github-stat-label {
            font-size: 0.8rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        
        .github-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2C3E50;
        }
        
        .content-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .language-bar {
            height: 8px;
            background: #F0F0F0;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            margin-bottom: 1rem;
        }
        
        .language-segment {
            height: 100%;
            transition: all 0.3s;
        }
        
        .language-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.75rem;
        }
        
        .language-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .language-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .language-name {
            font-size: 0.9rem;
            color: #666;
        }
        
        .language-percent {
            font-weight: 600;
            color: #2C3E50;
            margin-left: auto;
        }
        
        .commit-item {
            padding: 1rem;
            border-bottom: 1px solid #F0F0F0;
            transition: background 0.2s;
        }
        
        .commit-item:last-child {
            border-bottom: none;
        }
        
        .commit-item:hover {
            background: #FEFBFA;
        }
        
        .commit-message {
            font-size: 0.95rem;
            color: #2C3E50;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .commit-meta {
            font-size: 0.8rem;
            color: #999;
        }
        
        .commit-sha {
            font-family: 'Courier New', monospace;
            background: #F0F0F0;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            margin-left: 0.5rem;
        }
        
        .branch-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .branch-tag {
            background: #FDF6F3;
            color: #DD5A3A;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid #E8D5CF;
        }
        
        @media (max-width: 1200px) {
            .github-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .page-container {
                max-width: 95%;
            }
        }
        
        
        .chart-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .chart-title {
            font-size: 1.3rem;
            color: #DD5A3A;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        
        .readme-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .readme-content {
            line-height: 1.6;
            font-size: 0.95rem;
            padding: 1rem;
            background: #FAFAFA;
            border-radius: 8px;
            border: 1px solid #EEEEEE;
            overflow-x: auto;
        }
        
        .readme-content h1 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            font-weight: 700;
            color: #DD5A3A;
        }
        
        .readme-content h2 {
            font-size: 1.5rem;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: #333;
        }
        
        .readme-content h3 {
            font-size: 1.2rem;
            margin-top: 1.25rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #444;
        }
        
        .readme-content code {
            font-family: 'Courier New', monospace;
            background: #F0F0F0;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        
        .contributor-commits {
            margin-top: 2rem;
            border-top: 1px solid #EEE;
            padding-top: 1.5rem;
        }
        
        .contributor-commits h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .recent-commits {
            margin-left: 1rem;
        }
        
        .commit-item {
            padding: 1rem;
            border-bottom: 1px solid #F0F0F0;
            transition: background 0.2s;
        }
        
        .commit-date {
            font-size: 0.85rem;
            color: #999;
            display: inline-block;
            margin-right: 1rem;
        }
        
        .commit-message {
            font-size: 0.95rem;
        }
        
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .github-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .page-container {
                padding: 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .chart-container {
                height: 250px;
            }
            
            .chart-section, .readme-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    
    <header class="header">
        <div class="header-content">
            <div>
                <a href="index.php">
                    <img src="./assets/BrickMMO_Logo_Coloured.png" alt="BrickMMO" class="logo">
                </a>
            </div>
            <nav class="nav-links">
                <a href="<?= BASE_URL ?>" class="nav-link">Home</a>
                <a href="https://brickmmo.com" class="nav-link">BrickMMO Main Site</a>
                <?php if ($isAuthorizedAdmin): ?>
                    <a href="<?= BASE_URL ?>admin/" class="nav-link">Admin</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= BASE_URL ?>dashboard.php" class="nav-link">Dashboard</a>
                    <a href="<?= BASE_URL ?>auth/logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>auth/login.php" class="nav-link">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="page-container">
        
        <div class="page-header">
            <h1 class="page-title"><?= htmlspecialchars($repo['name']) ?></h1>
            <p class="page-subtitle">
                <?= htmlspecialchars($repo['description'] ?: 'Detailed view of repository information and statistics') ?>
            </p>
            <?php if ($repo['html_url']): ?>
                <a href="<?= htmlspecialchars($repo['html_url']) ?>" target="_blank" class="github-link">
                    View on GitHub →
                </a>
            <?php endif; ?>
        </div>
        
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-label">Total Hours</div>
                <div class="stat-value"><?= number_format($stats['total_hours'] ?? 0, 0) ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Contributors</div>
                <div class="stat-value"><?= (int)($stats['contributors'] ?? 0) ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Time Entries</div>
                <div class="stat-value"><?= (int)($stats['time_entries'] ?? 0) ?></div>
            </div>
        </div>
        
        <div class="contributors-section">
            <div class="section-header">
                <h2 class="section-title">Top Contributors</h2>
                <div class="section-controls">
                    <label for="contributor-sort">Sort by:</label>
                    <select id="contributor-sort" class="sort-dropdown">
                        <option value="hours" selected>Hours (highest first)</option>
                        <option value="entries">Entries (highest first)</option>
                        <option value="repos">Repositories (highest first)</option>
                        <option value="recent">Recent Activity</option>
                    </select>
                </div>
            </div>
            
            <?php if (!empty($top_contributors)): ?>
                <div class="contributor-cards" id="contributor-cards">
                    <?php foreach ($top_contributors as $contributor): ?>
                        <div class="contributor-card">
                            <div class="contributor-card-header">
                                <img src="<?= htmlspecialchars($contributor['avatar_url']) ?>" 
                                     alt="<?= htmlspecialchars($contributor['name'] ?? $contributor['login']) ?>" 
                                     class="contributor-avatar"/>
                                <div class="contributor-header-info">
                                    <a href="<?= BASE_URL ?>contributor.php?user=<?= urlencode($contributor['login']) ?>" 
                                       class="contributor-name">
                                        <?= htmlspecialchars($contributor['name'] ?? $contributor['login']) ?>
                                    </a>
                                    <span class="contributor-username">@<?= htmlspecialchars($contributor['login']) ?></span>
                                    <?php if (!empty($contributor['bio'])): ?>
                                        <p class="contributor-bio"><?= htmlspecialchars(substr($contributor['bio'], 0, 100)) ?><?= strlen($contributor['bio']) > 100 ? '...' : '' ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="contributor-stats">
                                <div class="stat-item">
                                    <span class="stat-value"><?= number_format($contributor['total_hours'], 1) ?></span>
                                    <span class="stat-label">Hours</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?= number_format($contributor['entries']) ?></span>
                                    <span class="stat-label">Entries</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?= number_format($contributor['days_worked']) ?></span>
                                    <span class="stat-label">Days</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?= number_format($contributor['total_repos']) ?></span>
                                    <span class="stat-label">Repos</span>
                                </div>
                            </div>
                            <div class="contributor-footer">
                                <span class="last-contribution">Last contribution: <?= date('M j, Y', strtotime($contributor['last_contribution'])) ?></span>
                                <a href="<?= BASE_URL ?>contributor.php?user=<?= urlencode($contributor['login']) ?>" class="view-profile-btn">View Profile</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No contributors have logged time for this repository yet.</p>
                </div>
            <?php endif; ?>
        </div>
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        <?php if (!empty($readmeContent)): ?>
            <div class="readme-section">
                <h2 class="chart-title"><i class="fas fa-file-alt"></i> README</h2>
                <div class="readme-content">
                    <?= $readmeContent ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initContributorSorting();
    });
    
    const contributorsData = <?php echo json_encode($top_contributors); ?>;
    
    function initContributorSorting() {
        const sortDropdown = document.getElementById('contributor-sort');
        if (sortDropdown) {
            renderContributors(contributorsData, 'hours');
            
            sortDropdown.addEventListener('change', function() {
                renderContributors(contributorsData, this.value);
            });
        }
    }
    
    function renderContributors(contributors, sortBy) {
        const container = document.getElementById('contributor-cards');
        if (!container) return;
        
        const sortedContributors = [...contributors].sort((a, b) => {
            switch (sortBy) {
                case 'hours':
                    return parseFloat(b.total_hours) - parseFloat(a.total_hours);
                case 'entries':
                    return parseInt(b.entries) - parseInt(a.entries);
                case 'repos':
                    return parseInt(b.total_repos) - parseInt(a.total_repos);
                case 'recent':
                    return new Date(b.last_contribution) - new Date(a.last_contribution);
                default:
                    return parseFloat(b.total_hours) - parseFloat(a.total_hours);
            }
        });
        
        container.innerHTML = '';
        sortedContributors.forEach(contributor => {
            const card = document.createElement('div');
            card.className = 'contributor-card';
            
            const name = contributor.name || contributor.login;
            const bio = contributor.bio ? 
                (contributor.bio.length > 100 ? contributor.bio.substring(0, 100) + '...' : contributor.bio) : '';
            
            card.innerHTML = `
                <div class="contributor-card-header">
                    <img src="${escapeHTML(contributor.avatar_url)}" 
                         alt="${escapeHTML(name)}" 
                         class="contributor-avatar"/>
                    <div class="contributor-header-info">
                        <a href="<?= BASE_URL ?>contributor.php?user=${encodeURIComponent(contributor.login)}" 
                           class="contributor-name">
                            ${escapeHTML(name)}
                        </a>
                        <span class="contributor-username">@${escapeHTML(contributor.login)}</span>
                        ${bio ? `<p class="contributor-bio">${escapeHTML(bio)}</p>` : ''}
                    </div>
                </div>
                <div class="contributor-stats">
                    <div class="stat-item">
                        <span class="stat-value">${Number(contributor.total_hours).toFixed(1)}</span>
                        <span class="stat-label">Hours</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">${Number(contributor.entries)}</span>
                        <span class="stat-label">Entries</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">${Number(contributor.days_worked)}</span>
                        <span class="stat-label">Days</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">${Number(contributor.total_repos)}</span>
                        <span class="stat-label">Repos</span>
                    </div>
                </div>
                <div class="contributor-footer">
                    <span class="last-contribution">Last contribution: ${formatDate(contributor.last_contribution)}</span>
                    <a href="<?= BASE_URL ?>contributor.php?user=${encodeURIComponent(contributor.login)}" class="view-profile-btn">View Profile</a>
                </div>
            `;
            
            container.appendChild(card);
        });
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
    }
    
    function escapeHTML(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    </script>
</body>
</html>
