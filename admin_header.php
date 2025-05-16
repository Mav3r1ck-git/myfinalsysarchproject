<?php
session_start();
require_once 'database.php';
require_once 'notification_helpers.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get unread notifications count for admin
$unread_count = count_unread_notifications($_SESSION['admin_id']);
$notifications = get_unread_notifications($_SESSION['admin_id'], 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            overflow-x: hidden;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
            position: fixed;
            width: 250px;
            z-index: 1000;
            left: 0;
            top: 0;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            transition: background-color 0.3s;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .sidebar a.active {
            background-color: #0d6efd;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
            min-height: 100vh;
            position: relative;
        }
        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-link i {
            width: 20px;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.7rem;
            padding: 0.2rem 0.45rem;
        }
        .notification-bell {
            position: relative;
            margin-right: 20px;
        }
        .top-bar {
            background-color: #f8f9fa;
            padding: 10px 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            width: 100%;
        }
        .notification-dropdown {
            width: 320px;
            padding: 0;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .notification-item {
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.3s;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .unread {
            background-color: #e8f4fd;
        }
        .notification-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .notification-message {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .notification-time {
            color: #adb5bd;
            font-size: 0.8rem;
        }
        .notification-footer {
            text-align: center;
            padding: 10px;
        }
        /* Responsive fixes */
        @media (max-width: 991px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
            }
        }
        @media (max-width: 767px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <img src="ccs2.png" alt="Profile Picture" class="profile-pic">
            <h4 class="text-white"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h4>
            <p class="text-white-50">Administrator</p>
        </div>
        <nav>
            <a href="admin_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="admin_manage_student.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_manage_student.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Manage Students
            </a>
            <a href="admin_manage_sitin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_manage_sitin.php' ? 'active' : ''; ?>">
                <i class="fas fa-desktop"></i> Manage Sit-in
            </a>
            <a href="admin_manage_reservations.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_manage_reservations.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Sit-in Requests
            </a>
            <a href="admin_manage_labs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_manage_labs.php' ? 'active' : ''; ?>">
                <i class="fas fa-laptop"></i> Computer Lab Management
            </a>
            <a href="admin_uploads.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_uploads.php' ? 'active' : ''; ?>">
                <i class="fas fa-upload"></i> Uploads
            </a>
            <a href="admin_announcements.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_announcements.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> Announcements
            </a>
            <a href="admin_logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar with Notifications -->
        <div class="top-bar">
            <div class="notification-bell">
                <div class="dropdown">
                    <a href="#" class="text-dark" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fa-lg"></i>
                        <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                            <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                        <div class="notification-header">
                            <span>Notifications</span>
                            <?php if ($unread_count > 0): ?>
                            <a href="mark_admin_notifications_read.php" class="text-decoration-none small">Mark all as read</a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (empty($notifications)): ?>
                        <div class="p-3 text-center text-muted">
                            <i class="fas fa-bell-slash mb-2"></i>
                            <p class="mb-0">No notifications</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item unread">
                                <div class="notification-title">
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                </div>
                                <div class="notification-message">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </div>
                                <div class="notification-time">
                                    <i class="far fa-clock me-1"></i>
                                    <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div class="notification-footer">
                            <a href="admin_notifications.php" class="text-decoration-none">View all notifications</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main content container -->
        <div class="container-fluid px-0"> 