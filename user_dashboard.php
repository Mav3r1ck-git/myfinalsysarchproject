<?php
require_once 'database.php';
require_once 'error_log.php'; // Include error logging
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: user_login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if user not found
if (!$user) {
    $_SESSION['error'] = "User information not found. Please login again.";
    session_unset();
    session_destroy();
    header("Location: user_login.php");
    exit();
}

// Get user's courses if the table exists
$courses = [];
$total_courses = 0;

try {
    $check_table = $conn->query("SHOW TABLES LIKE 'users_course'");
    if ($check_table->rowCount() > 0) {
        $sql = "SELECT * FROM users_course WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_courses = count($courses);
    }
} catch (PDOException $e) {
    // Table might not exist, that's okay
    $courses = [];
    $total_courses = 0;
}

// Get announcements
$announcements = [];
try {
    $announcements_query = "
        SELECT a.*, ad.first_name, ad.last_name 
        FROM announcements a
        JOIN admins ad ON a.created_by = ad.id
        ORDER BY a.created_at DESC
        LIMIT 5
    ";
    $announcements_stmt = $conn->query($announcements_query);
    if ($announcements_stmt) {
        $announcements = $announcements_stmt->fetchAll(PDO::FETCH_ASSOC);
        // For debugging, log the number of announcements retrieved
        if (function_exists('log_query')) {
            log_query($announcements_query, [], "Retrieved " . count($announcements) . " announcements");
        }
    } else {
        // For debugging, log the query failure
        if (function_exists('log_error')) {
            log_error("Failed to query announcements for user dashboard");
        }
        $announcements = [];
    }
} catch (PDOException $e) {
    // Log the error if the error_log.php file is included
    if (function_exists('log_error')) {
        log_error("Exception getting announcements for user dashboard: " . $e->getMessage(), [
            'code' => $e->getCode(),
            'user_id' => $user_id
        ]);
    }
    // Table might not exist, that's okay
    $announcements = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .info-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 20px;
            object-fit: cover;
        }
        .announcement-card {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            margin-bottom: 15px;
            transition: transform 0.3s;
        }
        .announcement-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
                    $profile_pic = $user['profile_picture'] ?? 'default_profile_picture.png';
                    $profile_path = file_exists('uploads/' . $profile_pic) ? 'uploads/' . $profile_pic : $profile_pic;
                    ?>
                    <img src="<?php echo htmlspecialchars($profile_path); ?>" alt="Profile Picture" class="profile-pic">
                    <h4 class="text-white"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p class="text-white-50">Student</p>
                </div>
                <nav>
                <a href="user_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="user_profile.php"><i class="fas fa-user"></i> Manage Profile</a>
                    <a href="user_sitin.php"><i class="fas fa-laptop"></i> Sitin</a>
                    <a href="user_materials.php"><i class="fas fa-book"></i> Materials</a>
                    <a href="user_lab_schedule.php"><i class="fas fa-calendar"></i> Lab Schedule</a>
                    <a href="user_leaderboard.php"><i class="fas fa-trophy"></i> Leaderboard</a>
                    <a href="user_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <h2 class="mb-4">Dashboard</h2>
                
                <!-- Announcements Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4><i class="fas fa-bullhorn me-2"></i> Announcements</h4>
                            </div>
                            
                            <?php if (empty($announcements)): ?>
                                <p class="text-muted">No announcements available at the moment.</p>
                            <?php else: ?>
                                <div class="accordion" id="announcementsAccordion">
                                    <?php foreach ($announcements as $index => $announcement): ?>
                                        <div class="accordion-item announcement-card">
                                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                                <button class="accordion-button <?php echo ($index > 0) ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo ($index === 0) ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                                    <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                                                    <span class="ms-auto text-muted small"><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></span>
                                                </button>
                                            </h2>
                                            <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo ($index === 0) ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#announcementsAccordion">
                                                <div class="accordion-body">
                                                    <div class="mb-3">
                                                        <?php echo $announcement['content']; ?>
                                                    </div>
                                                    <p class="text-muted small mb-0">
                                                        Posted by: <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?> | 
                                                        <?php echo date('F j, Y, g:i a', strtotime($announcement['created_at'])); ?>
                                                        <?php if ($announcement['created_at'] != $announcement['updated_at']): ?>
                                                            | Updated: <?php echo date('F j, Y', strtotime($announcement['updated_at'])); ?>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Student Information -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-card">
                            <h4>Student Information</h4>
                            <table class="table">
                                <tr>
                                    <th>Student ID:</th>
                                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                </tr>
                                <tr>
                                    <th>Name:</th>
                                    <td><?php 
                                    $full_name = $user['first_name'];
                                    if (!empty($user['middle_name'])) {
                                        $full_name .= ' ' . $user['middle_name'];
                                    }
                                    $full_name .= ' ' . $user['last_name'];
                                    echo htmlspecialchars($full_name); 
                                    ?></td>
                                </tr>
                                <tr>
                                    <th>Year Level:</th>
                                    <td><?php echo htmlspecialchars($user['year_level']); ?></td>
                                </tr>
                                <tr>
                                    <th>Course:</th>
                                    <td><?php echo htmlspecialchars($user['course']); ?></td>
                                </tr>
                                <?php if (isset($user['email'])): ?>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (isset($user['available_sessions'])): ?>
                                <tr>
                                    <th>Available Sessions:</th>
                                    <td><?php echo htmlspecialchars($user['available_sessions']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (isset($user['points'])): ?>
                                <tr>
                                    <th>Points:</th>
                                    <td><?php echo htmlspecialchars($user['points']); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="col-md-6">
                        <div class="info-card">
                            <h4>Quick Links</h4>
                            <div class="list-group">
                                <a href="user_profile.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-user me-2"></i> Edit My Profile
                                </a>
                                <a href="user_sitin.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-laptop me-2"></i> View My Sit-in Sessions
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Overview -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="info-card">
                            <h4>Course Overview</h4>
                            <?php if ($total_courses > 0): ?>
                                <p>You are enrolled in <?php echo $total_courses; ?> courses.</p>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Course Code</th>
                                                <th>Course Name</th>
                                                <th>Units</th>
                                                <th>Schedule</th>
                                                <th>Room</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($courses as $course): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['course_code'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($course['course_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($course['units'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($course['schedule'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($course['room'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">You are not enrolled in any courses yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 