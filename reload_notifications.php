<?php
session_start();
require_once 'database.php';
require_once 'notification_helpers.php';

// Return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get unread notification count
$unread_count = count_unread_notifications($user_id);

// Get notifications
try {
    $notifications = get_unread_notifications($user_id, 5);
    
    // Build HTML for notifications
    $html = '';
    
    if (empty($notifications)) {
        $html .= '<div class="p-3 text-center text-muted">';
        $html .= '<i class="fas fa-bell-slash mb-2 d-block"></i>';
        $html .= '<p class="mb-0">No notifications</p>';
        $html .= '</div>';
    } else {
        $html .= '<div class="notification-items">';
        foreach ($notifications as $notification) {
            $html .= '<div class="notification-item ' . (!$notification['is_read'] ? 'unread' : '') . '">';
            $html .= '<div class="notification-title">' . htmlspecialchars($notification['title']) . '</div>';
            $html .= '<div class="notification-message">' . htmlspecialchars($notification['message']) . '</div>';
            $html .= '<div class="notification-time"><i class="far fa-clock me-1"></i>' . date('M d, Y h:i A', strtotime($notification['created_at'])) . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
    }
    
    echo json_encode([
        'success' => true, 
        'count' => $unread_count,
        'html' => $html
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error loading notifications: ' . $e->getMessage()
    ]);
}
?> 