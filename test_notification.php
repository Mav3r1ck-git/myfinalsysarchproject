<?php
session_start();
require_once 'database.php';
require_once 'notification_helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to test notifications");
}

$user_id = $_SESSION['user_id'];

// Create a test notification
$result = create_notification(
    $user_id,
    'Test Notification',
    'This is a test notification to verify the notification system is working correctly.',
    'info',
    null
);

if ($result) {
    echo "Test notification created successfully! Please go back to any page to see it.";
} else {
    echo "Failed to create test notification. Please check the database connection.";
}
?> 