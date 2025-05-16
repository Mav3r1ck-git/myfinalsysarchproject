<?php
require_once 'database.php';
require_once 'admin_header.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error'], $_SESSION['success']);

// Handle PC status update
if (isset($_POST['update_pc_status'])) {
    $pc_id = $_POST['pc_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $conn->beginTransaction();
        
        // Get PC details
        $pc_stmt = $conn->prepare("
            SELECT lp.*, cl.lab_name 
            FROM lab_pcs lp
            JOIN computer_labs cl ON lp.lab_id = cl.id
            WHERE lp.id = ?
        ");
        $pc_stmt->execute([$pc_id]);
        $pc = $pc_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pc) {
            throw new Exception("PC not found");
        }
        
        // Check for active/pending reservations if changing to inactive
        if ($new_status == 'inactive') {
            $res_check = $conn->prepare("
                SELECT id 
                FROM sitin_reservations 
                WHERE lab = ? AND pc_number = ? 
                AND reservation_date >= CURRENT_DATE
                AND status IN ('approved', 'pending')
                LIMIT 1
            ");
            $res_check->execute([$pc['lab_name'], $pc['pc_number']]);
            
            if ($res_check->rowCount() > 0) {
                $_SESSION['error'] = "Cannot set PC to inactive - there are pending or approved reservations for this PC.";
                $conn->rollBack();
                header("Location: " . $_SERVER['PHP_SELF'] . "?lab_id=" . $pc['lab_id']);
                exit();
            }
        }
        
        // Update PC status
        $update_stmt = $conn->prepare("UPDATE lab_pcs SET status = ? WHERE id = ?");
        $result = $update_stmt->execute([$new_status, $pc_id]);
        
        if ($result) {
            $conn->commit();
            $success = "PC status updated successfully.";
        } else {
            $conn->rollBack();
            $error = "Failed to update PC status.";
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Error updating PC status: " . $e->getMessage();
    }
}

// Handle lab status update
if (isset($_POST['update_lab_status'])) {
    $lab_id = $_POST['lab_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $conn->beginTransaction();
        
        // Get lab details
        $lab_stmt = $conn->prepare("SELECT * FROM computer_labs WHERE id = ?");
        $lab_stmt->execute([$lab_id]);
        $lab = $lab_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$lab) {
            throw new Exception("Lab not found");
        }
        
        // If setting to inactive, check for reservations
        if ($new_status == 'inactive') {
            $res_check = $conn->prepare("
                SELECT id 
                FROM sitin_reservations 
                WHERE lab = ? 
                AND reservation_date >= CURRENT_DATE
                AND status IN ('approved', 'pending')
                LIMIT 1
            ");
            $res_check->execute([$lab['lab_name']]);
            
            if ($res_check->rowCount() > 0) {
                $_SESSION['error'] = "Cannot set lab to inactive - there are pending or approved reservations for this lab.";
                $conn->rollBack();
                header("Location: " . $_SERVER['PHP_SELF'] . "?lab_id=" . $lab_id);
                exit();
            }
        }
        
        // Update lab status
        $stmt = $conn->prepare("UPDATE computer_labs SET status = ? WHERE id = ?");
        $result = $stmt->execute([$new_status, $lab_id]);
        
        if ($result) {
            // If lab is set to inactive, set all PCs to inactive
            if ($new_status == 'inactive') {
                $pc_stmt = $conn->prepare("UPDATE lab_pcs SET status = 'inactive' WHERE lab_id = ?");
                $pc_stmt->execute([$lab_id]);
            }
            
            $conn->commit();
            $success = "Lab status updated successfully.";
        } else {
            $conn->rollBack();
            $error = "Failed to update lab status.";
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Error updating lab status: " . $e->getMessage();
    }
}

// Get selected lab
$selected_lab_id = isset($_GET['lab_id']) ? intval($_GET['lab_id']) : 0;

// Get all labs
$labs_stmt = $conn->query("SELECT * FROM computer_labs ORDER BY lab_name");
$labs = $labs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure all required labs exist
$required_labs = [
    'Lab 524', 'Lab 526', 'Lab 528', 'Lab 530', 'Lab 542', 'Lab 544', 'Lab 517'
];

$existing_lab_names = array_column($labs, 'lab_name');

foreach ($required_labs as $lab_name) {
    if (!in_array($lab_name, $existing_lab_names)) {
        // Create missing lab
        try {
            $create_lab = $conn->prepare("INSERT INTO computer_labs (lab_name, total_pcs, status) VALUES (?, 30, 'active')");
            $create_lab->execute([$lab_name]);
            
            // Get the new lab ID
            $new_lab_id = $conn->lastInsertId();
            
            // Create 30 PCs for this lab
            for ($i = 1; $i <= 30; $i++) {
                $pc_number = "PC" . str_pad($i, 2, "0", STR_PAD_LEFT);
                $create_pc = $conn->prepare("INSERT INTO lab_pcs (lab_id, pc_number, status) VALUES (?, ?, 'active')");
                $create_pc->execute([$new_lab_id, $pc_number]);
            }
            
            // Add to success message
            $success .= " Lab $lab_name was created with 30 PCs.";
        } catch (Exception $e) {
            $error .= " Error creating lab $lab_name: " . $e->getMessage();
        }
    }
}

// Refresh labs list if new ones were added
if (strpos($success, 'created') !== false) {
    $labs_stmt = $conn->query("SELECT * FROM computer_labs ORDER BY lab_name");
    $labs = $labs_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get selected lab details if a lab is selected
$selected_lab = null;
$pcs = [];

if ($selected_lab_id > 0) {
    // Get lab details
    $lab_stmt = $conn->prepare("SELECT * FROM computer_labs WHERE id = ?");
    $lab_stmt->execute([$selected_lab_id]);
    $selected_lab = $lab_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_lab) {
        // Get all PCs for the selected lab
        $pc_stmt = $conn->prepare("SELECT * FROM lab_pcs WHERE lab_id = ? ORDER BY pc_number");
        $pc_stmt->execute([$selected_lab_id]);
        $pcs = $pc_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check if we have all 30 PCs
        if (count($pcs) < 30) {
            // Find missing PC numbers and create them
            $existing_pc_numbers = array_column($pcs, 'pc_number');
            
            for ($i = 1; $i <= 30; $i++) {
                $pc_number = "PC" . str_pad($i, 2, "0", STR_PAD_LEFT);
                
                if (!in_array($pc_number, $existing_pc_numbers)) {
                    // Create missing PC
                    $create_pc = $conn->prepare("INSERT INTO lab_pcs (lab_id, pc_number, status) VALUES (?, ?, 'active')");
                    $create_pc->execute([$selected_lab_id, $pc_number]);
                }
            }
            
            // Refresh PC list
            $pc_stmt->execute([$selected_lab_id]);
            $pcs = $pc_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Computer Lab Management</h2>
        </div>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Lab Selection -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Labs</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($labs as $lab): ?>
                            <a href="?lab_id=<?php echo $lab['id']; ?>" class="list-group-item list-group-item-action <?php echo $selected_lab_id == $lab['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($lab['lab_name']); ?>
                                <?php if ($lab['status'] == 'inactive'): ?>
                                    <span class="badge bg-danger float-end">Inactive</span>
                                <?php else: ?>
                                    <span class="badge bg-success float-end">Active</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- PC Management -->
        <div class="col-md-9">
            <?php if ($selected_lab): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($selected_lab['lab_name']); ?> - PCs</h5>
                        <div>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="lab_id" value="<?php echo $selected_lab['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $selected_lab['status'] == 'active' ? 'inactive' : 'active'; ?>">
                                <button type="submit" name="update_lab_status" class="btn btn-sm <?php echo $selected_lab['status'] == 'active' ? 'btn-danger' : 'btn-success'; ?>">
                                    <?php echo $selected_lab['status'] == 'active' ? '<i class="fas fa-times-circle"></i> Set Inactive' : '<i class="fas fa-check-circle"></i> Set Active'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- PC Grid -->
                        <?php if (empty($pcs)): ?>
                            <div class="alert alert-info">No PCs found for this lab.</div>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
                                <?php foreach ($pcs as $pc): ?>
                                    <div class="col">
                                        <div class="card h-100 <?php 
                                            echo $pc['status'] == 'active' ? 'border-success' : 'border-danger';
                                        ?>">
                                            <div class="card-header <?php 
                                                echo $pc['status'] == 'active' ? 'bg-success' : 'bg-danger';
                                            ?> text-white">
                                                <h5 class="mb-0"><?php echo htmlspecialchars($pc['pc_number']); ?></h5>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text">
                                                    <strong>Status:</strong> 
                                                    <?php if ($pc['status'] == 'active'): ?>
                                                        <span class="text-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="text-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </p>
                                                <?php if ($pc['last_used']): ?>
                                                <p class="card-text">
                                                    <strong>Last Used:</strong> <?php echo date('M d, Y h:i A', strtotime($pc['last_used'])); ?>
                                                </p>
                                                <?php endif; ?>
                                                
                                                <form method="POST" action="">
                                                    <input type="hidden" name="pc_id" value="<?php echo $pc['id']; ?>">
                                                    <div class="mb-3">
                                                        <label for="new_status_<?php echo $pc['id']; ?>" class="form-label">Change Status:</label>
                                                        <select name="new_status" id="new_status_<?php echo $pc['id']; ?>" class="form-select form-select-sm">
                                                            <option value="active" <?php echo $pc['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                            <option value="inactive" <?php echo $pc['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                        </select>
                                                    </div>
                                                    <button type="submit" name="update_pc_status" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-sync-alt"></i> Update
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-success me-2">Active</span> Active
                                <span class="badge bg-danger mx-2">Inactive</span> Inactive
                            </div>
                            <div>
                                <strong>Total PCs:</strong> <?php echo count($pcs); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Please select a lab from the list to manage its PCs.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 