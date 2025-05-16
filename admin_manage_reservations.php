<?php
require_once 'database.php';
require_once 'notification_helpers.php';
require_once 'admin_header.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error'], $_SESSION['success']);

// Approve reservation
if (isset($_POST['approve_reservation'])) {
    $reservation_id = $_POST['reservation_id'];
    $admin_notes = $_POST['admin_notes'];
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Get reservation details
        $stmt = $conn->prepare("SELECT r.*, u.available_sessions 
                                FROM sitin_reservations r
                                JOIN users u ON r.user_id = u.user_id 
                                WHERE r.id = ?");
        $stmt->execute([$reservation_id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reservation) {
            // Verify lab exists and is active
            $lab_check = $conn->prepare("SELECT id FROM computer_labs WHERE lab_name = ? AND status = 'active'");
            $lab_check->execute([$reservation['lab']]);
            $lab_data = $lab_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$lab_data) {
                $error = "The requested lab is not available. Cannot approve.";
                $conn->rollBack();
            } else {
                $lab_id = $lab_data['id'];
                
                // Verify PC is available (for same-day reservations)
                if ($reservation['reservation_date'] == date('Y-m-d')) {
                    $pc_check = $conn->prepare("SELECT id, status FROM lab_pcs WHERE lab_id = ? AND pc_number = ?");
                    $pc_check->execute([$lab_id, $reservation['pc_number']]);
                    $pc_data = $pc_check->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$pc_data || $pc_data['status'] == 'inactive') {
                        $error = "The requested PC is not available or in maintenance. Cannot approve.";
                        $conn->rollBack();
                        exit;
                    }
                }
                
                // Check for conflicting reservations
                $check_stmt = $conn->prepare("
                    SELECT id FROM sitin_reservations 
                    WHERE id != ? AND lab = ? AND pc_number = ? AND reservation_date = ? 
                    AND start_time = ? AND status = 'approved'
                ");
                $check_stmt->execute([
                    $reservation_id,
                    $reservation['lab'], 
                    $reservation['pc_number'], 
                    $reservation['reservation_date'], 
                    $reservation['start_time']
                ]);
                
                if ($check_stmt->rowCount() > 0) {
                    $error = "There is a conflicting reservation for this PC and time. Cannot approve.";
                    $conn->rollBack();
                } else {
                    // Update reservation status
                    $update_stmt = $conn->prepare("
                        UPDATE sitin_reservations 
                        SET status = 'approved', admin_notes = ?, admin_id = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $update_result = $update_stmt->execute([$admin_notes, $admin_id, $reservation_id]);
                    
                    if ($update_result) {
                        // Check if student has available sessions
                        if ($reservation['available_sessions'] <= 0) {
                            $_SESSION['error'] = "Reservation approved, but student has no available sessions for sit-in.";
                            $success = "Reservation approved, but student has no available sessions for sit-in.";
                        } else {
                            // Create a sit-in session for all approved reservations regardless of date
                            $sitin_stmt = $conn->prepare("
                                INSERT INTO sitin_sessions 
                                (user_id, lab, pc_number, purpose, other_purpose, status) 
                                VALUES (?, ?, ?, ?, ?, 'active')
                            ");
                            $sitin_stmt->execute([
                                $reservation['user_id'],
                                $reservation['lab'],
                                $reservation['pc_number'],
                                $reservation['purpose'],
                                $reservation['other_purpose']
                            ]);
                            
                            // Update PC status to in-use if the reservation is for the current date
                            if ($reservation['reservation_date'] == date('Y-m-d')) {
                                $pc_stmt = $conn->prepare("
                                    UPDATE lab_pcs 
                                    SET status = 'in-use' 
                                    WHERE lab_id = ? AND pc_number = ?
                                ");
                                $pc_stmt->execute([$lab_id, $reservation['pc_number']]);
                            }
                            
                            $success = "Reservation approved and sit-in session created successfully.";
                        }
                        
                        // Create notification for user
                        $notification_title = 'Reservation Approved';
                        $notification_message = "Your reservation for {$reservation['lab']}, {$reservation['pc_number']} on " . 
                                              date('M d, Y', strtotime($reservation['reservation_date'])) . 
                                              " at " . date('h:i A', strtotime($reservation['start_time'])) .
                                              " has been approved.";
                        
                        create_notification(
                            $reservation['user_id'],
                            $notification_title,
                            $notification_message,
                            'reservation',
                            $reservation_id
                        );
                        
                        $conn->commit();
                    } else {
                        $conn->rollBack();
                        $error = "Failed to approve reservation.";
                    }
                }
            }
        } else {
            $conn->rollBack();
            $error = "Reservation not found.";
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Error approving reservation: " . $e->getMessage();
    }
}

// Reject reservation
if (isset($_POST['reject_reservation'])) {
    $reservation_id = $_POST['reservation_id'];
    $admin_notes = $_POST['admin_notes'];
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Get reservation details
        $stmt = $conn->prepare("SELECT * FROM sitin_reservations WHERE id = ?");
        $stmt->execute([$reservation_id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reservation) {
            // Update reservation status
            $update_stmt = $conn->prepare("
                UPDATE sitin_reservations 
                SET status = 'rejected', admin_notes = ?, admin_id = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $update_result = $update_stmt->execute([$admin_notes, $admin_id, $reservation_id]);
            
            if ($update_result) {
                // Create notification for user
                $notification_title = 'Reservation Rejected';
                $notification_message = "Your reservation for {$reservation['lab']}, {$reservation['pc_number']} on " . 
                                        date('M d, Y', strtotime($reservation['reservation_date'])) . 
                                        " has been rejected. Reason: " . ($admin_notes ? $admin_notes : 'Not specified');
                
                create_notification(
                    $reservation['user_id'],
                    $notification_title,
                    $notification_message,
                    'reservation',
                    $reservation_id
                );
                
                $conn->commit();
                $success = "Reservation rejected successfully.";
            } else {
                $conn->rollBack();
                $error = "Failed to reject reservation.";
            }
        } else {
            $conn->rollBack();
            $error = "Reservation not found.";
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Error rejecting reservation: " . $e->getMessage();
    }
}

// Get filter parameters
$lab_filter = isset($_GET['lab']) ? $_GET['lab'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Get log parameters
$log_status_filter = isset($_GET['log_status']) ? $_GET['log_status'] : 'all';
$log_search = isset($_GET['log_search']) ? $_GET['log_search'] : '';

// Build query for pending reservations
$reservation_sql = "
    SELECT r.*, u.first_name, u.middle_name, u.last_name, u.user_id as student_id
    FROM sitin_reservations r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.status = ?
";
$params = [$status_filter];

if ($lab_filter) {
    $reservation_sql .= " AND r.lab = ?";
    $params[] = $lab_filter;
}

if ($date_filter) {
    $reservation_sql .= " AND r.reservation_date = ?";
    $params[] = $date_filter;
}

$reservation_sql .= " ORDER BY r.reservation_date ASC, r.start_time ASC";

// Fetch reservations
$stmt = $conn->prepare($reservation_sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build query for logs (approved/rejected reservations)
$logs_sql = "
    SELECT r.*, u.first_name, u.middle_name, u.last_name, u.user_id as student_id, 
           a.first_name as admin_fname, a.last_name as admin_lname
    FROM sitin_reservations r
    JOIN users u ON r.user_id = u.user_id
    LEFT JOIN admins a ON r.admin_id = a.admin_id
    WHERE r.status IN (";

$log_params = [];

if ($log_status_filter == 'all') {
    $logs_sql .= "'approved', 'rejected'";
} else {
    $logs_sql .= "?";
    $log_params[] = $log_status_filter;
}

$logs_sql .= ")";

// Add search condition if provided
if ($log_search) {
    $logs_sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.middle_name LIKE ? OR u.user_id LIKE ?)";
    $search_term = "%$log_search%";
    $log_params[] = $search_term;
    $log_params[] = $search_term;
    $log_params[] = $search_term;
    $log_params[] = $search_term;
}

$logs_sql .= " ORDER BY r.updated_at DESC";

// Fetch logs
$logs_stmt = $conn->prepare($logs_sql);
$logs_stmt->execute($log_params);
$logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get labs for filter dropdown
$labs_stmt = $conn->query("SELECT DISTINCT lab FROM sitin_reservations ORDER BY lab");
$labs = $labs_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <?php if ($status_filter == 'pending'): ?>
                    Pending Sit-in Reservation Requests
                <?php elseif ($status_filter == 'approved'): ?>
                    Approved Sit-in Reservations
                <?php elseif ($status_filter == 'rejected'): ?>
                    Rejected Sit-in Reservations
                <?php else: ?>
                    All Sit-in Reservations
                <?php endif; ?>
            </h2>
        </div>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status:</label>
                    <select name="status" id="status" class="form-select">
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="lab" class="form-label">Lab:</label>
                    <select name="lab" id="lab" class="form-select">
                        <option value="">All Labs</option>
                        <?php foreach ($labs as $lab): ?>
                        <option value="<?php echo htmlspecialchars($lab); ?>" <?php echo $lab_filter == $lab ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lab); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Date:</label>
                    <input type="date" name="date" id="date" class="form-control" value="<?php echo $date_filter; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                    <a href="admin_manage_reservations.php" class="btn btn-secondary">Clear Filters</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reservations Table -->
    <div class="card mb-4">
        <div class="card-body">
            <?php if (empty($reservations)): ?>
                <div class="alert alert-info">
                    <?php if ($status_filter == 'pending'): ?>
                        No pending reservation requests found.
                    <?php elseif ($status_filter == 'approved'): ?>
                        No approved reservations found.
                    <?php elseif ($status_filter == 'rejected'): ?>
                        No rejected reservations found.
                    <?php else: ?>
                        No reservations found.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Lab</th>
                                <th>PC Number</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Purpose</th>
                                <?php if ($status_filter != 'pending'): ?>
                                <th>Admin Notes</th>
                                <?php endif; ?>
                                <th>Requested On</th>
                                <?php if ($status_filter == 'pending'): ?>
                                <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservations as $reservation): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($reservation['student_id']); ?><br>
                                    <?php echo htmlspecialchars($reservation['last_name'] . ', ' . $reservation['first_name'] . ' ' . $reservation['middle_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($reservation['lab']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['pc_number']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($reservation['reservation_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($reservation['start_time'])); ?></td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($reservation['purpose']);
                                    if ($reservation['other_purpose']) {
                                        echo ' - ' . htmlspecialchars($reservation['other_purpose']);
                                    }
                                    ?>
                                </td>
                                <?php if ($status_filter != 'pending'): ?>
                                <td><?php echo $reservation['admin_notes'] ? htmlspecialchars($reservation['admin_notes']) : '-'; ?></td>
                                <?php endif; ?>
                                <td><?php echo date('M d, Y h:i A', strtotime($reservation['created_at'])); ?></td>
                                
                                <?php if ($status_filter == 'pending'): ?>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $reservation['id']; ?>">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $reservation['id']; ?>">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    
                                    <!-- Approve Modal -->
                                    <div class="modal fade" id="approveModal<?php echo $reservation['id']; ?>" tabindex="-1" aria-labelledby="approveModalLabel<?php echo $reservation['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-success text-white">
                                                    <h5 class="modal-title" id="approveModalLabel<?php echo $reservation['id']; ?>">Approve Reservation</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                        
                                                        <p>Are you sure you want to approve this reservation?</p>
                                                        <p><strong>Note:</strong> Approving this reservation will automatically create a sit-in session for the student.</p>
                                                        
                                                        <div class="mb-3">
                                                            <label for="approve_notes<?php echo $reservation['id']; ?>" class="form-label">Notes (Optional):</label>
                                                            <textarea name="admin_notes" id="approve_notes<?php echo $reservation['id']; ?>" class="form-control" rows="3" placeholder="Any additional information for the student..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="approve_reservation" class="btn btn-success">Approve Reservation</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal<?php echo $reservation['id']; ?>" tabindex="-1" aria-labelledby="rejectModalLabel<?php echo $reservation['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="rejectModalLabel<?php echo $reservation['id']; ?>">Reject Reservation</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                        
                                                        <p>Are you sure you want to reject this reservation?</p>
                                                        
                                                        <div class="mb-3">
                                                            <label for="reject_notes<?php echo $reservation['id']; ?>" class="form-label">Reason:</label>
                                                            <textarea name="admin_notes" id="reject_notes<?php echo $reservation['id']; ?>" class="form-control" rows="3" placeholder="Reason for rejection..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="reject_reservation" class="btn btn-danger">Reject Reservation</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Logs Section -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h3 class="mb-0">Request Logs</h3>
        </div>
        <div class="card-body">
            <!-- Logs Filters -->
            <form method="GET" action="" class="row g-3 mb-4">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <input type="hidden" name="lab" value="<?php echo htmlspecialchars($lab_filter); ?>">
                <input type="hidden" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                
                <div class="col-md-4">
                    <label for="log_status" class="form-label">Log Status:</label>
                    <select name="log_status" id="log_status" class="form-select">
                        <option value="all" <?php echo $log_status_filter == 'all' ? 'selected' : ''; ?>>All Logs</option>
                        <option value="approved" <?php echo $log_status_filter == 'approved' ? 'selected' : ''; ?>>Approved Only</option>
                        <option value="rejected" <?php echo $log_status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected Only</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="log_search" class="form-label">Search Student:</label>
                    <input type="text" name="log_search" id="log_search" class="form-control" placeholder="Enter student name or ID" value="<?php echo htmlspecialchars($log_search); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i> Search Logs
                    </button>
                </div>
            </form>
            
            <!-- Logs Table -->
            <?php if (empty($logs)): ?>
                <div class="alert alert-info">No logs found matching your criteria.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Lab</th>
                                <th>PC</th>
                                <th>Date & Time</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Admin</th>
                                <th>Notes</th>
                                <th>Processed On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($logs as $log): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($log['student_id']); ?><br>
                                    <?php echo htmlspecialchars($log['last_name'] . ', ' . $log['first_name'] . ' ' . $log['middle_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['lab']); ?></td>
                                <td><?php echo htmlspecialchars($log['pc_number']); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($log['reservation_date'])); ?><br>
                                    <?php echo date('h:i A', strtotime($log['start_time'])); ?>
                                </td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($log['purpose']);
                                    if ($log['other_purpose']) {
                                        echo ' - ' . htmlspecialchars($log['other_purpose']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if($log['status'] == 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($log['admin_fname'] && $log['admin_lname']) {
                                        echo htmlspecialchars($log['admin_fname'] . ' ' . $log['admin_lname']);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $log['admin_notes'] ? htmlspecialchars($log['admin_notes']) : '-'; ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($log['updated_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 