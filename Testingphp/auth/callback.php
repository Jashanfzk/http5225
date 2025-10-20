<?php
/**
 * GitHub OAuth Callback
 * Handles the OAuth callback from GitHub
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Check if we have the authorization code
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    redirect(BASE_URL . 'index.php?error=oauth_error');
}

// Validate state parameter
if (!isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    redirect(BASE_URL . 'index.php?error=invalid_state');
}

$code = $_GET['code'];

// Exchange code for access token
$token_url = 'https://github.com/login/oauth/access_token';
$token_data = [
    'client_id' => GITHUB_CLIENT_ID,
    'client_secret' => GITHUB_CLIENT_SECRET,
    'code' => $code,
    'redirect_uri' => GITHUB_REDIRECT_URI
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'User-Agent: BrickMMO-Timesheets'
]);

$response = curl_exec($ch);
curl_close($ch);

$token_response = json_decode($response, true);

if (!isset($token_response['access_token'])) {
    // Log the error for debugging
    error_log("GitHub OAuth token error: " . $response);
    
    // Check if it's a client secret issue
    if (strpos($response, 'bad_verification_code') !== false) {
        redirect(BASE_URL . 'index.php?error=oauth_setup&message=Please check your GitHub OAuth Client Secret in config.php');
    } elseif (strpos($response, 'incorrect_client_credentials') !== false) {
        redirect(BASE_URL . 'index.php?error=oauth_setup&message=Please check your GitHub OAuth Client ID and Secret in config.php');
    } else {
        redirect(BASE_URL . 'index.php?error=token_error&message=' . urlencode($response));
    }
}

$access_token = $token_response['access_token'];

// Get user information from GitHub
$user_url = 'https://api.github.com/user';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: token ' . $access_token,
    'Accept: application/json',
    'User-Agent: BrickMMO-Timesheets'
]);

$user_response = curl_exec($ch);
curl_close($ch);

$user_data = json_decode($user_response, true);

if (!isset($user_data['id'])) {
    // Log the error for debugging
    error_log("GitHub user data error: " . $user_response);
    redirect(BASE_URL . 'index.php?error=user_data_error&message=' . urlencode($user_response));
}

// Connect to database
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    redirect(BASE_URL . 'index.php?error=database_error&message=Database connection failed. Please check your database configuration.');
}

// Check if user exists in database
$stmt = $db->prepare("SELECT * FROM users WHERE github_id = ?");
$stmt->execute([$user_data['id']]);
$user = $stmt->fetch();

if (!$user) {
    // Create new user
    $stmt = $db->prepare("
        INSERT INTO users (github_id, login, name, email, avatar_url, html_url) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_data['id'],
        $user_data['login'],
        $user_data['name'] ?? $user_data['login'],
        $user_data['email'] ?? null,
        $user_data['avatar_url'],
        $user_data['html_url']
    ]);
    
    $user_id = $db->lastInsertId();
} else {
    // Update existing user
    $stmt = $db->prepare("
        UPDATE users SET 
            login = ?, name = ?, email = ?, avatar_url = ?, html_url = ?, updated_at = CURRENT_TIMESTAMP
        WHERE github_id = ?
    ");
    $stmt->execute([
        $user_data['login'],
        $user_data['name'] ?? $user_data['login'],
        $user_data['email'] ?? null,
        $user_data['avatar_url'],
        $user_data['html_url'],
        $user_data['id']
    ]);
    
    $user_id = $user['id'];
}

// Set session variables
$_SESSION['user_id'] = $user_id;
$_SESSION['github_id'] = $user_data['id'];
$_SESSION['login'] = $user_data['login'];
$_SESSION['name'] = $user_data['name'] ?? $user_data['login'];
$_SESSION['avatar_url'] = $user_data['avatar_url'];
$_SESSION['is_admin'] = $user['is_admin'] ?? false;

// Clear OAuth state
unset($_SESSION['oauth_state']);

// Redirect to dashboard
redirect(BASE_URL . 'dashboard.php');
?>
