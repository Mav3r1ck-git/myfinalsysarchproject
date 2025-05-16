<?php
require_once 'database.php';
require_once 'admin_header.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$error = '';
$success = '';

// Handle student deletion
if (isset($_POST['delete_student'])) {
    $user_id = $_POST['user_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $success = "Student deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting student: " . $e->getMessage();
    }
}

// Handle reset all sessions
if (isset($_POST['reset_all_sessions'])) {
    try {
        $stmt = $conn->prepare("UPDATE users SET available_sessions = 30");
        $stmt->execute();
        $success = "All students' sessions have been reset to 30!";
    } catch (PDOException $e) {
        $error = "Error resetting sessions: " . $e->getMessage();
    }
}

// Handle individual student session reset
if (isset($_POST['reset_student_sessions'])) {
    $user_id = $_POST['user_id'];
    try {
        $stmt = $conn->prepare("UPDATE users SET available_sessions = 30 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $success = "Student's sessions have been reset to 30!";
    } catch (PDOException $e) {
        $error = "Error resetting student sessions: " . $e->getMessage();
    }
}

// Fetch students with search
$sql = "SELECT * FROM users WHERE user_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? ORDER BY last_name, first_name";
$search_param = "%$search%";
$stmt = $conn->prepare($sql);
$stmt->execute([$search_param, $search_param, $search_param, $search_param]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Students</h2>
    <div>
        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to reset all students\' sessions to 30?');">
            <button type="submit" name="reset_all_sessions" class="btn btn-warning me-2">
                <i class="fas fa-sync-alt"></i> Reset All Sessions
            </button>
        </form>
        <a href="add_student.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Student
        </a>
    </div>
</div>

<div class="search-container">
    <form method="GET" action="" class="d-flex">
        <input type="text" name="search" class="form-control me-2" placeholder="Search by ID, name, or email" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php if($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Actions</th>
                <th>User ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Year Level</th>
                <th>Course</th>
                <th>Available Sessions</th>
                <th>Points</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($students as $student): ?>
            <tr>
                <td>
                    <div class="btn-group">
                        <a href="edit_student.php?id=<?php echo $student['user_id']; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#sitinModal<?php echo $student['user_id']; ?>">
                            <i class="bi bi-pc-display"></i> Sit-in
                        </button>
                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to reset this student\'s sessions to 30?');">
                            <input type="hidden" name="user_id" value="<?php echo $student['user_id']; ?>">
                            <button type="submit" name="reset_student_sessions" class="btn btn-sm btn-warning">
                                <i class="fas fa-sync-alt"></i> Reset Sessions
                            </button>
                        </form>
                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this student?');">
                            <input type="hidden" name="user_id" value="<?php echo $student['user_id']; ?>">
                            <button type="submit" name="delete_student" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </div>

                    <!-- Sit-in Modal -->
                    <div class="modal fade" id="sitinModal<?php echo $student['user_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Sit-in Student</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="process_sitin.php" method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="user_id" value="<?php echo $student['user_id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label for="sitin_lab" class="form-label">Lab:</label>
                                            <select name="sitin_lab" class="form-control" required>
                                                <option value="" disabled selected>Select Lab</option>
                                                <option value="Lab 524">Lab 524</option>
                                                <option value="Lab 526">Lab 526</option>
                                                <option value="Lab 528">Lab 528</option>
                                                <option value="Lab 530">Lab 530</option>
                                                <option value="Lab 542">Lab 542</option>
                                                <option value="Lab 544">Lab 544</option>
                                                <option value="Lab 517">Lab 517</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="pc_number" class="form-label">PC Number:</label>
                                            <select name="pc_number" class="form-control" required>
                                                <option value="" disabled selected>Select PC Number</option>
                                                <?php for($i = 1; $i <= 30; $i++): ?>
                                                    <option value="<?php echo $i; ?>">PC <?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="sitin_purpose" class="form-label">Purpose:</label>
                                            <select name="sitin_purpose" class="form-control" required onchange="toggleOtherPurpose(this)">
                                                <option value="" disabled selected>Select Purpose</option>
                                                <option value="C Programming">C Programming</option>
                                                <option value="Java Programming">Java Programming</option>
                                                <option value="System Integration & Architecture">System Integration & Architecture</option>
                                                <option value="Embeded System & IOT">Embeded System & IOT</option>
                                                <option value="Digital Logic & Design">Digital Logic & Design</option>
                                                <option value="Computer Application">Computer Application</option>
                                                <option value="Database">Database</option>
                                                <option value="Project Management">Project Management</option>
                                                <option value="Python Programming">Python Programming</option>
                                                <option value="Mobile Application">Mobile Application</option>
                                                <option value="Others">Others</option>
                                            </select>
                                        </div>

                                        <div class="mb-3" id="other_purpose_div" style="display: none;">
                                            <label for="other_purpose" class="form-label">Specify Other Purpose:</label>
                                            <input type="text" name="other_purpose" class="form-control">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" name="start_sitin" class="btn btn-primary">Start Sit-in</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </td>
                <td><?php echo htmlspecialchars($student['user_id']); ?></td>
                <td><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']); ?></td>
                <td><?php echo htmlspecialchars($student['email']); ?></td>
                <td><?php echo htmlspecialchars($student['year_level']); ?></td>
                <td><?php echo htmlspecialchars($student['course']); ?></td>
                <td><?php echo htmlspecialchars($student['available_sessions'] ?? 0); ?></td>
                <td><?php echo htmlspecialchars($student['points'] ?? 0); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function toggleOtherPurpose(select) {
    var otherPurposeDiv = select.parentElement.nextElementSibling;
    if (select.value === 'Others') {
        otherPurposeDiv.style.display = 'block';
    } else {
        otherPurposeDiv.style.display = 'none';
    }
}
</script>

<?php require_once 'footer.php'; ?> 