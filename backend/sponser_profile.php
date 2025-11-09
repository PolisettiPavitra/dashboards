<?php
session_start();

// TEMPORARY: For testing without login - REMOVE THIS AFTER IMPLEMENTING LOGIN
$_SESSION['user_id'] = 1; // Change to a valid user_id from your database

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'db_config.php';

$user_id = $_SESSION['user_id'];

// Fetch user and sponsor data
$query = "SELECT u.user_id, u.username, u.email, u.phone_no, u.created_at, 
                 s.sponsor_id, s.first_name, s.last_name, s.dob, s.address, s.profile_picture
          FROM users u
          INNER JOIN sponsors s ON u.user_id = s.user_id
          WHERE u.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Sponsor not found. Please check if user_id=$user_id exists in both users and sponsors tables.");
}

$user_data = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Now include your HTML file
include $_SERVER['DOCUMENT_ROOT'] . '/dashboards/sponser_profile.html';

?>