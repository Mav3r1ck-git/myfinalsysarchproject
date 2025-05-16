<?php
$hostName = "localhost";
$dbUser = "root";
$dbPassword = "";
$dbName = "database_sysarch";

try {
    // Create PDO connection
    $conn = new PDO("mysql:host=$hostName;dbname=$dbName", $dbUser, $dbPassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Admin Account Fix Tool</h2>";
    
    // Check if admins table exists
    $result = $conn->query("SHOW TABLES LIKE 'admins'");
    $adminsTableExists = $result->rowCount() > 0;
    
    if (!$adminsTableExists) {
        // Create admins table if it doesn't exist
        $conn->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "<p>Created admins table</p>";
    }
    
    // Check for admin with ID 1
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        // Add admin with ID 1 if it doesn't exist
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO admins (id, first_name, last_name, email, password) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([1, 'Admin', 'User', 'admin@example.com', $hashedPassword]);
        
        if ($result) {
            echo "<p>Successfully created admin account with ID 1</p>";
        } else {
            echo "<p>Failed to create admin account</p>";
        }
    } else {
        echo "<p>Admin account with ID 1 already exists</p>";
    }
    
    // Now create a test announcement
    $result = $conn->query("SHOW TABLES LIKE 'announcements'");
    $announcementsTableExists = $result->rowCount() > 0;
    
    if (!$announcementsTableExists) {
        // Create announcements table if it doesn't exist
        $conn->exec("
            CREATE TABLE IF NOT EXISTS announcements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "<p>Created announcements table</p>";
    }
    
    // Add a test announcement
    $stmt = $conn->prepare("INSERT INTO announcements (title, content, created_by) VALUES (?, ?, ?)");
    $result = $stmt->execute(['Welcome to the Student Portal', '<p>Welcome to our new student portal system! Here you can access all your academic resources.</p>', 1]);
    
    if ($result) {
        echo "<p>Successfully created a test announcement</p>";
    } else {
        echo "<p>Failed to create test announcement</p>";
    }
    
    // Display all admins
    $stmt = $conn->query("SELECT * FROM admins");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Admin Accounts:</h3>";
    echo "<ul>";
    foreach ($admins as $admin) {
        echo "<li>ID: " . $admin['id'] . ", Name: " . $admin['first_name'] . " " . $admin['last_name'] . ", Email: " . $admin['email'] . "</li>";
    }
    echo "</ul>";
    
    // Display all announcements
    $stmt = $conn->query("SELECT * FROM announcements");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Announcements:</h3>";
    echo "<ul>";
    foreach ($announcements as $announcement) {
        echo "<li>ID: " . $announcement['id'] . ", Title: " . $announcement['title'] . ", Created by: " . $announcement['created_by'] . "</li>";
    }
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?> 