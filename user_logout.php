<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: user_login.php");
    exit();
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: user_login.php");
exit();
?> 