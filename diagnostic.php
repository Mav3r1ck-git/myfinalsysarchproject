<?php
require_once 'database.php';
require_once 'error_log.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Announcement System Diagnostic</h1>";

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    echo "<p>Database connection: <strong>SUCCESS</strong></p>";
    echo "<p>PDO driver name: " . $conn->getAttribute(PDO::ATTR_DRIVER_NAME) . "</p>";
    echo "<p>Server version: " . $conn->getAttribute(PDO::ATTR_SERVER_VERSION) . "</p>";
} catch (Exception $e) {
    echo "<p>Database connection: <strong>FAILED</strong></p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Check admins table
echo "<h2>Admins Table</h2>";
try {
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'admins'");
    $adminsTableExists = $result->rowCount() > 0;
    echo "<p>Table exists: " . ($adminsTableExists ? "Yes" : "No") . "</p>";
    
    if ($adminsTableExists) {
        // Show structure
        echo "<h3>Table Structure:</h3>";
        $result = $conn->query("DESCRIBE admins");
        echo "<pre>";
        print_r($result->fetchAll(PDO::FETCH_ASSOC));
        echo "</pre>";
        
        // Count records
        $result = $conn->query("SELECT COUNT(*) as count FROM admins");
        $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Number of records: $count</p>";
        
        // Show records
        if ($count > 0) {
            $result = $conn->query("SELECT * FROM admins");
            $admins = $result->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Admin Records:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr>";
            foreach (array_keys($admins[0]) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            foreach ($admins as $admin) {
                echo "<tr>";
                foreach ($admin as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} catch (PDOException $e) {
    echo "<p>Error checking admins table: " . $e->getMessage() . "</p>";
}

// Check announcements table
echo "<h2>Announcements Table</h2>";
try {
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'announcements'");
    $announcementsTableExists = $result->rowCount() > 0;
    echo "<p>Table exists: " . ($announcementsTableExists ? "Yes" : "No") . "</p>";
    
    if ($announcementsTableExists) {
        // Show structure
        echo "<h3>Table Structure:</h3>";
        $result = $conn->query("DESCRIBE announcements");
        echo "<pre>";
        print_r($result->fetchAll(PDO::FETCH_ASSOC));
        echo "</pre>";
        
        // Count records
        $result = $conn->query("SELECT COUNT(*) as count FROM announcements");
        $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Number of records: $count</p>";
        
        // Show records
        if ($count > 0) {
            $result = $conn->query("SELECT * FROM announcements");
            $announcements = $result->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Announcement Records:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr>";
            foreach (array_keys($announcements[0]) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            foreach ($announcements as $announcement) {
                echo "<tr>";
                foreach ($announcement as $value) {
                    if ($key == 'content') {
                        echo "<td>" . substr(htmlspecialchars($value), 0, 100) . "...</td>";
                    } else {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} catch (PDOException $e) {
    echo "<p>Error checking announcements table: " . $e->getMessage() . "</p>";
}

// Test the JOIN query that would be used in the dashboard
echo "<h2>JOIN Query Test</h2>";
if ($adminsTableExists && $announcementsTableExists) {
    try {
        $query = "SELECT a.*, ad.first_name, ad.last_name 
                 FROM announcements a
                 JOIN admins ad ON a.created_by = ad.admin_id
                 ORDER BY a.created_at DESC
                 LIMIT 5";
        
        $result = $conn->query($query);
        $joined_data = $result->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Number of records returned by JOIN: " . count($joined_data) . "</p>";
        
        if (count($joined_data) > 0) {
            echo "<h3>JOIN Result:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr>";
            foreach (array_keys($joined_data[0]) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            foreach ($joined_data as $row) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    if ($key == 'content') {
                        echo "<td>" . substr(htmlspecialchars($value), 0, 100) . "...</td>";
                    } else {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p><strong>Problem detected:</strong> JOIN query returned no results.</p>";
            
            // Additional diagnostics
            $result = $conn->query("SELECT * FROM announcements LIMIT 1");
            $announcement = $result->fetch(PDO::FETCH_ASSOC);
            
            if ($announcement) {
                $created_by = $announcement['created_by'];
                echo "<p>Sample announcement created_by value: $created_by</p>";
                
                $result = $conn->query("SELECT * FROM admins WHERE admin_id = $created_by");
                $matching_admin = $result->fetch(PDO::FETCH_ASSOC);
                
                if ($matching_admin) {
                    echo "<p>Matching admin found for created_by=$created_by</p>";
                } else {
                    echo "<p><strong>Issue found:</strong> No admin found with admin_id=$created_by</p>";
                }
            }
        }
    } catch (PDOException $e) {
        echo "<p>Error executing JOIN: " . $e->getMessage() . "</p>";
    }
}

// Session information
echo "<h2>Session Info</h2>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?> 