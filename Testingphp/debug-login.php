<?php
/**
 * Debug Login Test
 * Simple test to debug authentication flow
 */

require_once 'config/config.php';

echo "<h1>Debug Login Test</h1>";

// Test 1: Check if config is loaded
echo "<h2>1. Config Test</h2>";
echo "BASE_URL: " . BASE_URL . "<br>";
echo "Session started: " . (session_status() === PHP_SESSION_ACTIVE ? "Yes" : "No") . "<br>";

// Test 2: Check database connection
echo "<h2>2. Database Test</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "Database connection: <span style='color: green;'>SUCCESS</span><br>";
} catch (Exception $e) {
    echo "Database connection: <span style='color: red;'>FAILED</span> - " . $e->getMessage() . "<br>";
}

// Test 3: Test redirect function
echo "<h2>3. Redirect Test</h2>";
echo "Redirect function exists: " . (function_exists('redirect') ? "Yes" : "No") . "<br>";

// Test 4: Simulate login
echo "<h2>4. Login Simulation</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
    echo "Processing login...<br>";
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Create test user
        $stmt = $db->prepare("
            INSERT INTO users (github_id, login, name, avatar_url, html_url, is_admin) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE name = VALUES(name)
        ");
        $stmt->execute([
            99999,
            'testuser',
            'Test User',
            'https://via.placeholder.com/100/DD5A3A/FFFFFF?text=T',
            'https://github.com/testuser',
            0
        ]);
        
        // Get user ID
        $user_stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
        $user_stmt->execute(['testuser']);
        $user = $user_stmt->fetch();
        
        if ($user) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['github_id'] = 99999;
            $_SESSION['login'] = 'testuser';
            $_SESSION['name'] = 'Test User';
            $_SESSION['avatar_url'] = 'https://via.placeholder.com/100/DD5A3A/FFFFFF?text=T';
            $_SESSION['is_admin'] = 0;
            
            echo "Session set successfully!<br>";
            echo "User ID: " . $_SESSION['user_id'] . "<br>";
            echo "Login: " . $_SESSION['login'] . "<br>";
            
            echo "<br><a href='dashboard.php' style='background: #ff5b00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a>";
        } else {
            echo "Failed to create/find user<br>";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "<form method='POST'>";
    echo "<button type='submit' name='test_login' style='background: #ff5b00; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Login</button>";
    echo "</form>";
}

// Test 5: Check current session
echo "<h2>5. Current Session</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Login: " . ($_SESSION['login'] ?? 'Not set') . "<br>";
echo "Name: " . ($_SESSION['name'] ?? 'Not set') . "<br>";

echo "<br><a href='index.php'>Back to Home</a>";
?>
