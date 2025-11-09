<?php
// TEMPORARY: For testing without login - REMOVE THIS AFTER IMPLEMENTING LOGIN
session_start();
$_SESSION['user_id'] = 1;

// Get sponsor_id from query parameter
$sponsor_id = isset($_GET['sponsor_id']) ? intval($_GET['sponsor_id']) : 0;

if ($sponsor_id === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid sponsor ID'
    ]);
    exit;
}

// Set headers for JSON response
header('Content-Type: application/json');

// Include database connection
require_once 'db_config.php';

try {
    // Query to get all sponsored children for this sponsor
    $query = "
        SELECT 
            c.child_id,
            c.first_name,
            c.last_name,
            c.dob,
            c.gender,
            c.status,
            s.start_date,
            s.end_date,
            TIMESTAMPDIFF(YEAR, c.dob, CURDATE()) as age
        FROM children c
        INNER JOIN sponsorships s ON c.child_id = s.child_id
        WHERE s.sponsor_id = ?
        AND (s.end_date IS NULL OR s.end_date > CURDATE())
        ORDER BY s.start_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Bind parameter - 'i' means integer
    $stmt->bind_param('i', $sponsor_id);
    
    // Execute query
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // Get results
    $result = $stmt->get_result();
    $children = $result->fetch_all(MYSQLI_ASSOC);
    
    // Calculate statistics
    $total_count = count($children);
    $active_count = 0;
    
    foreach ($children as &$child) {
        // Format dates
        $child['dob_formatted'] = date('F j, Y', strtotime($child['dob']));
        $child['start_date_formatted'] = date('F j, Y', strtotime($child['start_date']));
        
        // Calculate sponsorship duration
        $start = new DateTime($child['start_date']);
        $now = new DateTime();
        $interval = $start->diff($now);
        
        $years = $interval->y;
        $months = $interval->m;
        
        if ($years > 0) {
            $child['sponsorship_duration'] = $years . ' year' . ($years > 1 ? 's' : '');
            if ($months > 0) {
                $child['sponsorship_duration'] .= ', ' . $months . ' month' . ($months > 1 ? 's' : '');
            }
        } else {
            $child['sponsorship_duration'] = $months . ' month' . ($months > 1 ? 's' : '');
        }
        
        // Count active sponsorships
        if ($child['status'] === 'Sponsored' && ($child['end_date'] === null || strtotime($child['end_date']) > time())) {
            $active_count++;
        }
        
        // Generate initials for profile picture placeholder
        $child['initials'] = strtoupper(substr($child['first_name'], 0, 1) . substr($child['last_name'], 0, 1));
    }
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'data' => $children,
        'stats' => [
            'total_count' => $total_count,
            'active_count' => $active_count
        ]
    ], JSON_PRETTY_PRINT);
    
    $stmt->close();
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

// Close connection
$conn->close();
?>