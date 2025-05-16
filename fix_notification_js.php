<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    echo "You must be logged in to use this fix.";
    exit;
}

$user_type = isset($_SESSION['user_id']) ? 'user' : 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Notification JavaScript</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .container { margin-top: 30px; }
        .alert { margin-bottom: 20px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; white-space: pre-wrap; }
        .btn-fix { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix Notification JavaScript</h1>
        <div class="alert alert-info">
            This page will update your notification JavaScript to ensure it works properly.
        </div>

        <h3>Current Status</h3>
        <div id="status" class="alert alert-warning">
            Checking notification system...
        </div>

        <button id="fixButton" class="btn btn-primary btn-fix">Apply Fix Now</button>
        
        <div class="mt-3">
            <a href="<?php echo $user_type === 'user' ? 'user_dashboard.php' : 'admin_dashboard.php'; ?>?nocache=<?php echo time(); ?>" class="btn btn-success">Return to Dashboard</a>
        </div>

        <h3 class="mt-4">Diagnostic Information</h3>
        <pre id="diagnosticOutput">Loading diagnostic information...</pre>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusElement = document.getElementById('status');
        const diagnosticElement = document.getElementById('diagnosticOutput');
        const fixButton = document.getElementById('fixButton');
        
        // Check notification status
        function checkNotificationStatus() {
            fetch('<?php echo $user_type === "user" ? "reload_notifications.php" : "reload_admin_notifications.php"; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let statusHTML = '';
                        if (data.count > 0) {
                            statusHTML = `<div class="alert alert-success">You have ${data.count} unread notification(s). The system appears to be working!</div>`;
                        } else {
                            statusHTML = `<div class="alert alert-info">You have no notifications.</div>`;
                        }
                        statusElement.innerHTML = statusHTML;
                        
                        // Update diagnostic info
                        diagnosticElement.textContent = JSON.stringify(data, null, 2);
                    } else {
                        statusElement.innerHTML = `<div class="alert alert-danger">Error checking notifications: ${data.message}</div>`;
                        diagnosticElement.textContent = JSON.stringify(data, null, 2);
                    }
                })
                .catch(error => {
                    statusElement.innerHTML = `<div class="alert alert-danger">Error communicating with server: ${error}</div>`;
                    diagnosticElement.textContent = error.toString();
                });
        }
        
        // Create a test notification - Functionality removed
        function createTestNotification() {
            statusElement.innerHTML += `<div class="alert alert-info">Test notification creation has been disabled.</div>`;
        }
        
        // Fix button action
        fixButton.addEventListener('click', function() {
            // Apply direct JavaScript fixes to the page
            const script = document.createElement('script');
            script.textContent = `
                // Force all notification elements to be visible
                document.addEventListener('DOMContentLoaded', function() {
                    // Fix dropdown visibility
                    const dropdowns = document.querySelectorAll('.dropdown-menu');
                    dropdowns.forEach(dropdown => {
                        dropdown.style.display = 'block';
                        dropdown.style.position = 'absolute';
                        dropdown.style.inset = '0px 0px auto auto';
                        dropdown.style.transform = 'translate(-8px, 40px)';
                        dropdown.style.zIndex = '1050';
                    });
                    
                    // Force notification badges to be visible if they have content
                    const badges = document.querySelectorAll('.notification-badge');
                    badges.forEach(badge => {
                        badge.style.display = 'block';
                    });
                    
                    // Test notification creation removed
                    console.log('Test notification creation disabled');
                    
                    // Set page to reload after 3 seconds
                    setTimeout(() => { 
                        location.reload();
                    }, 3000);
                });
            `;
            document.head.appendChild(script);
            
            // Show message
            statusElement.innerHTML = `
                <div class="alert alert-success">
                    <strong>Fix applied!</strong> The page will reload in 3 seconds, or you can 
                    <a href="<?php echo $user_type === 'user' ? 'user_dashboard.php' : 'admin_dashboard.php'; ?>?nocache=${Date.now()}" class="alert-link">click here</a> 
                    to go back to the dashboard.
                </div>
            `;
            
            // Disable the button
            fixButton.disabled = true;
            fixButton.textContent = 'Fix Applied';
        });
        
        // Run the initial check
        checkNotificationStatus();
    });
    </script>
</body>
</html> 