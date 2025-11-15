<?php
// Enable error reporting to see what's wrong
ini_set('display_errors', 0); // Don't display errors in output
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Start output buffering to catch any accidental output
ob_start();

session_start();
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendResponse($success, $message, $data = []) {
    ob_clean(); // Clear any output buffer
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $data));
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, "Method not allowed");
}

// Check if files exist before requiring them
$dbConfigPath = __DIR__ . '/../db_config.php';
$emailConfigPath = __DIR__ . '/email_config.php';
$vendorPath = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($dbConfigPath)) {
    sendResponse(false, "Database configuration file not found");
}

if (!file_exists($emailConfigPath)) {
    sendResponse(false, "Email configuration file not found");
}

if (!file_exists($vendorPath)) {
    sendResponse(false, "PHPMailer not installed. Run: composer require phpmailer/phpmailer");
}

// Include files
require_once $dbConfigPath;
require_once $emailConfigPath;
require_once $vendorPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get email
$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    sendResponse(false, "Email is required");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, "Invalid email format");
}

try {
    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        sendResponse(false, "Database connection failed");
    }

    // Check if email exists in database
    $stmt = $conn->prepare("SELECT user_id, email FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        sendResponse(false, "Database query error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Don't reveal if email doesn't exist (security best practice)
        sendResponse(true, "If this email exists, a verification code has been sent.");
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(0, 999999));
    
    // Set expiry time (10 minutes from now)
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Update database with OTP
    $updateStmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE email = ?");
    if (!$updateStmt) {
        sendResponse(false, "Database update error: " . $conn->error);
    }
    
    $updateStmt->bind_param("sss", $otp, $expiry, $email);
    
    if (!$updateStmt->execute()) {
        sendResponse(false, "Failed to save verification code");
    }
    $updateStmt->close();

    // Send email using PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Verification Code';
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2 style='color: #ffc107;'>Password Reset Request</h2>
                <p>You requested to reset your password. Use the verification code below:</p>
                <div style='background: #f5f5f5; padding: 20px; text-align: center; margin: 20px 0;'>
                    <h1 style='color: #1d1d1f; letter-spacing: 5px; margin: 0;'>{$otp}</h1>
                </div>
                <p>This code will expire in 10 minutes.</p>
                <p>If you didn't request this, please ignore this email.</p>
                <hr style='border: 1px solid #eee; margin: 20px 0;'>
                <p style='color: #999; font-size: 12px;'>Sponsor a Child Organization</p>
            </body>
            </html>
        ";
        $mail->AltBody = "Your verification code is: $otp\n\nThis code will expire in 10 minutes.";

        $mail->send();

        sendResponse(true, "Verification code sent to your email. Please check your inbox.");

    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        sendResponse(false, "Failed to send email. Please check email configuration.");
    }

} catch (Exception $e) {
    error_log("OTP generation error: " . $e->getMessage());
    sendResponse(false, "An error occurred: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>