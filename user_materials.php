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

// Fetch all materials
$materials_query = "SELECT * FROM materials ORDER BY upload_date DESC";
$materials_stmt = $conn->query($materials_query);
$materials = $materials_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Materials</title>
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
        .material-card {
            transition: transform 0.3s;
        }
        .material-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .file-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .pdf-icon { color: #e74c3c; }
        .doc-icon { color: #3498db; }
        .img-icon { color: #2ecc71; }
        .default-icon { color: #95a5a6; }
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
                    <a href="user_materials.php" class="active"><i class="fas fa-book"></i> Materials</a>
                    <a href="user_lab_schedule.php"><i class="fas fa-calendar"></i> Lab Schedule</a>
                    <a href="user_leaderboard.php"><i class="fas fa-trophy"></i> Leaderboard</a>
                    <a href="user_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <h2 class="mb-4">Learning Materials</h2>
                
                <?php if (empty($materials)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No materials available at the moment.
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($materials as $material): ?>
                            <div class="col">
                                <div class="card h-100 material-card">
                                    <div class="card-body text-center">
                                        <?php
                                        $file_extension = pathinfo($material['file_path'], PATHINFO_EXTENSION);
                                        if (in_array($file_extension, ['pdf'])) {
                                            echo '<i class="fas fa-file-pdf file-icon pdf-icon"></i>';
                                        } elseif (in_array($file_extension, ['doc', 'docx'])) {
                                            echo '<i class="fas fa-file-word file-icon doc-icon"></i>';
                                        } elseif (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                                            echo '<i class="fas fa-file-image file-icon img-icon"></i>';
                                        } else {
                                            echo '<i class="fas fa-file file-icon default-icon"></i>';
                                        }
                                        ?>
                                        <h5 class="card-title"><?php echo htmlspecialchars($material['title']); ?></h5>
                                        <p class="card-text text-muted">
                                            <?php echo htmlspecialchars($material['description'] ?? 'No description available'); ?>
                                        </p>
                                        <p class="card-text">
                                            <small class="text-muted">Uploaded: <?php echo date('M d, Y', strtotime($material['upload_date'])); ?></small>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent d-flex justify-content-between">
                                        <a href="uploads/materials/<?php echo htmlspecialchars($material['file_path']); ?>" class="btn btn-outline-primary" target="_blank">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                        <a href="download_material.php?id=<?php echo $material['id']; ?>" class="btn btn-outline-success">
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