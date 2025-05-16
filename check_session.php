<?php
session_start();
require_once 'database.php';

echo "<h2>Session Information</h2>";

echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "<h3>User Details</h3>";
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<pre>";
            print_r($user);
            echo "</pre>";
        } else {
            echo "<p>No user found with ID: " . htmlspecialchars($user_id) . "</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error fetching user: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>User Notifications</h3>";
    
    try {
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($notifications)) {
            echo "<p>Found " . count($notifications) . " notifications for this user.</p>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Read</th><th>Created At</th></tr>";
            
            foreach ($notifications as $notification) {
                echo "<tr>";
                echo "<td>" . $notification['id'] . "</td>";
                echo "<td>" . htmlspecialchars($notification['title']) . "</td>";
                echo "<td>" . htmlspecialchars($notification['message']) . "</td>";
                echo "<td>" . htmlspecialchars($notification['type']) . "</td>";
                echo "<td>" . ($notification['is_read'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . $notification['created_at'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No notifications found for this user.</p>";
            
            // Add a test notification button
            echo "<form method='post'>";
            echo "<input type='submit' name='add_test_notification' value='Add Test Notification'>";
            echo "</form>";
            
            if (isset($_POST['add_test_notification'])) {
                try {
                    $stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, title, message, type) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $result = $stmt->execute([
                        $user_id,
                        'Test Notification',
                        'This is a test notification added at ' . date('Y-m-d H:i:s'),
                        'info'
                    ]);
                    
                    if ($result) {
                        echo "<p style='color: green;'>Test notification added successfully!</p>";
                        echo "<p><a href=''>Refresh to see the notification</a></p>";
                    } else {
                        echo "<p style='color: red;'>Failed to add test notification</p>";
                    }
                } catch (PDOException $e) {
                    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
                }
            }
        }
    } catch (PDOException $e) {
        echo "<p>Error fetching notifications: " . $e->getMessage() . "</p>";
        
        // Check if notifications table exists
        try {
            $tables = $conn->query("SHOW TABLES LIKE 'notifications'")->fetchAll();
            if (empty($tables)) {
                echo "<p style='color: red;'>The notifications table does not exist!</p>";
                echo "<h4>Creating notifications table:</h4>";
                
                // Create the notifications table
                try {
                    $sql = "CREATE TABLE `notifications` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `user_id` varchar(50) NOT NULL,
                      `title` varchar(255) NOT NULL,
                      `message` text NOT NULL,
                      `type` varchar(50) DEFAULT 'info',
                      `reference_id` int(11) DEFAULT NULL,
                      `is_read` tinyint(1) DEFAULT 0,
                      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                      PRIMARY KEY (`id`),
                      KEY `user_id` (`user_id`),
                      KEY `is_read` (`is_read`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                    
                    $conn->exec($sql);
                    echo "<p style='color: green;'>Notifications table created successfully!</p>";
                } catch (PDOException $e2) {
                    echo "<p style='color: red;'>Error creating table: " . $e2->getMessage() . "</p>";
                }
            } else {
                echo "<p>Notifications table exists, but there was an error: " . $e->getMessage() . "</p>";
            }
        } catch (PDOException $e2) {
            echo "<p>Error checking tables: " . $e2->getMessage() . "</p>";
        }
    }
} else {
    echo "<p>No user is logged in.</p>";
}
?> 