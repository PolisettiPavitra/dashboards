<?php
// ----------------------
// CORS HEADERS - MUST BE FIRST!
// ----------------------
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ----------------------
// CONFIGURATION
// ----------------------
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

session_start();

// FIXED: Added missing slash before 'database'
require_once __DIR__ . '/../db_config.php';

// ----------------------
// ONLY ALLOW POST METHOD
// ----------------------
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

// ----------------------
// RETRIEVE FORM DATA
// ----------------------
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$terms = $_POST['terms'] ?? '';

$phone = trim($_POST['phone'] ?? '');
$dateOfBirth = trim($_POST['dateOfBirth'] ?? '');
$address = trim($_POST['address'] ?? '');

$errors = [];

// ----------------------
// VALIDATION
// ----------------------
if (empty($firstName) || !preg_match("/^[a-zA-Z-' ]*$/", $firstName)) {
    $errors['firstName'] = 'Valid first name is required.';
}
if (empty($lastName) || !preg_match("/^[a-zA-Z-' ]*$/", $lastName)) {
    $errors['lastName'] = 'Valid last name is required.';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Valid email is required.';
}
if (empty($password)) {
    $errors['password'] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
}
if ($password !== $confirmPassword) {
    $errors['confirmPassword'] = 'Passwords do not match.';
}
if ($terms !== 'on') {
    $errors['terms'] = 'You must agree to the Terms of Service.';
}

if (!empty($dateOfBirth)) {
    $dob = DateTime::createFromFormat('Y-m-d', $dateOfBirth);
    if (!$dob) {
        $errors['dateOfBirth'] = 'Invalid date format.';
    } else {
        $age = (new DateTime())->diff($dob)->y;
        if ($age < 18) {
            $errors['dateOfBirth'] = 'You must be at least 18 years old.';
        }
    }
}

// ----------------------
// RETURN ERRORS IF ANY
// ----------------------
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(["success" => false, "errors" => $errors]);
    exit;
}

// ----------------------
// CHECK EMAIL EXISTENCE
// ----------------------
$sql_check = "SELECT user_id FROM users WHERE email = ?";
$stmt_check = $conn->prepare($sql_check);

if (!$stmt_check) {
    http_response_code(500);
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(["success" => false, "message" => "Database error occurred."]);
    exit;
}

$stmt_check->bind_param("s", $email);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "errors" => ["email" => "Email already registered."]]);
    $stmt_check->close();
    exit;
}
$stmt_check->close();

// ----------------------
// INSERT INTO DATABASE
// ----------------------
$conn->begin_transaction();

try {
    // IMPORTANT: Hash the password for security!
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // FIXED: Added phone_no to users table insert
    $sql_user = "INSERT INTO users (password_hash, email, phone_no, must_change_password, user_role)
                 VALUES (?, ?, ?, 0, 'Sponsor')";

    $stmt_user = $conn->prepare($sql_user);
    if (!$stmt_user) {
        throw new Exception("Error preparing user insert: " . $conn->error);
    }

    $stmt_user->bind_param("sss", $password_hash, $email, $phone);
    
    if (!$stmt_user->execute()) {
        throw new Exception("Error executing user insert: " . $stmt_user->error);
    }
    
    $new_user_id = $conn->insert_id;
    $stmt_user->close();

    // FIXED: Removed phone - sponsors table doesn't have a phone column
    // Phone is stored in users.phone_no instead
    $sql_sponsor = "INSERT INTO sponsors (user_id, address, first_name, last_name, dob)
                    VALUES (?, ?, ?, ?, ?)";

    $stmt_sponsor = $conn->prepare($sql_sponsor);
    if (!$stmt_sponsor) {
        throw new Exception("Error preparing sponsor insert: " . $conn->error);
    }

    $stmt_sponsor->bind_param(
        "issss",
        $new_user_id,
        $address,
        $firstName,
        $lastName,
        $dateOfBirth
    );
    
    if (!$stmt_sponsor->execute()) {
        throw new Exception("Error executing sponsor insert: " . $stmt_sponsor->error);
    }
    
    $stmt_sponsor->close();

    $conn->commit();

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Account created successfully! Redirecting to login...",
        "user_id" => $new_user_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Registration error: " . $e->getMessage());
    error_log("SQL Error: " . $conn->error);
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Registration failed: " . $e->getMessage(),
        "debug" => [
            "error" => $e->getMessage(),
            "sql_error" => $conn->error
        ]
    ]);
}

// ----------------------
// CLOSE CONNECTION
// ----------------------
$conn->close();
?>