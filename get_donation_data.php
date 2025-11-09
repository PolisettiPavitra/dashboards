<?php
header('Content-Type: application/json');
session_start();

// Test data (remove when login is implemented)
$_SESSION['user_id'] = 1;

require_once 'db_config.php';

// Get sponsor_id from request or session
$sponsor_id = isset($_GET['sponsor_id']) ? intval($_GET['sponsor_id']) : 14;

try {
    // Get total donation stats
    $stats_query = "SELECT 
                        COUNT(*) as total_count,
                        COALESCE(SUM(amount), 0) as total_amount
                    FROM donations 
                    WHERE sponsor_id = ?";
    
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $stats_result = $stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    $stmt->close();

    // Get detailed donation list with child names
    $donations_query = "SELECT 
                            d.donation_id,
                            d.amount,
                            d.donation_date,
                            d.payment_method,
                            CONCAT(c.first_name, ' ', c.last_name) as child_name,
                            c.child_id
                        FROM donations d
                        INNER JOIN children c ON d.child_id = c.child_id
                        WHERE d.sponsor_id = ?
                        ORDER BY d.donation_date DESC";
    
    $stmt = $conn->prepare($donations_query);
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $donations_result = $stmt->get_result();
    
    $donations = [];
    while ($row = $donations_result->fetch_assoc()) {
        $donations[] = [
            'donation_id' => $row['donation_id'],
            'child_id' => $row['child_id'],
            'child_name' => $row['child_name'],
            'amount' => $row['amount'],
            'donation_date' => $row['donation_date'],
            'payment_method' => $row['payment_method']
        ];
    }
    $stmt->close();

    // Return success response
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_amount' => floatval($stats['total_amount']),
            'total_count' => intval($stats['total_count'])
        ],
        'donations' => $donations
    ]);

} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching donation data: ' . $e->getMessage(),
        'stats' => [
            'total_amount' => 0,
            'total_count' => 0
        ],
        'donations' => []
    ]);
}

$conn->close();
?>