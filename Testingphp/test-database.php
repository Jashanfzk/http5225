<?php
/**
 * Database Test Script
 * Test database connection and basic functionality
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Database Test</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Test users table
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    echo "<p>👥 Users in database: $user_count</p>";
    
    // Test applications table
    $stmt = $db->query("SELECT COUNT(*) as count FROM applications");
    $app_count = $stmt->fetch()['count'];
    echo "<p>📁 Applications in database: $app_count</p>";
    
    // Test hours table
    $stmt = $db->query("SELECT COUNT(*) as count FROM hours");
    $hours_count = $stmt->fetch()['count'];
    echo "<p>⏰ Time entries in database: $hours_count</p>";
    
    // Show sample users
    $stmt = $db->query("SELECT id, login, name FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    echo "<h3>Sample Users:</h3>";
    echo "<ul>";
    foreach ($users as $user) {
        echo "<li>ID: {$user['id']}, Login: {$user['login']}, Name: {$user['name']}</li>";
    }
    echo "</ul>";
    
    // Show sample applications
    $stmt = $db->query("SELECT id, name, is_active FROM applications LIMIT 5");
    $apps = $stmt->fetchAll();
    echo "<h3>Sample Applications:</h3>";
    echo "<ul>";
    foreach ($apps as $app) {
        $status = $app['is_active'] ? 'Active' : 'Inactive';
        echo "<li>ID: {$app['id']}, Name: {$app['name']}, Status: $status</li>";
    }
    echo "</ul>";
    
    echo "<p style='color: green;'>✅ All tests passed!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Common solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure XAMPP MySQL is running</li>";
    echo "<li>Run setup.php to create the database</li>";
    echo "<li>Check database credentials in config/config.php</li>";
    echo "</ul>";
}
?>

