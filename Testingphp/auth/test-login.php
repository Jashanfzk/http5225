<?php

require_once '../config/config.php';
require_once '../config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username'] ?? '');
    
    if (empty($username)) {
        $error = 'Please enter a username';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $user_stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
            $user_stmt->execute([$username]);
            $user = $user_stmt->fetch();
            
            if (!$user) {
                $insert_stmt = $db->prepare("
                    INSERT INTO users (github_id, login, name, avatar_url, html_url, is_admin) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $test_github_id = rand(10000, 99999);
                $insert_stmt->execute([
                    $test_github_id,
                    $username,
                    ucfirst($username),
                    'https://via.placeholder.com/100/DD5A3A/FFFFFF?text=' . urlencode(strtoupper(substr($username, 0, 1))),
                    'https://github.com/' . $username,
                    $username === 'admin' ? 1 : 0
                ]);
                
                $user_id = $db->lastInsertId();
                $user = [
                    'id' => $user_id,
                    'login' => $username,
                    'name' => ucfirst($username),
                    'avatar_url' => 'https://via.placeholder.com/100/DD5A3A/FFFFFF?text=' . urlencode(strtoupper(substr($username, 0, 1))),
                    'is_admin' => $username === 'admin' ? 1 : 0
                ];
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['github_id'] = $user['github_id'] ?? rand(10000, 99999);
            $_SESSION['login'] = $user['login'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['avatar_url'] = $user['avatar_url'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            redirect(BASE_URL . 'dashboard.php');
            
        } catch (Exception $e) {
            error_log("Test login error: " . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Test Login - BrickMMO Timesheets</title>
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
                </div>
                <nav class="flex items-center space-x-6">
                    <a class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors font-medium" href="../index.php">Home</a>
                    <button class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary transition-colors" id="theme-toggle">
                        <span class="material-icons">brightness_6</span>
                    </button>
                </nav>
            </div>
        </header>
        
        <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-md mx-auto">
                <div class="bg-card-light dark:bg-card-dark p-8 rounded-lg shadow-lg">
                    <div class="text-center mb-6">
                        <h1 class="text-2xl font-bold text-primary mb-2">Test Login</h1>
                        <p class="text-subtext-light dark:text-subtext-dark">Enter any username to test the system</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($message): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="username">Username</label>
                            <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:outline-none focus:ring-primary focus:border-primary rounded-md" 
                                   id="username" name="username" type="text" 
                                   placeholder="Enter any username (e.g., john, admin, jane)" 
                                   value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
                        </div>
                        
                        <button type="submit" name="login" 
                                class="w-full bg-primary hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-colors">
                            Login
                        </button>
                    </form>
                    
                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                        <h3 class="font-medium text-blue-800 dark:text-blue-200 mb-2">Test Accounts:</h3>
                        <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                            <li><strong>admin</strong> - Full admin access</li>
                            <li><strong>john</strong> - Regular user</li>
                            <li><strong>jane</strong> - Regular user</li>
                            <li><strong>Any name</strong> - Will create a new test user</li>
                        </ul>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <a href="../index.php" class="text-sm text-subtext-light dark:text-subtext-dark hover:text-primary">
                            ← Back to Home
                        </a>
                    </div>
                </div>
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
