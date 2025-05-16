<?php
require_once 'database.php';

/**
 * Create a new notification for a user
 * 
 * @param int $user_id User ID to send notification to
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $type Notification type (success, info, warning, error)
 * @param int|null $reference_id Optional ID reference (e.g., reservation ID)
 * @return bool Success status
 */
function create_notification($user_id, $title, $message, $type = 'info', $reference_id = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, reference_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $title, $message, $type, $reference_id]);
    } catch (PDOException $e) {
        // Log error
        if (function_exists('log_error')) {
            log_error("Error creating notification", [
                'message' => $e->getMessage(),
                'user_id' => $user_id,
                'title' => $title
            ]);
        }
        return false;
    }
}

/**
 * Get unread notifications for a user
 * 
 * @param int $user_id User ID
 * @param int $limit Max number of notifications to return
 * @return array Notifications array
 */
function get_unread_notifications($user_id, $limit = 10) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error
        if (function_exists('log_error')) {
            log_error("Error getting unread notifications", [
                'message' => $e->getMessage(),
                'user_id' => $user_id
            ]);
        }
        return [];
    }
}

/**
 * Get all notifications for a user
 * 
 * @param int $user_id User ID
 * @param int $limit Max number of notifications to return
 * @return array Notifications array
 */
function get_all_notifications($user_id, $limit = 50) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error
        if (function_exists('log_error')) {
            log_error("Error getting all notifications", [
                'message' => $e->getMessage(),
                'user_id' => $user_id
            ]);
        }
        return [];
    }
}

/**
 * Mark a notification as read
 * 
 * @param int $notification_id Notification ID
 * @param int $user_id User ID (for security check)
 * @return bool Success status
 */
function mark_notification_read($notification_id, $user_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$notification_id, $user_id]);
    } catch (PDOException $e) {
        // Log error
        if (function_exists('log_error')) {
            log_error("Error marking notification as read", [
                'message' => $e->getMessage(),
                'notification_id' => $notification_id,
                'user_id' => $user_id
            ]);
        }
        return false;
    }
}

/**
 * Mark all notifications as read for a user
 * 
 * @param int $user_id User ID
 * @return bool Success status
 */
function mark_all_notifications_read($user_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ?
        ");
        return $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        // Log error
        if (function_exists('log_error')) {
            log_error("Error marking all notifications as read", [
                'message' => $e->getMessage(),
                'user_id' => $user_id
            ]);
        }
        return false;
    }
}

/**
 * Count unread notifications for a user
 * 
 * @param int $user_id User ID
 * @return int Count of unread notifications
 */
function count_unread_notifications($user_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    } catch (PDOException $e) {
        // Log error
        if (function_exists('log_error')) {
            log_error("Error counting unread notifications", [
                'message' => $e->getMessage(),
                'user_id' => $user_id
            ]);
        }
        return 0;
    }
}
?> 