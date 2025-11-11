<?php
// CRITICAL: No output before this point - not even whitespace!
ob_start();
session_start();

// Set JSON header immediately
header('Content-Type: application/json');

// Function to send clean JSON response
function sendJsonResponse($success, $message, $additionalData = []) {
    ob_clean(); // Clear any accidental output
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
$otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Validate inputs
if (empty($otp)) {
    sendJsonResponse(false, 'Please enter the verification code');
}

if (empty($email)) {
    sendJsonResponse(false, 'Email is required');
}

// Validate OTP format (6 digits)
if (!preg_match('/^\d{6}$/', $otp)) {
    sendJsonResponse(false, 'Invalid verification code format');
}

try {
    // Include database connection
    require_once __DIR__ . '/../db_config.php';
    
    if (!isset($conn) || $conn->connect_error) {
        sendJsonResponse(false, 'Database connection failed');
    }

    // Get user with matching email and OTP
    $stmt = $conn->prepare("
        SELECT user_id, otp_code, otp_expiry 
        FROM users 
        WHERE email = ? 
        LIMIT 1
    ");
    
    if (!$stmt) {
        sendJsonResponse(false, 'Database query error');
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendJsonResponse(false, 'Invalid email or verification code');
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Check if OTP matches
    if ($user['otp_code'] !== $otp) {
        sendJsonResponse(false, 'Invalid verification code');
    }

    // Check if OTP has expired
    $currentTime = date('Y-m-d H:i:s');
    if ($currentTime > $user['otp_expiry']) {
        sendJsonResponse(false, 'Verification code has expired. Please request a new one.');
    }

    // Generate password reset token
    $resetToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $resetToken);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Update user with reset token and clear OTP
    $updateStmt = $conn->prepare("
        UPDATE users 
        SET reset_token_hash = ?,
            reset_token_expires_at = ?,
            otp_code = NULL,
            otp_expiry = NULL
        WHERE user_id = ?
    ");
    
    if (!$updateStmt) {
        sendJsonResponse(false, 'Failed to generate reset token');
    }
    
    $updateStmt->bind_param("ssi", $tokenHash, $expiresAt, $user['user_id']);
    
    if (!$updateStmt->execute()) {
        sendJsonResponse(false, 'Failed to process verification');
    }
    
    $updateStmt->close();

    // Return success with token
    sendJsonResponse(true, 'Verification successful!', [
        'token' => $resetToken
    ]);

} catch (Exception $e) {
    error_log("OTP verification error: " . $e->getMessage());
    sendJsonResponse(false, 'An error occurred. Please try again.');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>