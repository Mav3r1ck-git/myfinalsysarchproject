<?php
require_once 'database.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// Check if schedule ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_uploads.php");
    exit();
}

$schedule_id = $_GET['id'];

// Get schedule information for file deletion
$stmt = $conn->prepare("SELECT * FROM lab_schedules WHERE id = ?");
$stmt->execute([$schedule_id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if ($schedule) {
    // Delete file from server if it exists
    $file_path = 'uploads/schedules/' . $schedule['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Delete record from database
    $delete_stmt = $conn->prepare("DELETE FROM lab_schedules WHERE id = ?");
    $delete_stmt->execute([$schedule_id]);
}

// Redirect back to uploads page
header("Location: admin_uploads.php");
exit();
?> 