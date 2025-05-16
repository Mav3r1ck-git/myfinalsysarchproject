<?php
require_once 'database.php';
require_once 'admin_header.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$error = '';
$success = '';

// Handle material upload
if (isset($_POST['upload_material'])) {
    $title = $_POST['title'];
    $description = $_POST['description'] ?? '';
    
    // Check if file was uploaded
    if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['material_file']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            // Create uploads directory if it doesn't exist
            if (!file_exists('uploads/materials')) {
                mkdir('uploads/materials', 0777, true);
            }
            
            // Generate unique filename
            $new_filename = 'material_' . time() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $filename);
            $upload_path = 'uploads/materials/' . $new_filename;
            
            if (move_uploaded_file($_FILES['material_file']['tmp_name'], $upload_path)) {
                // Insert record into database
                try {
                    // Check if materials table exists, create if not
                    $conn->query("
                        CREATE TABLE IF NOT EXISTS materials (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            title VARCHAR(255) NOT NULL,
                            description TEXT,
                            file_path VARCHAR(255) NOT NULL,
                            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    
                    $stmt = $conn->prepare("INSERT INTO materials (title, description, file_path) VALUES (?, ?, ?)");
                    $stmt->execute([$title, $description, $new_filename]);
                    
                    $success = "Material uploaded successfully!";
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            } else {
                $error = "Failed to upload file. Please try again.";
            }
        } else {
            $error = "Invalid file type. Allowed types: " . implode(', ', $allowed);
        }
    } else {
        $error = "Please select a file to upload.";
    }
}

// Handle schedule upload
if (isset($_POST['upload_schedule'])) {
    $title = $_POST['title'];
    $lab_room = $_POST['lab_room'];
    $description = $_POST['description'] ?? '';
    
    // Check if file was uploaded
    if (isset($_FILES['schedule_file']) && $_FILES['schedule_file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['schedule_file']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            // Create uploads directory if it doesn't exist
            if (!file_exists('uploads/schedules')) {
                mkdir('uploads/schedules', 0777, true);
            }
            
            // Generate unique filename
            $new_filename = 'schedule_' . time() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $filename);
            $upload_path = 'uploads/schedules/' . $new_filename;
            
            if (move_uploaded_file($_FILES['schedule_file']['tmp_name'], $upload_path)) {
                // Insert record into database
                try {
                    // Check if lab_schedules table exists, create if not
                    $conn->query("
                        CREATE TABLE IF NOT EXISTS lab_schedules (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            title VARCHAR(255) NOT NULL,
                            lab_room VARCHAR(50) NOT NULL,
                            description TEXT,
                            file_path VARCHAR(255) NOT NULL,
                            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                    
                    $stmt = $conn->prepare("INSERT INTO lab_schedules (title, lab_room, description, file_path) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$title, $lab_room, $description, $new_filename]);
                    
                    $success = "Schedule uploaded successfully!";
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            } else {
                $error = "Failed to upload file. Please try again.";
            }
        } else {
            $error = "Invalid file type. Allowed types: " . implode(', ', $allowed);
        }
    } else {
        $error = "Please select a file to upload.";
    }
}

// Get existing materials
$materials_query = "SELECT * FROM materials ORDER BY upload_date DESC";
try {
    $materials_stmt = $conn->query($materials_query);
    $materials = $materials_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $materials = [];
}

// Get existing schedules
$schedules_query = "SELECT * FROM lab_schedules ORDER BY upload_date DESC";
try {
    $schedules_stmt = $conn->query($schedules_query);
    $schedules = $schedules_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $schedules = [];
}

// Get admin information from session (for sidebar)
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<h2 class="mb-4">Manage Uploads</h2>

<?php if($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Nav tabs -->
<ul class="nav nav-tabs mb-4" id="uploadTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="materials-tab" data-bs-toggle="tab" data-bs-target="#materials" type="button" role="tab" aria-controls="materials" aria-selected="true">
            <i class="fas fa-book me-2"></i>Learning Materials
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="schedules-tab" data-bs-toggle="tab" data-bs-target="#schedules" type="button" role="tab" aria-controls="schedules" aria-selected="false">
            <i class="fas fa-calendar-alt me-2"></i>Lab Schedules
        </button>
    </li>
</ul>

<!-- Tab content -->
<div class="tab-content">
    <!-- Materials Tab -->
    <div class="tab-pane fade show active" id="materials" role="tabpanel" aria-labelledby="materials-tab">
        <div class="row">
            <div class="col-md-5">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Upload New Material</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="material_file" class="form-label">File</label>
                                <input type="file" class="form-control" id="material_file" name="material_file" required>
                                <div class="form-text">Allowed file types: pdf, doc, docx, ppt, pptx, xls, xlsx, txt, jpg, jpeg, png, gif</div>
                            </div>
                            <button type="submit" name="upload_material" class="btn btn-primary">Upload Material</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Uploaded Materials</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($materials)): ?>
                            <div class="alert alert-info mb-0">No materials uploaded yet.</div>
                        <?php else: ?>
                            <div class="file-list">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Uploaded On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($materials as $material): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($material['title']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($material['description'], 0, 50)) . (strlen($material['description']) > 50 ? '...' : ''); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($material['upload_date'])); ?></td>
                                            <td>
                                                <a href="uploads/materials/<?php echo htmlspecialchars($material['file_path']); ?>" class="btn btn-sm btn-primary" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="delete_material.php?id=<?php echo $material['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this material?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
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
    </div>
    
    <!-- Schedules Tab -->
    <div class="tab-pane fade" id="schedules" role="tabpanel" aria-labelledby="schedules-tab">
        <div class="row">
            <div class="col-md-5">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Upload New Lab Schedule</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="lab_room" class="form-label">Lab Room</label>
                                <select class="form-select" id="lab_room" name="lab_room" required>
                                    <option value="">Select Lab Room</option>
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
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="schedule_file" class="form-label">Schedule File</label>
                                <input type="file" class="form-control" id="schedule_file" name="schedule_file" required>
                                <div class="form-text">Allowed file types: pdf, doc, docx, jpg, jpeg, png, gif</div>
                            </div>
                            <button type="submit" name="upload_schedule" class="btn btn-primary">Upload Schedule</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Uploaded Lab Schedules</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($schedules)): ?>
                            <div class="alert alert-info mb-0">No lab schedules uploaded yet.</div>
                        <?php else: ?>
                            <div class="file-list">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Lab Room</th>
                                            <th>Uploaded On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($schedules as $schedule): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($schedule['title']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['lab_room']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($schedule['upload_date'])); ?></td>
                                            <td>
                                                <a href="uploads/schedules/<?php echo htmlspecialchars($schedule['file_path']); ?>" class="btn btn-sm btn-primary" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="delete_schedule.php?id=<?php echo $schedule['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this schedule?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
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
    </div>
</div>

<?php require_once 'footer.php'; ?> 