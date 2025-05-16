<?php
session_start();
require_once 'database.php';
require_once 'notification_helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

// Get notification ID if provided
$notification_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Mark single notification or all as read
if ($notification_id > 0) {
    mark_notification_read($notification_id, $_SESSION['user_id']);
} else {
    mark_all_notifications_read($_SESSION['user_id']);
}

// Redirect back to referrer or dashboard
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'user_dashboard.php';
header("Location: $referrer");
exit();
?> 