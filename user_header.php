<?php
session_start();
require_once 'database.php';
require_once 'notification_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

// Get unread notifications count
$unread_count = count_unread_notifications($_SESSION['user_id']);

// Get all unread notifications with better error handling
try {
    $notifications = get_unread_notifications($_SESSION['user_id'], 5);
    // Ensure notifications array is valid
    if (!is_array($notifications)) {
        $notifications = [];
    }
} catch (Exception $e) {
    // Log error if possible
    error_log("Error fetching notifications: " . $e->getMessage());
    $notifications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
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
            object-fit: cover;
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
            max-height: 400px;
            overflow-y: auto;
            z-index: 1050 !important;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .notification-items {
            max-height: 300px;
            overflow-y: auto;
        }
        .notification-item {
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.3s;
            position: relative;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .notification-item.unread {
            background-color: #e8f4fd;
        }
        .notification-item.unread:before {
            content: '';
            position: absolute;
            left: 5px;
            top: 15px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #0d6efd;
        }
        .notification-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #212529;
        }
        .notification-message {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
            word-break: break-word;
        }
        .notification-time {
            color: #adb5bd;
            font-size: 0.8rem;
        }
        .notification-footer {
            text-align: center;
            padding: 10px;
            border-top: 1px solid #e9ecef;
            background-color: #f8f9fa;
            position: sticky;
            bottom: 0;
            z-index: 1;
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
            <img src="<?php echo $_SESSION['profile_picture'] ?? 'default_profile.png'; ?>" alt="Profile Picture" class="profile-pic">
            <h4 class="text-white"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h4>
            <p class="text-white-50">Student</p>
        </div>
        <nav>
            <a href="user_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="user_profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Manage Profile
            </a>
            <a href="user_sitin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_sitin.php' ? 'active' : ''; ?>">
                <i class="fas fa-desktop"></i> Sit-in
            </a>
            <a href="user_materials.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_materials.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> Materials
            </a>
            <a href="user_lab_schedule.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_lab_schedule.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> Lab Schedule
            </a>
            <a href="user_leaderboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_leaderboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-trophy"></i> Leaderboard
            </a>
            <a href="user_logout.php" class="nav-link">
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
                    <a href="#" class="text-dark position-relative" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
                            <a href="mark_notifications_read.php" class="text-decoration-none small">Mark all as read</a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (empty($notifications)): ?>
                        <div class="p-3 text-center text-muted">
                            <i class="fas fa-bell-slash mb-2 d-block"></i>
                            <p class="mb-0">No notifications</p>
                        </div>
                        <?php else: ?>
                            <div class="notification-items">
                                <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item <?php echo (!$notification['is_read']) ? 'unread' : ''; ?>">
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
                            </div>
                        <?php endif; ?>
                        
                        <div class="notification-footer">
                            <a href="user_notifications.php" class="text-decoration-none">View all notifications</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main content container -->
        <div class="container-fluid px-0">
        
        <!-- JavaScript to ensure notifications work -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize dropdown with positioning parameters
            var notificationDropdown = document.getElementById('notificationDropdown');
            if (notificationDropdown) {
                // Create dropdown with specific config to ensure proper display
                var dropdown = new bootstrap.Dropdown(notificationDropdown, {
                    offset: [0, 10],
                    popperConfig: {
                        placement: 'bottom-end'
                    }
                });
                
                // Add click event to notification bell
                notificationDropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    dropdown.toggle();
                });
                
                // Force dropdown to update its position when shown
                notificationDropdown.addEventListener('shown.bs.dropdown', function() {
                    var dropdownMenu = document.querySelector('.notification-dropdown');
                    if (dropdownMenu) {
                        dropdownMenu.style.display = 'block';
                        dropdownMenu.style.position = 'absolute';
                        dropdownMenu.style.inset = '0px 0px auto auto';
                        dropdownMenu.style.transform = 'translate(-8px, 40px)';
                    }
                });
            }
            
            // Check for notifications via AJAX
            function checkForNewNotifications() {
                fetch('reload_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update notification count badge
                            const badge = document.querySelector('.notification-badge');
                            const notificationContainer = document.querySelector('.notification-dropdown');
                            const notificationsContent = notificationContainer.querySelector('.notification-header').nextElementSibling;
                            
                            // Update badge
                            if (data.count > 0) {
                                if (badge) {
                                    badge.textContent = data.count > 9 ? '9+' : data.count;
                                } else {
                                    const newBadge = document.createElement('span');
                                    newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge';
                                    newBadge.textContent = data.count > 9 ? '9+' : data.count;
                                    document.getElementById('notificationDropdown').appendChild(newBadge);
                                }
                            } else if (badge) {
                                badge.remove();
                            }
                            
                            // Update notification content
                            notificationsContent.outerHTML = data.html;
                            
                            console.log('Notifications updated:', data.count > 0 ? data.count + ' unread' : 'None');
                        } else {
                            console.error('Error updating notifications:', data.message);
                        }
                    })
                    .catch(error => console.error('Error checking notifications:', error));
            }
            
            // Set interval for notification checking (15 seconds)
            setInterval(checkForNewNotifications, 15000);
            
            // Test notification button has been removed
        });
        </script>
</body></html>  