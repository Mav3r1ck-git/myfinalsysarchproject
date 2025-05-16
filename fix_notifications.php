<?php
session_start();
require_once 'database.php';

echo "<h1>Notification System Fix</h1>";

// Step 1: Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    echo "<div style='color: red; font-weight: bold;'>Error: No user is logged in. Please <a href='user_login.php'>login</a> first.</div>";
    exit;
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['admin_id'];
$user_type = isset($_SESSION['user_id']) ? 'user' : 'admin';

echo "<h2>Logged in as: {$user_type} (ID: {$user_id})</h2>";

// Step 2: Check if notifications table exists, create if it doesn't
try {
    $tables = $conn->query("SHOW TABLES LIKE 'notifications'")->fetchAll();
    if (empty($tables)) {
        echo "<p>Creating notifications table...</p>";
        
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
        echo "<p style='color: green;'>✓ Notifications table created successfully!</p>";
    } else {
        echo "<p style='color: green;'>✓ Notifications table already exists.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error checking/creating table: " . $e->getMessage() . "</p>";
}

// Step 3: Test notification code removed
echo "<p style='color: green;'>✓ Test notification creation code removed.</p>";

// Step 4: Count notifications for this user
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Total notifications for your account: <strong>" . $result['count'] . "</strong></p>";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Unread notifications for your account: <strong>" . $result['count'] . "</strong></p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error counting notifications: " . $e->getMessage() . "</p>";
}

// Step 5: Display and clear JS cache
echo "<h2>Next Steps</h2>";
echo "<p>1. Your notification system should now be working. <a href='" . ($user_type === 'user' ? 'user_dashboard.php' : 'admin_dashboard.php') . "'>Go to dashboard</a></p>";
echo "<p>2. If you still don't see notifications, try clearing your browser cache or opening in an incognito window.</p>";
echo "<p>3. You can also try refreshing the page a few times.</p>";

// Add a JavaScript button for immediate redirect with cache clearing
echo "<button onclick=\"location.href='" . ($user_type === 'user' ? 'user_dashboard.php' : 'admin_dashboard.php') . "?nocache=" . time() . "'\">Go to Dashboard Now</button>";
?> 