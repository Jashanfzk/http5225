<?php
/**
 * Simple Login Test
 * Bypass database for testing authentication flow
 */

require_once 'config/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    
    if (empty($username)) {
        $error = 'Please enter a username';
    } else {
        // Set session without database
        $_SESSION['user_id'] = 1;
        $_SESSION['github_id'] = 12345;
        $_SESSION['login'] = $username;
        $_SESSION['name'] = ucfirst($username);
        $_SESSION['avatar_url'] = 'https://via.placeholder.com/100/DD5A3A/FFFFFF?text=' . urlencode(strtoupper(substr($username, 0, 1)));
        $_SESSION['is_admin'] = ($username === 'admin') ? 1 : 0;
        
        // Redirect to dashboard
        header("Location: " . BASE_URL . "dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #ff5b00; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Simple Login Test</h1>
    <p>This bypasses database setup for testing authentication flow.</p>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" placeholder="Enter any username" required>
        </div>
        
        <button type="submit" name="login">Login</button>
    </form>
    
    <h3>Test Accounts:</h3>
    <ul>
        <li><strong>admin</strong> - Admin access</li>
        <li><strong>john</strong> - Regular user</li>
        <li><strong>jane</strong> - Regular user</li>
    </ul>
    
    <p><a href="index.php">← Back to Home</a></p>
    
    <h3>Current Session Status:</h3>
    <p>User ID: <?= $_SESSION['user_id'] ?? 'Not logged in' ?></p>
    <p>Login: <?= $_SESSION['login'] ?? 'Not logged in' ?></p>
    <p>Name: <?= $_SESSION['name'] ?? 'Not logged in' ?></p>
</body>
</html>
