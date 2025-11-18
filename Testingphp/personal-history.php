<?php

require_once 'config/config.php';
require_once 'config/database.php';

requireLogin();

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

$error_message = '';
$success_message = '';
$time_entries = [];
$total_pages = 1;
$summary_stats = [];
$monthly_data = [];
$applications = [];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    $user_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user = $user_stmt->fetch();
    
    if (!$user) {
        throw new Exception("User not found");
    }
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $app_filter = isset($_GET['app']) ? intval($_GET['app']) : 0;
    $date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';
    
    $offset = ($page - 1) * ITEMS_PER_PAGE;
    
    $where_conditions = ["h.user_id = ?"];
    $params = [$_SESSION['user_id']];
    
    if ($app_filter > 0) {
        $where_conditions[] = "h.application_id = ?";
        $params[] = $app_filter;
    }
    
    if ($date_from) {
        $where_conditions[] = "h.work_date >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $where_conditions[] = "h.work_date <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    $count_sql = "SELECT COUNT(*) as total FROM hours h $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_items / ITEMS_PER_PAGE);
    $sql = "
        SELECT 
            h.*,
            a.name as app_name,
            a.html_url as app_url
        FROM hours h
        JOIN applications a ON h.application_id = a.id
        $where_clause
        ORDER BY h.work_date DESC, h.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = ITEMS_PER_PAGE;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $time_entries = $stmt->fetchAll();
    
    $apps_stmt = $db->prepare("SELECT * FROM applications WHERE is_active = 1 ORDER BY name ASC");
    $apps_stmt->execute();
    $applications = $apps_stmt->fetchAll();
    
    $has_entries_stmt = $db->prepare("SELECT COUNT(*) as count FROM hours WHERE user_id = ?");
    $has_entries_stmt->execute([$_SESSION['user_id']]);
    $has_entries = $has_entries_stmt->fetch()['count'] > 0;
    $stats_sql = "
        SELECT 
            COUNT(*) as total_entries,
            SUM(duration) as total_hours,
            COUNT(DISTINCT application_id) as projects_worked_on,
            MIN(work_date) as first_entry,
            MAX(work_date) as last_entry,
            AVG(duration) as avg_hours_per_entry
        FROM hours h
        $where_clause
    ";
    $stats_stmt = $db->prepare($stats_sql);
    $stats_stmt->execute(array_slice($params, 0, -2));
    $summary_stats = $stats_stmt->fetch();
    
    $monthly_sql = "
        SELECT 
            DATE_FORMAT(work_date, '%Y-%m') as month,
            SUM(duration) as total_hours,
            COUNT(*) as entries
        FROM hours h
        WHERE h.user_id = ? AND YEAR(work_date) = YEAR(CURDATE())
        GROUP BY DATE_FORMAT(work_date, '%Y-%m')
        ORDER BY month DESC
    ";
    $monthly_stmt = $db->prepare($monthly_sql);
    $monthly_stmt->execute([$_SESSION['user_id']]);
    $monthly_data = $monthly_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Personal history error: " . $e->getMessage());
    $error_message = "Error loading data: " . $e->getMessage();
    $time_entries = [];
    $total_pages = 1;
    $summary_stats = [];
    $monthly_data = [];
    $applications = [];
    $has_entries = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Personal History - BrickMMO Timesheets</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #FAF8F7;
            color: #1A1A1A;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .stat-card {
            background: #FFF5F3;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #E8D5CF;
        }
        
        .stat-card h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #666;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card p {
            font-size: 2rem;
            font-weight: 700;
            color: #DD5A3A;
        }
        
        .stat-card p.small-text {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1A1A1A;
        }
        
        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #E8D5CF;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            background: white;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #DD5A3A;
        }
        
        .btn-primary {
            background: #DD5A3A;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.2s;
            font-family: 'Inter', sans-serif;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: #C14D30;
        }
        
        .entry-item {
            background: #FFF5F3;
            border: 1px solid #E8D5CF;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1.5rem;
        }
        
        .entry-content {
            flex: 1;
        }
        
        .entry-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
        }
        
        .entry-title {
            font-weight: 600;
            color: #DD5A3A;
            font-size: 1.1rem;
        }
        
        .entry-date {
            font-size: 0.875rem;
            color: #666;
        }
        
        .entry-description {
            color: #444;
            margin: 0.5rem 0;
            line-height: 1.5;
        }
        
        .entry-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            font-size: 0.75rem;
            color: #999;
            margin-top: 0.5rem;
        }
        
        .entry-meta .material-icons {
            font-size: 14px;
            vertical-align: middle;
            margin-right: 4px;
        }
        
        .entry-hours {
            text-align: right;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .hours-value {
            font-size: 2rem;
            font-weight: 700;
            color: #DD5A3A;
        }
        
        .repo-link {
            font-size: 0.75rem;
            color: #666;
            text-decoration: none;
        }
        
        .repo-link:hover {
            color: #DD5A3A;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #E8D5CF;
            border-radius: 6px;
            text-decoration: none;
            color: #1A1A1A;
            font-weight: 500;
        }
        
        .pagination a:hover {
            background: #FFF5F3;
            border-color: #DD5A3A;
        }
        
        .pagination .active {
            background: #DD5A3A;
            color: white;
            border-color: #DD5A3A;
        }
        
        .pagination .disabled {
            color: #CCC;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background: #FEE;
            border: 1px solid #FCC;
            color: #C00;
        }
        
        .alert-success {
            background: #EFE;
            border: 1px solid #CFC;
            color: #060;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-state .material-icons {
            font-size: 4rem;
            color: #CCC;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    
    <header style="background: white; border-bottom: 1px solid #E8D5CF; padding: 1.5rem 0; margin-bottom: 2rem;">
        <div style="max-width: 1600px; margin: 0 auto; padding: 0 1rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <a href="index.php">
                    <img src="./assets/BrickMMO_Logo_Coloured.png" alt="BrickMMO" style="height: 48px;">
                </a>
            </div>
            <nav style="display: flex; gap: 2rem; align-items: center;">
                <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                $nav_items = [
                    ['url' => BASE_URL, 'label' => 'Home', 'page' => 'index.php'],
                    ['url' => BASE_URL . 'dashboard.php', 'label' => 'Dashboard', 'page' => 'dashboard.php'],
                    ['url' => BASE_URL . 'personal-history.php', 'label' => 'My History', 'page' => 'personal-history.php'],
                    ['url' => BASE_URL . 'auth/logout.php', 'label' => 'Logout', 'page' => 'logout.php']
                ];
                
                foreach ($nav_items as $item):
                    $is_active = ($current_page === $item['page']);
                    $style = "color: #DD5A3A; text-decoration: " . ($is_active ? "underline" : "none") . "; font-weight: 600; font-size: 0.95rem;";
                ?>
                    <a href="<?= $item['url'] ?>" style="<?= $style ?>"><?= $item['label'] ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2.5rem; font-weight: 700; color: #1A1A1A; margin-bottom: 0.5rem;">Personal History</h1>
            <p style="font-size: 1.1rem; color: #666;">View your logged hours and contributions.</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
                <br><small>Please check your database connection and try again.</small>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">Summary Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Hours</h4>
                    <p><?= number_format($summary_stats['total_hours'] ?? 0, 1) ?></p>
                </div>
                <div class="stat-card">
                    <h4>Total Entries</h4>
                    <p><?= $summary_stats['total_entries'] ?? 0 ?></p>
                </div>
                <div class="stat-card">
                    <h4>Projects</h4>
                    <p><?= $summary_stats['projects_worked_on'] ?? 0 ?></p>
                </div>
                <div class="stat-card">
                    <h4>Avg per Entry</h4>
                    <p><?= number_format($summary_stats['avg_hours_per_entry'] ?? 0, 1) ?></p>
                </div>
                <div class="stat-card">
                    <h4>First Entry</h4>
                    <p class="small-text">
                        <?= $summary_stats['first_entry'] ? date('M j, Y', strtotime($summary_stats['first_entry'])) : 'N/A' ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">Filters</h2>
            <form method="GET" class="filters-form">
                <div class="form-group">
                    <label for="app">Repository</label>
                    <select id="app" name="app">
                        <option value="">All Repositories</option>
                        <?php foreach ($applications as $app): ?>
                            <option value="<?= $app['id'] ?>" <?= $app_filter == $app['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($app['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_from">From Date</label>
                    <input id="date_from" name="date_from" type="date" value="<?= htmlspecialchars($date_from) ?>"/>
                </div>
                
                <div class="form-group">
                    <label for="date_to">To Date</label>
                    <input id="date_to" name="date_to" type="date" value="<?= htmlspecialchars($date_to) ?>"/>
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.5rem; font-weight: 700;">Time Entries</h2>
                <span style="font-size: 0.875rem; color: #666;">
                    Showing <?= count($time_entries) ?> of <?= $total_items ?? 0 ?> entries
                </span>
            </div>
            
            <?php if (!empty($time_entries)): ?>
                <div>
                    <?php foreach ($time_entries as $entry): ?>
                        <div class="entry-item">
                            <div class="entry-content">
                                <div class="entry-header">
                                    <h3 class="entry-title"><?= htmlspecialchars($entry['app_name']) ?></h3>
                                    <span class="entry-date">
                                        <?= date('l, F j, Y', strtotime($entry['work_date'])) ?>
                                    </span>
                                </div>
                                
                                <?php if ($entry['description']): ?>
                                    <p class="entry-description">
                                        <?= htmlspecialchars($entry['description']) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="entry-meta">
                                    <span>
                                        <span class="material-icons">schedule</span>
                                        Logged <?= date('M j, Y g:i A', strtotime($entry['created_at'])) ?>
                                    </span>
                                    <?php if ($entry['updated_at'] != $entry['created_at']): ?>
                                        <span>
                                            <span class="material-icons">edit</span>
                                            Updated <?= date('M j, Y g:i A', strtotime($entry['updated_at'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="entry-hours">
                                <div class="hours-value"><?= number_format($entry['duration'], 1) ?>h</div>
                                <a href="<?= htmlspecialchars($entry['app_url']) ?>" target="_blank" class="repo-link">
                                    View Repository →
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?><?= $app_filter ? '&app=' . $app_filter : '' ?><?= $date_from ? '&date_from=' . urlencode($date_from) : '' ?><?= $date_to ? '&date_to=' . urlencode($date_to) : '' ?>">
                                ← Previous
                            </a>
                        <?php else: ?>
                            <span class="disabled">← Previous</span>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?><?= $app_filter ? '&app=' . $app_filter : '' ?><?= $date_from ? '&date_from=' . urlencode($date_from) : '' ?><?= $date_to ? '&date_to=' . urlencode($date_to) : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?><?= $app_filter ? '&app=' . $app_filter : '' ?><?= $date_from ? '&date_from=' . urlencode($date_from) : '' ?><?= $date_to ? '&date_to=' . urlencode($date_to) : '' ?>">
                                Next →
                            </a>
                        <?php else: ?>
                            <span class="disabled">Next →</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="material-icons">schedule</div>
                    <h3>No time entries found</h3>
                    <p>
                        <?php if ($app_filter || $date_from || $date_to): ?>
                            No entries match your current filters. Try adjusting your search criteria.
                        <?php elseif (!$has_entries): ?>
                            You haven't logged any hours yet. <a href="dashboard.php" style="color: #DD5A3A; font-weight: 600;">Start logging hours</a> to see them here.
                        <?php else: ?>
                            No entries found with the current filters.
                        <?php endif; ?>
                    </p>
                    <?php if ($app_filter || $date_from || $date_to): ?>
                        <a href="personal-history.php" class="btn-primary" style="display: inline-block; width: auto;">
                            Clear Filters
                        </a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn-primary" style="display: inline-block; width: auto;">
                            Log Hours
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        const monthlyData = <?= json_encode($monthly_data) ?>;
    </script>
</body>
</html>
