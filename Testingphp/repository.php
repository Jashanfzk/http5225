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
    
    // Get top contributors with additional information
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

// GitHub API integration
$headers = ["User-Agent: BrickMMO-Timesheets"];

// Create cache directory if it doesn't exist
$cache_dir = __DIR__ . '/cache';
if (!is_dir($cache_dir)) {
    if (!mkdir($cache_dir, 0755, true)) {
        error_log("GitHub API Cache: Failed to create cache directory");
    }
}

function fetchGitHubData($url, $headers) {
    global $cache_dir;
    $debug = true;
    
    // Create a cache key from the URL
    $cache_key = md5($url);
    $cache_file = $cache_dir . '/repo_' . $cache_key . '.json';
    
    // Check if we have a valid cache
    if (file_exists($cache_file)) {
        $cache_time = filemtime($cache_file);
        if (time() - $cache_time < 15 * 60) { // 15 minutes cache
            if ($debug) {
                error_log("GitHub API Debug - Using cached data for: " . basename($url));
            }
            $cached_data = file_get_contents($cache_file);
            $data = json_decode($cached_data, true);
            if (is_array($data) || is_object($data)) {
                return $data;
            }
        }
    }
    
    // If no cache or invalid cache, fetch from API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for cURL errors or invalid response
    if ($response === false || $httpCode !== 200) {
        error_log("GitHub API Error for URL: $url - HTTP Code: $httpCode");
        if ($response !== false) {
            error_log("Response: " . substr($response, 0, 100));
        }
        return [];
    }
    
    // Decode JSON response
    $data = json_decode($response, true);
    
    // Verify data is an array or object
    if (!is_array($data) && !is_object($data)) {
        error_log("Invalid JSON response from GitHub API: " . substr($response, 0, 100));
        return [];
    }
    
    return $data;
}

// Fetch GitHub data
$repoUrl = "https://api.github.com/repos/$owner/$repoName";
$commitsUrl = "$repoUrl/commits";
$contributorsUrl = "$repoUrl/contributors";
$branchesUrl = "$repoUrl/branches";
$languagesUrl = "$repoUrl/languages";
$issuesUrl = "$repoUrl/issues?state=open";
$forksUrl = "$repoUrl/forks";
$mergesUrl = "$repoUrl/pulls?state=closed";
$clonesUrl = "$repoUrl/traffic/clones";
$readmeUrl = "$repoUrl/readme";

// Add authentication to headers if available
if (defined('GITHUB_TOKEN') && !empty(GITHUB_TOKEN)) {
    $headers[] = "Authorization: token " . GITHUB_TOKEN;
}

// Add query parameters for OAuth app if token not available
$authQuery = "";
if (!defined('GITHUB_TOKEN') || empty(GITHUB_TOKEN)) {
    if (defined('GITHUB_CLIENT_ID') && !empty(GITHUB_CLIENT_ID)) {
        $authQuery = "?client_id=" . GITHUB_CLIENT_ID;
        
        if (defined('GITHUB_CLIENT_SECRET') && !empty(GITHUB_CLIENT_SECRET)) {
            $authQuery .= "&client_secret=" . GITHUB_CLIENT_SECRET;
        }
    }
}

// Apply auth query to all URLs if needed
if (!empty($authQuery)) {
    $repoUrl .= $authQuery;
    $commitsUrl .= $authQuery;
    $contributorsUrl .= $authQuery;
    $branchesUrl .= $authQuery;
    $languagesUrl .= $authQuery;
    $issuesUrl .= strpos($issuesUrl, '?') ? '&' . substr($authQuery, 1) : $authQuery;
    $forksUrl .= $authQuery;
    $mergesUrl .= strpos($mergesUrl, '?') ? '&' . substr($authQuery, 1) : $authQuery;
    $clonesUrl .= $authQuery;
    $readmeUrl .= $authQuery;
}

// Fetch data from GitHub API
$repoData = fetchGitHubData($repoUrl, $headers);
$commitsData = fetchGitHubData($commitsUrl, $headers);
$githubContributors = fetchGitHubData($contributorsUrl, $headers);
$branchesData = fetchGitHubData($branchesUrl, $headers);
$languagesData = fetchGitHubData($languagesUrl, $headers);
$issuesData = fetchGitHubData($issuesUrl, $headers);
$forksData = fetchGitHubData($forksUrl, $headers);
$mergesData = fetchGitHubData($mergesUrl, $headers);
$clonesData = fetchGitHubData($clonesUrl, $headers);
$readmeData = fetchGitHubData($readmeUrl, $headers);

// Process data
$totalCommits = is_array($commitsData) ? count($commitsData) : 0;
$totalBranches = is_array($branchesData) ? count($branchesData) : 0;
$openIssues = is_array($issuesData) ? count($issuesData) : 0;
$githubContributorCount = is_array($githubContributors) ? count($githubContributors) : 0;
$forksCount = is_array($forksData) ? count($forksData) : 0;
$mergesCount = is_array($mergesData) ? count($mergesData) : 0;
$clonesCount = $clonesData['count'] ?? 'N/A';

// Process README content
$readmeContent = '';
if (!empty($readmeData) && isset($readmeData['content']) && is_string($readmeData['content'])) {
    try {
        $readmeContent = base64_decode($readmeData['content'], true);
        
        // Verify base64 decode was successful
        if ($readmeContent === false) {
            $readmeContent = 'README content could not be decoded.';
        } else {
            // Convert markdown to basic HTML (simple conversion)
            $readmeContent = htmlspecialchars($readmeContent);
            $readmeContent = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $readmeContent);
            $readmeContent = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $readmeContent);
            $readmeContent = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $readmeContent);
            $readmeContent = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $readmeContent);
            
            // Add more markdown conversions
            $readmeContent = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $readmeContent); // Italics
            $readmeContent = preg_replace('/`(.+?)`/', '<code>$1</code>', $readmeContent); // Inline code
            
            // Convert URLs to links
            $readmeContent = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2" target="_blank">$1</a>', $readmeContent);
            
            // Convert new lines to <br> tags
            $readmeContent = nl2br($readmeContent);
        }
    } catch (Exception $e) {
        error_log("Error processing README: " . $e->getMessage());
        $readmeContent = 'Error processing README content.';
    }
} else {
    $readmeContent = 'No README content available.';
}

// Process issues data with labels
$bugIssuesCount = 0;
$goodFirstIssueCount = 0;

if (!empty($issuesData) && is_array($issuesData)) {
    foreach ($issuesData as $issue) {
        if (!is_array($issue)) {
            continue;
        }
        
        $labels = isset($issue['labels']) && is_array($issue['labels']) ? $issue['labels'] : [];
        
        foreach ($labels as $label) {
            if (!is_array($label) || !isset($label['name']) || !is_string($label['name'])) {
                continue;
            }
            
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

// Process commit activity by contributor for chart
$commitsByContributor = [];
if (!empty($commitsData) && is_array($commitsData)) {
    foreach ($commitsData as $commit) {
        // Ensure commit has the expected structure
        if (!isset($commit['commit']) || !is_array($commit['commit'])) {
            continue;
        }
        
        // Safely extract author name with fallbacks
        $author = isset($commit['commit']['author']['name']) 
            ? $commit['commit']['author']['name'] 
            : (isset($commit['author']['login']) ? $commit['author']['login'] : 'Unknown');
        
        // Initialize array for this contributor if needed
        if (!isset($commitsByContributor[$author])) {
            $commitsByContributor[$author] = [];
        }
        
        // Safely extract commit details with fallbacks
        $commitsByContributor[$author][] = [
            'date' => $commit['commit']['author']['date'] ?? date('Y-m-d'),
            'message' => $commit['commit']['message'] ?? 'No message',
            'sha' => $commit['sha'] ?? 'unknown'
        ];
    }
}

// Process languages
$languages = [];
$totalBytes = 0;

// Ensure we have valid language data
if (!empty($languagesData) && is_array($languagesData)) {
    // First calculate total bytes
    foreach ($languagesData as $lang => $bytes) {
        if (is_numeric($bytes)) {
            $totalBytes += $bytes;
        }
    }
    
    // Then calculate percentages and build language array
    foreach ($languagesData as $lang => $bytes) {
        if (!is_string($lang) || !is_numeric($bytes)) {
            continue;
        }
        
        $percentage = $totalBytes > 0 ? ($bytes / $totalBytes) * 100 : 0;
        $languages[] = [
            'name' => $lang,
            'percentage' => $percentage
        ];
    }
}

// If no languages were found, add a placeholder
if (empty($languages)) {
    $languages[] = [
        'name' => 'Unknown',
        'percentage' => 100
    ];
    // Sort by percentage
    usort($languages, function($a, $b) {
        return $b['percentage'] <=> $a['percentage'];
    });
}

// Get recent commits (last 5)
$recentCommits = [];
if (is_array($commitsData)) {
    $recentCommits = array_slice($commitsData, 0, 5);
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
        
        /* Chart sections */
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
        
        /* README section */
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
        
        /* Commit by contributor section */
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
        
        /* Responsive adjustments */
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
    <!-- Header -->
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
        <!-- Header -->
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
        
        <!-- Statistics Cards -->
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
        
        <!-- Repository Statistics Section -->
        <div class="repo-stats-section">
            <h2 class="section-title">Repository Statistics</h2>
            <div class="repo-stats-grid">
                <div class="repo-stats-card">
                    <div class="stats-icon"><i class="fas fa-code-branch"></i></div>
                    <div class="stats-details">
                        <h3 class="stats-title">Commits</h3>
                        <div class="stats-value"><?= number_format($totalCommits) ?></div>
                        <p class="stats-description">Total code commits made to this repository</p>
                    </div>
                </div>
                <div class="repo-stats-card">
                    <div class="stats-icon"><i class="fas fa-users"></i></div>
                    <div class="stats-details">
                        <h3 class="stats-title">Contributors</h3>
                        <div class="stats-value"><?= number_format($githubContributorCount) ?></div>
                        <p class="stats-description">Developers who contributed code</p>
                    </div>
                </div>
                <div class="repo-stats-card">
                    <div class="stats-icon"><i class="fas fa-code"></i></div>
                    <div class="stats-details">
                        <h3 class="stats-title">Languages</h3>
                        <div class="stats-value"><?= is_array($languagesData) ? count($languagesData) : 0 ?></div>
                        <p class="stats-description">Programming languages used</p>
                    </div>
                </div>
                <div class="repo-stats-card">
                    <div class="stats-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="stats-details">
                        <h3 class="stats-title">Issues</h3>
                        <div class="stats-value"><?= number_format($openIssues) ?></div>
                        <p class="stats-description">Open GitHub issues</p>
                    </div>
                </div>
                <div class="repo-stats-card">
                    <div class="stats-icon"><i class="fas fa-code-branch"></i></div>
                    <div class="stats-details">
                        <h3 class="stats-title">Branches</h3>
                        <div class="stats-value"><?= number_format($totalBranches) ?></div>
                        <p class="stats-description">Active branches in repository</p>
                    </div>
                </div>
                <div class="repo-stats-card">
                    <div class="stats-icon"><i class="fas fa-clock"></i></div>
                    <div class="stats-details">
                        <h3 class="stats-title">Hours Logged</h3>
                        <div class="stats-value"><?= number_format((float)($stats['total_hours'] ?? 0), 1) ?></div>
                        <p class="stats-description">Total time tracked on this project</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Contributors Section -->
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
        
        <!-- GitHub Statistics -->
        <h2 class="section-title" style="margin: 3rem 0 1.5rem 0;">GitHub Statistics</h2>
        <div class="github-stats-grid">
            <div class="github-stat-card">
                <div class="github-stat-label">Commits</div>
                <div class="github-stat-value"><?= $totalCommits ?></div>
            </div>
            <div class="github-stat-card">
                <div class="github-stat-label">Branches</div>
                <div class="github-stat-value"><?= $totalBranches ?></div>
            </div>
            <div class="github-stat-card">
                <div class="github-stat-label">Open Issues</div>
                <div class="github-stat-value"><?= $openIssues ?></div>
            </div>
            <div class="github-stat-card">
                <div class="github-stat-label">GitHub Contributors</div>
                <div class="github-stat-value"><?= $githubContributorCount ?></div>
            </div>
            <div class="github-stat-card">
                <div class="github-stat-label">Forks</div>
                <div class="github-stat-value"><?= $forksCount ?></div>
            </div>
            <div class="github-stat-card">
                <div class="github-stat-label">Merged PRs</div>
                <div class="github-stat-value"><?= $mergesCount ?></div>
            </div>
            <?php if ($bugIssuesCount > 0): ?>
            <div class="github-stat-card">
                <div class="github-stat-label">Bug Issues</div>
                <div class="github-stat-value"><?= $bugIssuesCount ?></div>
            </div>
            <?php endif; ?>
            <?php if ($goodFirstIssueCount > 0): ?>
            <div class="github-stat-card">
                <div class="github-stat-label">Good First Issues</div>
                <div class="github-stat-value"><?= $goodFirstIssueCount ?></div>
            </div>
            <?php endif; ?>
            <?php if ($clonesCount !== 'N/A'): ?>
            <div class="github-stat-card">
                <div class="github-stat-label">Recent Clones</div>
                <div class="github-stat-value"><?= $clonesCount ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Languages Section -->
        <?php if (!empty($languages)): ?>
            <div class="content-section">
                <h2 class="section-title">Languages Used</h2>
                <div class="language-bar">
                    <?php 
                    $colors = ['#DD5A3A', '#3498DB', '#2ECC71', '#F39C12', '#9B59B6', '#E74C3C', '#1ABC9C', '#34495E'];
                    foreach ($languages as $index => $lang): 
                        if ($lang['percentage'] > 1): // Only show languages > 1%
                    ?>
                        <div class="language-segment" 
                             style="width: <?= $lang['percentage'] ?>%; background: <?= $colors[$index % count($colors)] ?>"
                             title="<?= $lang['name'] ?>: <?= number_format($lang['percentage'], 1) ?>%">
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                <div class="language-list">
                    <?php foreach ($languages as $index => $lang): ?>
                        <div class="language-item">
                            <div class="language-dot" style="background: <?= $colors[$index % count($colors)] ?>"></div>
                            <span class="language-name"><?= htmlspecialchars($lang['name']) ?></span>
                            <span class="language-percent"><?= number_format($lang['percentage'], 1) ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Recent Commits Section -->
        <?php if (!empty($recentCommits)): ?>
            <div class="content-section">
                <h2 class="section-title">Recent Commits</h2>
                <?php foreach ($recentCommits as $commit): ?>
                    <div class="commit-item">
                        <div class="commit-message">
                            <?= htmlspecialchars(substr($commit['commit']['message'], 0, 100)) ?><?= strlen($commit['commit']['message']) > 100 ? '...' : '' ?>
                        </div>
                        <div class="commit-meta">
                            <strong><?= htmlspecialchars($commit['commit']['author']['name'] ?? 'Unknown') ?></strong>
                            committed <?= date('M j, Y', strtotime($commit['commit']['author']['date'])) ?>
                            <span class="commit-sha"><?= substr($commit['sha'], 0, 7) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Branches Section -->
        <?php if (!empty($branchesData)): ?>
            <div class="content-section">
                <h2 class="section-title">Branches (<?= count($branchesData) ?>)</h2>
                <div class="branch-list">
                    <?php foreach ($branchesData as $branch): ?>
                        <div class="branch-tag">
                            <?= htmlspecialchars($branch['name']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- GitHub Contributors Section -->
        <?php if (!empty($githubContributors)): ?>
            <div class="content-section">
                <h2 class="section-title">GitHub Contributors</h2>
                <div class="contributor-list">
                    <?php foreach (array_slice($githubContributors, 0, 10) as $contributor): ?>
                        <div class="contributor-row">
                            <div class="contributor-info">
                                <img src="<?= htmlspecialchars($contributor['avatar_url']) ?>" 
                                     alt="<?= htmlspecialchars($contributor['login']) ?>" 
                                     class="contributor-avatar"/>
                                <a href="<?= htmlspecialchars($contributor['html_url']) ?>" 
                                   target="_blank"
                                   class="contributor-name">
                                    <?= htmlspecialchars($contributor['login']) ?>
                                </a>
                            </div>
                            <span class="contributor-hours"><?= $contributor['contributions'] ?> contributions</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Data Visualization Charts -->
        <?php if (!empty($languages) || !empty($commitsByContributor)): ?>
            <!-- Language Distribution Chart -->
            <?php if (!empty($languages)): ?>
                <div class="chart-section">
                    <h2 class="chart-title"><i class="fas fa-chart-pie"></i> Language Distribution</h2>
                    <div class="chart-container">
                        <canvas id="languageChart"></canvas>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Commit Activity Chart -->
            <?php if (!empty($commitsByContributor)): ?>
                <div class="chart-section">
                    <h2 class="chart-title"><i class="fas fa-chart-bar"></i> Commit Activity by Contributor</h2>
                    <div class="chart-container">
                        <canvas id="commitChart"></canvas>
                    </div>
                    
                    <!-- Detailed Commit History by Contributor -->
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
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- README Section -->
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
    // Create charts if data exists
    <?php if (!empty($languages)): ?>
    // Language Distribution Pie Chart
    const languageData = <?= json_encode($languagesData) ?>;
    function createLanguageChart() {
        const ctx = document.getElementById('languageChart').getContext('2d');
        if (!ctx) return;
        const labels = Object.keys(languageData);
        const data = Object.values(languageData);
        const total = data.reduce((a, b) => a + b, 0);
        const colors = [
            '#DD5A3A', '#FF8C42', '#FFB347', '#FFD1AE', '#E8D5CF',
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
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed;
                                let percent = total ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} bytes (${percent}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    <?php if (!empty($commitsByContributor)): ?>
    // Commit Activity Bar Chart
    const commitData = <?= json_encode($commitsByContributor) ?>;
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
                    backgroundColor: '#DD5A3A',
                    borderColor: '#C14D30',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { 
                            stepSize: 1,
                            font: {
                                family: 'Inter',
                                size: 12
                            }
                        },
                        grid: {
                            color: '#E8D5CF'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Inter',
                                size: 12
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1A1A1A',
                        titleFont: {
                            family: 'Inter',
                            size: 14
                        },
                        bodyFont: {
                            family: 'Inter',
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 6
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    // Load charts when page is ready
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($languages)): ?>
        createLanguageChart();
        <?php endif; ?>
        
        <?php if (!empty($commitsByContributor)): ?>
        createCommitChart();
        <?php endif; ?>
        
        // Initialize contributor sorting
        initContributorSorting();
    });
    
    // Convert PHP contributors array to JavaScript
    const contributorsData = <?php echo json_encode($top_contributors); ?>;
    
    function initContributorSorting() {
        const sortDropdown = document.getElementById('contributor-sort');
        if (sortDropdown) {
            // Initial sort
            renderContributors(contributorsData, 'hours');
            
            // Add event listener for sort changes
            sortDropdown.addEventListener('change', function() {
                renderContributors(contributorsData, this.value);
            });
        }
    }
    
    function renderContributors(contributors, sortBy) {
        const container = document.getElementById('contributor-cards');
        if (!container) return;
        
        // Sort the contributors based on the selected option
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
        
        // Clear container
        container.innerHTML = '';
        
        // Rebuild the contributor cards
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
