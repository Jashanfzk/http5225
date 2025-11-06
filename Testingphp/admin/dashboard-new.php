<?php
/**
 * Modern Admin Dashboard - Repositories Management
 * Clean UI matching the BrickMMO design system
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/GitHubImport.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// Enforce admin role for this page (no dev-mode elevation)
requireAdmin();

// Toggle handling (server-side form)
if (isset($_POST['action']) && $_POST['action'] === 'toggle' && isset($_POST['repo_id'])) {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Unauthorized'];
    } else {
        $repoId = (int) $_POST['repo_id'];
        $newStatus = isset($_POST['status']) && $_POST['status'] === '1' ? 1 : 0;
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare('UPDATE applications SET is_active = ?, updated_at = NOW() WHERE id = ?');
            $ok = $stmt->execute([$newStatus, $repoId]);
            $_SESSION['flash'] = ['type' => $ok ? 'success' : 'error', 
                                  'message' => $ok ? 'Repository status updated.' : 'Failed to update.'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'DB error: ' . $e->getMessage()];
        }
    }
    header('Location: dashboard-new.php');
    exit;
}

// Admin check
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Handle Import from GitHub (also allow GET during development for troubleshooting)
if ($isAdmin && (isset($_POST['import_repos']) || isset($_GET['import_repos']) || isset($_GET['do_import']))) {
    error_log("=== IMPORT STARTED === isAdmin: " . ($isAdmin ? 'true' : 'false'));
    try {
        $db = (new Database())->getConnection();
        $login = GITHUB_ORG;
        // Use the reusable importer
        $result = GitHubImporter::importUserRepos($db, $login);
        // Store debug logs and result for display
        $_SESSION['import_logs'] = $result['logs'] ?? [];
        $_SESSION['import_result'] = [
            'fail' => false,
            'imported' => (int)$result['imported'],
            'updated'  => (int)$result['updated'],
            'total'    => (int)$result['total']
        ];
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Import completed! Imported ' . $result['imported'] . ' new. Updated ' . $result['updated'] . '. Total: ' . $result['total'] . ' repositories.'];
        // No redirect - stay on page to see console logs and notification
     
    } catch (Exception $e) {
        error_log("Import error: " . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Import failed: ' . $e->getMessage()];
        $_SESSION['import_logs'] = $_SESSION['import_logs'] ?? [];
        $_SESSION['import_logs'][] = 'Exception: ' . $e->getMessage();
        $_SESSION['import_result'] = ['fail' => true, 'reason' => ($e->getMessage() ?: 'Import failed')];
    }
    // No redirect: render the page so you can see console logs and alerts
}

// Get search parameter
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination settings
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 8;
$offset = ($page - 1) * $per_page;

// Fetch repositories with pagination (always for admin; access already enforced above)
$repositories = [];
$total_repos = 0;
// Contributors aggregates (initialized)
$contributors = [];
$total_contributors = 0;
$cpage = isset($_GET['cpage']) ? max(1, (int)$_GET['cpage']) : 1;
$cper_page = 8;
$coffset = ($cpage - 1) * $cper_page;
if (true) { // requireAdmin() already ensured only admins reach this page
    try {
        $db = (new Database())->getConnection();
        
        // Count total repositories (with optional search)
        if (!empty($searchTerm)) {
            $count_stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE name LIKE ? OR description LIKE ?");
            $searchParam = '%' . $searchTerm . '%';
            $count_stmt->execute([$searchParam, $searchParam]);
        } else {
            $count_stmt = $db->query("SELECT COUNT(*) FROM applications");
        }
        $total_repos = (int) ($count_stmt ? $count_stmt->fetchColumn() : 0);

        // Fetch paginated repositories using validated LIMIT/OFFSET (most reliable)
        $perInt = max(1, (int)$per_page);
        $offInt = max(0, (int)$offset);
        if (!empty($searchTerm)) {
            $searchEsc = str_replace(['%', '_'], ['\\%','\\_'], $searchTerm);
            $query = "SELECT id, name, description, is_active, visibility
                      FROM applications
                      WHERE name LIKE '%$searchEsc%' OR description LIKE '%$searchEsc%'
                      ORDER BY name ASC
                      LIMIT $perInt OFFSET $offInt";
        } else {
            $query = "SELECT id, name, description, is_active, visibility
                      FROM applications
                      ORDER BY name ASC
                      LIMIT $perInt OFFSET $offInt";
        }
        $stmt = $db->query($query);
        $repositories = $stmt ? $stmt->fetchAll() : [];

        // If DB has rows but current view is empty, add a soft warning banner
        if (empty($repositories) && $total_repos > 0) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Data exists (' . $total_repos . " repos) but this view is empty. Reset filters or go to page 1."
            ];
        }

        // ---------------- Contributors aggregate (only users with logged hours) ----------------
        // Fetch all contributors with totals (slice for pagination in PHP to avoid LIMIT binding issues)
        $contribStmt = $db->query(
            "SELECT u.id, u.login, u.name, u.avatar_url,
                    COUNT(h.id) AS entries,
                    COALESCE(SUM(h.duration), 0) AS total_hours,
                    COUNT(DISTINCT h.application_id) AS projects,
                    MAX(h.work_date) AS last_date
             FROM hours h
             INNER JOIN users u ON u.id = h.user_id
             GROUP BY u.id, u.login, u.name, u.avatar_url
             ORDER BY total_hours DESC"
        );
        $allContributors = $contribStmt ? $contribStmt->fetchAll() : [];
        $total_contributors = is_array($allContributors) ? count($allContributors) : 0;
        $contributors = array_slice($allContributors, $coffset, $cper_page);

        // For each contributor in the current page, fetch top 3 repositories by hours
        $contributorTopRepos = [];
        foreach ($contributors as $c) {
            $cid = (int)$c['id'];
            $topStmt = $db->prepare(
                "SELECT a.name, COALESCE(SUM(h.duration),0) AS hours, COUNT(h.id) AS entries
                 FROM hours h
                 JOIN applications a ON a.id = h.application_id
                 WHERE h.user_id = ?
                 GROUP BY a.id, a.name
                 ORDER BY hours DESC
                 LIMIT 3"
            );
            $topStmt->execute([$cid]);
            $contributorTopRepos[$cid] = $topStmt->fetchAll();
        }
        // Expose for rendering section below
        $contributors_top_repos = $contributorTopRepos;
        
    } catch (Exception $e) {
        error_log("Admin dashboard error: " . $e->getMessage());
        $repositories = [];
        $contributors = [];
        $total_contributors = 0;
    }
}

$total_pages = ceil($total_repos / $per_page);
$total_contrib_pages = $cper_page > 0 ? ceil(max(0, $total_contributors) / $cper_page) : 1;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Repositories - BrickMMO Admin</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="../css/w3-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        /* Header - Matching contributor.php style */
        .header {
            background: white;
            border-bottom: 1px solid #E8D5CF;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }

        .header-content {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo img {
            height: 48px;
        }

        .nav-tabs {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-tab {
            color: #DD5A3A;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: opacity 0.2s;
        }

        .nav-tab:hover {
            opacity: 0.8;
        }

        .nav-tab.active {
            text-decoration: underline;
        }

        /* Main Content */
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
            min-height: calc(100vh - 280px);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .page-title {
            font-size: 2.75rem;
            font-weight: 700;
            color: #2C3E50;
            letter-spacing: -0.02em;
        }

        .btn-primary {
            background: #DD5A3A;
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            background: #C14D30;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(221, 90, 58, 0.2);
        }

        /* Search Bar */
        .search-container {
            background: white;
            border: 1px solid #E8D5CF;
            border-radius: 12px;
            padding: 1.25rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            max-width: 900px;
        }

        .search-icon {
            width: 22px;
            height: 22px;
            color: #999;
        }

        .search-input {
            border: none;
            outline: none;
            flex: 1;
            font-size: 1.0625rem;
            color: #2C3E50;
            background: transparent;
        }

        .search-input::placeholder {
            color: #999;
        }

        .search-input:focus {
            outline: none;
        }

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-tab {
            padding: 0.75rem 2rem;
            background: white;
            border: 1px solid #E8D5CF;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-tab:hover {
            border-color: #DD5A3A;
            color: #DD5A3A;
            background: #FFF8F6;
        }

        .filter-tab.active {
            background: #2C3E50;
            color: white;
            border-color: #2C3E50;
        }

        /* Repository Cards */
        .repo-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(700px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .repo-card {
            background: white;
            border: 1px solid #E8D5CF;
            border-radius: 12px;
            padding: 2.5rem 3rem;
            transition: all 0.2s;
        }

        .repo-card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border-color: #DD5A3A;
            transform: translateY(-3px);
        }

        .repo-name {
            color: #2C3E50;
            font-weight: 600;
            font-size: 1.35rem;
            margin-bottom: 1.25rem;
            line-height: 1.4;
        }

        .repo-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .visibility-badge {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 500;
            background: #F0F0F0;
            color: #666;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 32px;
        }

        .toggle-switch input[type="checkbox"] {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #e5e5e5;
            border-radius: 32px;
            transition: 0.3s;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            border-radius: 50%;
            transition: 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .toggle-switch input:checked + .toggle-slider {
            background-color: #FF6B35;
        }

        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(28px);
        }

        .btn-view {
            color: #DD5A3A;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-view:hover {
            color: #C14D30;
            text-decoration: underline;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2.5rem;
            padding: 1.5rem 0;
            border-top: 1px solid #E8D5CF;
        }

        .pagination-btn {
            background: #DD5A3A;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9375rem;
            transition: all 0.2s;
        }

        .pagination-btn:hover {
            background: #C14D30;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(221, 90, 58, 0.2);
        }

        .pagination-info {
            color: #666;
            font-weight: 500;
            font-size: 0.9375rem;
        }

        /* Flash Messages */
        .flash {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9375rem;
        }

        .flash.success {
            background: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }

        .flash.error {
            background: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border: 1px solid #E8D5CF;
            border-radius: 8px;
            color: #999;
        }

        .empty-state-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 0.75rem;
        }

        .empty-state p {
            color: #999;
            font-size: 0.9375rem;
        }

        /* Non-Admin State */
        .access-denied {
            background: white;
            border: 1px solid #E8D5CF;
            border-radius: 12px;
            padding: 4rem 3rem;
            text-align: center;
            margin: 3rem auto;
            max-width: 600px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .access-denied h2 {
            font-size: 1.75rem;
            margin-bottom: 1rem;
            color: #2C3E50;
            font-weight: 700;
        }

        .access-denied p {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1rem;
            line-height: 1.6;
        }

        .btn-admin {
            background: #DD5A3A;
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(221, 90, 58, 0.2);
        }

        .btn-admin:hover {
            background: #C14D30;
            box-shadow: 0 4px 8px rgba(221, 90, 58, 0.3);
            transform: translateY(-1px);
        }

        /* Footer */
        footer {
            background: white;
            border-top: 1px solid #E8D5CF;
            padding: 2rem 0;
            margin-top: auto;
        }

        .footer-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 1rem;
            text-align: center;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .social-icons a {
            color: #DD5A3A;
            font-size: 1.5rem;
            transition: opacity 0.2s;
        }

        .social-icons a:hover {
            opacity: 0.7;
        }

        #copyright-container {
            color: #666;
            font-size: 0.875rem;
            line-height: 1.6;
        }

        #copyright-container p {
            margin: 0.25rem 0;
        }
        
        /* Wrapper for sticky footer */
        html, body {
            height: 100%;
        }
        
        body {
            display: flex;
            flex-direction: column;
        }
        
        main.container {
            flex: 1 0 auto;
        }
        
        footer {
            flex-shrink: 0;
        }

        /* Responsive */
        @media (max-width: 1400px) {
            .repo-cards {
                grid-template-columns: repeat(auto-fill, minmax(600px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            .repo-cards {
                grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .repo-cards {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 2.25rem;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-tabs {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }

            .container {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.875rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                margin-bottom: 1.5rem;
            }

            .repo-cards {
                grid-template-columns: 1fr;
            }

            .repo-card {
                padding: 1.5rem 2rem;
            }

            .repo-name {
                font-size: 1.125rem;
            }

            .search-container {
                max-width: 100%;
                padding: 1rem 1.5rem;
            }
            
            .social-icons {
                flex-wrap: wrap;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
<script>
(function(){
  // Always print importer logs if present
  var logs = <?php
    $logs = $_SESSION['import_logs'] ?? [];
    unset($_SESSION['import_logs']);
    echo json_encode($logs, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
  ?>;
  if (Array.isArray(logs) && logs.length) {
    try { console.group('GitHub Import Logs'); logs.forEach(function(ln){ console.log(ln); }); console.groupEnd(); } catch(e) {}
  }
  // Show result popups without redirect
  var res = <?php
    $res = $_SESSION['import_result'] ?? null;
    unset($_SESSION['import_result']);
    echo json_encode($res, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
  ?>;
  if (res) {
    try {
      if (res.fail) {
        alert('Import failed: ' + (res.reason || 'unknown'));
      } else {
        alert('Import completed: ' + (res.imported||0) + ' new, ' + (res.updated||0) + ' updated. Total now: ' + (res.total||0) + ' repositories.');
        // Reload page to show updated repo list
        setTimeout(function(){ window.location.href = 'dashboard-new.php?page=1'; }, 500);
      }
    } catch(e) {}
  }
})();
</script>

<!-- Header -->
<header class="header">
    <div class="header-content">
        <div class="logo">
            <a href="../index.php">
                <img src="../assets/BrickMMO_Logo_Coloured.png" alt="BrickMMO" style="height: 48px;">
            </a>
        </div>
        
        <nav class="nav-tabs">
            <a href="../index.php" class="nav-tab">Home</a>
            <a href="dashboard-new.php" class="nav-tab active">Admin Dashboard</a>
            <a href="contributors.php" class="nav-tab">Contributors</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="../auth/logout.php" class="nav-tab">Logout</a>
            <?php else: ?>
                <a href="../auth/login.php" class="nav-tab">Login</a>
        <?php endif; ?>
        </nav>
    </div>
</header>

<!-- Main Content -->
<main class="container">
        <?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="flash <?= htmlspecialchars($f['type']) ?>">
            <?= htmlspecialchars($f['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Repositories</h1>
            <form method="POST" style="display: inline;" onsubmit="return confirm('Import all repositories from GitHub?')">
                <input type="hidden" name="import_repos" value="1">
                <button type="submit" class="btn-primary">Import from GitHub</button>
            </form>
            <?php if (defined('DEVELOPMENT') && DEVELOPMENT): ?>
                <a href="dashboard-new.php?do_import=1" class="btn-primary" style="margin-left:1rem;">Run Import (Dev)</a>
            <?php endif; ?>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="dashboard-new.php">
            <div class="search-container">
                <svg class="search-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="8" cy="8" r="6"/>
                    <path d="M12.5 12.5L16 16"/>
                </svg>
                <input 
                    type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="Search repositories" 
                    value="<?= htmlspecialchars($searchTerm) ?>"
                />
            </div>
        </form>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <button type="button" class="filter-tab active">Visibility</button>
            <button type="button" class="filter-tab">Status</button>
                            </div>
                            
        <!-- Repository Cards -->
        <div class="repo-cards">
            <?php if (empty($repositories)): ?>
                <div class="empty-state">
                    <div class="empty-state-title">No repositories found</div>
                    <p>Import repositories from GitHub to get started.</p>
                            </div>
            <?php else: ?>
                <?php foreach ($repositories as $repo): ?>
                    <div class="repo-card">
                        <div class="repo-name">BrickMMO/<?= htmlspecialchars($repo['name']) ?></div>
                        <div class="repo-meta">
                            <span class="visibility-badge"><?= ucfirst(htmlspecialchars($repo['visibility'] ?? 'public')) ?></span>
                            <label class="toggle-switch">
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="repo_id" value="<?= (int)$repo['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $repo['is_active'] ? '0' : '1' ?>">
                                    <input 
                                        type="checkbox" 
                                        <?= $repo['is_active'] ? 'checked' : '' ?>
                                        onchange="this.form.submit()"
                                    />
                                    <span class="toggle-slider"></span>
                                </form>
                            </label>
                        </div>
                        <a href="../repository.php?repo=<?= urlencode($repo['name']) ?>" class="btn-view">View</a>
                    </div>
                            <?php endforeach; ?>
                    <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" class="pagination-btn">← Previous</a>
                    <?php endif; ?>
                    
                    <div class="pagination-info">
                        Page <?= $page ?> of <?= $total_pages ?> (<?= $total_repos ?> repositories)
                    </div>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" class="pagination-btn">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        
</main>

<!-- Contributors section moved to contributors.php -->

<!-- Footer -->
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

</body>
</html>