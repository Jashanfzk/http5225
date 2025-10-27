<?php
require_once(__DIR__ . '/../config/config.php');

// Set GitHub API headers
$headers = [
    'User-Agent: PHP Script',
    'Accept: application/vnd.github.v3+json',
    'Authorization: token ' . GITHUB_TOKEN
];

$apiUrl = 'https://api.github.com/orgs/BrickMMO/repos';
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BrickMMO Repositories</title>
    <style>
        .repo-list {
            max-width: 800px;
            margin: 0 auto;
        }
        .repo-item {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .contributors-list {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .contributor-link {
            text-decoration: none;
            color: #0366d6;
        }
        .contributor-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="repo-list">
        <h1>BrickMMO Repositories</h1>
        
        <?php
        if ($httpCode === 200) {
            $repos = json_decode($response, true);
            foreach ($repos as $repo) {
                // Store repository in database
                $stmt = mysqli_prepare($connection, "
                    INSERT INTO repositories (name, url, description) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    url = VALUES(url),
                    description = VALUES(description)
                ");
                
                mysqli_stmt_bind_param($stmt, "sss", 
                    $repo['name'],
                    $repo['html_url'],
                    $repo['description']
                );
                mysqli_stmt_execute($stmt);
                $repoId = mysqli_insert_id($connection);
                if (!$repoId) {
                    $repoId = mysqli_fetch_assoc(mysqli_query($connection, 
                        "SELECT id FROM repositories WHERE name = '{$repo['name']}'"))['id'];
                }
                
                // Fetch contributors for this repo
                $contributorsUrl = $repo['contributors_url'];
                $ch = curl_init($contributorsUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_TIMEOUT => 30
                ]);
                
                $contributorsResponse = curl_exec($ch);
                $contributorsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                echo "<div class='repo-item'>";
                echo "<h2><a href='{$repo['html_url']}'>{$repo['name']}</a></h2>";
                echo "<p>{$repo['description']}</p>";
                
                if ($contributorsHttpCode === 200) {
                    $contributors = json_decode($contributorsResponse, true);
                    echo "<div class='contributors-list'>";
                    foreach ($contributors as $contributor) {
                        // Store contributor in database
                        $stmt = mysqli_prepare($connection, "
                            INSERT INTO contributors (github_id, login, avatar_url, html_url)
                            VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                            login = VALUES(login),
                            avatar_url = VALUES(avatar_url),
                            html_url = VALUES(html_url)
                        ");
                        
                        mysqli_stmt_bind_param($stmt, "isss",
                            $contributor['id'],
                            $contributor['login'],
                            $contributor['avatar_url'],
                            $contributor['html_url']
                        );
                        mysqli_stmt_execute($stmt);
                        
                        // Get or create contributor relationship
                        $contributorId = mysqli_insert_id($connection);
                        if (!$contributorId) {
                            $contributorId = mysqli_fetch_assoc(mysqli_query($connection, 
                                "SELECT id FROM contributors WHERE github_id = {$contributor['id']}"))['id'];
                        }
                        
                        // Update repo_contributors
                        $stmt = mysqli_prepare($connection, "
                            INSERT INTO repo_contributors (repo_id, contributor_id, contributions)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                            contributions = VALUES(contributions)
                        ");
                        mysqli_stmt_bind_param($stmt, "iii", 
                            $repoId, 
                            $contributorId, 
                            $contributor['contributions']
                        );
                        mysqli_stmt_execute($stmt);
                        
                        echo "<a href='contributor.php?id={$contributorId}' class='contributor-link'>";
                        echo "<img src='{$contributor['avatar_url']}' alt='{$contributor['login']}' class='contributor-avatar'>";
                        echo "</a>";
                    }
                    echo "</div>";
                }
                echo "</div>";
            }
        } else {
            echo "<p>Error fetching repositories: $httpCode</p>";
        }
        mysqli_close($connection);
        ?>
    </div>
</body>
</html>