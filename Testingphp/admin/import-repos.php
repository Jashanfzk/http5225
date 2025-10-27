<?php
/**
 * Admin: Import repositories from GitHub organization and upsert into applications table
 * Protect this endpoint - only admins should access it.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../applications-v1/lib/github_helper.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Basic admin-check (adjust to your auth system)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Helpful guidance when unauthorized during development
    http_response_code(403);
    echo 'Forbidden - admin only. For development you can temporarily enable admin by setting $_SESSION["is_admin"] = true in admin/import-repos.php (remove after use).';
    exit;
}

$db = (new Database())->getConnection();
$org = defined('GITHUB_ORG') ? GITHUB_ORG : 'BrickMMO';

$imported = 0;
$page = 1;
// fetch pages of repos
do {
    $url = "https://api.github.com/orgs/" . urlencode($org) . "/repos?per_page=100&page={$page}&type=all";
    $repos = github_request_cached($url, 900, false);
    if (!is_array($repos) || empty($repos)) break;

    $stmt = $db->prepare("INSERT INTO applications (name, description, html_url, primary_language, is_active, created_at, updated_at) VALUES (:name, :description, :html_url, :language, 1, NOW(), NOW()) ON DUPLICATE KEY UPDATE description = VALUES(description), html_url = VALUES(html_url), primary_language = VALUES(primary_language), updated_at = NOW()");

    foreach ($repos as $r) {
        if (!isset($r['name'])) continue;
        $stmt->execute([
            ':name' => $r['name'],
            ':description' => $r['description'] ?? '',
            ':html_url' => $r['html_url'] ?? '',
            ':language' => $r['language'] ?? null
        ]);
        $imported++;
    }

    $page++;
} while (count($repos) === 100);

// Simple UI feedback
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Import Repositories</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="max-width:900px;margin:40px auto;padding:0 20px">
    <h1>Import Repositories</h1>
    <p>Imported/Updated: <strong><?php echo (int)$imported; ?></strong> repositories from org <strong><?php echo htmlspecialchars($org); ?></strong>.</p>
    <p><a href="dashboard.php">Back to Admin Dashboard</a></p>
</body>
</html>
<?php
/**
 * Import Repositories from GitHub
 * Fetches all BrickMMO repositories and imports them to the database
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Require admin access
requireAdmin();

$message = '';
$error = '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_repos'])) {
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            // Fetch repositories from GitHub API
            $headers = ["User-Agent: BrickMMO-Timesheets"];
            
            function fetchGitHubData($url, $headers) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code !== 200) {
                    throw new Exception("GitHub API returned HTTP $http_code");
                }
                
                return json_decode($response, true);
            }
            
            // Fetch organization repositories
            $repos_url = "https://api.github.com/orgs/" . GITHUB_ORG . "/repos?per_page=100&sort=name";
            $repositories = fetchGitHubData($repos_url, $headers);
            
            if (empty($repositories)) {
                throw new Exception("No repositories found for organization " . GITHUB_ORG);
            }
            
            $imported_count = 0;
            $updated_count = 0;
            $errors = [];
            
            foreach ($repositories as $repo) {
                try {
                    // Check if repository already exists
                    $check_stmt = $db->prepare("SELECT id FROM applications WHERE github_id = ?");
                    $check_stmt->execute([$repo['id']]);
                    $existing = $check_stmt->fetch();
                    
                    // Get languages for this repository
                    $languages = [];
                    if ($repo['languages_url']) {
                        try {
                            $languages = fetchGitHubData($repo['languages_url'], $headers);
                        } catch (Exception $e) {
                            error_log("Failed to fetch languages for {$repo['name']}: " . $e->getMessage());
                        }
                    }
                    
                    $primary_language = $repo['language'] ?? 'N/A';
                    $languages_json = json_encode($languages);
                    
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
                            $repo['id']
                        ]);
                        
                        $updated_count++;
                    } else {
                        // Insert new repository
                        $insert_stmt = $db->prepare("
                            INSERT INTO applications (github_id, name, full_name, description, html_url, clone_url, language, languages, is_active) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
                        ");
                        
                        $insert_stmt->execute([
                            $repo['id'],
                            $repo['name'],
                            $repo['full_name'],
                            $repo['description'],
                            $repo['html_url'],
                            $repo['clone_url'],
                            $primary_language,
                            $languages_json
                        ]);
                        
                        $imported_count++;
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "Error processing {$repo['name']}: " . $e->getMessage();
                    error_log("Repository import error for {$repo['name']}: " . $e->getMessage());
                }
            }
            
            if ($imported_count > 0 || $updated_count > 0) {
                $message = "Import completed successfully! ";
                if ($imported_count > 0) {
                    $message .= "Imported $imported_count new repositories. ";
                }
                if ($updated_count > 0) {
                    $message .= "Updated $updated_count existing repositories. ";
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
    
    // Get current repositories
    $repos_stmt = $db->prepare("SELECT * FROM applications ORDER BY name ASC");
    $repos_stmt->execute();
    $current_repos = $repos_stmt->fetchAll();
    
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
                    <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="dashboard.php">Admin Dashboard</a>
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
                <h2 class="text-xl font-bold mb-4">Import from GitHub</h2>
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
                            <div class="bg-background-light dark:bg-background-dark p-4 rounded-lg">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-medium text-primary"><?= htmlspecialchars($repo['name']) ?></h3>
                                    <span class="px-2 py-1 text-xs rounded-full <?= $repo['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $repo['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </div>
                                
                                <?php if ($repo['description']): ?>
                                    <p class="text-sm text-subtext-light dark:text-subtext-dark mb-2">
                                        <?= htmlspecialchars($repo['description']) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="text-xs text-subtext-light dark:text-subtext-dark mb-2">
                                    <p><strong>Language:</strong> <?= htmlspecialchars($repo['language']) ?></p>
                                    <p><strong>GitHub ID:</strong> <?= $repo['github_id'] ?></p>
                                    <p><strong>Updated:</strong> <?= date('M j, Y g:i A', strtotime($repo['updated_at'])) ?></p>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <a href="<?= htmlspecialchars($repo['html_url']) ?>" target="_blank" 
                                       class="flex-1 text-center bg-gray-500 hover:bg-gray-600 text-white text-xs py-1 px-2 rounded transition-colors">
                                        GitHub
                                    </a>
                                    <a href="../repository.php?repo=<?= urlencode($repo['name']) ?>" 
                                       class="flex-1 text-center bg-primary hover:bg-red-700 text-white text-xs py-1 px-2 rounded transition-colors">
                                        View Details
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
