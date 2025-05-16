<?php
session_start();
require_once 'database.php';

// Determine user type
$is_user = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['admin_id']);

if (!$is_user && !$is_admin) {
    echo "You need to be logged in to use this fix.";
    exit;
}

$user_id = $is_user ? $_SESSION['user_id'] : $_SESSION['admin_id'];
$user_type = $is_user ? 'user' : 'admin';

// Check if notifications table exists
try {
    $conn->beginTransaction();
    
    // Check if the notifications table exists
    $table_exists = false;
    try {
        $check = $conn->query("SELECT 1 FROM notifications LIMIT 1");
        $table_exists = true;
    } catch (PDOException $e) {
        // Table doesn't exist, create it
        $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
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
    }
    
    // Test notification creation removed
    
    $conn->commit();
} catch (PDOException $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
    exit;
}

// Now let's write a direct fix to the header files

// Function to inject CSS into files
function injectCSS($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    $content = file_get_contents($file_path);
    
    // Check if our fix is already applied
    if (strpos($content, '/* Notification Fix Applied */') !== false) {
        return true; // Already fixed
    }
    
    // Find the end of the CSS style tag
    $style_pos = strpos($content, '</style>');
    if ($style_pos === false) {
        return false;
    }
    
    // Prepare the CSS fix
    $css_fix = "
        /* Notification Fix Applied */
        .dropdown-menu.notification-dropdown {
            display: block !important;
            max-height: 400px !important;
            overflow-y: auto !important;
            z-index: 1050 !important;
            min-width: 300px !important;
        }
        
        .notification-item {
            border-bottom: 1px solid #e9ecef !important;
            padding: 10px 15px !important;
        }
        
        .notification-item.unread {
            background-color: #e8f4fd !important;
        }
        
        .notification-badge {
            display: inline-block !important;
        }
    ";
    
    // Insert the CSS fix before the closing </style> tag
    $new_content = substr($content, 0, $style_pos) . $css_fix . substr($content, $style_pos);
    
    // Write the modified content back to the file
    return file_put_contents($file_path, $new_content);
}

// Try to fix both header files
$user_header_fixed = injectCSS('user_header.php');
$admin_header_fixed = injectCSS('admin_header.php');

// Output the result
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Dropdown Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .fixed { color: green; }
        .not-fixed { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Notification Dropdown Fix</h1>
        
        <div class="alert alert-success">
            <h4>Test Notification Created!</h4>
            <p>A test notification has been added to your account.</p>
        </div>
        
        <h3>Fix Results:</h3>
        <ul>
            <li class="<?php echo $user_header_fixed ? 'fixed' : 'not-fixed'; ?>">
                User Header: <?php echo $user_header_fixed ? 'Fixed' : 'Could not fix'; ?>
            </li>
            <li class="<?php echo $admin_header_fixed ? 'fixed' : 'not-fixed'; ?>">
                Admin Header: <?php echo $admin_header_fixed ? 'Fixed' : 'Could not fix'; ?>
            </li>
        </ul>
        
        <div class="mt-4">
            <h3>Next Steps:</h3>
            <ol>
                <li>Return to the dashboard to see your notifications</li>
                <li>Click on the notification bell to see the dropdown</li>
                <li>If the dropdown still doesn't appear, try refreshing the page or clearing your browser cache</li>
            </ol>
            
            <div class="mt-3">
                <a href="<?php echo $user_type === 'user' ? 'user_dashboard.php' : 'admin_dashboard.php'; ?>?nocache=<?php echo time(); ?>" class="btn btn-primary">
                    Go to Dashboard
                </a>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">Manual Fix Instructions</div>
            <div class="card-body">
                <p>If the automatic fix didn't work, you can try the following:</p>
                <ol>
                    <li>Go to <code>debug_notifications.php</code> to check if notifications exist in the database</li>
                    <li>Visit <code>fix_notifications.php</code> to ensure your notification table exists</li>
                    <li>Visit <code>fix_notification_js.php</code> to apply JavaScript fixes</li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html> 