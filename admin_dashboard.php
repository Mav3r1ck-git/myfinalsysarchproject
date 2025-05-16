<?php
require_once 'database.php';
require_once 'admin_header.php';

// Get total number of students
$sql = "SELECT COUNT(*) as total FROM users";
$stmt = $conn->prepare($sql);
$stmt->execute();
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get students by year level
$sql = "SELECT year_level, COUNT(*) as count FROM users GROUP BY year_level";
$stmt = $conn->prepare($sql);
$stmt->execute();
$year_level_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get students by course
$sql = "SELECT course, COUNT(*) as count FROM users GROUP BY course";
$stmt = $conn->prepare($sql);
$stmt->execute();
$course_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top 5 users by points + sessions for leaderboard
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
try {
    $leaderboard_stmt = $conn->query($leaderboard_query);
    $leaderboard = $leaderboard_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $leaderboard = [];
}
?>

<h2 class="mb-4">Dashboard</h2>
                
<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h3>Total Students</h3>
                <h2><?php echo $total_students; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Year Level Distribution -->
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-body">
                <h4>Students by Year Level</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Year Level</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($year_level_stats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['year_level']); ?></td>
                            <td><?php echo $stat['count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Course Distribution -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-body">
                <h4>Students by Course</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($course_stats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['course']); ?></td>
                            <td><?php echo $stat['count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Leaderboard Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Student Leaderboard (Top 5)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($leaderboard)): ?>
                    <p class="text-muted">No leaderboard data available yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Year Level</th>
                                    <th>Points</th>
                                    <th>Sessions</th>
                                    <th>Total Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $rank = 1;
                                    foreach ($leaderboard as $student): 
                                ?>
                                <tr>
                                    <td><?php echo $rank++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $student['profile_picture'] ? htmlspecialchars($student['profile_picture']) : 'default_profile.png'; ?>" 
                                                 class="rounded-circle me-2" width="40" height="40">
                                            <div>
                                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($student['user_id']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['course']); ?></td>
                                    <td><?php echo htmlspecialchars($student['year_level']); ?></td>
                                    <td><?php echo $student['points']; ?></td>
                                    <td><?php echo $student['total_sessions']; ?></td>
                                    <td><strong><?php echo $student['total_score']; ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 