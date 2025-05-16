<?php
require_once 'database.php';
require_once 'error_log.php';

echo "<h2>Database Check Tool</h2>";

// Check if admins table exists and has data
try {
    $result = $conn->query("SHOW TABLES LIKE 'admins'");
    $adminsTableExists = $result->rowCount() > 0;
    echo "<p>Admins table exists: " . ($adminsTableExists ? "Yes" : "No") . "</p>";
    
    if ($adminsTableExists) {
        $result = $conn->query("SELECT * FROM admins");
        $admins = $result->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Number of admin records: " . count($admins) . "</p>";
        
        if (count($admins) > 0) {
            echo "<h3>Admin Records:</h3>";
            echo "<ul>";
            foreach ($admins as $admin) {
                echo "<li>ID: " . $admin['admin_id'] . ", Name: " . $admin['first_name'] . " " . $admin['last_name'] . "</li>";
            }
            echo "</ul>";
        }
    }
} catch (PDOException $e) {
    echo "<p>Error checking admins table: " . $e->getMessage() . "</p>";
    log_error("Error checking admins table", ['message' => $e->getMessage()]);
}

// Check if announcements table exists and has data
try {
    $result = $conn->query("SHOW TABLES LIKE 'announcements'");
    $announcementsTableExists = $result->rowCount() > 0;
    echo "<p>Announcements table exists: " . ($announcementsTableExists ? "Yes" : "No") . "</p>";
    
    if ($announcementsTableExists) {
        $result = $conn->query("SELECT * FROM announcements");
        $announcements = $result->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Number of announcement records: " . count($announcements) . "</p>";
        
        if (count($announcements) > 0) {
            echo "<h3>Announcement Records:</h3>";
            echo "<ul>";
            foreach ($announcements as $announcement) {
                echo "<li>ID: " . $announcement['id'] . 
                     ", Title: " . $announcement['title'] . 
                     ", Created by: " . $announcement['created_by'] . 
                     ", Date: " . $announcement['created_at'] . "</li>";
            }
            echo "</ul>";
        }
    }
} catch (PDOException $e) {
    echo "<p>Error checking announcements table: " . $e->getMessage() . "</p>";
    log_error("Error checking announcements table", ['message' => $e->getMessage()]);
}

// Check if we can join the two tables
if ($adminsTableExists && $announcementsTableExists && count($admins) > 0 && count($announcements) > 0) {
    try {
        $join_query = "SELECT a.id, a.title, a.created_by, ad.first_name, ad.last_name 
                       FROM announcements a
                       JOIN admins ad ON a.created_by = ad.id";
        $result = $conn->query($join_query);
        $joined_data = $result->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Number of joined announcement-admin records: " . count($joined_data) . "</p>";
        
        if (count($joined_data) > 0) {
            echo "<h3>Joined Records:</h3>";
            echo "<ul>";
            foreach ($joined_data as $item) {
                echo "<li>Announcement ID: " . $item['id'] . 
                     ", Title: " . $item['title'] . 
                     ", Admin: " . $item['first_name'] . " " . $item['last_name'] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p><strong>JOIN issue detected</strong>: No records returned from the JOIN. The admin_id in the announcements table might not match any admin_id in the admins table.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error performing JOIN: " . $e->getMessage() . "</p>";
        log_error("Error performing JOIN", ['message' => $e->getMessage()]);
    }
}
?> 