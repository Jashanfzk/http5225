<?php
/**
 * Test Database Connection
 * Quick test to verify database setup
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Database Connection Test</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test if tables exist
    $tables = ['users', 'applications', 'hours'];
    foreach ($tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' missing</p>";
        }
    }
    
    // Test users table
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>Users in database: " . $result['count'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config/config.php</p>";
    echo "<p>Make sure MySQL is running and the database 'brickmmo_timesheets' exists.</p>";
    echo "<p>You can create the database by running: <code>mysql -u root -p < database/schema.sql</code></p>";
}
?>

<p><a href="index.php">← Back to Home</a></p>
