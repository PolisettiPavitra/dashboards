<?php
session_start();

// For testing - remove this line in production
// $_SESSION['user_id'] = 1;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../db_config.php';
$user_id = $_SESSION['user_id'];

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
    die("Sponsor not found.");
}

$user_data = $result->fetch_assoc();
$sponsor_id = intval($user_data['sponsor_id']);

$stmt->close();
$conn->close();

// Set the correct path to the HTML file
$html_file = __DIR__ . '/sponser_profile.html';

// Check if file exists
if (!file_exists($html_file)) {
    die("Error: sponser_profile.html not found at: " . $html_file);
}

// Read and output the HTML file
include $html_file;
?>

<script>
    // Set sponsor_id BEFORE loading the main script
    window.SPONSOR_ID = <?php echo $sponsor_id; ?>;
    console.log('✅ SPONSOR_ID set to:', window.SPONSOR_ID);
</script>
<script src="sponser_profile.js"></script>