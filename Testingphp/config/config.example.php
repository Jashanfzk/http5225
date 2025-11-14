<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DEVELOPMENT', true); Enable development mode for better error messages
define('APP_NAME', 'BrickMMO Timesheets');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/http5225/Testingphp/');

define('GITHUB_CLIENT_ID', 'YOUR_GITHUB_CLIENT_ID_HERE');
define('GITHUB_CLIENT_SECRET', 'YOUR_GITHUB_CLIENT_SECRET_HERE');
define('GITHUB_REDIRECT_URI', BASE_URL . 'auth/callback.php');

define('DB_HOST', 'localhost');
define('DB_NAME', 'brickmmo_timesheets');
define('DB_USER', 'root');
define('DB_PASS', '');

define('CSRF_TOKEN_NAME', '_token');
define('SESSION_TIMEOUT', 3600); 1 hour

define('GITHUB_API_BASE', 'https://api.github.com');
define('GITHUB_ORG', 'BrickMMO');
define('GITHUB_TOKEN', ''); Optional: Add your personal access token here if needed to avoid rate limiting

define('ITEMS_PER_PAGE', 8);
define('MAX_HOURS_PER_DAY', 16.0);
define('MIN_HOURS_PER_ENTRY', 0.25);

if (defined('DEVELOPMENT') && DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . 'auth/test-login.php');
    }
}

function requireAdmin() {
    requireLogin();
    
    if (!isset($_SESSION['user_id'])) {
        redirect(BASE_URL . 'index.php?error=access_denied');
    }
    
    try {
        require_once __DIR__ . '/database.php';
        $db = (new Database())->getConnection();
        $stmt = $db->prepare('SELECT name FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || !isset($user['name']) || $user['name'] !== 'Jashanpreet Singh Gill') {
            redirect(BASE_URL . 'index.php?error=access_denied');
        }
        
        $updateStmt = $db->prepare('UPDATE users SET is_admin = 1 WHERE id = ?');
        $updateStmt->execute([$_SESSION['user_id']]);
        $_SESSION['is_admin'] = true;
        
    } catch (Exception $e) {
        error_log("Admin access check error: " . $e->getMessage());
        redirect(BASE_URL . 'index.php?error=access_denied');
    }
}

try {
    $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
    
    if (!$connection) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    
    $create_db_query = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    if (!mysqli_query($connection, $create_db_query)) {
        throw new Exception("Failed to create database: " . mysqli_error($connection));
    }
    
    if (!mysqli_select_db($connection, DB_NAME)) {
        throw new Exception("Failed to select database: " . mysqli_error($connection));
    }
    
    $create_table_query = "
        CREATE TABLE IF NOT EXISTS repositories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            url VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
    
    if (!mysqli_query($connection, $create_table_query)) {
        throw new Exception("Failed to create table: " . mysqli_error($connection));
    }
    
    mysqli_set_charset($connection, 'utf8mb4');
    
} catch (Exception $e) {
    if (DEVELOPMENT) {
        die("Connection error: " . $e->getMessage());
    } else {
        die("A database error occurred. Please try again later.");
    }
}

?>

