<?php
/**
 * BrickMMO Timesheets - Home Page
 * Public repository listing with GitHub API integration and database statistics
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Pagination setup
$perPage = 9;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Get search parameters
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterName = isset($_GET['filter_name']) ? true : false;
$filterLanguage = isset($_GET['filter_language']) ? true : false;
$filterDescription = isset($_GET['filter_description']) ? true : false;

// If no filters are set, default to name and language
if (!$filterName && !$filterLanguage && !$filterDescription) {
    $filterName = true;
    $filterLanguage = true;
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// GitHub API integration - fetch BrickMMO repositories
function fetchAllGitHubRepos() {
    $url = "https://api.github.com/users/brickmmo/repos";
    $headers = [
        "User-Agent: BrickMMO-Timesheets",
        "Accept: application/vnd.github.v3+json"
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $repos = json_decode($response, true);
        if (is_array($repos)) {
            // Sort repositories by name
            usort($repos, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            return $repos;
        }
    }
    
    // If API call fails, return demo repositories
    return [
        [
            'name' => 'Demo Repository',
            'description' => 'GitHub API connection issue. Please try again later.',
            'html_url' => 'https://github.com/BrickMMO',
            'language' => 'N/A',
            'is_demo' => true
        ]
    ];
    
    // Direct authentication with GitHub OAuth client credentials
    // This is a basic fallback method using client ID as query parameter
    $auth_query = "";
    if (defined('GITHUB_CLIENT_ID') && !empty(GITHUB_CLIENT_ID)) {
        $auth_query = "client_id=" . GITHUB_CLIENT_ID;
        
        // Add client secret if available for higher rate limits
        if (defined('GITHUB_CLIENT_SECRET') && !empty(GITHUB_CLIENT_SECRET)) {
            $auth_query .= "&client_secret=" . GITHUB_CLIENT_SECRET;
        }
    }
    
    // Personal access token has higher priority if available
    if (defined('GITHUB_TOKEN') && !empty(GITHUB_TOKEN)) {
        $headers[] = "Authorization: token " . GITHUB_TOKEN;
        // If using token, don't use client credentials
        $auth_query = "";
    }
    
    // First, try to get a single repository to debug API access
    $ch = curl_init();
    $org_url = "https://api.github.com/orgs/$organization";
    if (!empty($auth_query)) {
        $org_url .= "?" . $auth_query;
    }
    curl_setopt($ch, CURLOPT_URL, $org_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Verify SSL certificate
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Set timeout to 10 seconds
    $orgResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($debug) {
        error_log("GitHub API Debug - Organization check: $organization, Status: $httpCode");
        error_log("GitHub API Debug - Response: " . substr($orgResponse, 0, 200) . "...");
    }
    
    // If we can't access the organization, try the alternative public repositories endpoint
    if ($httpCode != 200) {
        if ($debug) {
            error_log("GitHub API Debug - Failed to access organization via orgs endpoint, trying public repos");
        }
        
        // Try alternate approach using the users endpoint instead of orgs
        $alt_ch = curl_init();
        $alt_url = "https://api.github.com/users/$organization/repos?per_page=100&sort=updated";
        if (!empty($auth_query)) {
            $alt_url .= "&" . $auth_query;
        }
        
        curl_setopt($alt_ch, CURLOPT_URL, $alt_url);
        curl_setopt($alt_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($alt_ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($alt_ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($alt_ch, CURLOPT_TIMEOUT, 10);
        $alt_response = curl_exec($alt_ch);
        $alt_httpCode = curl_getinfo($alt_ch, CURLINFO_HTTP_CODE);
        curl_close($alt_ch);
        
        if ($debug) {
            error_log("GitHub API Debug - Alternative endpoint status: $alt_httpCode");
            error_log("GitHub API Debug - First 200 chars: " . substr($alt_response, 0, 200));
        }
        
        // If alternative endpoint works, use it
        if ($alt_httpCode == 200) {
            $alt_repos = json_decode($alt_response, true);
            if (is_array($alt_repos) && !empty($alt_repos)) {
                return $alt_repos;
            }
        }
        
        // If both approaches fail, return demo repositories
        if ($debug) {
            error_log("GitHub API Debug - Both approaches failed, using demo data");
        }
        
        return [
            [
                'name' => 'demo-repository',
                'full_name' => "$organization/demo-repository",
                'description' => 'Demo repository for testing. GitHub API access issue detected.',
                'html_url' => "https://github.com/$organization/demo-repository",
                'language' => 'PHP',
                'id' => 0,
                'is_demo' => true
            ],
            [
                'name' => 'another-demo-repo',
                'full_name' => "$organization/another-demo-repo",
                'description' => 'Second demo repository for testing. Please check GitHub API access.',
                'html_url' => "https://github.com/$organization/another-demo-repo",
                'language' => 'JavaScript',
                'id' => 1,
                'is_demo' => true
            ]
        ];
    }
    
    // If organization check is successful, proceed with fetching repos
    do {
        $ch = curl_init();
        $repos_url = "https://api.github.com/orgs/$organization/repos?per_page=100&page=$page";
        if (!empty($auth_query)) {
            $repos_url .= "&" . $auth_query;
        }
        
        curl_setopt($ch, CURLOPT_URL, $repos_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($debug && $page === 1) {
            error_log("GitHub API Debug - Repos fetch status: $httpCode");
            error_log("GitHub API Debug - First 200 chars: " . substr($response, 0, 200));
            
            // Check for rate limit info
            $rate_limit = curl_getinfo($ch, CURLINFO_HEADER_OUT);
            $remaining = @$http_response_header['X-RateLimit-Remaining'] ?? 'Unknown';
            $limit = @$http_response_header['X-RateLimit-Limit'] ?? 'Unknown';
            error_log("GitHub API Debug - Rate limits: $remaining/$limit remaining");
        }
        
        // Check for cURL errors
        if ($response === false) {
            error_log("cURL Error: " . curl_error($ch));
            curl_close($ch);
            return [];
        }
        
        curl_close($ch);
        
        // Decode JSON response
        $repos = json_decode($response, true);
        
        // Check if response is valid and an array
        if (!is_array($repos)) {
            error_log("GitHub API Error: Invalid response format - " . substr($response, 0, 200));
            return [];
        }
        
        if (empty($repos)) {
            break;
        }
        
        $allRepos = array_merge($allRepos, $repos);
        $page++;
        
    } while (count($repos) === 100);
    
    if ($debug) {
        error_log("GitHub API Debug - Total repos fetched: " . count($allRepos));
    }
    
    // Save to cache if we have repositories
    if (!empty($allRepos)) {
        if ($debug) {
            error_log("GitHub API Debug - Saving repositories to cache");
        }
        file_put_contents($cache_file, json_encode($allRepos));
    }
    
    return $allRepos;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if GitHub organization is defined
    $organization = defined('GITHUB_ORG') && !empty(GITHUB_ORG) ? GITHUB_ORG : 'BrickMMO';
    
    // Feedback for debugging
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        echo "<div style='padding: 10px; background: #fffae6; border: 1px solid #e6b800; margin-bottom: 20px;'>";
        echo "<p><strong>GitHub API Debug Mode:</strong> Attempting to connect to $organization organization</p>";
        echo "<p>For detailed API diagnostics, visit <a href='test-github-api.php'>GitHub API Test Page</a></p>";
        echo "</div>";
    }
    
    // Fetch all repositories from GitHub API
    $githubRepos = fetchAllGitHubRepos($organization);
    
    // Check if we got valid repositories
    if (empty($githubRepos)) {
        error_log("No GitHub repositories returned or error occurred - falling back to database only");
        $githubRepos = []; // Ensure it's an empty array even if null was returned
        
        // If GitHub API fails, we'll create repository objects from database data only
        if (!empty($dbRepos)) {
            foreach ($dbRepos as $dbRepo) {
                // Only include active repositories in fallback mode
                if ($dbRepo['is_active']) {
                    $githubRepos[] = [
                        'name' => $dbRepo['name'],
                        'full_name' => 'BrickMMO/' . $dbRepo['name'],
                        'description' => $dbRepo['description'] ?? 'No description available',
                        'html_url' => 'https://github.com/BrickMMO/' . $dbRepo['name'],
                        'language' => $dbRepo['primary_language'] ?? 'N/A',
                        'id' => $dbRepo['id'],
                        'is_fallback' => true // Mark as fallback data
                    ];
                }
            }
        }
        
        // If still no repositories, create demo repositories to avoid empty page
        if (empty($githubRepos)) {
            error_log("No repositories found in database either - using demo data");
            $githubRepos = [
                [
                    'name' => 'smart-City-Apps',
                    'full_name' => 'BrickMMO/smart-City-Apps',
                    'description' => 'BrickMMO Smart City mobile application collection. Currently showing demo data due to GitHub API connectivity issues.',
                    'html_url' => 'https://github.com/BrickMMO/smart-city-apps',
                    'language' => 'Swift',
                    'id' => 0,
                    'contributor_count' => 5,
                    'entry_count' => 25,
                    'total_hours' => 80,
                    'is_demo' => true,
                    'created_at' => '2023-09-15T10:30:00Z',
                    'updated_at' => '2025-09-01T14:22:10Z'
                ],
                [
                    'name' => 'display-apps',
                    'full_name' => 'BrickMMO/display-apps',
                    'description' => 'Applications for BrickMMO interactive displays. Demo placeholder while resolving API connection issues.',
                    'html_url' => 'https://github.com/BrickMMO/display-apps',
                    'language' => 'JavaScript',
                    'id' => 1,
                    'contributor_count' => 3,
                    'entry_count' => 18,
                    'total_hours' => 45,
                    'is_demo' => true,
                    'created_at' => '2023-11-10T08:15:22Z',
                    'updated_at' => '2025-08-20T16:45:30Z'
                ],
                [
                    'name' => 'timesheet-application',
                    'full_name' => 'BrickMMO/timesheet-application',
                    'description' => 'BrickMMO contributor time tracking system. Demo repository while we resolve API connectivity.',
                    'html_url' => 'https://github.com/BrickMMO/timesheet-application',
                    'language' => 'PHP',
                    'id' => 2,
                    'contributor_count' => 8,
                    'entry_count' => 42,
                    'total_hours' => 120,
                    'is_demo' => true,
                    'created_at' => '2024-01-05T09:20:15Z',
                    'updated_at' => '2025-10-01T11:30:45Z'
                ],
                [
                    'name' => 'web-dashboard',
                    'full_name' => 'BrickMMO/web-dashboard',
                    'description' => 'Web-based dashboard for BrickMMO system management and monitoring. Showing sample data.',
                    'html_url' => 'https://github.com/BrickMMO/web-dashboard',
                    'language' => 'TypeScript',
                    'id' => 3,
                    'contributor_count' => 6,
                    'entry_count' => 30,
                    'total_hours' => 95,
                    'is_demo' => true,
                    'created_at' => '2024-03-18T13:40:22Z',
                    'updated_at' => '2025-09-15T10:20:35Z'
                ],
                [
                    'name' => 'LEGO-api',
                    'full_name' => 'BrickMMO/LEGO-api',
                    'description' => 'API for interfacing with LEGO hardware components. Demo repository - check GitHub API connection.',
                    'html_url' => 'https://github.com/BrickMMO/LEGO-api',
                    'language' => 'Python',
                    'id' => 4,
                    'contributor_count' => 4,
                    'entry_count' => 22,
                    'total_hours' => 68,
                    'is_demo' => true,
                    'created_at' => '2024-05-22T15:10:05Z',
                    'updated_at' => '2025-08-30T09:15:40Z'
                ],
                [
                    'name' => 'documentation',
                    'full_name' => 'BrickMMO/documentation',
                    'description' => 'Comprehensive documentation for all BrickMMO systems and applications. Demo data shown.',
                    'html_url' => 'https://github.com/BrickMMO/documentation',
                    'language' => 'Markdown',
                    'id' => 5,
                    'contributor_count' => 10,
                    'entry_count' => 50,
                    'total_hours' => 150,
                    'is_demo' => true,
                    'created_at' => '2023-08-10T11:25:30Z',
                    'updated_at' => '2025-10-10T14:50:20Z'
                ]
            ];
        }
    }
    
    // Also get repositories and statistics from database
    $db_stmt = $db->prepare("
        SELECT 
            a.*,
            COUNT(DISTINCT h.user_id) as contributor_count,
            COUNT(h.id) as entry_count,
            SUM(h.duration) as total_hours
        FROM applications a
        LEFT JOIN hours h ON a.id = h.application_id
        GROUP BY a.id
    ");
    $db_stmt->execute();
    $dbRepos = $db_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert database repositories to a lookup array by name
    $dbReposByName = [];
    foreach ($dbRepos as $repo) {
        $dbReposByName[$repo['name']] = $repo;
    }
    
    // Combine GitHub data with database statistics
    $repositories = [];
    foreach ($githubRepos as $repo) {
        // Validate repo data - ensure all expected keys exist
        if (!isset($repo['name']) || !is_array($repo)) {
            continue; // Skip invalid repo entries
        }
        
        $repoData = [
            'name' => $repo['name'],
            'full_name' => $repo['full_name'] ?? $repo['name'],
            'description' => $repo['description'] ?? 'No description available',
            'html_url' => $repo['html_url'] ?? '#',
            'language' => $repo['language'] ?? 'N/A',
            'created_at' => $repo['created_at'] ?? '',
            'updated_at' => $repo['updated_at'] ?? '',
            'github_id' => $repo['id'] ?? 0,
            // Default values if not in database
            'contributor_count' => 0,
            'entry_count' => 0,
            'total_hours' => 0
        ];
        
        // Add database statistics if available
        if (isset($dbReposByName[$repo['name']])) {
            $dbRepo = $dbReposByName[$repo['name']];
            $repoData['contributor_count'] = $dbRepo['contributor_count'] ?? 0;
            $repoData['entry_count'] = $dbRepo['entry_count'] ?? 0;
            $repoData['total_hours'] = $dbRepo['total_hours'] ?? 0;
            $repoData['id'] = $dbRepo['id']; // Database ID for linking
            $repoData['is_active'] = $dbRepo['is_active'];
        }
        
        $repositories[] = $repoData;
    }
    
    // Filter repositories based on search term
    if (!empty($searchTerm)) {
        $searchTermLower = strtolower($searchTerm);
        $repositories = array_filter($repositories, function($repo) use ($searchTermLower, $filterName, $filterLanguage, $filterDescription) {
            $matches = false;
            
            if ($filterName && stripos($repo['name'], $searchTermLower) !== false) {
                $matches = true;
            }
            
            if ($filterLanguage && isset($repo['language']) && stripos($repo['language'], $searchTermLower) !== false) {
                $matches = true;
            }
            
            if ($filterDescription && isset($repo['description']) && stripos($repo['description'], $searchTermLower) !== false) {
                $matches = true;
            }
            
            return $matches;
        });
        
        // Re-index array
        $repositories = array_values($repositories);
    }
    
    // Sort repositories by name
    usort($repositories, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    // Calculate pagination
    $totalRepos = count($repositories);
    $totalPages = (int) ceil($totalRepos / $perPage);
    if ($totalPages < 1) { $totalPages = 1; }
    $currentPage = min($currentPage, $totalPages);
    
    // Get repositories for current page
    $offset = ($currentPage - 1) * $perPage;
    $repositories = array_slice($repositories, $offset, $perPage);
    
} catch (Exception $e) {
    error_log("Index page error: " . $e->getMessage());
    $repositories = [];
    $totalRepos = 0;
    $totalPages = 1;
}

// Function to highlight search terms
function highlightSearchTerm($text, $searchTerm) {
    if (empty($searchTerm) || empty($text)) {
        return htmlspecialchars($text);
    }
    
    $highlighted = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<mark>$1</mark>', htmlspecialchars($text));
    return $highlighted;
}

// Build query string for pagination
function buildQueryString($params, $exclude = []) {
    $filtered = [];
    foreach ($params as $k => $v) {
        if (in_array($k, $exclude, true)) continue;
        if ($v === null || $v === '') continue;
        $filtered[$k] = $v;
    }
    return !empty($filtered) ? '?' . http_build_query($filtered) : '';
}

$baseQuery = [
    'search' => $searchTerm,
    'filter_name' => $filterName ? '1' : null,
    'filter_language' => $filterLanguage ? '1' : null,
    'filter_description' => $filterDescription ? '1' : null
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications | BrickMMO</title>
    <link rel="icon" type="image/x-icon" href="./assets/BrickMMO_Logo_Coloured.png" />
    
    <!-- Google Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=arrow_forward" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS Styling -->
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <!-- header section -->
    <header>
        <!-- container for desktop header section, including logo and horizontal nav -->
        <nav id="desktop-nav">
            <!-- container for logo -->
            <div class="logo">
                <a href="index.php">
                    <img src="./assets/BrickMMO_Logo_Coloured.png" alt="brickmmo logo" width="80px">
                </a>
            </div>

            <!-- container for menu links -->
            <div>
                <ul class="nav-links">
                    <li><a href="https://brickmmo.com/">BrickMMo Main Site</a></li>
                    <?php if ($isLoggedIn): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="personal-history.php">My History</a></li>
                        <li><a href="auth/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="auth/login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <section id="hero">
            <h1>BrickMMO Applications</h1>
            <p>A place for all BrickMMO Applications</p>

            <!-- Search Bar -->
            <div class="search-container">
                <form method="GET" action="index.php" id="search-form">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" id="search-input" placeholder="Search repositories by name or language..." value="<?php echo htmlspecialchars($searchTerm); ?>" />
                        <button id="clear-search" class="clear-btn" type="button" onclick="window.location.href='index.php'" style="display: <?php echo !empty($searchTerm) ? 'block' : 'none'; ?>;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="search-filters">
                        <label>
                            <input type="checkbox" id="filter-name" name="filter_name" <?php echo $filterName ? 'checked' : ''; ?>> Repository Name
                        </label>
                        <label>
                            <input type="checkbox" id="filter-language" name="filter_language" <?php echo $filterLanguage ? 'checked' : ''; ?>> Programming Language
                        </label>
                        <label>
                            <input type="checkbox" id="filter-description" name="filter_description" <?php echo $filterDescription ? 'checked' : ''; ?>> Description
                        </label>
                    </div>
                </form>
            </div>
        </section>
    </header>
    
    <!-- main section -->
    <main>
        <section id="applications">
            <!-- Results Info -->
            <div id="search-info" style="margin-bottom: 20px; padding: 10px 15px; background-color: <?php echo !empty($searchTerm) ? '#e3f2fd' : 'transparent'; ?>; border-radius: 8px; display: <?php echo !empty($searchTerm) || (isset($githubRepos) && array_key_exists(0, $githubRepos) && isset($githubRepos[0]['is_demo'])) ? 'block' : 'none'; ?>;">
                <?php if (!empty($searchTerm)): ?>
                    <p id="search-results-text" style="margin: 0; font-size: 16px;"><i class="fas fa-search" style="margin-right: 8px; color: #3498db;"></i> Found <strong><?php echo $totalRepos; ?></strong> repositories matching "<strong><?php echo htmlspecialchars($searchTerm); ?></strong>"</p>
                <?php elseif (isset($githubRepos) && array_key_exists(0, $githubRepos) && isset($githubRepos[0]['is_demo'])): ?>
                    <p style="margin: 0; font-size: 16px; color: #e67e22;"><i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i> <strong>Note:</strong> Currently showing demo data due to GitHub API connection issues. Please try again later.</p>
                <?php endif; ?>
            </div>
            
            <div class="applications-container" id="repo-container">
                <?php 
                // Fetch repositories from GitHub API
                $repositories = fetchAllGitHubRepos();
                
                if (empty($repositories)): ?>
                    <div class="error-message" style="text-align: center; padding: 40px; background-color: #fff3f3; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 100%; max-width: 600px; margin: 0 auto;">
                        <img src="./assets/placeholder.png" alt="No repositories" style="width: 120px; height: auto; margin-bottom: 20px;">
                        <h3 style="margin-bottom: 15px; color: #e74c3c; font-size: 24px;">No Repositories Found</h3>
                        <p style="font-size: 16px; margin-bottom: 15px;">Unable to retrieve repositories at this time.</p>
                        <p style="margin-top: 10px; font-size: 14px; color: #666;">This could be due to GitHub API limitations or connectivity issues.</p>
                        <div style="margin-top: 25px;">
                            <button onclick="window.location.reload()" class="button-app-info" style="margin-right: 10px;">Refresh Page</button>
                            <button onclick="window.location.href='index.php'" class="button-app-github">Clear Search</button>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($repositories as $repo): ?>
                        <?php
                          $repoName = $repo['name'];
                          $repoDescription = $repo['description'] ?? 'No description available';
                          $repoLanguage = $repo['language'] ?? 'N/A';
                          $isDemoRepo = isset($repo['is_demo']) && $repo['is_demo'];
                          
                          // Set special styling for demo repos
                          $cardStyle = $isDemoRepo ? "box-shadow: 0 0 0 2px #e74c3c;" : "";
                        ?>
                        <div class="app-card" style="<?php echo $cardStyle; ?> position: relative; overflow: hidden; display: flex; flex-direction: column; height: 100%;">
                            <?php if ($isDemoRepo): ?>
                                <div class="demo-ribbon" style="position: absolute; top: 10px; right: -30px; transform: rotate(45deg); background: #e74c3c; color: white; padding: 5px 30px; font-size: 10px; font-weight: bold;">Demo</div>
                            <?php endif; ?>
                            
                            <div class="card-content" style="flex: 1; display: flex; flex-direction: column;">
                                <h3 class="card-title"><?php echo htmlspecialchars($repoName); ?></h3>
                                <p class="app-description" style="flex-grow: 1; min-height: 60px;"><?php echo htmlspecialchars($repoDescription); ?></p>
                                
                                <p><strong>Language:</strong> <span class="language-badge" style="display: inline-block; padding: 2px 8px; background-color: #f0f0f0; border-radius: 4px; font-size: 12px;"><?php echo htmlspecialchars($repoLanguage); ?></span></p>
                                
                                <div style="display: flex; justify-content: center; margin: 15px 0; padding: 5px 0; font-size: 13px; color: #333; text-align: center;">
                                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; padding: 5px 0;">
                                        <strong style="display: block; font-size: 16px; margin-bottom: 3px;">0</strong>
                                        <span style="font-size: 12px; color: #666;">hours</span>
                                    </div>
                                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; padding: 5px 0; border-left: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05);">
                                        <strong style="display: block; font-size: 16px; margin-bottom: 3px;">0</strong>
                                        <span style="font-size: 12px; color: #666;">contributors</span>
                                    </div>
                                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; padding: 5px 0;">
                                        <strong style="display: block; font-size: 16px; margin-bottom: 3px;">0</strong>
                                        <span style="font-size: 12px; color: #666;">entries</span>
                                    </div>
                                </div>
                                
                            </div>
                            
                            <div class="card-buttons-container" style="margin-top: auto; padding-top: 15px;">
                                <button class="button-app-info" onclick="window.open('<?php echo htmlspecialchars($repo['html_url']); ?>', '_blank')"><i class="fab fa-github" style="margin-right: 5px;"></i> GitHub</button>
                                <button class="button-app-github" onclick="window.location.href='applications-v1/repo_details.php?repo=<?php echo urlencode($repo['name']); ?>'"><i class="fas fa-info-circle" style="margin-right: 5px;"></i> View Details</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <div id="pagination">
                <?php if ($totalPages > 1): ?>
                    <?php if ($currentPage > 1): ?>
                        <button class="pagination-btn" onclick="window.location.href='index.php<?php echo buildQueryString(array_merge($baseQuery, ['page' => $currentPage - 1]), []); ?>'">Previous</button>
                    <?php endif; ?>
                    
                    <span class="page-indicator"><?php echo $currentPage; ?> / <?php echo $totalPages; ?></span>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <button class="pagination-btn" onclick="window.location.href='index.php<?php echo buildQueryString(array_merge($baseQuery, ['page' => $currentPage + 1]), []); ?>'">Next</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <!-- footer section -->
    <footer>
        <div class="footer-container">
            <div class="social-icons">
                <a href="https://www.instagram.com/brickmmo/" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="https://www.youtube.com/channel/UCJJPeP10HxC1qwX_paoHepQ" target="_blank"><i class="fab fa-youtube"></i></a>
                <a href="https://x.com/brickmmo" target="_blank"><i class="fab fa-x"></i></a>
                <a href="https://github.com/BrickMMO" target="_blank"><i class="fab fa-github"></i></a>
                <a href="https://www.tiktok.com/@brickmmo" target="_blank"><i class="fab fa-tiktok"></i></a>
            </div>
            <div id="copyright-container">
                <p id="brickmmo copyright">&copy; BrickMMO. 2025. All rights reserved.</p>
                <p id="lego copyright">LEGO, the LEGO logo and the Minifigure are trademarks of the LEGO Group.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.brickmmo.com/bar@1.0.0/bar.js"></script>
    <script>
        // Auto-submit form when checkboxes change
        document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                document.getElementById('search-form').submit();
            });
        });
    </script>
</body>
</html>
