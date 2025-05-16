<?php
session_start();
require_once 'database.php';

// Return JSON response
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in as admin to view this page'
    ]);
    exit;
}

// Test notifications have been disabled by admin
echo json_encode([
    'success' => false,
    'message' => 'Test notification creation has been disabled'
]);
?> 