<?php
require_once 'database.php';
session_start();

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $year_level = $_POST['year_level'];
    $course = $_POST['course'];

    // Validate passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if user_id or email already exists
        $check_sql = "SELECT * FROM users WHERE user_id = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute([$user_id, $email]);

        if ($check_stmt->rowCount() > 0) {
            $error = "User ID or Email already exists!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Set default profile picture
            $profile_picture = 'default_profile_picture.png';
            
            // Insert new user
            $sql = "INSERT INTO users (user_id, first_name, middle_name, last_name, email, password, year_level, course, profile_picture) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            try {
                if ($stmt->execute([$user_id, $first_name, $middle_name, $last_name, $email, $hashed_password, $year_level, $course, $profile_picture])) {
                    $success = "Registration successful! You can now login.";
                    // Clear form data after successful registration
                    $user_id = $first_name = $middle_name = $last_name = $email = $year_level = $course = '';
                } else {
                    $error = "Error: " . $stmt->errorInfo()[2];
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "User ID or Email already exists!";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .registration-form {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-form">
            <h2 class="text-center mb-4">User Registration</h2>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="user_id" class="form-label">User ID</label>
                        <input type="text" class="form-control" id="user_id" name="user_id" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="middle_name" class="form-label">Middle Name</label>
                        <input type="text" class="form-control" id="middle_name" name="middle_name">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="year_level" class="form-label">Year Level</label>
                        <select class="form-select" id="year_level" name="year_level" required>
                            <option value="">Select Year Level</option>
                            <option value="1st">1st Year</option>
                            <option value="2nd">2nd Year</option>
                            <option value="3rd">3rd Year</option>
                            <option value="4th">4th Year</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="course" class="form-label">Course</label>
                        <select class="form-select" id="course" name="course" required>
                            <option value="">Select Course</option>
                            <option value="BSA">Bachelor of Science in Accountancy (BSA)</option>
                            <option value="BAA">Bachelor of Arts (BAA)</option>
                            <option value="BSEd-BioSci">BSEd Major in Biological Science (BSEd-BioSci)</option>
                            <option value="BSBA">Bachelor of Science in Business Administration (BSBA)</option>
                            <option value="BSCE">Bachelor of Science in Civil Engineering (BSCE)</option>
                            <option value="BSCpE">Bachelor of Science in Computer Engineering (BSCpE)</option>
                            <option value="BSIT">Bachelor of Science in Information Technology (BSIT)</option>
                            <option value="BSEE">Bachelor of Science in Electrical Engineering (BSEE)</option>
                            <option value="BSECE">Bachelor of Science in Electronics and Communication Engineering (BSECE)</option>
                            <option value="BSME">Bachelor of Science in Mechanical Engineering (BSME)</option>
                            <option value="BSOA">Bachelor of Science in Office Administration (BSOA)</option>
                            <option value="BSREM">Bachelor of Science in Real Estate Management (BSREM)</option>
                            <option value="BSCS">Bachelor of Science in Computer Studies(BSCS)</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <p>Already have an account? <a href="user_login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 