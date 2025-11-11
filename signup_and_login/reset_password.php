<?php
// CRITICAL: No whitespace or output before this line!
ob_start();
session_start();

// Set JSON header immediately
header('Content-Type: application/json');

// Function to send clean JSON response
function sendJsonResponse($success, $message, $additionalData = []) {
    ob_clean(); // Clear any buffer
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $additionalData));
    exit();
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

// Get form data
$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

// Validate inputs
if (empty($token)) {
    sendJsonResponse(false, 'Reset token is missing');
}

if (empty($password)) {
    sendJsonResponse(false, 'Password is required');
}

if (empty($confirmPassword)) {
    sendJsonResponse(false, 'Please confirm your password');
}

if ($password !== $confirmPassword) {
    sendJsonResponse(false, 'Passwords do not match');
}

if (strlen($password) < 6) {
    sendJsonResponse(false, 'Password must be at least 6 characters');
}

try {
    // Include database connection
    require_once __DIR__ . '/../db_config.php';
    
    if (!isset($conn)) {
        sendJsonResponse(false, 'Database connection failed');
    }
    
    if ($conn->connect_error) {
        sendJsonResponse(false, 'Database connection error: ' . $conn->connect_error);
    }

    // Hash the token to compare with database
    $tokenHash = hash('sha256', $token);
    $currentTime = date('Y-m-d H:i:s');

    // Find user with valid token
    $stmt = $conn->prepare("
        SELECT user_id, email, reset_token_expires_at 
        FROM users 
        WHERE reset_token_hash = ? 
        LIMIT 1
    ");
    
    if (!$stmt) {
        sendJsonResponse(false, 'Database query preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $tokenHash);
    
    if (!$stmt->execute()) {
        sendJsonResponse(false, 'Database query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendJsonResponse(false, 'Invalid or expired reset token. Please request a new password reset.');
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Check if token is expired
    if ($user['reset_token_expires_at'] === null) {
        sendJsonResponse(false, 'Reset token has expired. Please request a new one.');
    }

    if ($currentTime > $user['reset_token_expires_at']) {
        sendJsonResponse(false, 'Reset token has expired. Please request a new password reset.');
    }

    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Update password and clear reset token
    $updateStmt = $conn->prepare("
        UPDATE users 
        SET password_hash = ?,
            reset_token_hash = NULL,
            reset_token_expires_at = NULL
        WHERE user_id = ?
    ");
    
    if (!$updateStmt) {
        sendJsonResponse(false, 'Failed to prepare password update: ' . $conn->error);
    }
    
    $updateStmt->bind_param("si", $hashedPassword, $user['user_id']);
    
    if (!$updateStmt->execute()) {
        sendJsonResponse(false, 'Failed to update password: ' . $updateStmt->error);
    }
    
    $updateStmt->close();

    // Success!
    sendJsonResponse(true, 'Password reset successful! You can now login with your new password.');

} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    sendJsonResponse(false, 'An error occurred: ' . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>