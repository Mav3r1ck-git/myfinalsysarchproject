<?php
require_once 'database.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$error = '';
$success = '';

// Get student ID from URL
if (!isset($_GET['id'])) {
    header("Location: admin_manage_student.php");
    exit();
}

$student_id = $_GET['id'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $year_level = $_POST['year_level'];
    $course = $_POST['course'];
    
    // Debug information
    error_log("Year Level submitted: " . $year_level);
    
    try {
        $sql = "UPDATE users SET 
                first_name = ?, 
                middle_name = ?, 
                last_name = ?, 
                year_level = ?, 
                course = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$first_name, $middle_name, $last_name, $year_level, $course, $student_id]);
        
        if ($result) {
            $success = "Student information updated successfully!";
            error_log("Update successful for student ID: " . $student_id);
        } else {
            $error = "Error updating student: " . implode(", ", $stmt->errorInfo());
            error_log("Update failed: " . implode(", ", $stmt->errorInfo()));
        }
    } catch(PDOException $e) {
        $error = "Error updating student: " . $e->getMessage();
        error_log("PDO Exception: " . $e->getMessage());
    }
}

// Get student information
try {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header("Location: admin_manage_student.php");
        exit();
    }
    
    // Debug information
    error_log("Current year level in database: " . $student['year_level']);
} catch(PDOException $e) {
    $error = "Error fetching student information: " . $e->getMessage();
    error_log("Error fetching student: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
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
        .edit-form {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
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
                    <img src="ccs2.png" alt="Profile Picture" class="profile-pic" style="width: 150px; height: 150px; border-radius: 50%;">
                    <h4 class="text-white"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h4>
                    <p class="text-white-50">Administrator</p>
                </div>
                <nav>
                    <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="admin_manage_student.php" class="active"><i class="fas fa-users"></i> Manage Students</a>
                    <a href="manage_courses.php"><i class="fas fa-book"></i> Manage Courses</a>
                    <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                    <a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Edit Student</h2>
                    <a href="admin_manage_student.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>

                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="edit-form">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="user_id" class="form-label">Student ID</label>
                                <input type="text" class="form-control" id="user_id" value="<?php echo htmlspecialchars($student['user_id']); ?>" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($student['middle_name']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="year_level" class="form-label">Year Level</label>
                                <select class="form-select" id="year_level" name="year_level" required>
                                    <option value="1st" <?php echo $student['year_level'] == '1st' ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2nd" <?php echo $student['year_level'] == '2nd' ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3rd" <?php echo $student['year_level'] == '3rd' ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4th" <?php echo $student['year_level'] == '4th' ? 'selected' : ''; ?>>4th Year</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="course" class="form-label">Course</label>
                                <select class="form-select" id="course" name="course" required>
                                    <option value="">Select Course</option>
                                    <option value="BSA" <?php echo $student['course'] == 'BSA' ? 'selected' : ''; ?>>Bachelor of Science in Accountancy (BSA)</option>
                                    <option value="BAA" <?php echo $student['course'] == 'BAA' ? 'selected' : ''; ?>>Bachelor of Arts (BAA)</option>
                                    <option value="BSEd-BioSci" <?php echo $student['course'] == 'BSEd-BioSci' ? 'selected' : ''; ?>>BSEd Major in Biological Science (BSEd-BioSci)</option>
                                    <option value="BSBA" <?php echo $student['course'] == 'BSBA' ? 'selected' : ''; ?>>Bachelor of Science in Business Administration (BSBA)</option>
                                    <option value="BSCE" <?php echo $student['course'] == 'BSCE' ? 'selected' : ''; ?>>Bachelor of Science in Civil Engineering (BSCE)</option>
                                    <option value="BSCpE" <?php echo $student['course'] == 'BSCpE' ? 'selected' : ''; ?>>Bachelor of Science in Computer Engineering (BSCpE)</option>
                                    <option value="BSIT" <?php echo $student['course'] == 'BSIT' ? 'selected' : ''; ?>>Bachelor of Science in Information Technology (BSIT)</option>
                                    <option value="BSEE" <?php echo $student['course'] == 'BSEE' ? 'selected' : ''; ?>>Bachelor of Science in Electrical Engineering (BSEE)</option>
                                    <option value="BSECE" <?php echo $student['course'] == 'BSECE' ? 'selected' : ''; ?>>Bachelor of Science in Electronics and Communication Engineering (BSECE)</option>
                                    <option value="BSME" <?php echo $student['course'] == 'BSME' ? 'selected' : ''; ?>>Bachelor of Science in Mechanical Engineering (BSME)</option>
                                    <option value="BSOA" <?php echo $student['course'] == 'BSOA' ? 'selected' : ''; ?>>Bachelor of Science in Office Administration (BSOA)</option>
                                    <option value="BSREM" <?php echo $student['course'] == 'BSREM' ? 'selected' : ''; ?>>Bachelor of Science in Real Estate Management (BSREM)</option>
                                    <option value="BSCS" <?php echo $student['course'] == 'BSCS' ? 'selected' : ''; ?>>Bachelor of Science in Computer Studies(BSCS)</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="admin_manage_student.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Student</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 