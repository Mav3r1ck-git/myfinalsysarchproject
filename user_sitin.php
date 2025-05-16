<?php
require_once 'database.php';
require_once 'notification_helpers.php';
require_once 'user_header.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle feedback submission
if (isset($_POST['submit_feedback'])) {
    $session_id = $_POST['session_id'];
    $feedback = $_POST['feedback'];
    $rating = $_POST['rating'];
    
    try {
        $stmt = $conn->prepare("UPDATE sitin_sessions SET feedback = ?, rating = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$feedback, $rating, $session_id, $user_id]);
        $success = "Feedback submitted successfully!";
    } catch (PDOException $e) {
        $error = "Error submitting feedback: " . $e->getMessage();
    }
}

// Handle reservation submission
if (isset($_POST['submit_reservation'])) {
    $lab = $_POST['lab'];
    $pc_number = $_POST['pc_number'];
    $reservation_date = $_POST['reservation_date'];
    $start_time = $_POST['start_time'];
    $purpose = $_POST['purpose'];
    $other_purpose = isset($_POST['other_purpose']) ? $_POST['other_purpose'] : null;
    
    // Format PC number to PC## format for database storage
    $pc_formatted = 'PC' . str_pad($pc_number, 2, '0', STR_PAD_LEFT);
    
    try {
        // Verify lab exists
        $lab_check = $conn->prepare("SELECT id FROM computer_labs WHERE lab_name = ?");
        $lab_check->execute([$lab]);
        $lab_data = $lab_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$lab_data) {
            // If lab doesn't exist in database, create it
            $create_lab = $conn->prepare("INSERT INTO computer_labs (lab_name, total_pcs, status) VALUES (?, 30, 'active')");
            $create_lab->execute([$lab]);
            $lab_id = $conn->lastInsertId();
        } else {
            $lab_id = $lab_data['id'];
        }
        
        // Check if PC exists in the lab, create if it doesn't
        $pc_check = $conn->prepare("SELECT id FROM lab_pcs WHERE lab_id = ? AND pc_number = ?");
        $pc_check->execute([$lab_id, $pc_formatted]);
        
        if ($pc_check->rowCount() == 0) {
            // If PC doesn't exist, create it
            $create_pc = $conn->prepare("INSERT INTO lab_pcs (lab_id, pc_number, status) VALUES (?, ?, 'active')");
            $create_pc->execute([$lab_id, $pc_formatted]);
        }
        
        // Check if there's a conflicting reservation (keep this check for scheduling conflicts)
        $check_stmt = $conn->prepare("
            SELECT id FROM sitin_reservations 
            WHERE lab = ? AND pc_number = ? AND reservation_date = ? 
            AND start_time = ? AND status = 'approved'
        ");
        $check_stmt->execute([$lab, $pc_formatted, $reservation_date, $start_time]);
        
        if ($check_stmt->rowCount() > 0) {
            $error = "The selected PC is already reserved during that time. Please choose a different time or PC.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO sitin_reservations (user_id, lab, pc_number, reservation_date, start_time, purpose, other_purpose)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$user_id, $lab, $pc_formatted, $reservation_date, $start_time, $purpose, $other_purpose]);
            
            if ($result) {
                $success = "Reservation request submitted successfully! An admin will review your request.";
            } else {
                $error = "Failed to submit reservation. Please try again.";
            }
        }
    } catch (PDOException $e) {
        $error = "Error submitting reservation: " . $e->getMessage();
    }
}

// Fetch current session
$current_stmt = $conn->prepare("SELECT * FROM sitin_sessions WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC");
$current_stmt->execute([$user_id]);
$current_session = $current_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch logged out sessions
$logged_out_stmt = $conn->prepare("SELECT * FROM sitin_sessions WHERE user_id = ? AND status IN ('logged_out', 'rewarded') ORDER BY ended_at DESC");
$logged_out_stmt->execute([$user_id]);
$logged_out_sessions = $logged_out_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending reservations
$pending_stmt = $conn->prepare("SELECT * FROM sitin_reservations WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC");
$pending_stmt->execute([$user_id]);
$pending_reservations = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved/rejected reservations
$history_stmt = $conn->prepare("SELECT * FROM sitin_reservations WHERE user_id = ? AND status IN ('approved', 'rejected') ORDER BY updated_at DESC");
$history_stmt->execute([$user_id]);
$reservation_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available labs for the dropdown with PC availability counts
$labs = [
    ['lab_name' => 'Lab 524', 'total_pcs' => 30],
    ['lab_name' => 'Lab 526', 'total_pcs' => 30],
    ['lab_name' => 'Lab 528', 'total_pcs' => 30],
    ['lab_name' => 'Lab 530', 'total_pcs' => 30],
    ['lab_name' => 'Lab 542', 'total_pcs' => 30],
    ['lab_name' => 'Lab 544', 'total_pcs' => 30],
    ['lab_name' => 'Lab 517', 'total_pcs' => 30]
];

// Get availability counts for each lab
$available_counts = [];
foreach ($labs as &$lab) {
    $lab_stmt = $conn->prepare("
        SELECT cl.id,
               SUM(CASE WHEN lp.status = 'active' THEN 1 ELSE 0 END) as available_pcs,
               cl.status
        FROM computer_labs cl
        LEFT JOIN lab_pcs lp ON cl.id = lp.lab_id
        WHERE cl.lab_name = ?
        GROUP BY cl.id
    ");
    $lab_stmt->execute([$lab['lab_name']]);
    $lab_data = $lab_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lab_data) {
        $lab['id'] = $lab_data['id'];
        $lab['available_pcs'] = $lab_data['available_pcs'] ?? 0;
        $lab['status'] = $lab_data['status'] ?? 'active';
    } else {
        $lab['id'] = 0;
        $lab['available_pcs'] = 0;
        $lab['status'] = 'inactive';
    }
}
?>

<style>
    select option:checked {
        background-color: #007bff !important;
        color: white !important;
    }
</style>

<div class="container mt-4">
    <h2 class="mb-4">My Sit-in Sessions</h2>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Reservation Form Button -->
    <div class="mb-4">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reservationModal">
            <i class="fas fa-calendar-plus me-2"></i> Request Reservation
        </button>
    </div>

    <!-- Current Session -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Current Session</h4>
        </div>
        <div class="card-body">
            <?php if($current_session): ?>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Lab:</strong> <?php echo htmlspecialchars($current_session['lab']); ?></p>
                        <p><strong>PC Number:</strong> <?php echo htmlspecialchars($current_session['pc_number']); ?></p>
                        <p><strong>Purpose:</strong> <?php echo htmlspecialchars($current_session['purpose']); ?></p>
                        <?php if($current_session['other_purpose']): ?>
                            <p><strong>Other Purpose:</strong> <?php echo htmlspecialchars($current_session['other_purpose']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Start Time:</strong> <?php echo date('M d, Y h:i A', strtotime($current_session['created_at'])); ?></p>
                        <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted">No active session found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Reservations -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">Pending Reservations</h4>
        </div>
        <div class="card-body">
            <?php if($pending_reservations): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Lab</th>
                                <th>PC Number</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Requested On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending_reservations as $reservation): ?>
                            <tr>
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
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($reservation['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No pending reservations found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reservation History -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0">Reservation History</h4>
        </div>
        <div class="card-body">
            <?php if($reservation_history): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Lab</th>
                                <th>PC Number</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Admin Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservation_history as $reservation): ?>
                            <tr>
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
                                <td>
                                    <?php if($reservation['status'] == 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $reservation['admin_notes'] ? htmlspecialchars($reservation['admin_notes']) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No reservation history found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Logged Out Sessions -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h4 class="mb-0">Logged Out Sessions</h4>
        </div>
        <div class="card-body">
            <?php if($logged_out_sessions): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Lab</th>
                                <th>PC Number</th>
                                <th>Purpose</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($logged_out_sessions as $session): ?>
                            <tr>
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
                                <td>
                                    <?php if($session['feedback']): ?>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewFeedbackModal<?php echo $session['id']; ?>">
                                            <i class="fas fa-eye"></i> View Feedback
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal<?php echo $session['id']; ?>">
                                            <i class="fas fa-comment"></i> Add Feedback
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Feedback Modal -->
                            <div class="modal fade" id="feedbackModal<?php echo $session['id']; ?>" tabindex="-1" aria-labelledby="feedbackModalLabel<?php echo $session['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="feedbackModalLabel<?php echo $session['id']; ?>">Add Feedback</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="">
                                            <div class="modal-body">
                                                <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label for="rating<?php echo $session['id']; ?>" class="form-label">Rating:</label>
                                                    <select name="rating" id="rating<?php echo $session['id']; ?>" class="form-select" required>
                                                        <option value="" disabled selected>Select Rating</option>
                                                        <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                                        <option value="4">⭐⭐⭐⭐ Very Good</option>
                                                        <option value="3">⭐⭐⭐ Good</option>
                                                        <option value="2">⭐⭐ Fair</option>
                                                        <option value="1">⭐ Poor</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="feedback<?php echo $session['id']; ?>" class="form-label">Feedback:</label>
                                                    <textarea name="feedback" id="feedback<?php echo $session['id']; ?>" class="form-control" rows="4" required placeholder="Share your experience..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" name="submit_feedback" class="btn btn-primary">Submit Feedback</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- View Feedback Modal -->
                            <div class="modal fade" id="viewFeedbackModal<?php echo $session['id']; ?>" tabindex="-1" aria-labelledby="viewFeedbackModalLabel<?php echo $session['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewFeedbackModalLabel<?php echo $session['id']; ?>">View Feedback</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Rating:</label>
                                                <div>
                                                    <?php
                                                    $rating = $session['rating'];
                                                    for($i = 1; $i <= 5; $i++) {
                                                        echo $i <= $rating ? '⭐' : '☆';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Feedback:</label>
                                                <p class="form-control-static"><?php echo nl2br(htmlspecialchars($session['feedback'])); ?></p>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No logged out sessions found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reservation Request Modal -->
<div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="reservationModalLabel">Request PC Reservation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="lab" class="form-label">Lab:</label>
                            <select name="lab" id="lab" class="form-select" required>
                                <option value="" disabled selected>Select Lab</option>
                                <?php foreach ($labs as $lab): ?>
                                    <option value="<?php echo htmlspecialchars($lab['lab_name']); ?>">
                                        <?php echo htmlspecialchars($lab['lab_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="pc_number" class="form-label">PC Number:</label>
                            <select name="pc_number" id="pc_number" class="form-select" required disabled>
                                <option value="" disabled selected>Select PC Number</option>
                                <!-- PC numbers will be populated via JavaScript -->
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="reservation_date" class="form-label">Date:</label>
                            <input type="date" name="reservation_date" id="reservation_date" class="form-control" required
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="start_time" class="form-label">Start Time:</label>
                            <input type="time" name="start_time" id="start_time" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="purpose" class="form-label">Purpose:</label>
                        <select name="purpose" id="purpose" class="form-select" required>
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
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="other_purpose_container" style="display: none;">
                        <label for="other_purpose" class="form-label">Specify Other Purpose:</label>
                        <textarea name="other_purpose" id="other_purpose" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="submit_reservation" class="btn btn-primary">Submit Reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Show/hide other purpose textarea based on selection
document.getElementById('purpose').addEventListener('change', function() {
    const otherPurposeContainer = document.getElementById('other_purpose_container');
    if (this.value === 'Other') {
        otherPurposeContainer.style.display = 'block';
        document.getElementById('other_purpose').setAttribute('required', 'required');
    } else {
        otherPurposeContainer.style.display = 'none';
        document.getElementById('other_purpose').removeAttribute('required');
    }
});

// Load PCs when lab is selected
document.getElementById('lab').addEventListener('change', function() {
    const pcSelect = document.getElementById('pc_number');
    const lab = this.value;
    
    if (lab) {
        // Clear existing options
        pcSelect.innerHTML = '<option value="" disabled selected>Loading PCs...</option>';
        pcSelect.disabled = true;
        
        // Fetch PCs for the selected lab
        fetch('get_available_pcs.php?lab=' + encodeURIComponent(lab))
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                pcSelect.innerHTML = '<option value="" disabled selected>Select PC Number</option>';
                
                if (data.length > 0) {
                    data.forEach(pc => {
                        const option = document.createElement('option');
                        option.value = pc.pc_number;
                        option.textContent = 'PC ' + pc.pc_number;
                        pcSelect.appendChild(option);
                    });
                    pcSelect.disabled = false;
                } else {
                    pcSelect.innerHTML = '<option value="" disabled selected>No PCs available in this lab</option>';
                }
            })
            .catch(error => {
                console.error('Error fetching PCs:', error);
                pcSelect.innerHTML = '<option value="" disabled selected>Error loading PCs</option>';
            });
    } else {
        pcSelect.innerHTML = '<option value="" disabled selected>Select PC Number</option>';
        pcSelect.disabled = true;
    }
});
</script>

<?php require_once 'footer.php'; ?> 