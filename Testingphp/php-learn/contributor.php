<?php
require_once(__DIR__ . '/../config/config.php');

if (!isset($_GET['id'])) {
    die("No contributor specified");
}

$contributor_id = (int)$_GET['id'];

// Get contributor info
$stmt = mysqli_prepare($connection, "
    SELECT c.*, COUNT(rc.repo_id) as repo_count, SUM(rc.contributions) as total_contributions
    FROM contributors c
    LEFT JOIN repo_contributors rc ON c.id = rc.contributor_id
    WHERE c.id = ?
    GROUP BY c.id
");
mysqli_stmt_bind_param($stmt, "i", $contributor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$contributor = mysqli_fetch_assoc($result);

if (!$contributor) {
    die("Contributor not found");
}

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contributor Details - <?= htmlspecialchars($contributor['login']) ?></title>
    <style>
        .contributor-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        li {
            margin-bottom: 20px;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body>
    <h1>Contributor Details</h1>
    
    <div class="contributor-info">
        <img src="<?= htmlspecialchars($contributor['avatar_url']) ?>" alt="Avatar" class="avatar">
        <div>
            <h2><?= htmlspecialchars($contributor['login']) ?></h2>
            <p>Total Contributions: <?= $contributor['total_contributions'] ?></p>
            <p>Repositories Contributed To: <?= $contributor['repo_count'] ?></p>
            <a href="<?= htmlspecialchars($contributor['html_url']) ?>" target="_blank">View GitHub Profile</a>
        </div>
    </div>

    <h2>Repositories</h2>
    <?php
    // Get all repositories this contributor has worked on
    $stmt = mysqli_prepare($connection, "
        SELECT r.*, rc.contributions
        FROM repositories r
        JOIN repo_contributors rc ON r.id = rc.repo_id
        WHERE rc.contributor_id = ?
        ORDER BY rc.contributions DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $contributor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    ?>

    <ul>
    <?php while ($repo = mysqli_fetch_assoc($result)): ?>
        <li>
            <strong>
                <a href="<?= htmlspecialchars($repo['url']) ?>" target="_blank">
                    <?= htmlspecialchars($repo['name']) ?>
                </a>
            </strong>
            <p>Contributions: <?= $repo['contributions'] ?></p>
            <?php if ($repo['description']): ?>
                <p><?= htmlspecialchars($repo['description']) ?></p>
            <?php endif; ?>
        </li>
    <?php endwhile; ?>
    </ul>

    <p><a href="index.php">← Back to Repository List</a></p>

    <?php mysqli_close($connection); ?>
</body>
</html>