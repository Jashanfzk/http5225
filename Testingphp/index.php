<?php
require_once 'config/config.php';
require_once 'config/database.php';

$perPage = 9;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterName = isset($_GET['filter_name']) ? true : false;
$filterLanguage = isset($_GET['filter_language']) ? true : false;
$filterDescription = isset($_GET['filter_description']) ? true : false;

if (!$filterName && !$filterLanguage && !$filterDescription) {
    $filterName = true;
    $filterLanguage = true;
}

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

$isAuthorizedAdmin = false;
if ($isLoggedIn) {
    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare('SELECT name, login FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user && isset($user['name']) && isset($user['login']) && 
            ($user['name'] === 'Jashanpreet Singh Gill' || 
             $user['name'] === 'Adam Thomas' || 
             $user['login'] === 'codeadamca')) {
            $isAuthorizedAdmin = true;
        }
    } catch (Exception $e) {
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $conditions = ['a.is_active = 1'];
    $params = [];
    if (!empty($searchTerm)) {
        $like = '%' . $searchTerm . '%';
        $or = [];
        if ($filterName) { $or[] = 'a.name LIKE ?'; $params[] = $like; }
        if ($filterLanguage) { $or[] = 'a.language LIKE ?'; $params[] = $like; }
        if ($filterDescription) { $or[] = 'a.description LIKE ?'; $params[] = $like; }
        if (!empty($or)) {
            $conditions[] = '(' . implode(' OR ', $or) . ')';
        }
    }
    $whereSql = !empty($conditions) ? ('WHERE ' . implode(' AND ', $conditions)) : '';

    $countSql = "SELECT COUNT(*) FROM applications a $whereSql";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalRepos = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalRepos / $perPage));
    $currentPage = min($currentPage, $totalPages);
    $offset = ($currentPage - 1) * $perPage;
    $dataSql = "
        SELECT a.*, 
               COUNT(DISTINCT h.user_id) AS contributor_count,
               COUNT(h.id) AS entry_count,
               COALESCE(SUM(h.duration), 0) AS total_hours
        FROM applications a
        LEFT JOIN hours h ON h.application_id = a.id
        $whereSql
        GROUP BY a.id
        ORDER BY a.name ASC
        LIMIT $perPage OFFSET $offset
    ";
    $dataStmt = $db->prepare($dataSql);
    $dataStmt->execute($params);
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    $repositories = array_map(function($r) {
        return [
            'name' => $r['name'],
            'full_name' => $r['full_name'],
            'description' => $r['description'] ?? 'No description available',
            'html_url' => $r['html_url'] ?? '#',
            'language' => $r['language'] ?? 'N/A',
            'id' => $r['id'],
            'contributor_count' => (int)($r['contributor_count'] ?? 0),
            'entry_count' => (int)($r['entry_count'] ?? 0),
            'total_hours' => (float)($r['total_hours'] ?? 0),
        ];
    }, $rows);
    
} catch (Exception $e) {
    error_log("Index page error: " . $e->getMessage());
    $repositories = [];
    $totalRepos = 0;
    $totalPages = 1;
}

function highlightSearchTerm($text, $searchTerm) {
    if (empty($searchTerm) || empty($text)) {
        return htmlspecialchars($text);
    }
    
    $highlighted = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<mark>$1</mark>', htmlspecialchars($text));
    return $highlighted;
}

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
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=arrow_forward" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    
    
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    
    <header>
        
        <nav id="desktop-nav">
            
            <div class="logo">
                <a href="index.php">
                    <img src="./assets/BrickMMO_Logo_Coloured.png" alt="brickmmo logo" width="80px">
                </a>
            </div>
            
            <div>
                <ul class="nav-links">
                    <li><a href="https://brickmmo.com/">BrickMMo Main Site</a></li>
                    <?php if ($isAuthorizedAdmin): ?>
                        <li><a href="admin/">Admin</a></li>
                    <?php endif; ?>
                    <?php if ($isLoggedIn): ?>
                        <li><a href="dashboard.php">User</a></li>
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
    
    <main>
        <section id="applications">
            
            <div id="search-info" style="margin-bottom: 20px; padding: 10px 15px; background-color: <?php echo !empty($searchTerm) ? '#e3f2fd' : 'transparent'; ?>; border-radius: 8px; display: <?php echo !empty($searchTerm) ? 'block' : 'none'; ?>;">
                <?php if (!empty($searchTerm)): ?>
                    <p id="search-results-text" style="margin: 0; font-size: 16px;"><i class="fas fa-search" style="margin-right: 8px; color: #3498db;"></i> Found <strong><?php echo $totalRepos; ?></strong> repositories matching "<strong><?php echo htmlspecialchars($searchTerm); ?></strong>"</p>
                <?php endif; ?>
            </div>
            
            <div style="margin-bottom: 20px; padding: 12px 15px; background-color: #f8f9fa; border-left: 4px solid #DD5A3A; border-radius: 4px;">
                <p style="margin: 0; font-size: 15px; color: #333;">
                    <i class="fas fa-folder" style="margin-right: 8px; color: #DD5A3A;"></i>
                    Showing <strong><?= count($repositories) ?></strong> of <strong><?= $totalRepos ?></strong> repositories
                    <?php if ($totalPages > 1): ?>
                        - Page <strong><?= $currentPage ?></strong> of <strong><?= $totalPages ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="applications-container" id="repo-container">
                <?php 
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
        document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                document.getElementById('search-form').submit();
            });
        });
    </script>
</body>
</html>
