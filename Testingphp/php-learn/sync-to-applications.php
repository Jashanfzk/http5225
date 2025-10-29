<?php
/**
 * Sync Script: Copy repositories from php-learn to applications table
 * This bridges the php-learn implementation with the main timesheet system
 */

require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/database.php');

// Check if we're using mysqli or PDO connection
$usingPDO = false;
try {
    // Try to get PDO connection first
    $database = new Database();
    $pdo = $database->getConnection();
    $usingPDO = true;
} catch (Exception $e) {
    // Fall back to mysqli if needed
    if (!isset($connection)) {
        die("Database connection not available");
    }
}

$synced = 0;
$errors = [];
$updated = 0;

try {
    // Fetch all repositories from php-learn repositories table
    if ($usingPDO) {
        $stmt = $pdo->query("SELECT id, name, url, description FROM repositories");
        $repositories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $result = mysqli_query($connection, "SELECT id, name, url, description FROM repositories");
        $repositories = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    
    if (empty($repositories)) {
        throw new Exception("No repositories found in php-learn table. Please run php-learn/index.php first to fetch data from GitHub.");
    }
    
    // Set GitHub API headers for fetching repo details
    $headers = [
        'User-Agent: PHP Script',
        'Accept: application/vnd.github.v3+json'
    ];
    if (defined('GITHUB_TOKEN') && GITHUB_TOKEN) {
        $headers[] = 'Authorization: token ' . GITHUB_TOKEN;
    }
    
    // Process each repository
    foreach ($repositories as $repo) {
        try {
            $repoName = $repo['name'];
            $htmlUrl = $repo['url'];
            $description = $repo['description'] ?? '';
            
            // Try to fetch additional details from GitHub API
            $apiUrl = "https://api.github.com/repos/BrickMMO/" . urlencode($repoName);
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 10
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Parse GitHub data if available
            $githubId = null;
            $fullName = "BrickMMO/" . $repoName;
            $cloneUrl = "https://github.com/BrickMMO/" . $repoName . ".git";
            $language = 'N/A';
            $languagesJson = '{}';
            
            if ($httpCode === 200) {
                $repoData = json_decode($response, true);
                if ($repoData) {
                    $githubId = $repoData['id'] ?? null;
                    $fullName = $repoData['full_name'] ?? $fullName;
                    $cloneUrl = $repoData['clone_url'] ?? $cloneUrl;
                    $language = $repoData['language'] ?? 'N/A';
                    
                    // Fetch languages if available
                    if (isset($repoData['languages_url'])) {
                        $langCh = curl_init($repoData['languages_url']);
                        curl_setopt_array($langCh, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_HTTPHEADER => $headers,
                            CURLOPT_TIMEOUT => 5
                        ]);
                        $langResponse = curl_exec($langCh);
                        $langHttpCode = curl_getinfo($langCh, CURLINFO_HTTP_CODE);
                        curl_close($langCh);
                        
                        if ($langHttpCode === 200) {
                            $languagesJson = $langResponse;
                        }
                    }
                }
            }
            
            // If we couldn't get github_id, use a fallback (negative ID based on repo table ID)
            if (!$githubId) {
                $githubId = -($repo['id']); // Negative to avoid conflicts with real GitHub IDs
            }
            
            // Check if application already exists (by name or github_id)
            if ($usingPDO) {
                $checkStmt = $pdo->prepare("SELECT id, github_id FROM applications WHERE name = ? OR github_id = ?");
                $checkStmt->execute([$repoName, $githubId]);
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $checkStmt = mysqli_prepare($connection, "SELECT id, github_id FROM applications WHERE name = ? OR github_id = ?");
                mysqli_stmt_bind_param($checkStmt, "si", $repoName, $githubId);
                mysqli_stmt_execute($checkStmt);
                $result = mysqli_stmt_get_result($checkStmt);
                $existing = mysqli_fetch_assoc($result);
            }
            
            if ($existing) {
                // Update existing application
                if ($usingPDO) {
                    $updateStmt = $pdo->prepare("
                        UPDATE applications 
                        SET github_id = ?,
                            name = ?,
                            full_name = ?,
                            description = ?,
                            html_url = ?,
                            clone_url = ?,
                            language = ?,
                            languages = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([
                        $githubId,
                        $repoName,
                        $fullName,
                        $description,
                        $htmlUrl,
                        $cloneUrl,
                        $language,
                        $languagesJson,
                        $existing['id']
                    ]);
                } else {
                    $updateStmt = mysqli_prepare($connection, "
                        UPDATE applications 
                        SET github_id = ?,
                            name = ?,
                            full_name = ?,
                            description = ?,
                            html_url = ?,
                            clone_url = ?,
                            language = ?,
                            languages = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    mysqli_stmt_bind_param($updateStmt, "isssssssi",
                        $githubId,
                        $repoName,
                        $fullName,
                        $description,
                        $htmlUrl,
                        $cloneUrl,
                        $language,
                        $languagesJson,
                        $existing['id']
                    );
                    mysqli_stmt_execute($updateStmt);
                }
                $updated++;
            } else {
                // Insert new application
                if ($usingPDO) {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO applications 
                        (github_id, name, full_name, description, html_url, clone_url, language, languages, is_active, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
                    ");
                    $insertStmt->execute([
                        $githubId,
                        $repoName,
                        $fullName,
                        $description,
                        $htmlUrl,
                        $cloneUrl,
                        $language,
                        $languagesJson
                    ]);
                } else {
                    $insertStmt = mysqli_prepare($connection, "
                        INSERT INTO applications 
                        (github_id, name, full_name, description, html_url, clone_url, language, languages, is_active, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
                    ");
                    mysqli_stmt_bind_param($insertStmt, "isssssss",
                        $githubId,
                        $repoName,
                        $fullName,
                        $description,
                        $htmlUrl,
                        $cloneUrl,
                        $language,
                        $languagesJson
                    );
                    mysqli_stmt_execute($insertStmt);
                }
                $synced++;
            }
            
        } catch (Exception $e) {
            $errors[] = "Error syncing {$repo['name']}: " . $e->getMessage();
            error_log("Sync error for {$repo['name']}: " . $e->getMessage());
        }
    }
    
    // Close connection
    if (!$usingPDO && isset($connection)) {
        mysqli_close($connection);
    }
    
} catch (Exception $e) {
    $errors[] = "Fatal error: " . $e->getMessage();
    error_log("Sync fatal error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Results - BrickMMO</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #FDF6F3;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #DD5A3A;
            margin: 0 0 1rem 0;
        }
        .success {
            background: #D4EDDA;
            color: #155724;
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem 0;
            border: 1px solid #C3E6CB;
        }
        .error {
            background: #F8D7DA;
            color: #721C24;
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem 0;
            border: 1px solid #F5C6CB;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 1.5rem 0;
        }
        .stat {
            text-align: center;
            padding: 1rem;
            background: #FDF6F3;
            border-radius: 6px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #DD5A3A;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        .button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #DD5A3A;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 1rem;
        }
        .button:hover {
            background: #C44A2A;
        }
        .error-list {
            margin-top: 0.5rem;
            padding-left: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Repository Sync Results</h1>
        
        <?php if (empty($errors) && ($synced > 0 || $updated > 0)): ?>
            <div class="success">
                <strong>✓ Sync completed successfully!</strong>
                <p style="margin: 0.5rem 0 0 0;">Repositories have been synced from php-learn to the applications table.</p>
            </div>
        <?php elseif (!empty($errors)): ?>
            <div class="error">
                <strong>⚠ Sync completed with errors</strong>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-number"><?= $synced ?></div>
                <div class="stat-label">New</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= $updated ?></div>
                <div class="stat-label">Updated</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= count($errors) ?></div>
                <div class="stat-label">Errors</div>
            </div>
        </div>
        
        <p style="color: #666; margin-top: 1.5rem;">
            The synced repositories are now available in the dashboard dropdown for time logging.
        </p>
        
        <div style="margin-top: 2rem;">
            <a href="index.php" class="button">Back to php-learn</a>
            <a href="../dashboard.php" class="button" style="background: #5A6C7D;">Go to Dashboard</a>
        </div>
    </div>
</body>
</html>


