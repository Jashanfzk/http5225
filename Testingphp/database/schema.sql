-- BrickMMO Timesheets Management System Database Schema
-- Created: September 2025

CREATE DATABASE IF NOT EXISTS brickmmo_timesheets;
USE brickmmo_timesheets;

-- Users table for GitHub OAuth authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    github_id INT UNIQUE NOT NULL,
    login VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    email VARCHAR(255),
    avatar_url TEXT,
    html_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_admin BOOLEAN DEFAULT FALSE
);

-- Applications (repositories) table
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    github_id INT UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    description TEXT,
    html_url TEXT,
    clone_url TEXT,
    language VARCHAR(100),
    languages JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Hours table for time tracking entries
CREATE TABLE hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    application_id INT NOT NULL,
    work_date DATE NOT NULL,
    duration DECIMAL(4,2) NOT NULL CHECK (duration >= 0.25 AND duration <= 16.00),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, work_date),
    INDEX idx_application_date (application_id, work_date)
);

-- Sessions table for user authentication
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    data TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Insert default admin user (replace with actual GitHub ID)
INSERT INTO users (github_id, login, name, is_admin) VALUES (12345, 'admin', 'Admin User', TRUE);

-- Insert sample applications from BrickMMO organization
INSERT INTO applications (github_id, name, full_name, description, html_url, language, is_active) VALUES
(1, '.GITHUB', 'BrickMMO/.GITHUB', 'Organization README.md file.', 'https://github.com/BrickMMO/.GITHUB', 'N/A', TRUE),
(2, 'API-V1', 'BrickMMO/API-V1', 'The core API for the BrickMMO network.', 'https://github.com/BrickMMO/API-V1', 'PHP', TRUE),
(3, 'APPLICATIONS-V1', 'BrickMMO/APPLICATIONS-V1', 'BrickMMO Applications', 'https://github.com/BrickMMO/APPLICATIONS-V1', 'CSS', TRUE),
(4, 'ASSETS', 'BrickMMO/ASSETS', 'BrickMMO Assets Directory', 'https://github.com/BrickMMO/ASSETS', 'N/A', TRUE),
(5, 'BMCLI', 'BrickMMO/BMCLI', 'BrickMMO CLI tool', 'https://github.com/BrickMMO/BMCLI', 'Python', TRUE),
(6, 'BMOS-API-V1', 'BrickMMO/BMOS-API-V1', 'A temporary API for BMOS', 'https://github.com/BrickMMO/BMOS-API-V1', 'PHP', TRUE),
(7, 'BMOS-CORE-V1', 'BrickMMO/BMOS-CORE-V1', 'BrickMMO OS in Python', 'https://github.com/BrickMMO/BMOS-CORE-V1', 'Python', TRUE),
(8, 'BRANDING-BRICKMMO', 'BrickMMO/BRANDING-BRICKMMO', 'BrickMMO Branding Guidelines', 'https://github.com/BrickMMO/BRANDING-BRICKMMO', 'N/A', TRUE);
