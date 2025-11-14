<?php
require_once '../config/config.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    setcookie(session_name(), '', time() - 3600, '/');
}

session_regenerate_id(true);
unset($_SESSION['oauth_state']);

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$github_url = 'https://github.com/login/oauth/authorize?' . http_build_query([
    'client_id' => GITHUB_CLIENT_ID,
    'redirect_uri' => GITHUB_REDIRECT_URI,
    'scope' => 'user:email',
    'state' => $state,
    'allow_signup' => 'true'
]);

header("Location: $github_url");
exit();
?>
