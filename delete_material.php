<?php
require_once 'database.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// Check if material ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_uploads.php");
    exit();
}

$material_id = $_GET['id'];

// Get material information for file deletion
$stmt = $conn->prepare("SELECT * FROM materials WHERE id = ?");
$stmt->execute([$material_id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);

if ($material) {
    // Delete file from server if it exists
    $file_path = 'uploads/materials/' . $material['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Delete record from database
    $delete_stmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
    $delete_stmt->execute([$material_id]);
}

// Redirect back to uploads page
header("Location: admin_uploads.php");
exit();
?> 