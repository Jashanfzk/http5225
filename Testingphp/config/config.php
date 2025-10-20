<?php
/**
 * Application Configuration
 * BrickMMO Timesheets Management System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Environment configuration
define('DEVELOPMENT', true); // Enable development mode for better error messages
define('APP_NAME', 'BrickMMO Timesheets');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/http5225/Testingphp/');

// GitHub OAuth Configuration
// TODO: Replace with your actual GitHub OAuth App credentials
define('GITHUB_CLIENT_ID', 'Ov23liSoXSEVRlA3Zk6e'); // Your actual GitHub OAuth Client ID
define('GITHUB_CLIENT_SECRET', 'c2cf4a7a05b3d01b8d818fd4ef02116ac0e10697'); // Replace with real client secret
define('GITHUB_REDIRECT_URI', BASE_URL . 'auth/callback.php');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'brickmmo_timesheets');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security Configuration
define('CSRF_TOKEN_NAME', '_token');
define('SESSION_TIMEOUT', 3600); // 1 hour

// GitHub API Configuration
define('GITHUB_API_BASE', 'https://api.github.com');
define('GITHUB_ORG', 'BrickMMO');

// Application Settings
define('ITEMS_PER_PAGE', 8);
define('MAX_HOURS_PER_DAY', 16.0);
define('MIN_HOURS_PER_ENTRY', 0.25);

// Error Reporting
if (defined('DEVELOPMENT') && DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Utility Functions
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
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        redirect(BASE_URL . 'index.php?error=access_denied');
    }
}
?>
