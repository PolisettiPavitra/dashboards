<?php
/**
 * AJAX endpoint for refreshing sponsor dashboard counts
 * Returns JSON data with current statistics for a specific sponsor
 */

session_start();
require_once __DIR__ . '/../db_config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'success' => false]);
    exit();
}

// Get sponsor_id from request
$sponsor_id = isset($_GET['sponsor_id']) ? intval($_GET['sponsor_id']) : 0;

if (!$sponsor_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid sponsor ID', 'success' => false]);
    exit();
}

// Verify that the logged-in user owns this sponsor account
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT sponsor_id FROM sponsors WHERE sponsor_id = ? AND user_id = ?");
$stmt->bind_param("ii", $sponsor_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Not your account', 'success' => false]);
    exit();
}
$stmt->close();

try {
    $counts = [];
    
    // 1. Sponsored Children (active sponsorships)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM sponsorships 
        WHERE sponsor_id = ? 
        AND (end_date IS NULL OR end_date > CURDATE())
    ");
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $counts['sponsored_children'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // 2. Total Donations
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM donations 
        WHERE sponsor_id = ?
    ");
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $counts['total_donated'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // 3. New Reports (last 30 days) - Using report_date field as in sponser_profile.php
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT cr.report_id) as count 
        FROM child_reports cr
        INNER JOIN sponsorships sp ON cr.child_id = sp.child_id
        WHERE sp.sponsor_id = ? 
        AND cr.report_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND (sp.end_date IS NULL OR sp.end_date > CURDATE())
    ");
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $counts['new_reports'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // 4. Recent Timeline Events (last 30 days)
    // Check if table exists first
    $table_check = $conn->query("SHOW TABLES LIKE 'timeline_events'");
    if ($table_check && $table_check->num_rows > 0) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM timeline_events 
            WHERE sponsor_id = ? 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->bind_param("i", $sponsor_id);
        $stmt->execute();
        $counts['recent_events'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    } else {
        $counts['recent_events'] = 0;
    }
    
    // Additional useful statistics
    
    // 5. Total children ever sponsored
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM sponsorships 
        WHERE sponsor_id = ?
    ");
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $counts['total_children_ever'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // 6. Donation count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM donations 
        WHERE sponsor_id = ?
    ");
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $counts['donation_count'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // 7. Last donation date
    $stmt = $conn->prepare("
        SELECT MAX(donation_date) as last_date 
        FROM donations 
        WHERE sponsor_id = ?
    ");
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $counts['last_donation_date'] = $result['last_date'] ?? null;
    $stmt->close();
    
    // 8. Total reports received
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM child_reports 
        WHERE sponsor_id = ?
    ");
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $counts['total_reports'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // Add timestamp and success flag
    $counts['timestamp'] = date('Y-m-d H:i:s');
    $counts['success'] = true;
    
    // Return JSON response
    echo json_encode($counts);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'success' => false
    ]);
}

$conn->close();
?>