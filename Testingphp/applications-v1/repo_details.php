<?php
require_once '../config/config.php';

if (isset($_GET['repo'])) {
    $repoName = $_GET['repo'];
    $owner = "BrickMMO"; 

    $headers = [
        "User-Agent: BrickMMO-WebApp"
    ];
    
    if (!empty(GITHUB_TOKEN)) {
        $headers[] = "Authorization: token " . GITHUB_TOKEN;
    }

    function fetchGitHubData($url, $headers) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 403) {
            $responseData = json_decode($response, true);
            if (isset($responseData['message']) && strpos($responseData['message'], 'rate limit') !== false) {
                $errorMsg = "GitHub API rate limit exceeded. ";
                if (empty(GITHUB_TOKEN)) {
                    $errorMsg .= "GITHUB_TOKEN is missing in .env file. Add your GitHub Personal Access Token to .env file: GITHUB_TOKEN=your_token_here";
                } else {
                    $errorMsg .= "Please add a valid GITHUB_TOKEN to your .env file to increase rate limits.";
                }
                error_log($errorMsg);
                return null;
            }
        }
        
        if ($response === false || $httpCode >= 400) {
            $errorMsg = "GitHub API Error: HTTP $httpCode for URL: $url";
            if (empty(GITHUB_TOKEN) && $httpCode === 403) {
                $errorMsg .= ". GITHUB_TOKEN is missing in .env file. Add your token: GITHUB_TOKEN=your_token_here";
            }
            error_log($errorMsg);
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Decode Error: " . json_last_error_msg() . " for URL: $url");
            return null;
        }
        
        return $data;
    }

    $repoUrl = "https://api.github.com/repos/$owner/$repoName";
    $commitsUrl = "$repoUrl/commits";
    $contributorsUrl = "$repoUrl/contributors";
    $branchesUrl = "$repoUrl/branches";
    $forksUrl = "$repoUrl/forks";
    $mergesUrl = "$repoUrl/pulls?state=closed";
    $clonesUrl = "$repoUrl/traffic/clones";
    $languagesUrl = "$repoUrl/languages";
    $readmeUrl = "$repoUrl/readme";

    $repoData = fetchGitHubData($repoUrl, $headers);
    
    if (!$repoData || !is_array($repoData)) {
        $errorMsg = "<h1>Error</h1><p>Could not fetch repository data. Please check if the repository exists or try again later.</p>";
        if (empty(GITHUB_TOKEN)) {
            $errorMsg .= "<p><strong>GITHUB_TOKEN is missing in .env file.</strong> GitHub API may be rate limited. Add your GitHub Personal Access Token to .env file: <code>GITHUB_TOKEN=your_token_here</code></p>";
            $errorMsg .= "<p>Get token from: <a href='https://github.com/settings/tokens' target='_blank'>https://github.com/settings/tokens</a></p>";
        } else {
            $errorMsg .= "<p>GitHub API might be rate limited. <a href='../index.php'>Go back</a></p>";
        }
        die($errorMsg . "<p><a href='../index.php'>Go back</a></p>");
    }
    
    $commitsData = fetchGitHubData($commitsUrl, $headers);
    $contributorsData = fetchGitHubData($contributorsUrl, $headers);
    $branchesData = fetchGitHubData($branchesUrl, $headers);
    $forksData = fetchGitHubData($forksUrl, $headers);
    $mergesData = fetchGitHubData($mergesUrl, $headers);
    $clonesData = fetchGitHubData($clonesUrl, $headers);
    $languagesData = fetchGitHubData($languagesUrl, $headers);
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

    $latestCommit = isset($commitsData[0]) ? $commitsData[0]['commit']['author'] : null;
    $contributors = [];
    if ($contributorsData && is_array($contributorsData)) {
        foreach ($contributorsData as $contributor) {
            if (is_array($contributor) && isset($contributor['login'])) {
                $contributors[] = [
                    'login' => $contributor['login'] ?? 'Unknown',
                    'avatar_url' => $contributor['avatar_url'] ?? '',
                    'html_url' => $contributor['html_url'] ?? '#',
                    'contributions' => $contributor['contributions'] ?? 0
                ];
            }
        }
    }

    $branches = array_map(fn($branch) => "<a href='{$repoData['html_url']}/tree/{$branch['name']}' target='_blank'>{$branch['name']}</a>", $branchesData ?? []);
    $forksCount = count($forksData ?? []);
    $mergesCount = count($mergesData ?? []);
    $clonesCount = $clonesData['count'] ?? 'N/A';
    $languages = (is_array($languagesData) && !empty($languagesData)) ? $languagesData : [];
    $commitsByContributor = [];
    if ($commitsData && is_array($commitsData)) {
        foreach ($commitsData as $commit) {
            if (is_array($commit) && isset($commit['commit']['author']['name'])) {
                $author = $commit['commit']['author']['name'] ?? 'Unknown';
                if (!isset($commitsByContributor[$author])) {
                    $commitsByContributor[$author] = [];
                }
                $commitsByContributor[$author][] = [
                    'date' => $commit['commit']['author']['date'] ?? '',
                    'message' => $commit['commit']['message'] ?? '',
                    'sha' => $commit['sha'] ?? ''
                ];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repository Details - <?= htmlspecialchars($repoData['name'] ?? 'N/A') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./css/detail.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body>
    <header>
        <div class="logo">
            <a href="../index.php">
                <img src="./assets/BrickMMO_Logo_Coloured.png" alt="brickmmo logo" width="80px">
            </a>
        </div>
        <nav>
            <a href="../index.php" class="return-btn">&larr; Return</a>
        </nav>
    </header>
    <main>
        <section id="repo-card">
            <div class="repo-image">
                <img src="<?= $repoData['owner']['avatar_url'] ?? './assets/placeholder.png' ?>" alt="Repository Image">
            </div>
            <div class="repo-info">
                <div id="repo-brief">
                    <h2 id="repo-title"> <?= htmlspecialchars($repoData['name'] ?? 'N/A') ?> </h2>
                    <p id="repo-description"> <?= htmlspecialchars($repoData['description'] ?? 'No description available') ?> </p>
                    <a id="repo-link" href="<?= $repoData['html_url'] ?? '#' ?>" target="_blank" class="github-btn">GitHub Link</a>
                </div>
                <div id="repo-details">
                    <h3>Repository Details</h3>
                    <ul>
                        <li><strong>Forks:</strong> <span id="forks"> <?= $forksCount ?? 'N/A' ?> </span></li>
                        <li><strong>Branches:</strong> <span id="branches"> <?= implode(', ', $branches) ?: 'N/A' ?> </span></li>
                        <li><strong>Contributors:</strong> 
                            <div id="contributors-list">
                                <?php if (!empty($contributors)): ?>
                                    <?php foreach ($contributors as $contributor): ?>
                                        <div class="contributor-item">
                                            <img src="<?= $contributor['avatar_url'] ?>" alt="<?= $contributor['login'] ?>" class="contributor-avatar">
                                            <a href="<?= $contributor['html_url'] ?>" target="_blank" class="contributor-link">
                                                <?= $contributor['login'] ?>
                                            </a>
                                            <span class="contribution-count">(<?= $contributor['contributions'] ?> contributions)</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span>No contributors found</span>
                                <?php endif; ?>
                            </div>
                        </li>
                        <li><strong>Last Commit:</strong> 
                            <span id="commits"> <?= $latestCommit['name'] ?? 'N/A' ?> on <?= $latestCommit['date'] ?? 'N/A' ?> </span>
                        </li>
                        <li><strong>Merges:</strong> <span id="merges"> <?= $mergesCount ?? 'N/A' ?> </span></li>
                        <li><strong>Clones:</strong> <span id="clones"> <?= $clonesCount ?> </span></li>
                        <li><strong>Languages Used:</strong> 
                            <span id="languages"> <?= implode(', ', array_keys($languages)) ?: 'N/A' ?> </span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="graphs-section" style="max-width:1200px;margin:30px auto 0 auto;">
            <div style="background:#fff;padding:40px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.08);margin-bottom:30px;border:1px solid #e0e0e0;">
                <h3 style="color:#ff5b00;margin-bottom:20px;font-weight:600;"><i class="fas fa-chart-pie"></i> Language Distribution</h3>
                <div style="max-width:400px;max-height:400px;margin:0 auto;">
                    <canvas id="languageChart"></canvas>
                </div>
            </div>
            <div style="background:#fff;padding:40px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.08);border:1px solid #e0e0e0;">
                <h3 style="color:#ff5b00;margin-bottom:20px;font-weight:600;"><i class="fas fa-chart-line"></i> Commit Activity by Contributor</h3>
                <div style="max-width:700px;max-height:400px;margin:0 auto;">
                    <canvas id="commitChart"></canvas>
                </div>
                <div id="commit-details">
                    <?php foreach ($commitsByContributor as $contributor => $commits): ?>
                        <div class="contributor-commits">
                            <h4><?= htmlspecialchars($contributor) ?> (<?= count($commits) ?> commits)</h4>
                            <div class="recent-commits">
                                <?php foreach (array_slice($commits, 0, 5) as $commit): ?>
                                    <div class="commit-item">
                                        <span class="commit-date"><?= date('M j, Y', strtotime($commit['date'])) ?></span>
                                        <span class="commit-message"><?= htmlspecialchars(substr($commit['message'], 0, 80)) ?><?= strlen($commit['message']) > 80 ? '...' : '' ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if (!empty($readmeContent)): ?>
        <section id="readme-section">
            <h3><i class="fas fa-file-alt"></i> README</h3>
            <div class="readme-content">
                <?= $readmeContent ?>
            </div>
        </section>
        <?php endif; ?>

    </main>
    <footer>
        <div class="social-icons">
            <a href="https://www.instagram.com/brickmmo/" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://www.youtube.com/channel/UCJJPeP10HxC1qwX_paoHepQ" target="_blank"><i class="fab fa-youtube"></i></a>
            <a href="https://x.com/brickmmo" target="_blank"><i class="fab fa-x"></i></a>
            <a href="https://github.com/BrickMMO" target="_blank"><i class="fab fa-github"></i></a>
            <a href="https://www.tiktok.com/@brickmmo" target="_blank"><i class="fab fa-tiktok"></i></a>
        </div>
        <p>&copy; BrickMMO, 2025. All rights reserved.</p>
        <p>LEGO, the LEGO logo and the Minifigure are trademarks of the LEGO Group.</p>
    </footer>

    <script>
    const languageData = <?= json_encode($languages) ?>;
    const commitData = <?= json_encode($commitsByContributor) ?>;

    function createLanguageChart() {
        const ctx = document.getElementById('languageChart').getContext('2d');
        if (!ctx) return;
        const labels = Object.keys(languageData);
        const data = Object.values(languageData);
        const total = data.reduce((a, b) => a + b, 0);
        const colors = [
            '#ff5b00', '#ff8c42', '#ffa366', '#ffba8a', '#ffd1ae',
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
                maintainAspectRatio: true,
                aspectRatio: 1,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            padding: 12,
                            font: { size: 11 }
                        }
                    },
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

    function createCommitChart() {
        const ctx = document.getElementById('commitChart').getContext('2d');
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
                    backgroundColor: '#ff5b00',
                    borderColor: '#ea5302',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: { 
                    legend: { display: false }
                }
            }
        });
    }

    window.addEventListener('DOMContentLoaded', function() {
        createLanguageChart();
        createCommitChart();
    });
    </script>
</body>
</html>
