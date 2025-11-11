<?php
// Start session first
session_start();
header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../db_config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

// Check if required fields are present
if (empty($_POST['email']) || empty($_POST['password'])) {
    echo json_encode(["success" => false, "message" => "All fields are required", "attempts" => 0]);
    exit();
}

// Get form data
$user_email = trim($_POST['email']);
$password = trim($_POST['password']);

// Initialize or increment attempt counter
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}

// Clean old attempts (older than 30 minutes)
$current_time = time();
$_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function($timestamp) use ($current_time) {
    return ($current_time - $timestamp) < 1800; // 30 minutes
});

try {
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT user_id, email, password_hash, user_role FROM users WHERE email = ? LIMIT 1");
    
    if (!$stmt) {
        error_log("Database prepare failed: " . $conn->error);
        echo json_encode(["success" => false, "message" => "Database error", "attempts" => 0]);
        exit();
    }
    
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $row['password_hash'])) {
            // Login successful - Clear attempts and set session variables
            unset($_SESSION['login_attempts']);
            
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['user_role'];
            $_SESSION['logged_in'] = true;

            // Close database connections
            $stmt->close();
            $conn->close();

            // Determine redirect based on role
            $redirect_url = '';
            switch ($row['user_role']) {
                case 'Sponsor':
                    $redirect_url = '../sponser/sponser_main_page.php';
                    break;
                case 'Staff':
                    $redirect_url = '../staff/staff_home_old.php';
                    break;
                case 'Owner':
                case 'Admin':
                    $redirect_url = '../owner/owner_home.php';
                    break;
                default:
                    $redirect_url = '../dashboard.html';
            }

            // Return success with redirect URL
            echo json_encode([
                "success" => true,
                "message" => "Login successful",
                "redirect" => $redirect_url
            ]);
            exit();
            
        } else {
            // Invalid password - Track attempt
            $_SESSION['login_attempts'][] = time();
            $attempt_count = count($_SESSION['login_attempts']);
            
            $stmt->close();
            $conn->close();
            
            // Return error with attempt count
            echo json_encode([
                "success" => false,
                "message" => "Invalid email or password",
                "attempts" => $attempt_count
            ]);
            exit();
        }
        
    } else {
        // User not found - Track attempt
        $_SESSION['login_attempts'][] = time();
        $attempt_count = count($_SESSION['login_attempts']);
        
        $stmt->close();
        $conn->close();
        
        echo json_encode([
            "success" => false,
            "message" => "Invalid email or password",
            "attempts" => $attempt_count
        ]);
        exit();
    }
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
    
    echo json_encode([
        "success" => false,
        "message" => "An error occurred. Please try again.",
        "attempts" => 0
    ]);
    exit();
}
?>