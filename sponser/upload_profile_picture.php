<?php
session_start();

// Set header for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Include database connection
require_once __DIR__ . '/../db_config.php';

// Check if file was uploaded
if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

$file = $_FILES['profile_picture'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error occurred']);
    exit();
}

// Validate file type
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
$file_type = mime_content_type($file['tmp_name']);

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, and PNG are allowed']);
    exit();
}

// Validate file size (5MB max)
$max_size = 5 * 1024 * 1024; // 5MB in bytes
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
    exit();
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get sponsor_id from database
$query = "SELECT sponsor_id FROM sponsors WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Sponsor not found']);
    exit();
}

$sponsor_data = $result->fetch_assoc();
$sponsor_id = $sponsor_data['sponsor_id'];

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/profile_pictures/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Get old profile picture path to delete it later
$old_picture_query = "SELECT profile_picture FROM sponsors WHERE sponsor_id = ?";
$stmt = $conn->prepare($old_picture_query);
$stmt->bind_param("i", $sponsor_id);
$stmt->execute();
$old_picture_result = $stmt->get_result();
$old_picture_data = $old_picture_result->fetch_assoc();
$old_picture_path = $old_picture_data['profile_picture'];

// Generate unique filename
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = 'sponsor_' . $sponsor_id . '_' . time() . '.' . $file_extension;
$destination = $upload_dir . $new_filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $destination)) {
    // Update database with new image path
    $update_query = "UPDATE sponsors SET profile_picture = ? WHERE sponsor_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $destination, $sponsor_id);
    
    if ($stmt->execute()) {
        // Delete old profile picture if it exists and is different from the new one
        if (!empty($old_picture_path) && file_exists($old_picture_path) && $old_picture_path !== $destination) {
            unlink($old_picture_path);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Profile picture updated successfully',
            'image_path' => $destination
        ]);
    } else {
        // If database update fails, delete the uploaded file
        unlink($destination);
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
}

$conn->close();
?>