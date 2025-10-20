<?php
/**
 * Setup Script for BrickMMO Timesheets
 * Initializes database and provides configuration guidance
 */

// Prevent direct access in production
if (file_exists('config/config.php')) {
    $config = include 'config/config.php';
    if (!defined('DEVELOPMENT') || !DEVELOPMENT) {
        die('Setup script is only available in development mode.');
    }
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Step 1: Database Configuration
if ($step == 1 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'brickmmo_timesheets';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    
    try {
        // Test database connection
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $pdo->exec("USE `$db_name`");
        
        // Read and execute schema
        $schema = file_get_contents('database/schema.sql');
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        $success = "Database created and initialized successfully!";
        $step = 2;
        
    } catch (Exception $e) {
        $error = "Database setup failed: " . $e->getMessage();
    }
}

// Step 2: GitHub OAuth Configuration
if ($step == 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $github_client_id = $_POST['github_client_id'] ?? '';
    $github_client_secret = $_POST['github_client_secret'] ?? '';
    
    if (empty($github_client_id) || empty($github_client_secret)) {
        $error = "Please provide both GitHub Client ID and Client Secret.";
    } else {
        $success = "Configuration saved! You can now use the application.";
        $step = 3;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BrickMMO Timesheets Setup</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="css/w3-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#DD5A3A",
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
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-12 max-w-2xl">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-primary mb-2">BrickMMO Timesheets Setup</h1>
                <p class="text-gray-600">Configure your timesheets management system</p>
            </div>
            
            <!-- Progress Indicator -->
            <div class="flex items-center justify-center mb-8">
                <div class="flex items-center">
                    <div class="flex items-center <?= $step >= 1 ? 'text-primary' : 'text-gray-400' ?>">
                        <div class="w-8 h-8 rounded-full <?= $step >= 1 ? 'bg-primary text-white' : 'bg-gray-200' ?> flex items-center justify-center text-sm font-bold">1</div>
                        <span class="ml-2 text-sm">Database</span>
                    </div>
                    <div class="w-16 h-1 mx-2 <?= $step >= 2 ? 'bg-primary' : 'bg-gray-200' ?>"></div>
                    <div class="flex items-center <?= $step >= 2 ? 'text-primary' : 'text-gray-400' ?>">
                        <div class="w-8 h-8 rounded-full <?= $step >= 2 ? 'bg-primary text-white' : 'bg-gray-200' ?> flex items-center justify-center text-sm font-bold">2</div>
                        <span class="ml-2 text-sm">GitHub OAuth</span>
                    </div>
                    <div class="w-16 h-1 mx-2 <?= $step >= 3 ? 'bg-primary' : 'bg-gray-200' ?>"></div>
                    <div class="flex items-center <?= $step >= 3 ? 'text-primary' : 'text-gray-400' ?>">
                        <div class="w-8 h-8 rounded-full <?= $step >= 3 ? 'bg-primary text-white' : 'bg-gray-200' ?> flex items-center justify-center text-sm font-bold">3</div>
                        <span class="ml-2 text-sm">Complete</span>
                    </div>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <!-- Step 1: Database Setup -->
            <?php if ($step == 1): ?>
                <form method="POST" class="space-y-6">
                    <h2 class="text-xl font-bold mb-4">Database Configuration</h2>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="db_host">Database Host</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="db_name">Database Name</label>
                        <input type="text" id="db_name" name="db_name" value="brickmmo_timesheets" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="db_user">Database Username</label>
                        <input type="text" id="db_user" name="db_user" value="root" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="db_pass">Database Password</label>
                        <input type="password" id="db_pass" name="db_pass" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    
                    <button type="submit" class="w-full bg-primary hover:bg-red-700 text-white font-bold py-3 px-6 rounded transition-colors">
                        Setup Database
                    </button>
                </form>
            <?php endif; ?>
            
            <!-- Step 2: GitHub OAuth Setup -->
            <?php if ($step == 2): ?>
                <div class="space-y-6">
                    <h2 class="text-xl font-bold mb-4">GitHub OAuth Configuration</h2>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <h3 class="font-medium text-blue-800 mb-2">Setup Instructions:</h3>
                        <ol class="list-decimal list-inside text-sm text-blue-700 space-y-1">
                            <li>Go to <a href="https://github.com/settings/applications/new" target="_blank" class="underline">GitHub OAuth Apps</a></li>
                            <li>Fill in the application details:</li>
                            <ul class="list-disc list-inside ml-4 mt-1">
                                <li><strong>Application name:</strong> BrickMMO Timesheets</li>
                                <li><strong>Homepage URL:</strong> <?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) ?></li>
                                <li><strong>Authorization callback URL:</strong> <?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) ?>/auth/callback.php</li>
                            </ul>
                            <li>Click "Register application"</li>
                            <li>Copy the Client ID and Client Secret below</li>
                        </ol>
                    </div>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1" for="github_client_id">GitHub Client ID</label>
                            <input type="text" id="github_client_id" name="github_client_id" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1" for="github_client_secret">GitHub Client Secret</label>
                            <input type="text" id="github_client_secret" name="github_client_secret" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" required>
                        </div>
                        
                        <button type="submit" class="w-full bg-primary hover:bg-red-700 text-white font-bold py-3 px-6 rounded transition-colors">
                            Complete Setup
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
            <!-- Step 3: Setup Complete -->
            <?php if ($step == 3): ?>
                <div class="text-center space-y-6">
                    <div class="text-green-500">
                        <span class="material-icons text-6xl">check_circle</span>
                    </div>
                    
                    <h2 class="text-xl font-bold">Setup Complete!</h2>
                    <p class="text-gray-600">Your BrickMMO Timesheets system is now ready to use.</p>
                    
                    <div class="space-y-4">
                        <a href="index.php" class="block w-full bg-primary hover:bg-red-700 text-white font-bold py-3 px-6 rounded transition-colors">
                            Go to Public Site
                        </a>
                        <a href="dashboard.php" class="block w-full bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded transition-colors">
                            Go to User Dashboard
                        </a>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-left">
                        <h3 class="font-medium text-yellow-800 mb-2">Next Steps:</h3>
                        <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1">
                            <li>Import repositories using the Admin Dashboard</li>
                            <li>Configure your GitHub OAuth credentials in config/config.php</li>
                            <li>Test the login functionality</li>
                            <li>Start logging hours!</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
