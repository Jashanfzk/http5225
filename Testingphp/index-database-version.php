<?php
/**
 * BrickMMO Timesheets - Home Page
 * Public repository listing with database integration
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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Build WHERE clause for search
    $where_conditions = ["is_active = 1"];
    $params = [];
    
    if (!empty($searchTerm)) {
        $search_conditions = [];
        $searchPattern = "%$searchTerm%";
        
        if ($filterName) {
            $search_conditions[] = "name LIKE ?";
            $params[] = $searchPattern;
        }
        if ($filterLanguage) {
            $search_conditions[] = "language LIKE ?";
            $params[] = $searchPattern;
        }
        if ($filterDescription) {
            $search_conditions[] = "description LIKE ?";
            $params[] = $searchPattern;
        }
        
        if (!empty($search_conditions)) {
            $where_conditions[] = "(" . implode(" OR ", $search_conditions) . ")";
        }
    }
    
    $where_sql = implode(" AND ", $where_conditions);
    
    // Get total count for pagination
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE $where_sql");
    $count_stmt->execute($params);
    $totalRepos = $count_stmt->fetchColumn();
    
    // Calculate pagination
    $totalPages = (int) ceil($totalRepos / $perPage);
    if ($totalPages < 1) { $totalPages = 1; }
    $currentPage = min($currentPage, $totalPages);
    
    // Get repositories for current page with statistics
    $offset = ($currentPage - 1) * $perPage;
    $repos_stmt = $db->prepare("
        SELECT 
            a.*,
            COUNT(DISTINCT h.user_id) as contributor_count,
            COUNT(h.id) as entry_count,
            SUM(h.duration) as total_hours
        FROM applications a
        LEFT JOIN hours h ON a.id = h.application_id
        WHERE $where_sql
        GROUP BY a.id
        ORDER BY a.name ASC
        LIMIT $perPage OFFSET $offset
    ");
    $repos_stmt->execute($params);
    $repositories = $repos_stmt->fetchAll();
    
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
            <div id="search-info" style="display: <?php echo !empty($searchTerm) ? 'block' : 'none'; ?>;">
                <p id="search-results-text">Found <?php echo $totalRepos; ?> repositories matching "<?php echo htmlspecialchars($searchTerm); ?>"</p>
            </div>
            
            <div class="applications-container" id="repo-container">
                <?php if (empty($repositories)): ?>
                    <p><?php echo !empty($searchTerm) ? 'No repositories found matching your search criteria.' : 'No repositories available.'; ?></p>
                <?php else: ?>
                    <?php foreach ($repositories as $repo): ?>
                        <?php
                          $repoName = highlightSearchTerm($repo['name'], $searchTerm);
                          $repoDescription = highlightSearchTerm($repo['description'] ?? 'No description available', $searchTerm);
                          $repoLanguage = highlightSearchTerm($repo['language'] ?? 'N/A', $searchTerm);
                        ?>
                        <div class="app-card">
                            <h3 class="card-title"><?php echo $repoName; ?></h3>
                            <p class="app-description"><?php echo $repoDescription; ?></p>
                            <p><strong>Language:</strong> <?php echo $repoLanguage; ?></p>
                            <div style="display: flex; gap: 1rem; margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                                <span><strong><?php echo number_format($repo['total_hours'] ?? 0, 0); ?></strong> hours</span>
                                <span><strong><?php echo $repo['contributor_count'] ?? 0; ?></strong> contributors</span>
                                <span><strong><?php echo $repo['entry_count'] ?? 0; ?></strong> entries</span>
                            </div>
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
