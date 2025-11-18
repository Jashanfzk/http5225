<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

try {
    $db = (new Database())->getConnection();

    $totalHours = (float)($db->query("SELECT COALESCE(SUM(h.duration),0) FROM hours h")->fetchColumn());
    $totalContributors = (int)($db->query("SELECT COUNT(DISTINCT h.user_id) FROM hours h")->fetchColumn());
    $totalEntries = (int)($db->query("SELECT COUNT(*) FROM hours h")->fetchColumn());

    $cpage = isset($_GET['cpage']) ? max(1, (int)$_GET['cpage']) : 1;
    $cperPage = 8;
    $coffset = ($cpage - 1) * $cperPage;

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
    $totalContribRows = is_array($allContributors) ? count($allContributors) : 0;
    $totalContribPages = $cperPage > 0 ? (int)ceil($totalContribRows / $cperPage) : 1;
    $contributors = array_slice($allContributors, $coffset, $cperPage);

    $contributorsTopRepos = [];
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
        $contributorsTopRepos[$cid] = $topStmt->fetchAll();
    }
} catch (Exception $e) {
    error_log('Admin contributors page error: ' . $e->getMessage());
    $totalHours = 0; $totalContributors = 0; $totalEntries = 0;
    $contributors = []; $contributorsTopRepos = []; $totalContribPages = 1; $totalContribRows = 0; $cpage = 1; $cperPage = 8;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Contributors - BrickMMO Admin</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="../css/w3-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #FDF6F3; color: #333; }
        .header { background: white; border-bottom: 1px solid #E8D5CF; padding: 1.5rem 0; margin-bottom: 2rem; }
        .header-content { max-width: 1600px; margin: 0 auto; padding: 0 1rem; display: flex; justify-content: space-between; align-items: center; }
        .logo img { height: 48px; }
        .nav-tabs { display: flex; gap: 2rem; align-items: center; }
        .nav-tab { color: #DD5A3A; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: opacity 0.2s; }
        .nav-tab:hover { opacity: 0.8; }
        .nav-tab.active { text-decoration: underline; }
        .container { max-width: 1600px; margin: 0 auto; padding: 2rem; }
        .page-title { font-size: 2.25rem; font-weight: 700; color: #2C3E50; letter-spacing: -0.02em; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: 12px; padding: 1.25rem; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .stat-label { font-size: 0.85rem; color: #666; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #DD5A3A; line-height: 1; }
        .cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(700px, 1fr)); gap: 1.25rem; }
        .card { background: white; border: 1px solid #E8D5CF; border-radius: 12px; padding: 1.5rem 1.75rem; }
        .badge { display: inline-block; padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.8125rem; font-weight: 500; background: #f0f0f0; color: #666; }
        .pagination { display: flex; justify-content: space-between; align-items: center; margin-top: 2rem; padding: 1.5rem 0; border-top: 1px solid #E8D5CF; }
        .pagination-btn { background: #DD5A3A; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9375rem; transition: all 0.2s; }
        .pagination-btn:hover { background: #C14D30; transform: translateY(-1px); box-shadow: 0 4px 8px rgba(221, 90, 58, 0.2); }
        .pagination-info { color: #666; font-weight: 500; font-size: 0.9375rem; }
        @media (max-width: 1200px) { .cards { grid-template-columns: 1fr; } .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="logo">
            <a href="../index.php">
                <img src="../assets/BrickMMO_Logo_Coloured.png" alt="BrickMMO">
            </a>
        </div>
        <nav class="nav-tabs">
            <a href="../index.php" class="nav-tab">Home</a>
            <a href="dashboard-new.php" class="nav-tab">Admin Dashboard</a>
            <a href="contributors.php" class="nav-tab active">Contributors</a>
            <a href="../auth/logout.php" class="nav-tab">Logout</a>
        </nav>
    </div>
</header>

<main class="container">
    <h1 class="page-title">Contributor Overview</h1>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Hours</div>
            <div class="stat-value"><?= number_format($totalHours, 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Contributors</div>
            <div class="stat-value"><?= number_format($totalContributors, 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Time Entries</div>
            <div class="stat-value"><?= number_format($totalEntries, 0) ?></div>
        </div>
    </div>

    <?php if (empty($contributors)): ?>
        <div class="card" style="text-align:center; color:#666;">No contributor activity yet.</div>
    <?php else: ?>
        <h2 style="font-size:1.25rem; font-weight:600; color:#2C3E50; margin: 0 0 1rem 0;">Top Contributors</h2>
        <div class="cards">
            <?php foreach ($contributors as $c): ?>
                <div class="card">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 0.75rem;">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <img src="<?= htmlspecialchars($c['avatar_url'] ?? '') ?>" alt="avatar" style="width:44px;height:44px;border-radius:50%;border:2px solid #F0F0F0;object-fit:cover;">
                            <div>
                                <div style="font-weight:600; color:#2C3E50;">
                                    <?= htmlspecialchars($c['name'] ?: $c['login']) ?>
                                </div>
                                <div style="color:#718096; font-size:0.85rem;">@<?= htmlspecialchars($c['login']) ?></div>
                            </div>
                        </div>
                        <a href="../contributor.php?user=<?= urlencode($c['login']) ?>&from=admin" class="nav-tab" style="text-decoration:underline;">View</a>
                    </div>

                    <div style="display:flex; gap:1rem;">
                        <div style="flex:1; text-align:center;">
                            <div class="stat-label">Hours</div>
                            <div class="stat-value" style="font-size:1.5rem;"><?= number_format((float)$c['total_hours'], 1) ?></div>
                        </div>
                        <div style="flex:1; text-align:center;">
                            <div class="stat-label">Entries</div>
                            <div class="stat-value" style="font-size:1.5rem;"><?= (int)$c['entries'] ?></div>
                        </div>
                        <div style="flex:1; text-align:center;">
                            <div class="stat-label">Projects</div>
                            <div class="stat-value" style="font-size:1.5rem;"><?= (int)$c['projects'] ?></div>
                        </div>
                        <div style="flex:1; text-align:center;">
                            <div class="stat-label">Last</div>
                            <div style="font-size:1rem; color:#2C3E50; font-weight:600; margin-top:0.25rem;">
                                <?= $c['last_date'] ? date('Y-m-d', strtotime($c['last_date'])) : '-' ?>
                            </div>
                        </div>
                    </div>

                    <?php $tops = $contributorsTopRepos[(int)$c['id']] ?? []; ?>
                    <?php if (!empty($tops)): ?>
                        <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.75rem;">
                            <?php foreach ($tops as $tr): ?>
                                <span class="badge" title="<?= (int)$tr['entries'] ?> entries">
                                    <?= htmlspecialchars($tr['name']) ?> (<?= number_format((float)$tr['hours'], 1) ?>h)
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalContribPages > 1): ?>
            <div class="pagination">
                <?php if ($cpage > 1): ?>
                    <a href="?cpage=<?= $cpage - 1 ?>" class="pagination-btn">← Previous</a>
                <?php endif; ?>
                <div class="pagination-info">Page <?= $cpage ?> of <?= $totalContribPages ?> (<?= $totalContribRows ?> contributors)</div>
                <?php if ($cpage < $totalContribPages): ?>
                    <a href="?cpage=<?= $cpage + 1 ?>" class="pagination-btn">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<footer style="background:white; border-top:1px solid #E8D5CF; padding:2rem 0; margin-top:auto;">
    <div style="max-width:1600px; margin:0 auto; padding:0 1rem; text-align:center; color:#666; font-size:0.875rem;">
        <p>&copy; BrickMMO. 2025. All rights reserved.</p>
    </div>
</footer>

</body>
</html>
