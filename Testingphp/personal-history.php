<?php
/**
 * Personal History Page
 * View detailed time entry history with filtering and pagination
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Require login
requireLogin();

// Initialize variables
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
    
    // Check if database connection is working
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Get user information
    $user_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user = $user_stmt->fetch();
    
    if (!$user) {
        throw new Exception("User not found");
    }
    
    // Get filter parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $app_filter = isset($_GET['app']) ? intval($_GET['app']) : 0;
    $date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';
    
    $offset = ($page - 1) * ITEMS_PER_PAGE;
    
    // Build query conditions
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
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM hours h $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_items / ITEMS_PER_PAGE);
    
    // Get time entries with pagination
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
    
    // Get applications for filter dropdown
    $apps_stmt = $db->prepare("SELECT * FROM applications WHERE is_active = 1 ORDER BY name ASC");
    $apps_stmt->execute();
    $applications = $apps_stmt->fetchAll();
    
    // Check if user has any time entries at all
    $has_entries_stmt = $db->prepare("SELECT COUNT(*) as count FROM hours WHERE user_id = ?");
    $has_entries_stmt->execute([$_SESSION['user_id']]);
    $has_entries = $has_entries_stmt->fetch()['count'] > 0;
    
    // Get summary statistics
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
    $stats_stmt->execute(array_slice($params, 0, -2)); // Remove pagination params
    $summary_stats = $stats_stmt->fetch();
    
    // Get monthly breakdown for current year
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
                    <a class="w3-bar-item w3-button w3-hover-theme" href="dashboard.php">Dashboard</a>
                    <a class="w3-bar-item w3-button w3-hover-theme" href="auth/logout.php">Logout</a>
                    <button class="w3-bar-item w3-button w3-hover-theme" id="theme-toggle">
                        <span class="material-icons">brightness_6</span>
                    </button>
                </nav>
            </div>
        </header>
        
        <main class="w3-content w3-padding-large">
            <div class="w3-margin-bottom">
                <h1 class="w3-xxxlarge w3-text-theme w3-bold w3-margin-bottom">Personal History</h1>
                <p class="w3-text-grey">View your logged hours and contributions.</p>
            </div>
            
            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
                <div class="w3-panel w3-red w3-padding w3-round w3-margin-bottom">
                    <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
                    <br><small>Please check your database connection and try again.</small>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if (!empty($success_message)): ?>
                <div class="w3-panel w3-green w3-padding w3-round w3-margin-bottom">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Summary Statistics -->
            <div class="brickmmo-card w3-card w3-white w3-padding w3-margin-bottom">
                <h2 class="w3-large w3-bold w3-margin-bottom">Summary Statistics</h2>
                <div class="brickmmo-stats-grid">
                    <div class="brickmmo-stats-item">
                        <h4>Total Hours</h4>
                        <p><?= number_format($summary_stats['total_hours'] ?? 0, 1) ?></p>
                    </div>
                    <div class="brickmmo-stats-item">
                        <h4>Total Entries</h4>
                        <p><?= $summary_stats['total_entries'] ?? 0 ?></p>
                    </div>
                    <div class="brickmmo-stats-item">
                        <h4>Projects</h4>
                        <p><?= $summary_stats['projects_worked_on'] ?? 0 ?></p>
                    </div>
                    <div class="brickmmo-stats-item">
                        <h4>Avg per Entry</h4>
                        <p><?= number_format($summary_stats['avg_hours_per_entry'] ?? 0, 1) ?></p>
                    </div>
                    <div class="brickmmo-stats-item">
                        <h4>First Entry</h4>
                        <p class="w3-small">
                            <?= $summary_stats['first_entry'] ? date('M j, Y', strtotime($summary_stats['first_entry'])) : 'N/A' ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Chart -->
            <?php if (!empty($monthly_data)): ?>
                <div class="bg-card-light dark:bg-card-dark p-4 rounded-lg shadow-md mb-6">
                    <h2 class="text-lg font-bold mb-3">Monthly Activity (<?= date('Y') ?>)</h2>
                    <div style="height: 200px; position: relative;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="bg-card-light dark:bg-card-dark p-4 rounded-lg shadow-md mb-6">
                <h2 class="text-lg font-bold mb-3">Filters</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="app">Repository</label>
                        <select class="w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md" 
                                id="app" name="app">
                            <option value="">All Repositories</option>
                            <?php foreach ($applications as $app): ?>
                                <option value="<?= $app['id'] ?>" <?= $app_filter == $app['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($app['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="date_from">From Date</label>
                        <input class="w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:outline-none focus:ring-primary focus:border-primary rounded-md" 
                               id="date_from" name="date_from" type="date" value="<?= htmlspecialchars($date_from) ?>"/>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="date_to">To Date</label>
                        <input class="w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:outline-none focus:ring-primary focus:border-primary rounded-md" 
                               id="date_to" name="date_to" type="date" value="<?= htmlspecialchars($date_to) ?>"/>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-primary hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-colors">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Time Entries -->
            <div class="bg-card-light dark:bg-card-dark p-4 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-lg font-bold">Time Entries</h2>
                    <span class="text-xs text-subtext-light dark:text-subtext-dark">
                        Showing <?= count($time_entries) ?> of <?= $total_items ?> entries
                    </span>
                </div>
                
                <?php if (!empty($time_entries)): ?>
                    <div class="space-y-3">
                        <?php foreach ($time_entries as $entry): ?>
                            <div class="bg-background-light dark:bg-background-dark p-3 rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2">
                                            <h3 class="font-medium text-primary mr-4"><?= htmlspecialchars($entry['app_name']) ?></h3>
                                            <span class="text-sm text-subtext-light dark:text-subtext-dark">
                                                <?= date('l, F j, Y', strtotime($entry['work_date'])) ?>
                                            </span>
                                        </div>
                                        
                                        <?php if ($entry['description']): ?>
                                            <p class="text-sm text-subtext-light dark:text-subtext-dark mb-2">
                                                <?= htmlspecialchars($entry['description']) ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="flex items-center text-xs text-subtext-light dark:text-subtext-dark">
                                            <span class="material-icons text-xs mr-1">schedule</span>
                                            <span>Logged <?= date('M j, Y g:i A', strtotime($entry['created_at'])) ?></span>
                                            <?php if ($entry['updated_at'] != $entry['created_at']): ?>
                                                <span class="ml-4">
                                                    <span class="material-icons text-xs mr-1">edit</span>
                                                    <span>Updated <?= date('M j, Y g:i A', strtotime($entry['updated_at'])) ?></span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="text-right ml-4">
                                        <div class="text-2xl font-bold text-primary"><?= number_format($entry['duration'], 1) ?>h</div>
                                        <a href="<?= htmlspecialchars($entry['app_url']) ?>" target="_blank" 
                                           class="text-xs text-subtext-light dark:text-subtext-dark hover:text-primary">
                                            View Repository
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="flex justify-center mt-8">
                            <nav class="flex rounded-md shadow-sm">
                                <?php if ($page > 1): ?>
                                    <a class="px-4 py-2 text-sm font-medium text-subtext-light dark:text-subtext-dark bg-white dark:bg-card-dark rounded-l-md hover:bg-gray-50 dark:hover:bg-gray-700" 
                                       href="?page=<?= $page - 1 ?><?= $app_filter ? '&app=' . $app_filter : '' ?><?= $date_from ? '&date_from=' . urlencode($date_from) : '' ?><?= $date_to ? '&date_to=' . urlencode($date_to) : '' ?>">Previous</a>
                                <?php else: ?>
                                    <span class="px-4 py-2 text-sm font-medium text-gray-400 bg-white dark:bg-card-dark rounded-l-md">Previous</span>
                                <?php endif; ?>
                                
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="px-4 py-2 text-sm font-medium text-white bg-primary z-10"><?= $i ?></span>
                                    <?php else: ?>
                                        <a class="px-4 py-2 text-sm font-medium text-subtext-light dark:text-subtext-dark bg-white dark:bg-card-dark hover:bg-gray-50 dark:hover:bg-gray-700" 
                                           href="?page=<?= $i ?><?= $app_filter ? '&app=' . $app_filter : '' ?><?= $date_from ? '&date_from=' . urlencode($date_from) : '' ?><?= $date_to ? '&date_to=' . urlencode($date_to) : '' ?>"><?= $i ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a class="px-4 py-2 text-sm font-medium text-subtext-light dark:text-subtext-dark bg-white dark:bg-card-dark rounded-r-md hover:bg-gray-50 dark:hover:bg-gray-700" 
                                       href="?page=<?= $page + 1 ?><?= $app_filter ? '&app=' . $app_filter : '' ?><?= $date_from ? '&date_from=' . urlencode($date_from) : '' ?><?= $date_to ? '&date_to=' . urlencode($date_to) : '' ?>">Next</a>
                                <?php else: ?>
                                    <span class="px-4 py-2 text-sm font-medium text-gray-400 bg-white dark:bg-card-dark rounded-r-md">Next</span>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="material-icons text-4xl text-subtext-light dark:text-subtext-dark mb-3">schedule</div>
                        <h3 class="text-lg font-medium mb-2">No time entries found</h3>
                        <p class="text-subtext-light dark:text-subtext-dark mb-4">
                            <?php if ($app_filter || $date_from || $date_to): ?>
                                No entries match your current filters. Try adjusting your search criteria.
                            <?php elseif (!$has_entries): ?>
                                You haven't logged any hours yet. <a href="dashboard.php" class="text-primary hover:underline">Start logging hours</a> to see them here.
                            <?php else: ?>
                                No entries found with the current filters.
                            <?php endif; ?>
                        </p>
                        <?php if ($app_filter || $date_from || $date_to): ?>
                            <a href="personal-history.php" class="inline-block bg-primary hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-colors">
                                Clear Filters
                            </a>
                        <?php else: ?>
                            <a href="dashboard.php" class="inline-block bg-primary hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-colors">
                                Log Hours
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        document.getElementById('theme-toggle').addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
        });
        
        // Monthly Chart
        const monthlyData = <?= json_encode($monthly_data) ?>;
        
        function createMonthlyChart() {
            const canvas = document.getElementById('monthlyChart');
            if (!canvas) {
                console.log('Monthly chart canvas not found');
                return;
            }
            
            if (!monthlyData || monthlyData.length === 0) {
                console.log('No monthly data available');
                // Show a message instead of hiding
                const chartContainer = canvas.closest('.bg-card-light, .bg-card-dark');
                if (chartContainer) {
                    const existingMessage = chartContainer.querySelector('.no-data-message');
                    if (!existingMessage) {
                        const message = document.createElement('div');
                        message.className = 'no-data-message text-center py-8 text-subtext-light dark:text-subtext-dark';
                        message.innerHTML = '<p>No activity data available for this year.</p>';
                        chartContainer.appendChild(message);
                    }
                    canvas.style.display = 'none';
                }
                return;
            }
            
            try {
                const ctx = canvas.getContext('2d');
                const months = monthlyData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short' });
                }).reverse();
                const hours = monthlyData.map(item => parseFloat(item.total_hours) || 0).reverse();
                
                // Remove any existing no-data message
                const chartContainer = canvas.closest('.bg-card-light, .bg-card-dark');
                const existingMessage = chartContainer.querySelector('.no-data-message');
                if (existingMessage) {
                    existingMessage.remove();
                }
                canvas.style.display = 'block';
                
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
            } catch (error) {
                console.error('Chart creation failed:', error);
                // Show error message
                const chartContainer = canvas.closest('.bg-card-light, .bg-card-dark');
                if (chartContainer) {
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'text-center py-4 text-red-500';
                    errorMessage.innerHTML = '<p>Chart could not be loaded.</p>';
                    chartContainer.appendChild(errorMessage);
                    canvas.style.display = 'none';
                }
            }
        }
        
        // Create chart when page loads
        document.addEventListener('DOMContentLoaded', createMonthlyChart);
    </script>
</body>
</html>
