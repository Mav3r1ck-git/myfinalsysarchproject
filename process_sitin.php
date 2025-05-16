<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['start_sitin'])) {
    $user_id = $_POST['user_id'];
    $lab = $_POST['sitin_lab'];
    $pc_number = $_POST['pc_number'];
    $purpose = $_POST['sitin_purpose'];
    $other_purpose = isset($_POST['other_purpose']) ? $_POST['other_purpose'] : null;

    try {
        // Check if PC is already in use
        $check_pc = $conn->prepare("SELECT * FROM sitin_sessions WHERE lab = ? AND pc_number = ? AND status = 'active'");
        $check_pc->execute([$lab, $pc_number]);
        
        if ($check_pc->rowCount() > 0) {
            $_SESSION['error'] = "This PC is already in use!";
            header("Location: admin_manage_student.php");
            exit();
        }

        // Check if student has available sessions
        $check_sessions = $conn->prepare("SELECT available_sessions FROM users WHERE user_id = ?");
        $check_sessions->execute([$user_id]);
        $user = $check_sessions->fetch(PDO::FETCH_ASSOC);

        if ($user['available_sessions'] <= 0) {
            $_SESSION['error'] = "Student has no available sessions!";
            header("Location: admin_manage_student.php");
            exit();
        }

        // Start sit-in session
        $stmt = $conn->prepare("INSERT INTO sitin_sessions (user_id, lab, pc_number, purpose, other_purpose, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$user_id, $lab, $pc_number, $purpose, $other_purpose]);

        // Remove session deduction from here - will be done on logout/reward

        $_SESSION['success'] = "Sit-in session started successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error starting sit-in session: " . $e->getMessage();
    }

    header("Location: admin_manage_sitin.php");
    exit();
}

// Handle logout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout_sitin'])) {
    $session_id = $_POST['session_id'];
    $user_id = $_POST['user_id'];
    
    try {
        // Update session status
        $stmt = $conn->prepare("UPDATE sitin_sessions SET status = 'logged_out', ended_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$session_id]);
        
        // Decrease available sessions when logging out
        $update_sessions = $conn->prepare("UPDATE users SET available_sessions = available_sessions - 1 WHERE user_id = ?");
        $update_sessions->execute([$user_id]);
        
        $_SESSION['success'] = "Student logged out successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error logging out student: " . $e->getMessage();
    }

    header("Location: admin_manage_sitin.php");
    exit();
}

// Handle reward
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reward_sitin'])) {
    $session_id = $_POST['session_id'];
    $user_id = $_POST['user_id'];
    
    try {
        // Update session status
        $stmt = $conn->prepare("UPDATE sitin_sessions SET status = 'rewarded', ended_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$session_id]);

        // Decrease available sessions when rewarding
        $update_sessions = $conn->prepare("UPDATE users SET available_sessions = available_sessions - 1 WHERE user_id = ?");
        $update_sessions->execute([$user_id]);

        // Add point to user
        $update_points = $conn->prepare("UPDATE users SET points = points + 1 WHERE user_id = ?");
        $update_points->execute([$user_id]);

        // Check if points are divisible by 3
        $check_points = $conn->prepare("SELECT points FROM users WHERE user_id = ?");
        $check_points->execute([$user_id]);
        $user = $check_points->fetch(PDO::FETCH_ASSOC);

        if ($user['points'] % 3 == 0) {
            // Add bonus session
            $add_session = $conn->prepare("UPDATE users SET available_sessions = available_sessions + 1 WHERE user_id = ?");
            $add_session->execute([$user_id]);
            $_SESSION['success'] = "Student rewarded! Bonus session added!";
        } else {
            $_SESSION['success'] = "Student rewarded!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error rewarding student: " . $e->getMessage();
    }

    header("Location: admin_manage_sitin.php");
    exit();
}
?> 