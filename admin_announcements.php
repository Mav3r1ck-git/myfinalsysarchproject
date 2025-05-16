<?php
require_once 'database.php';
require_once 'error_log.php'; // Include error logging
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$error = '';
$success = '';

// Get admin information
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Log admin data for debugging
if (!$admin) {
    log_error("Admin data not found for user_id: {$admin_id}");
} else {
    log_query("Admin data retrieved", ['admin_id' => $admin_id], true);
}

// Handle announcement creation
if (isset($_POST['create_announcement'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    
    if (empty($title) || empty($content)) {
        $error = "Title and content are required!";
        log_error("Announcement creation failed - empty fields");
    } else {
        try {
            $query = "INSERT INTO announcements (title, content, created_by) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$title, $content, $admin_id]);
            
            if ($result) {
                $success = "Announcement created successfully!";
                log_query($query, [$title, "content_length:" . strlen($content), $admin_id], true);
            } else {
                $error = "Failed to insert announcement. Please try again.";
                log_error("Announcement creation failed - execute returned false");
            }
        } catch (PDOException $e) {
            $error = "Error creating announcement: " . $e->getMessage();
            log_error("Announcement creation exception", [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }
}

// Handle announcement update
if (isset($_POST['update_announcement'])) {
    $announcement_id = $_POST['announcement_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    
    if (empty($title) || empty($content)) {
        $error = "Title and content are required!";
        log_error("Announcement update failed - empty fields");
    } else {
        try {
            $query = "UPDATE announcements SET title = ?, content = ? WHERE id = ? AND created_by = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$title, $content, $announcement_id, $admin_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $success = "Announcement updated successfully!";
                log_query($query, [$title, "content_length:" . strlen($content), $announcement_id, $admin_id], true);
            } else {
                $error = "No changes were made to the announcement.";
                log_error("Announcement update - no rows affected", [
                    'announcement_id' => $announcement_id,
                    'admin_id' => $admin_id
                ]);
            }
        } catch (PDOException $e) {
            $error = "Error updating announcement: " . $e->getMessage();
            log_error("Announcement update exception", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'announcement_id' => $announcement_id
            ]);
        }
    }
}

// Handle announcement deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $announcement_id = $_GET['delete'];
    
    try {
        $query = "DELETE FROM announcements WHERE id = ? AND created_by = ?";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([$announcement_id, $admin_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            $success = "Announcement deleted successfully!";
            log_query($query, [$announcement_id, $admin_id], true);
        } else {
            $error = "Failed to delete announcement. It may have already been removed.";
            log_error("Announcement deletion - no rows affected", [
                'announcement_id' => $announcement_id,
                'admin_id' => $admin_id
            ]);
        }
    } catch (PDOException $e) {
        $error = "Error deleting announcement: " . $e->getMessage();
        log_error("Announcement deletion exception", [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'announcement_id' => $announcement_id
        ]);
    }
}

// Get all announcements
try {
    $announcements_query = "SELECT a.*, ad.first_name, ad.last_name 
                            FROM announcements a
                            JOIN admins ad ON a.created_by = ad.id
                            ORDER BY a.created_at DESC";
    $announcements_stmt = $conn->query($announcements_query);
    $announcements = $announcements_stmt->fetchAll(PDO::FETCH_ASSOC);
    log_query($announcements_query, [], true);
} catch (PDOException $e) {
    $error = "Error fetching announcements: " . $e->getMessage();
    $announcements = [];
    log_error("Fetching announcements failed", [
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Announcements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.4.2/tinymce.min.js" integrity="sha512-sWydClczl0KPyMWlARx1JaxJo2upoFYj4MUoD4CxVZ+/CIXQjx8CiAVaFKPT7hnXwCR1UAzJlygxQlxiwTL0jA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        tinymce.init({
            selector: 'textarea#content',
            height: 300,
            promotion: false,
            menubar: false,
            branding: false,
            plugins: 'lists link',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link',
            setup: function(editor) {
                editor.on('init', function() {
                    const editTextareas = document.querySelectorAll('textarea[id^="edit_content"]');
                    if (editTextareas.length > 0) {
                        tinymce.init({
                            selector: 'textarea[id^="edit_content"]',
                            height: 300,
                            promotion: false,
                            menubar: false,
                            branding: false,
                            plugins: 'lists link',
                            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link'
                        });
                    }
                });
            }
        });
    </script>
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .main-content {
            padding: 20px;
        }
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 20px;
            object-fit: cover;
        }
        .announcement-card {
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="text-center mb-4">
                    <?php
                    $profile_pic = $admin['profile_picture'] ?? 'default_profile_picture.png';
                    $profile_path = file_exists('uploads/' . $profile_pic) ? 'uploads/' . $profile_pic : $profile_pic;
                    ?>
                    <img src="<?php echo htmlspecialchars($profile_path); ?>" alt="Profile Picture" class="profile-pic">
                    <h4 class="text-white"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h4>
                    <p class="text-white-50">Administrator</p>
                </div>
                <nav>
                    <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="admin_manage_student.php"><i class="fas fa-users"></i> Manage Students</a>
                    <a href="admin_manage_sitin.php"><i class="fas fa-laptop"></i> Manage Sit-ins</a>
                    <a href="admin_uploads.php"><i class="fas fa-upload"></i> Uploads</a>
                    <a href="admin_announcements.php" class="active"><i class="fas fa-bullhorn"></i> Announcements</a>
                    <a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <h2 class="mb-4">Manage Announcements</h2>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Create Announcement Form -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Create New Announcement</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">Content</label>
                                <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                            </div>
                            <button type="submit" name="create_announcement" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i> Create Announcement
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Announcements List -->
                <h4 class="mb-3">Existing Announcements</h4>
                
                <?php if (empty($announcements)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No announcements available.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card announcement-card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAnnouncement<?php echo $announcement['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this announcement?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <?php echo $announcement['content']; ?>
                                        </div>
                                        <p class="card-text text-muted small">
                                            <i class="fas fa-user me-1"></i> Posted by: <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?><br>
                                            <i class="fas fa-clock me-1"></i> <?php echo date('F j, Y, g:i a', strtotime($announcement['created_at'])); ?>
                                            <?php if ($announcement['created_at'] != $announcement['updated_at']): ?>
                                                <br><i class="fas fa-edit me-1"></i> Updated: <?php echo date('F j, Y, g:i a', strtotime($announcement['updated_at'])); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Announcement Modal -->
                            <div class="modal fade" id="editAnnouncement<?php echo $announcement['id']; ?>" tabindex="-1" aria-labelledby="editAnnouncementLabel<?php echo $announcement['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editAnnouncementLabel<?php echo $announcement['id']; ?>">Edit Announcement</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="">
                                                <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="edit_title<?php echo $announcement['id']; ?>" class="form-label">Title</label>
                                                    <input type="text" class="form-control" id="edit_title<?php echo $announcement['id']; ?>" name="title" value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_content<?php echo $announcement['id']; ?>" class="form-label">Content</label>
                                                    <textarea class="form-control" id="edit_content<?php echo $announcement['id']; ?>" name="content" rows="5" required><?php echo htmlspecialchars($announcement['content']); ?></textarea>
                                                </div>
                                                <button type="submit" name="update_announcement" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i> Update Announcement
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 