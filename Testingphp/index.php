<?php
/**
 * BrickMMO Timesheets - Home Page
 * Public repository listing with GitHub API integration (like applications-v1)
 */

// Add authentication support
require_once 'config/config.php';

// GitHub API Configuration
$githubUsername = "brickmmo";
$perPage = 9;

// Get current page from URL parameter
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

// Function to fetch all repositories from GitHub
function fetchAllRepos($username) {
    $allRepos = [];
    $page = 1;
    $headers = [
        "User-Agent: BrickMMO-WebApp"
    ];
    
    do {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.github.com/users/$username/repos?per_page=100&page=$page");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $repos = json_decode($response, true);
        
        if (!is_array($repos) || empty($repos)) {
            break;
        }
        
        $allRepos = array_merge($allRepos, $repos);
        $page++;
        
    } while (count($repos) === 100);
    
    return $allRepos;
}

// Function to fetch languages for a repository
function fetchLanguages($languagesUrl) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $languagesUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: BrickMMO-WebApp"]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $languages = json_decode($response, true);
    return is_array($languages) ? implode(", ", array_keys($languages)) : "N/A";
}

// Fetch all repositories
$allRepos = fetchAllRepos($githubUsername);

// Filter repositories based on search term
$filteredRepos = $allRepos;

if (!empty($searchTerm)) {
    $searchTermLower = strtolower($searchTerm);
    $filteredRepos = array_filter($allRepos, function($repo) use ($searchTermLower, $filterName, $filterLanguage, $filterDescription) {
        $matches = false;
        
        // Search in repository name
        if ($filterName && stripos($repo['name'], $searchTermLower) !== false) {
            $matches = true;
        }
        
        // Search in primary language
        if ($filterLanguage && isset($repo['language']) && $repo['language'] && stripos($repo['language'], $searchTermLower) !== false) {
            $matches = true;
        }
        
        // Search in description
        if ($filterDescription && isset($repo['description']) && $repo['description'] && stripos($repo['description'], $searchTermLower) !== false) {
            $matches = true;
        }
        
        return $matches;
    });
    
    // Re-index array
    $filteredRepos = array_values($filteredRepos);
}

// Calculate pagination
$totalRepos = count($filteredRepos);
$totalPages = (int) ceil($totalRepos / $perPage);
if ($totalPages < 1) { $totalPages = 1; }
$currentPage = min($currentPage, $totalPages); // Ensure valid page

// Get repositories for current page
$start = ($currentPage - 1) * $perPage;
$repositories = array_slice($filteredRepos, $start, $perPage);

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
            <div id="search-info" style="display: <?php echo !empty($searchTerm) ? 'block' : 'none'; ?>;">
                <p id="search-results-text">Found <?php echo $totalRepos; ?> repositories matching "<?php echo htmlspecialchars($searchTerm); ?>"</p>
            </div>
            
            <div class="applications-container" id="repo-container">
                <?php if (empty($repositories)): ?>
                    <p><?php echo !empty($searchTerm) ? 'No repositories found matching your search criteria.' : 'No repositories available.'; ?></p>
                <?php else: ?>
                    <?php foreach ($repositories as $repo): ?>
                        <?php
                          $languages = fetchLanguages($repo['languages_url']);
                          $repoName = highlightSearchTerm($repo['name'], $searchTerm);
                          $repoDescription = highlightSearchTerm($repo['description'] ?? 'No description available', $searchTerm);
                          $repoLanguages = highlightSearchTerm($languages, $searchTerm);
                        ?>
                        <div class="app-card">
                            <h3 class="card-title"><?php echo $repoName; ?></h3>
                            <p class="app-description"><?php echo $repoDescription; ?></p>
                            <p><strong>Languages:</strong> <?php echo $repoLanguages; ?></p>
                            <div class="card-buttons-container">
                                <button class="button-app-info" onclick="window.open('<?php echo htmlspecialchars($repo['html_url']); ?>', '_blank')">GitHub</button>
                                <button class="button-app-github" onclick="window.location.href='repository.php?repo=<?php echo urlencode($repo['name']); ?>'">View Details</button>
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
        document.querySelectorAll('.search-filters input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                document.getElementById('search-form').submit();
            });
        });
    </script>
</body>
</html>
