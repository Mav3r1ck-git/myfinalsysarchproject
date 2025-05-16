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

// Get top 5 users by points + sessions
$leaderboard_query = "
    SELECT u.user_id, u.first_name, u.last_name, u.profile_picture, 
           u.course, u.year_level, u.points, 
           COUNT(s.id) as total_sessions,
           (IFNULL(u.points, 0) + COUNT(s.id)) as total_score
    FROM users u
    LEFT JOIN sitin_sessions s ON u.user_id = s.user_id
    GROUP BY u.user_id
    ORDER BY total_score DESC, u.points DESC
    LIMIT 5
";
$leaderboard_stmt = $conn->query($leaderboard_query);
$leaderboard = $leaderboard_stmt->fetchAll(PDO::FETCH_ASSOC);

// Find current user rank
$rank_query = "
    SELECT ranked_users.rank
    FROM (
        SELECT user_id, 
               @rank := @rank + 1 as rank
        FROM (
            SELECT u.user_id, 
                   (IFNULL(u.points, 0) + COUNT(s.id)) as total_score
            FROM users u
            LEFT JOIN sitin_sessions s ON u.user_id = s.user_id
            GROUP BY u.user_id
            ORDER BY total_score DESC, u.points DESC
        ) as user_scores,
        (SELECT @rank := 0) as r
    ) as ranked_users
    WHERE ranked_users.user_id = ?
";

$rank_stmt = $conn->prepare($rank_query);
$rank_stmt->execute([$user_id]);
$user_rank = $rank_stmt->fetch(PDO::FETCH_ASSOC);

// Get user's stats
$user_stats_query = "
    SELECT COUNT(id) as total_sessions
    FROM sitin_sessions
    WHERE user_id = ?
";
$user_stats_stmt = $conn->prepare($user_stats_query);
$user_stats_stmt->execute([$user_id]);
$user_stats = $user_stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard</title>
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
        .leaderboard-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        .leaderboard-item {
            background-color: #fff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .leaderboard-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .rank-1 {
            background-color: #fef9e7;
            border-left: 5px solid #f1c40f;
        }
        .rank-2 {
            background-color: #f8f9f9;
            border-left: 5px solid #bdc3c7;
        }
        .rank-3 {
            background-color: #fff3e0;
            border-left: 5px solid #d35400;
        }
        .rank-badge {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        .rank-1 .rank-badge {
            background-color: #f1c40f;
        }
        .rank-2 .rank-badge {
            background-color: #bdc3c7;
        }
        .rank-3 .rank-badge {
            background-color: #d35400;
        }
        .user-stats {
            background-color: #e8f4fd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
                    <a href="user_lab_schedule.php"><i class="fas fa-calendar"></i> Lab Schedule</a>
                    <a href="user_leaderboard.php" class="active"><i class="fas fa-trophy"></i> Leaderboard</a>
                    <a href="user_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <h2 class="mb-4">Leaderboard</h2>
                
                <!-- User Stats -->
                <div class="user-stats">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Your Statistics</h4>
                            <div class="d-flex align-items-center mt-3">
                                <img src="<?php echo htmlspecialchars($profile_path); ?>" alt="Your Profile" class="leaderboard-avatar">
                                <div>
                                    <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($user['course'] . ' - ' . $user['year_level']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row mt-3">
                                <div class="col-md-4 text-center">
                                    <h4><?php echo isset($user_rank['rank']) ? $user_rank['rank'] : 'N/A'; ?></h4>
                                    <p class="text-muted">Your Rank</p>
                                </div>
                                <div class="col-md-4 text-center">
                                    <h4><?php echo $user['points'] ?? 0; ?></h4>
                                    <p class="text-muted">Points</p>
                                </div>
                                <div class="col-md-4 text-center">
                                    <h4><?php echo $user_stats['total_sessions'] ?? 0; ?></h4>
                                    <p class="text-muted">Sessions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top 5 Leaderboard -->
                <h4 class="mb-4">Top 5 Students</h4>
                
                <?php if (empty($leaderboard)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No leaderboard data available yet.
                    </div>
                <?php else: ?>
                    <?php foreach ($leaderboard as $index => $leader): ?>
                        <?php 
                        $rank = $index + 1;
                        $rank_class = ($rank <= 3) ? "rank-$rank" : "";
                        
                        $leader_pic = $leader['profile_picture'] ?? 'default_profile_picture.png';
                        $leader_path = file_exists('uploads/' . $leader_pic) ? 'uploads/' . $leader_pic : $leader_pic;
                        ?>
                        <div class="leaderboard-item <?php echo $rank_class; ?> d-flex align-items-center">
                            <div class="rank-badge"><?php echo $rank; ?></div>
                            <img src="<?php echo htmlspecialchars($leader_path); ?>" alt="Student" class="leaderboard-avatar">
                            <div class="flex-grow-1">
                                <h5><?php echo htmlspecialchars($leader['first_name'] . ' ' . $leader['last_name']); ?></h5>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($leader['course'] . ' - ' . $leader['year_level']); ?></p>
                            </div>
                            <div class="text-end">
                                <h5 class="mb-0"><?php echo htmlspecialchars($leader['total_score']); ?> Points</h5>
                                <small class="text-muted"><?php echo htmlspecialchars($leader['points'] ?? 0); ?> points + <?php echo htmlspecialchars($leader['total_sessions']); ?> sessions</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 