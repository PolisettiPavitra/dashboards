<?php
/**
 * AJAX endpoint for refreshing dashboard counts
 * Returns JSON data with current statistics
 */

session_start();
require_once __DIR__ . '/../db_config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Verify user is staff
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_role FROM users WHERE user_id = ? AND user_role = 'Staff'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Staff access required']);
    exit();
}
$stmt->close();

try {
    // Get all dashboard counts
    $counts = [];
    
    // 1. Children needing sponsors
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM children WHERE status = 'Unsponsored'");
    $stmt->execute();
    $counts['children_needing'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // 2. Children having sponsors
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM children WHERE status = 'Sponsored'");
    $stmt->execute();
    $counts['children_having'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // 3. Total sponsors
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sponsors");
    $stmt->execute();
    $counts['total_sponsors'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // 4. Fraud cases
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sponsors WHERE is_flagged = 1");
    $stmt->execute();
    $counts['fraud_cases'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // 5. Total children
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM children");
    $stmt->execute();
    $counts['total_children'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // 6. Active sponsorships
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sponsorships WHERE status = 'Active'");
    $stmt->execute();
    $counts['active_sponsorships'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // 7. Sponsorship rate
    if ($counts['total_children'] > 0) {
        $counts['sponsorship_rate'] = round(($counts['children_having'] / $counts['total_children']) * 100, 1);
    } else {
        $counts['sponsorship_rate'] = 0;
    }
    
    // 8. Active sponsors
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sponsors WHERE is_flagged = 0");
    $stmt->execute();
    $counts['active_sponsors'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // 9. New unsponsored this week
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM children 
        WHERE status = 'Unsponsored' 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    $counts['new_unsponsored_week'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // 10. New sponsored this week
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM children 
        WHERE status = 'Sponsored' 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    $counts['new_sponsored_week'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // Add timestamp
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