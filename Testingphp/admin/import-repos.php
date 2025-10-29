<?php
/**
 * Import Repositories from GitHub
 * Fetches all BrickMMO repositories and imports them to the database
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../lib/GitHubClient.php';

// Require admin access
requireAdmin();

$message = '';
$error = '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Handle toggle switch
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_repo_id'])) {
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            $repoId = (int) $_POST['toggle_repo_id'];
            $newStatus = isset($_POST['toggle_status']) && $_POST['toggle_status'] === '1' ? 1 : 0;
            
            try {
                $stmt = $db->prepare('UPDATE applications SET is_active = ?, updated_at = NOW() WHERE id = ?');
                $ok = $stmt->execute([$newStatus, $repoId]);
                $message = $ok ? 'Repository status updated successfully.' : 'Failed to update repository status.';
            } catch (Exception $e) {
                $error = 'Error updating repository: ' . $e->getMessage();
            }
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_repos'])) {
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            // Fetch repositories from GitHub API (single call)
            $client = new GitHubClient(defined('GITHUB_TOKEN') ? GITHUB_TOKEN : null);
            $repos_url = "https://api.github.com/orgs/" . GITHUB_ORG . "/repos?per_page=100&sort=name";
            $res = $client->get($repos_url);
            if ($res['status'] !== 200) {
                throw new Exception("GitHub API returned HTTP " . $res['status']);
            }
            $repositories = is_array($res['json']) ? $res['json'] : [];
            
            if (empty($repositories)) {
                throw new Exception("No repositories found for organization " . GITHUB_ORG);
            }
            
            $imported_count = 0;
            $updated_count = 0;
            $errors = [];
            $seenGithubIds = [];
            
            foreach ($repositories as $repo) {
                try {
                    if (!isset($repo['id'])) {
                        continue;
                    }
                    $seenGithubIds[] = (int)$repo['id'];
                    // Check if repository already exists
                    $check_stmt = $db->prepare("SELECT id FROM applications WHERE github_id = ?");
                    $check_stmt->execute([$repo['id']]);
                    $existing = $check_stmt->fetch();
                    
                    $primary_language = $repo['language'] ?? 'N/A';
                    $languages_json = json_encode([]);
                    
                    // Get visibility
                    $visibility = isset($repo['private']) && $repo['private'] === true ? 'private' : 'public';
                    $archived = isset($repo['archived']) && $repo['archived'] === true;
                    
                    if ($existing) {
                        // Update existing repository
                        $update_stmt = $db->prepare("
                            UPDATE applications SET 
                                name = ?, 
                                full_name = ?, 
                                description = ?, 
                                html_url = ?, 
                                clone_url = ?, 
                                language = ?, 
                                languages = ?,
                                visibility = ?,
                                last_synced_at = CURRENT_TIMESTAMP,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE github_id = ?
                        ");
                        
                        $update_stmt->execute([
                            $repo['name'],
                            $repo['full_name'],
                            $repo['description'],
                            $repo['html_url'],
                            $repo['clone_url'],
                            $primary_language,
                            $languages_json,
                            $visibility,
                            $repo['id']
                        ]);
                        
                        // If archived, auto-disable but do not flip active if not archived
                        if ($archived) {
                            $db->prepare('UPDATE applications SET is_active = 0 WHERE github_id = ?')->execute([$repo['id']]);
                        }
                        
                        $updated_count++;
                    } else {
                        // Insert new repository
                        $insert_stmt = $db->prepare("
                            INSERT INTO applications (github_id, name, full_name, description, html_url, clone_url, language, languages, visibility, is_active, last_synced_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)
                        ");
                        
                        $insert_stmt->execute([
                            $repo['id'],
                            $repo['name'],
                            $repo['full_name'],
                            $repo['description'],
                            $repo['html_url'],
                            $repo['clone_url'],
                            $primary_language,
                            $languages_json,
                            $visibility
                        ]);
                        
                        // If archived, ensure disabled
                        if ($archived) {
                            $db->prepare('UPDATE applications SET is_active = 0 WHERE github_id = ?')->execute([$repo['id']]);
                        }
                        
                        $imported_count++;
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "Error processing {$repo['name']}: " . $e->getMessage();
                    error_log("Repository import error for {$repo['name']}: " . $e->getMessage());
                }
            }

            // Auto-disable repositories that no longer appear in GitHub list
            if (!empty($seenGithubIds)) {
                $placeholders = implode(',', array_fill(0, count($seenGithubIds), '?'));
                $stmtDisable = $db->prepare("UPDATE applications SET is_active = 0 WHERE github_id NOT IN ($placeholders)");
                $stmtDisable->execute($seenGithubIds);
            }
            
            if ($imported_count > 0 || $updated_count > 0) {
                $message = "Import completed successfully! ";
                if ($imported_count > 0) {
                    $message .= "Imported $imported_count new repositories. ";
                }
                if ($updated_count > 0) {
                    $message .= "Updated $updated_count existing repositories. ";
                }
                if (!empty($res['rate']) && isset($res['rate']['remaining'])) {
                    $message .= "Rate remaining: " . (int)$res['rate']['remaining'] . ". ";
                }
                if (!empty($errors)) {
                    $message .= "Note: " . count($errors) . " repositories had errors.";
                }
            } else {
                $error = "No repositories were imported or updated.";
            }
            
            if (!empty($errors)) {
                error_log("Repository import errors: " . implode('; ', $errors));
            }
        }
    }
    
    // Get current repositories with visibility
    $repos_stmt = $db->prepare("SELECT id, github_id, name, full_name, description, html_url, language, visibility, is_active, updated_at FROM applications ORDER BY name ASC");
    $repos_stmt->execute();
    $current_repos = $repos_stmt->fetchAll();

    // Last sync time (max of last_synced_at)
    try {
        $last_sync_stmt = $db->query("SELECT MAX(last_synced_at) AS last_sync FROM applications");
        $row = $last_sync_stmt->fetch();
        $last_sync = $row && !empty($row['last_sync']) ? $row['last_sync'] : null;
    } catch (Exception $e) {
        $last_sync = null;
    }
    
} catch (Exception $e) {
    error_log("Import repositories error: " . $e->getMessage());
    $error = "An error occurred while importing repositories: " . $e->getMessage();
    $current_repos = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Import Repositories - BrickMMO Timesheets</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="../css/w3-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#DD5A3A",
                        "background-light": "#F9F9F9",
                        "background-dark": "#121212",
                        "card-light": "#F3E9E5",
                        "card-dark": "#1E1E1E",
                        "text-light": "#333333",
                        "text-dark": "#E0E0E0",
                        "subtext-light": "#666666",
                        "subtext-dark": "#A0A0A0",
                    },
                    fontFamily: {
                        display: ["Roboto", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.5rem",
                    },
                },
            },
        };
    </script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        
        /* Toggle Switch Styles */
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
        
        .visibility-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 500;
            background: #f0f0f0;
            color: #666;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-light dark:text-text-dark transition-colors duration-300">
    <div id="app">
        <header class="py-4 px-8 border-b border-gray-200 dark:border-gray-700">
            <div class="container mx-auto flex justify-between items-center">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-primary">BrickMMO</span>
                    <span class="text-sm text-subtext-light dark:text-subtext-dark ml-2">Admin Panel</span>
                </div>
                <nav class="flex items-center space-x-6">
                    <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="dashboard-new.php">Admin Dashboard</a>
                    <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="../index.php">Public Site</a>
                    <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="../auth/logout.php">Logout</a>
                    <button class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors" id="theme-toggle">
                        <span class="material-icons">brightness_6</span>
                    </button>
                </nav>
            </div>
        </header>
        
        <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-primary mb-2">Import Repositories</h1>
                <p class="text-subtext-light dark:text-subtext-dark">Import and update BrickMMO repositories from GitHub.</p>
            </div>
            
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Import Form -->
            <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-xl font-bold mb-2">Import from GitHub</h2>
                <p class="text-sm mb-4">
                    <?php if (!empty($last_sync)): ?>
                        Last sync: <?= htmlspecialchars($last_sync) ?>
                    <?php else: ?>
                        Last sync: Never
                    <?php endif; ?>
                </p>
                <p class="text-subtext-light dark:text-subtext-dark mb-4">
                    This will fetch all repositories from the <?= GITHUB_ORG ?> organization and import them into the database. 
                    Existing repositories will be updated with the latest information.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <button type="submit" name="import_repos" 
                            class="bg-primary hover:bg-red-700 text-white font-bold py-3 px-6 rounded transition-colors"
                            onclick="return confirm('This will import/update all repositories from GitHub. Continue?')">
                        <span class="material-icons inline mr-2">cloud_download</span>
                        Import Repositories
                    </button>
                </form>
            </div>
            
            <!-- Current Repositories -->
            <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold mb-4">Current Repositories (<?= count($current_repos) ?>)</h2>
                
                <?php if (!empty($current_repos)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($current_repos as $repo): ?>
                            <div class="bg-background-light dark:bg-background-dark p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div class="mb-3">
                                    <h3 class="font-semibold text-primary text-lg">BrickMMO/<?= htmlspecialchars($repo['name']) ?></h3>
                                </div>
                                
                                <div class="flex justify-between items-center mb-3">
                                    <span class="visibility-badge"><?= ucfirst(htmlspecialchars($repo['visibility'] ?? 'public')) ?></span>
                                    <label class="toggle-switch">
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="toggle_repo_id" value="<?= (int)$repo['id'] ?>">
                                            <input type="hidden" name="toggle_status" value="<?= $repo['is_active'] ? '0' : '1' ?>">
                                            <input 
                                                type="checkbox" 
                                                <?= $repo['is_active'] ? 'checked' : '' ?>
                                                onchange="this.form.submit()"
                                            />
                                            <span class="toggle-slider"></span>
                                        </form>
                                    </label>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <a href="<?= htmlspecialchars($repo['html_url']) ?>" target="_blank" 
                                       class="flex-1 text-center bg-gray-500 hover:bg-gray-600 text-white text-xs py-2 px-3 rounded transition-colors">
                                        GitHub
                                    </a>
                                    <a href="../repository.php?repo=<?= urlencode($repo['name']) ?>" 
                                       class="flex-1 text-center bg-primary hover:bg-red-700 text-white text-xs py-2 px-3 rounded transition-colors">
                                        View
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="material-icons text-6xl text-subtext-light dark:text-subtext-dark mb-4">folder_open</div>
                        <h3 class="text-lg font-medium mb-2">No repositories found</h3>
                        <p class="text-subtext-light dark:text-subtext-dark">Import repositories from GitHub to get started.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        document.getElementById('theme-toggle').addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
        });
    </script>
</body>
</html>
