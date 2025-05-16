<?php
// Database connection
require_once 'database.php';

try {
    // Create users table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            student_id VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            course VARCHAR(100) NOT NULL,
            year_level VARCHAR(20) NOT NULL,
            profile_picture VARCHAR(255) DEFAULT 'default_profile_picture.png',
            points INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create admins table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS admins (
            admin_id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            profile_picture VARCHAR(255) DEFAULT 'default_profile_picture.png',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create materials table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS materials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            file_path VARCHAR(255) NOT NULL,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create lab schedules table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS lab_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            lab_room VARCHAR(50) NOT NULL,
            description TEXT,
            file_path VARCHAR(255) NOT NULL,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create sit-in sessions table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS sitin_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            lab_room VARCHAR(50) NOT NULL,
            pc_number VARCHAR(20) NOT NULL,
            start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            end_time TIMESTAMP NULL DEFAULT NULL,
            status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");

    // Create feedback table for completed sit-in sessions
    $conn->exec("
        CREATE TABLE IF NOT EXISTS session_feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            comments TEXT,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES sitin_sessions(id) ON DELETE CASCADE
        )
    ");
    
    // Create announcements table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES admins(admin_id) ON DELETE CASCADE
        )
    ");

    // Create necessary directories if they don't exist
    $directories = [
        'uploads',
        'uploads/materials',
        'uploads/schedules',
        'uploads/profile_pictures'
    ];

    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    echo "Database and tables created successfully.";
} catch(PDOException $e) {
    echo "Error creating database tables: " . $e->getMessage();
}
?> 