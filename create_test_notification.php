<?php
session_start();
require_once 'database.php';

// Return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to view this page'
    ]);
    exit;
}

// Test notifications have been disabled by admin
echo json_encode([
    'success' => false,
    'message' => 'Test notification creation has been disabled'
]);
?> 