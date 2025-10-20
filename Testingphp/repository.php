<?php
/**
 * Repository Details Page
 * Shows detailed information about a specific repository
 */

require_once 'config/config.php';
require_once 'config/database.php';

if (!isset($_GET['repo'])) {
    redirect(BASE_URL . 'index.php?error=repository_not_found');
}

$repoName = sanitizeInput($_GET['repo']);
$owner = GITHUB_ORG;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get repository from database
    $stmt = $db->prepare("SELECT * FROM applications WHERE name = ? AND is_active = 1");
    $stmt->execute([$repoName]);
    $repo = $stmt->fetch();
    
    if (!$repo) {
        redirect(BASE_URL . 'index.php?error=repository_not_found');
    }
    
    // Get repository statistics
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
    
    // Get top contributors
    $contributors_stmt = $db->prepare("
        SELECT 
            u.name,
            u.login,
            u.avatar_url,
            SUM(h.duration) as total_hours
        FROM hours h
        JOIN users u ON h.user_id = u.id
        WHERE h.application_id = ?
        GROUP BY u.id, u.name, u.login, u.avatar_url
        ORDER BY total_hours DESC
        LIMIT 10
    ");
    $contributors_stmt->execute([$repo['id']]);
    $top_contributors = $contributors_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    redirect(BASE_URL . 'index.php?error=database_error');
}

// GitHub API integration
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

// Fetch GitHub data
$repoUrl = "https://api.github.com/repos/$owner/$repoName";
$commitsUrl = "$repoUrl/commits";
$contributorsUrl = "$repoUrl/contributors";
$branchesUrl = "$repoUrl/branches";
$forksUrl = "$repoUrl/forks";
$mergesUrl = "$repoUrl/pulls?state=closed";
$languagesUrl = "$repoUrl/languages";
$issuesUrl = "$repoUrl/issues?state=open";
$readmeUrl = "$repoUrl/readme";

$repoData = fetchGitHubData($repoUrl, $headers);
$commitsData = fetchGitHubData($commitsUrl, $headers);
$contributorsData = fetchGitHubData($contributorsUrl, $headers);
$branchesData = fetchGitHubData($branchesUrl, $headers);
$forksData = fetchGitHubData($forksUrl, $headers);
$mergesData = fetchGitHubData($mergesUrl, $headers);
$languagesData = fetchGitHubData($languagesUrl, $headers);
$issuesData = fetchGitHubData($issuesUrl, $headers);
$readmeData = fetchGitHubData($readmeUrl, $headers);

$readmeContent = '';
if ($readmeData && isset($readmeData['content'])) {
    $readmeContent = base64_decode($readmeData['content']);
    $readmeContent = htmlspecialchars($readmeContent);
    $readmeContent = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $readmeContent);
    $readmeContent = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $readmeContent);
    $readmeContent = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $readmeContent);
    $readmeContent = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $readmeContent);
    $readmeContent = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $readmeContent);
    $readmeContent = nl2br($readmeContent);
}

// Process issues data
$openIssuesCount = 0;
$bugIssuesCount = 0;
$goodFirstIssueCount = 0;

if ($issuesData && is_array($issuesData)) {
    $openIssuesCount = count($issuesData);
    
    foreach ($issuesData as $issue) {
        $labels = $issue['labels'] ?? [];
        foreach ($labels as $label) {
            $labelName = strtolower($label['name']);
            if (strpos($labelName, 'bug') !== false) {
                $bugIssuesCount++;
            }
            if (strpos($labelName, 'good first issue') !== false || strpos($labelName, 'good-first-issue') !== false) {
                $goodFirstIssueCount++;
            }
        }
    }
}

$latestCommit = isset($commitsData[0]) ? $commitsData[0]['commit']['author'] : null;

// Enhanced contributors with profile pictures
$github_contributors = [];
if ($contributorsData && is_array($contributorsData)) {
    foreach ($contributorsData as $contributor) {
        $github_contributors[] = [
            'login' => $contributor['login'],
            'avatar_url' => $contributor['avatar_url'],
            'html_url' => $contributor['html_url'],
            'contributions' => $contributor['contributions']
        ];
    }
}

$branches = array_map(fn($branch) => "<a href='{$repoData['html_url']}/tree/{$branch['name']}' target='_blank'>{$branch['name']}</a>", $branchesData ?? []);
$forksCount = count($forksData ?? []);
$mergesCount = count($mergesData ?? []);
$languages = $languagesData ?: [];

// Process commit activity by contributor
$commitsByContributor = [];
if ($commitsData && is_array($commitsData)) {
    foreach ($commitsData as $commit) {
        $author = $commit['commit']['author']['name'] ?? 'Unknown';
        if (!isset($commitsByContributor[$author])) {
            $commitsByContributor[$author] = [];
        }
        $commitsByContributor[$author][] = [
            'date' => $commit['commit']['author']['date'],
            'message' => $commit['commit']['message'],
            'sha' => $commit['sha']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repository Details - <?= htmlspecialchars($repoData['name'] ?? $repoName) ?></title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/w3-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#DD5A3A",
                        "background-light": "#F9F9F9",
                        "background-dark": "#121212",
                        "card-light": "#F3E9E5",
                        "card-dark": "#1E1E1E",
                        "text-light": "#333333",
                        "text-dark": "#E0E0E0",
                        "subtext-light": "#666666",
                        "subtext-dark": "#A0A0A0",
                    },
                },
            },
        };
    </script>
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
                </div>
                <nav class="flex items-center space-x-6">
                    <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="index.php">Home</a>
                    <?php if (isLoggedIn()): ?>
                        <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="dashboard.php">User Dashboard</a>
                        <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="auth/logout.php">Logout</a>
                    <?php else: ?>
                        <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="auth/test-login.php">Login</a>
                    <?php endif; ?>
                    <button class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors" id="theme-toggle">
                        <span class="material-icons">brightness_6</span>
                    </button>
                </nav>
            </div>
        </header>
        
        <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <section class="mb-16" id="repository">
                <div class="bg-card-light dark:bg-card-dark p-8 rounded-lg shadow-lg">
                    <h2 class="text-3xl font-bold text-primary mb-2"><?= htmlspecialchars($repoData['name'] ?? $repoName) ?></h2>
                    <p class="text-subtext-light dark:text-subtext-dark mb-6">
                        <?= htmlspecialchars($repoData['description'] ?? 'Detailed view of repository information and statistics') ?>
                    </p>
                    
                    <!-- Key Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-background-light dark:bg-background-dark p-4 rounded-lg text-center">
                            <h4 class="text-sm font-medium text-subtext-light dark:text-subtext-dark">Total Hours</h4>
                            <p class="text-2xl font-bold text-primary"><?= number_format($stats['total_hours'] ?? 0, 0) ?></p>
                        </div>
                        <div class="bg-background-light dark:bg-background-dark p-4 rounded-lg text-center">
                            <h4 class="text-sm font-medium text-subtext-light dark:text-subtext-dark">Contributors</h4>
                            <p class="text-2xl font-bold text-primary"><?= $stats['contributors'] ?? 0 ?></p>
                        </div>
                        <div class="bg-background-light dark:bg-background-dark p-4 rounded-lg text-center">
                            <h4 class="text-sm font-medium text-subtext-light dark:text-subtext-dark">Time Entries</h4>
                            <p class="text-2xl font-bold text-primary"><?= $stats['time_entries'] ?? 0 ?></p>
                        </div>
                    </div>
                    
                    <!-- Top Contributors -->
                    <h3 class="text-xl font-bold mb-4">Top Contributors</h3>
                    <ul class="space-y-4">
                        <?php if (!empty($top_contributors)): ?>
                            <?php foreach ($top_contributors as $contributor): ?>
                                <li class="flex items-center justify-between bg-background-light dark:bg-background-dark p-4 rounded-lg">
                                    <div class="flex items-center">
                                        <img alt="Contributor avatar" class="h-10 w-10 rounded-full mr-4" src="<?= htmlspecialchars($contributor['avatar_url']) ?>"/>
                                        <a href="contributor.php?user=<?= urlencode($contributor['login']) ?>" class="font-medium text-primary hover:underline">
                                            <?= htmlspecialchars($contributor['name']) ?>
                                        </a>
                                    </div>
                                    <span class="font-bold text-primary"><?= number_format($contributor['total_hours'], 0) ?> hours</span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="bg-background-light dark:bg-background-dark p-4 rounded-lg text-center text-subtext-light dark:text-subtext-dark">
                                No time entries recorded yet.
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <!-- GitHub Repository Information -->
                    <?php if ($repoData): ?>
                        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-xl font-bold mb-4">Repository Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="font-medium mb-2">Repository Details</h4>
                                    <ul class="space-y-2 text-sm">
                                        <li><strong>Forks:</strong> <?= $forksCount ?></li>
                                        <li><strong>Branches:</strong> <?= implode(', ', $branches) ?: 'N/A' ?></li>
                                        <li><strong>Last Commit:</strong> <?= $latestCommit['name'] ?? 'N/A' ?> on <?= $latestCommit['date'] ?? 'N/A' ?></li>
                                        <li><strong>Merges:</strong> <?= $mergesCount ?></li>
                                        <li><strong>Languages:</strong> <?= implode(', ', array_keys($languages)) ?: 'N/A' ?></li>
                                        <li><strong>Open Issues:</strong> <?= $openIssuesCount ?></li>
                                        <?php if ($bugIssuesCount > 0): ?>
                                            <li><strong>Bug Issues:</strong> <?= $bugIssuesCount ?></li>
                                        <?php endif; ?>
                                        <?php if ($goodFirstIssueCount > 0): ?>
                                            <li><strong>Good First Issues:</strong> <?= $goodFirstIssueCount ?></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div>
                                    <h4 class="font-medium mb-2">GitHub Contributors</h4>
                                    <div class="space-y-2">
                                        <?php foreach (array_slice($github_contributors, 0, 5) as $contributor): ?>
                                            <div class="flex items-center justify-between bg-background-light dark:bg-background-dark p-2 rounded">
                                                <div class="flex items-center">
                                                    <img alt="<?= $contributor['login'] ?>" class="h-6 w-6 rounded-full mr-2" src="<?= $contributor['avatar_url'] ?>"/>
                                                    <a href="contributor.php?user=<?= urlencode($contributor['login']) ?>" class="text-sm text-primary hover:underline"><?= htmlspecialchars($contributor['login']) ?></a>
                                                    <a href="<?= $contributor['html_url'] ?>" target="_blank" class="text-xs text-gray-500 hover:text-primary ml-2">
                                                        <span class="material-icons text-xs">open_in_new</span>
                                                    </a>
                                                </div>
                                                <span class="text-xs text-subtext-light dark:text-subtext-dark"><?= $contributor['contributions'] ?> commits</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Charts Section -->
            <?php if (!empty($languages) || !empty($commitsByContributor)): ?>
                <section id="graphs-section" class="max-w-4xl mx-auto">
                    <?php if (!empty($languages)): ?>
                        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md mb-6">
                            <h3 class="text-xl font-bold text-primary mb-4"><span class="material-icons inline mr-2">pie_chart</span>Language Distribution</h3>
                            <canvas id="languageChart" height="300"></canvas>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($commitsByContributor)): ?>
                        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md">
                            <h3 class="text-xl font-bold text-primary mb-4"><span class="material-icons inline mr-2">timeline</span>Commit Activity by Contributor</h3>
                            <canvas id="commitChart" height="300"></canvas>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
            
            <!-- README Section -->
            <?php if (!empty($readmeContent)): ?>
                <section id="readme-section" class="mt-8">
                    <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold text-primary mb-4"><span class="material-icons inline mr-2">description</span>README</h3>
                        <div class="readme-content prose dark:prose-invert max-w-none">
                            <?= $readmeContent ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        document.getElementById('theme-toggle').addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
        });
        
        // Language data from PHP
        const languageData = <?= json_encode($languages) ?>;
        const commitData = <?= json_encode($commitsByContributor) ?>;
        
        // Create Language Distribution Pie Chart
        function createLanguageChart() {
            const ctx = document.getElementById('languageChart');
            if (!ctx) return;
            
            const labels = Object.keys(languageData);
            const data = Object.values(languageData);
            const total = data.reduce((a, b) => a + b, 0);
            const colors = [
                '#DD5A3A', '#ff8c42', '#ffa366', '#ffba8a', '#ffd1ae',
                '#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6',
                '#1abc9c', '#34495e', '#16a085', '#27ae60', '#2980b9'
            ];
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, labels.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.parsed;
                                    let percent = total ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value} (${percent}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Create Commit Activity Bar Chart
        function createCommitChart() {
            const ctx = document.getElementById('commitChart');
            if (!ctx) return;
            
            const contributors = Object.keys(commitData);
            const commitCounts = contributors.map(contributor => commitData[contributor].length);
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: contributors,
                    datasets: [{
                        label: 'Number of Commits',
                        data: commitCounts,
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
                            ticks: { stepSize: 1 }
                        }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
        
        // Render charts on page load
        window.addEventListener('DOMContentLoaded', function() {
            if (Object.keys(languageData).length > 0) {
                createLanguageChart();
            }
            if (Object.keys(commitData).length > 0) {
                createCommitChart();
            }
        });
    </script>
</body>
</html>
