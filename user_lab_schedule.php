<?php
require_once 'database.php';
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: user_login.php");
    exit();
}

// Get user information for the sidebar
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all lab schedules
$schedules_query = "SELECT * FROM lab_schedules ORDER BY upload_date DESC";
$schedules_stmt = $conn->query($schedules_query);
$schedules = $schedules_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Schedules</title>
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
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 20px;
            object-fit: cover;
        }
        .schedule-card {
            transition: transform 0.3s;
        }
        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .schedule-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #3498db;
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
                    <a href="user_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="user_profile.php"><i class="fas fa-user"></i> Manage Profile</a>
                    <a href="user_sitin.php"><i class="fas fa-laptop"></i> Sitin</a>
                    <a href="user_materials.php"><i class="fas fa-book"></i> Materials</a>
                    <a href="user_lab_schedule.php" class="active"><i class="fas fa-calendar"></i> Lab Schedule</a>
                    <a href="user_leaderboard.php"><i class="fas fa-trophy"></i> Leaderboard</a>
                    <a href="user_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <h2 class="mb-4">Lab Schedules</h2>
                
                <?php if (empty($schedules)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No lab schedules available at the moment.
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($schedules as $schedule): ?>
                            <div class="col">
                                <div class="card h-100 schedule-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calendar-alt schedule-icon"></i>
                                        <h5 class="card-title"><?php echo htmlspecialchars($schedule['title']); ?></h5>
                                        <p class="card-text text-muted">
                                            <?php echo htmlspecialchars($schedule['lab_room'] ?? 'No lab room specified'); ?>
                                        </p>
                                        <p class="card-text text-muted">
                                            <?php echo htmlspecialchars($schedule['description'] ?? 'No description available'); ?>
                                        </p>
                                        <p class="card-text">
                                            <small class="text-muted">Uploaded: <?php echo date('M d, Y', strtotime($schedule['upload_date'])); ?></small>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent d-flex justify-content-between">
                                        <a href="uploads/schedules/<?php echo htmlspecialchars($schedule['file_path']); ?>" class="btn btn-outline-primary" target="_blank">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                        <a href="download_schedule.php?id=<?php echo $schedule['id']; ?>" class="btn btn-outline-success">
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
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