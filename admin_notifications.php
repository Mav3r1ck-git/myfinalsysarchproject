<?php
require_once 'database.php';
require_once 'notification_helpers.php';
require_once 'admin_header.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get all notifications
$notifications = get_all_notifications($_SESSION['admin_id']);
?>

<div class="container">
    <h2 class="mb-4">Notifications</h2>
    
    <?php if (empty($notifications)): ?>
        <div class="alert alert-info">
            <i class="fas fa-bell-slash me-2"></i> You have no notifications.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>All Notifications</span>
                <a href="mark_admin_notifications_read.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-check-double me-1"></i> Mark All as Read
                </a>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $notification): ?>
                    <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h5>
                            <small class="text-muted">
                                <i class="far fa-clock me-1"></i>
                                <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                            </small>
                        </div>
                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                        <?php if (!$notification['is_read']): ?>
                            <div class="mt-2">
                                <a href="mark_admin_notifications_read.php?id=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-check me-1"></i> Mark as Read
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?> 