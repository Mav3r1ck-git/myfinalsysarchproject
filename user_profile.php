<?php
require_once 'database.php';
require_once 'user_header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Temporary debug info
if (!$user) {
    echo '<div class="alert alert-danger">Debug info: Looking for user_id = ' . htmlspecialchars($user_id) . '. User not found in database.</div>';
    
    // Check if we have any users at all
    $all_users = $conn->query("SELECT user_id FROM users LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($all_users)) {
        echo '<div class="alert alert-warning">No users found in database at all. Please register first.</div>';
    } else {
        echo '<div class="alert alert-info">Some existing user IDs: ' . implode(', ', $all_users) . '</div>';
    }
    
    // Don't redirect immediately for debugging purposes
    echo '<p><a href="user_login.php" class="btn btn-primary">Return to Login</a></p>';
    require_once 'footer.php';
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $year_level = $_POST['year_level'];
    $course = $_POST['course'];
    
    try {
        // Handle profile picture upload
        $profile_picture = $user['profile_picture']; // Keep existing picture by default
        
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_picture']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                // Create uploads directory if it doesn't exist
                if (!file_exists('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                
                // Generate unique filename
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $filetype;
                $upload_path = 'uploads/' . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    // Delete old profile picture if it's not the default
                    if ($user['profile_picture'] != 'default_profile_picture.png' && file_exists('uploads/' . $user['profile_picture'])) {
                        unlink('uploads/' . $user['profile_picture']);
                    }
                    $profile_picture = $new_filename;
                }
            } else {
                $error = "Invalid file type. Please upload a JPG, JPEG, PNG, or GIF file.";
            }
        }
        
        // Update user information
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, email = ?, year_level = ?, course = ?, profile_picture = ? WHERE user_id = ?");
        $stmt->execute([$first_name, $middle_name, $last_name, $email, $year_level, $course, $profile_picture, $user_id]);
        
        $success = "Profile updated successfully!";
        
        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <?php
                    $profile_pic = $user['profile_picture'] ?? 'default_profile_picture.png';
                    $profile_path = file_exists('uploads/' . $profile_pic) ? 'uploads/' . $profile_pic : $profile_pic;
                    ?>
                    <img src="<?php echo htmlspecialchars($profile_path); ?>" class="rounded-circle img-fluid mb-3" style="width: 200px; height: 200px; object-fit: cover;" alt="Profile Picture">
                    <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user['user_id']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Edit Profile</h4>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="year_level" class="form-label">Year Level</label>
                                <select class="form-select" id="year_level" name="year_level" required>
                                    <option value="1st Year" <?php echo $user['year_level'] == '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2nd Year" <?php echo $user['year_level'] == '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3rd Year" <?php echo $user['year_level'] == '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4th Year" <?php echo $user['year_level'] == '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="course" class="form-label">Course</label>
                                <select class="form-select" id="course" name="course" required>
                                    <option value="BSCS" <?php echo $user['course'] == 'BSCS' ? 'selected' : ''; ?>>BSCS</option>
                                    <option value="BSIT" <?php echo $user['course'] == 'BSIT' ? 'selected' : ''; ?>>BSIT</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                            <small class="text-muted">Leave empty to keep current picture. Maximum file size: 2MB</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?> 