<?php
/**
 * GitHub OAuth Login
 * Redirects user to GitHub OAuth authorization
 */

require_once '../config/config.php';

// Generate state parameter for CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Build GitHub OAuth URL
$github_url = 'https://github.com/login/oauth/authorize?' . http_build_query([
    'client_id' => GITHUB_CLIENT_ID,
    'redirect_uri' => GITHUB_REDIRECT_URI,
    'scope' => 'user:email',
    'state' => $state
]);

// Redirect to GitHub
header("Location: $github_url");
exit();
?>
