<?php
require_once 'database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

// Check if material ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: user_materials.php");
    exit();
}

$material_id = $_GET['id'];

// Get material information
$stmt = $conn->prepare("SELECT * FROM materials WHERE id = ?");
$stmt->execute([$material_id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    header("Location: user_materials.php");
    exit();
}

$file_path = 'uploads/materials/' . $material['file_path'];

// Check if file exists
if (!file_exists($file_path)) {
    header("Location: user_materials.php");
    exit();
}

// Set appropriate headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($material['file_path']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// Read file and output to browser
readfile($file_path);
exit();
?> 