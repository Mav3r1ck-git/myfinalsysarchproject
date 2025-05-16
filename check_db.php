<?php
require_once 'database.php';

// Check if admins table exists
try {
    $result = $conn->query("SHOW TABLES LIKE 'admins'");
    $adminTableExists = $result->rowCount() > 0;
    echo "Admins table exists: " . ($adminTableExists ? "Yes" : "No") . "\n";
    
    if ($adminTableExists) {
        $result = $conn->query("DESCRIBE admins");
        echo "Admins table structure:\n";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Key'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Error checking admins table: " . $e->getMessage() . "\n";
}

echo "\n";

// Check if sitin_sessions table exists
try {
    $result = $conn->query("SHOW TABLES LIKE 'sitin_sessions'");
    $sitinTableExists = $result->rowCount() > 0;
    echo "Sitin_sessions table exists: " . ($sitinTableExists ? "Yes" : "No") . "\n";
    
    if ($sitinTableExists) {
        $result = $conn->query("DESCRIBE sitin_sessions");
        echo "Sitin_sessions table structure:\n";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Key'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Error checking sitin_sessions table: " . $e->getMessage() . "\n";
}
?> 