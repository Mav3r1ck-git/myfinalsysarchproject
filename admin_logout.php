<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: admin_login.php");
exit();
?> 

