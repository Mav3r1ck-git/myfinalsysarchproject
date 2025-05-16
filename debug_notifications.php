<?php
require_once 'database.php';
require_once 'notification_helpers.php';

// Set to true to view all notifications, false to just count them
$view_all = true;

// Count all notifications in the database
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM notifications");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Total notifications in database: " . $result['count'] . "</h3>";
} catch (PDOException $e) {
    echo "<h3>Error counting notifications: " . $e->getMessage() . "</h3>";
    // Check if the table exists
    echo "<p>Checking if the notifications table exists...</p>";
    try {
        $tables = $conn->query("SHOW TABLES LIKE 'notifications'")->fetchAll();
        if (empty($tables)) {
            echo "<p style='color: red;'>The notifications table does not exist!</p>";
            echo "<h4>Creating notifications table:</h4>";
            echo "<pre>";
            echo "CREATE TABLE `notifications` (
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
            echo "</pre>";
        } else {
            echo "<p>Notifications table exists.</p>";
        }
    } catch (PDOException $e2) {
        echo "<p>Error checking tables: " . $e2->getMessage() . "</p>";
    }
}

// List notifications by user
echo "<hr><h3>Notifications by User</h3>";
try {
    $stmt = $conn->query("SELECT user_id, COUNT(*) as count FROM notifications GROUP BY user_id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p>No notifications found for any user.</p>";
    } else {
        echo "<ul>";
        foreach ($users as $user) {
            echo "<li>User ID: " . htmlspecialchars($user['user_id']) . " - " . $user['count'] . " notifications</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "<p>Error getting users: " . $e->getMessage() . "</p>";
}

// View all notifications if specified
if ($view_all) {
    echo "<hr><h3>All Notifications</h3>";
    try {
        $stmt = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($notifications)) {
            echo "<p>No notifications found in the database.</p>";
        } else {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>Read</th><th>Created At</th></tr>";
            
            foreach ($notifications as $notification) {
                echo "<tr>";
                echo "<td>" . $notification['id'] . "</td>";
                echo "<td>" . htmlspecialchars($notification['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars($notification['title']) . "</td>";
                echo "<td>" . htmlspecialchars($notification['message']) . "</td>";
                echo "<td>" . htmlspecialchars($notification['type']) . "</td>";
                echo "<td>" . ($notification['is_read'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . $notification['created_at'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p>Error getting notifications: " . $e->getMessage() . "</p>";
    }
}

// Add a sample notification for testing (uncomment to use)
if (isset($_GET['add_test'])) {
    echo "<hr><h3>Adding Test Notification</h3>";
    // Get user ID from URL or use a default
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '2020-00001';
    
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
            echo "<p style='color: green;'>Test notification added successfully for user ID: " . htmlspecialchars($user_id) . "</p>";
        } else {
            echo "<p style='color: red;'>Failed to add test notification</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p>To add a test notification, <a href='?add_test=1'>click here</a>.</p>";
echo "<p>To add a test notification for a specific user: <code>?add_test=1&user_id=YOUR_USER_ID</code></p>";

?> 