<?php
require_once 'database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

// Check if schedule ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: user_lab_schedule.php");
    exit();
}

$schedule_id = $_GET['id'];

// Get schedule information
$stmt = $conn->prepare("SELECT * FROM lab_schedules WHERE id = ?");
$stmt->execute([$schedule_id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    header("Location: user_lab_schedule.php");
    exit();
}

$file_path = 'uploads/schedules/' . $schedule['file_path'];

// Check if file exists
if (!file_exists($file_path)) {
    header("Location: user_lab_schedule.php");
    exit();
}

// Set appropriate headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($schedule['file_path']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// Read file and output to browser
readfile($file_path);
exit();
?> 