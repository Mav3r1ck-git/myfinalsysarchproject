<?php
require_once 'database.php';
require_once 'admin_header.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error'], $_SESSION['success']);

// Get filter parameters
$lab_filter = isset($_GET['lab']) ? $_GET['lab'] : '';
$purpose_filter = isset($_GET['purpose']) ? $_GET['purpose'] : '';

// Build query for current sessions
$current_sql = "SELECT s.*, u.first_name, u.middle_name, u.last_name, u.user_id 
                FROM sitin_sessions s 
                JOIN users u ON s.user_id = u.user_id 
                WHERE s.status = 'active'";
$current_params = [];

// Build query for logged out sessions
$logged_out_sql = "SELECT s.*, u.first_name, u.middle_name, u.last_name, u.user_id 
                   FROM sitin_sessions s 
                   JOIN users u ON s.user_id = u.user_id 
                   WHERE s.status IN ('logged_out', 'rewarded')";

if ($lab_filter) {
    $current_sql .= " AND s.lab = ?";
    $logged_out_sql .= " AND s.lab = ?";
    $current_params[] = $lab_filter;
}

if ($purpose_filter) {
    $current_sql .= " AND s.purpose = ?";
    $logged_out_sql .= " AND s.purpose = ?";
    $current_params[] = $purpose_filter;
}

$current_sql .= " ORDER BY s.created_at DESC";
$logged_out_sql .= " ORDER BY s.ended_at DESC";

// Fetch current sessions
$current_stmt = $conn->prepare($current_sql);
$current_stmt->execute($current_params);
$current_sessions = $current_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch logged out sessions
$logged_out_stmt = $conn->prepare($logged_out_sql);
$logged_out_stmt->execute($current_params);
$logged_out_sessions = $logged_out_stmt->fetchAll(PDO::FETCH_ASSOC);

// Export parameters
$filter_query = http_build_query(['lab' => $lab_filter, 'purpose' => $purpose_filter]);
?>

<h2 class="mb-4">Manage Sit-in Sessions</h2>

<?php if($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="filter-container">
    <form method="GET" action="" class="row g-3">
        <div class="col-md-4">
            <label for="lab" class="form-label">Filter by Lab:</label>
            <select name="lab" id="lab" class="form-select">
                <option value="">All Labs</option>
                <option value="Lab 524" <?php echo $lab_filter == 'Lab 524' ? 'selected' : ''; ?>>Lab 524</option>
                <option value="Lab 526" <?php echo $lab_filter == 'Lab 526' ? 'selected' : ''; ?>>Lab 526</option>
                <option value="Lab 528" <?php echo $lab_filter == 'Lab 528' ? 'selected' : ''; ?>>Lab 528</option>
                <option value="Lab 530" <?php echo $lab_filter == 'Lab 530' ? 'selected' : ''; ?>>Lab 530</option>
                <option value="Lab 542" <?php echo $lab_filter == 'Lab 542' ? 'selected' : ''; ?>>Lab 542</option>
                <option value="Lab 544" <?php echo $lab_filter == 'Lab 544' ? 'selected' : ''; ?>>Lab 544</option>
                <option value="Lab 517" <?php echo $lab_filter == 'Lab 517' ? 'selected' : ''; ?>>Lab 517</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="purpose" class="form-label">Filter by Purpose:</label>
            <select name="purpose" id="purpose" class="form-select">
                <option value="">All Purposes</option>
                <option value="C Programming" <?php echo $purpose_filter == 'C Programming' ? 'selected' : ''; ?>>C Programming</option>
                <option value="Java Programming" <?php echo $purpose_filter == 'Java Programming' ? 'selected' : ''; ?>>Java Programming</option>
                <option value="System Integration & Architecture" <?php echo $purpose_filter == 'System Integration & Architecture' ? 'selected' : ''; ?>>System Integration & Architecture</option>
                <option value="Embeded System & IOT" <?php echo $purpose_filter == 'Embeded System & IOT' ? 'selected' : ''; ?>>Embeded System & IOT</option>
                <option value="Digital Logic & Design" <?php echo $purpose_filter == 'Digital Logic & Design' ? 'selected' : ''; ?>>Digital Logic & Design</option>
                <option value="Computer Application" <?php echo $purpose_filter == 'Computer Application' ? 'selected' : ''; ?>>Computer Application</option>
                <option value="Database" <?php echo $purpose_filter == 'Database' ? 'selected' : ''; ?>>Database</option>
                <option value="Project Management" <?php echo $purpose_filter == 'Project Management' ? 'selected' : ''; ?>>Project Management</option>
                <option value="Python Programming" <?php echo $purpose_filter == 'Python Programming' ? 'selected' : ''; ?>>Python Programming</option>
                <option value="Mobile Application" <?php echo $purpose_filter == 'Mobile Application' ? 'selected' : ''; ?>>Mobile Application</option>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
            <a href="admin_manage_sitin.php" class="btn btn-secondary">Clear Filters</a>
        </div>
    </form>
</div>

<!-- Current Sessions -->
<h3 class="mb-3">Current Sit-in Sessions</h3>
<div class="table-responsive mb-4">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Lab</th>
                <th>PC Number</th>
                <th>Purpose</th>
                <th>Start Time</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($current_sessions as $session): ?>
            <tr>
                <td><?php echo htmlspecialchars($session['user_id']); ?></td>
                <td><?php echo htmlspecialchars($session['last_name'] . ', ' . $session['first_name'] . ' ' . $session['middle_name']); ?></td>
                <td><?php echo htmlspecialchars($session['lab']); ?></td>
                <td><?php echo htmlspecialchars($session['pc_number']); ?></td>
                <td>
                    <?php 
                    echo htmlspecialchars($session['purpose']);
                    if ($session['other_purpose']) {
                        echo ' - ' . htmlspecialchars($session['other_purpose']);
                    }
                    ?>
                </td>
                <td><?php echo date('M d, Y h:i A', strtotime($session['created_at'])); ?></td>
                <td>
                    <form method="POST" action="process_sitin.php" style="display: inline;">
                        <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $session['user_id']; ?>">
                        <button type="submit" name="logout_sitin" class="btn btn-sm btn-warning">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                        <button type="submit" name="reward_sitin" class="btn btn-sm btn-success">
                            <i class="bi bi-star"></i> Reward
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Logged Out Sessions -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Logged Out Sit-in Sessions</h3>
    <div class="btn-group">
        <a href="export_sessions.php?format=csv&<?php echo $filter_query; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-file-csv me-1"></i> CSV
        </a>
        <a href="export_sessions.php?format=excel&<?php echo $filter_query; ?>" class="btn btn-sm btn-outline-success">
            <i class="fas fa-file-excel me-1"></i> Excel
        </a>
        <a href="export_sessions.php?format=print&<?php echo $filter_query; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
            <i class="fas fa-print me-1"></i> Print
        </a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Lab</th>
                <th>PC Number</th>
                <th>Purpose</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($logged_out_sessions as $session): ?>
            <tr>
                <td><?php echo htmlspecialchars($session['user_id']); ?></td>
                <td><?php echo htmlspecialchars($session['last_name'] . ', ' . $session['first_name'] . ' ' . $session['middle_name']); ?></td>
                <td><?php echo htmlspecialchars($session['lab']); ?></td>
                <td><?php echo htmlspecialchars($session['pc_number']); ?></td>
                <td>
                    <?php 
                    echo htmlspecialchars($session['purpose']);
                    if ($session['other_purpose']) {
                        echo ' - ' . htmlspecialchars($session['other_purpose']);
                    }
                    ?>
                </td>
                <td><?php echo date('M d, Y h:i A', strtotime($session['created_at'])); ?></td>
                <td><?php echo date('M d, Y h:i A', strtotime($session['ended_at'])); ?></td>
                <td>
                    <?php if($session['status'] == 'rewarded'): ?>
                        <span class="badge bg-success">Rewarded</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Logged Out</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?> 