<?php
require_once(__DIR__ . '/../config/config.php');

try {
    // Create repositories table
    $query = "CREATE TABLE IF NOT EXISTS repositories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        url VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($connection, $query)) {
        throw new Exception("Error creating repositories table: " . mysqli_error($connection));
    }

    // Create contributors table
    $query = "CREATE TABLE IF NOT EXISTS contributors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        github_id INT NOT NULL UNIQUE,
        login VARCHAR(255) NOT NULL,
        avatar_url VARCHAR(255),
        html_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($connection, $query)) {
        throw new Exception("Error creating contributors table: " . mysqli_error($connection));
    }

    // Create repo_contributors junction table
    $query = "CREATE TABLE IF NOT EXISTS repo_contributors (
        repo_id INT,
        contributor_id INT,
        contributions INT DEFAULT 0,
        PRIMARY KEY (repo_id, contributor_id),
        FOREIGN KEY (repo_id) REFERENCES repositories(id) ON DELETE CASCADE,
        FOREIGN KEY (contributor_id) REFERENCES contributors(id) ON DELETE CASCADE
    )";
    
    if (!mysqli_query($connection, $query)) {
        throw new Exception("Error creating repo_contributors table: " . mysqli_error($connection));
    }

    echo "Database tables created successfully!";

} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage());
} finally {
    mysqli_close($connection);
}