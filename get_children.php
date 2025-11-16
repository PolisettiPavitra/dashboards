<?php
// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

header('Content-Type: application/json');

require_once __DIR__ . '/db_config.php';

if (!isset($conn)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Get parameters
    $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $age_range = isset($_GET['age_range']) ? $_GET['age_range'] : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

    // Items per page
    $limit = 9;
    $offset = $page * $limit;

    // Build the WHERE clause - show ALL children
    $where_clauses = [];
    $params = [];
    $types = '';

    // Search by name - FIXED: Now searches both first and last name properly
    if (!empty($search)) {
        $where_clauses[] = "(LOWER(first_name) LIKE LOWER(?) OR LOWER(last_name) LIKE LOWER(?) OR LOWER(CONCAT(first_name, ' ', last_name)) LIKE LOWER(?))";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'sss';
    }

    // Age range filter - FIXED: Proper date calculation
    if (!empty($age_range)) {
        $age_parts = explode('-', $age_range);
        if (count($age_parts) === 2) {
            $min_age = intval($age_parts[0]);
            $max_age = intval($age_parts[1]);
            
            // Calculate birth date ranges
            $today = new DateTime();
            
            // For max age (older limit) - born on or after this date
            $max_birth_date = (clone $today)->modify("-" . ($max_age + 1) . " years")->modify("+1 day")->format('Y-m-d');
            
            // For min age (younger limit) - born on or before this date
            $min_birth_date = (clone $today)->modify("-{$min_age} years")->format('Y-m-d');
            
            $where_clauses[] = "(dob >= ? AND dob <= ?)";
            $params[] = $max_birth_date;
            $params[] = $min_birth_date;
            $types .= 'ss';
        }
    }

    // Build ORDER BY clause
    $order_by = "first_name ASC";
    switch ($sort) {
        case 'name_asc':
            $order_by = "first_name ASC, last_name ASC";
            break;
        case 'name_desc':
            $order_by = "first_name DESC, last_name DESC";
            break;
        case 'age_asc':
            $order_by = "dob DESC"; // Younger first (more recent birth dates)
            break;
        case 'age_desc':
            $order_by = "dob ASC"; // Older first (older birth dates)
            break;
        case 'newest':
            $order_by = "created_at DESC";
            break;
    }

    // Build the query
    $where_sql = count($where_clauses) > 0 ? implode(' AND ', $where_clauses) : '1=1';
    $query = "SELECT child_id, first_name, last_name, dob, gender, profile_picture, created_at 
              FROM children 
              WHERE {$where_sql} 
              ORDER BY {$order_by} 
              LIMIT ? OFFSET ?";

    // Prepare statement
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $conn->error);
    }

    // Add limit and offset to params
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    // Bind parameters
    if (!empty($types)) {
        $bind_names = [$types];
        for ($i = 0; $i < count($params); $i++) {
            $bind_names[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    // Execute query
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    $children = [];
    while ($row = $result->fetch_assoc()) {
        // Handle profile picture - if it doesn't exist or path is wrong, just send empty string
        $profile_pic = '';
        if (!empty($row['profile_picture'])) {
            $profile_pic = $row['profile_picture'];
        }

        $children[] = [
            'child_id' => (int)$row['child_id'],
            'first_name' => htmlspecialchars($row['first_name']),
            'last_name' => htmlspecialchars($row['last_name']),
            'dob' => $row['dob'],
            'gender' => $row['gender'],
            'profile_picture' => $profile_pic,
            'created_at' => $row['created_at']
        ];
    }

    $stmt->close();
    $conn->close();

    // Return response
    echo json_encode([
        'success' => true,
        'children' => $children,
        'page' => $page,
        'count' => count($children),
        'has_more' => count($children) === $limit
    ]);

} catch (Exception $e) {
    // Log the error
    error_log('get_children.php error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching children',
        'error' => $e->getMessage()
    ]);
}
?>