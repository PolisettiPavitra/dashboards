<?php
session_start();
require_once __DIR__ . '/../db_config.php';

// Test cases - Remove these in production
$_SESSION['user_id'] = 1;
$sponsor_id = 14;

// Get sponsor information
$sponsor_query = "SELECT s.*, u.username, u.email 
                  FROM sponsors s 
                  JOIN users u ON s.user_id = u.user_id 
                  WHERE s.sponsor_id = ?";
$stmt = $conn->prepare($sponsor_query);
$stmt->bind_param("i", $sponsor_id);
$stmt->execute();
$sponsor_result = $stmt->get_result();
$sponsor_data = $sponsor_result->fetch_assoc();

// Achievement Statistics

// 1. Total children sponsored by this sponsor
$total_children_query = "SELECT COUNT(DISTINCT child_id) as total 
                         FROM sponsorships 
                         WHERE sponsor_id = ? AND (end_date IS NULL OR end_date > CURDATE())";
$stmt = $conn->prepare($total_children_query);
$stmt->bind_param("i", $sponsor_id);
$stmt->execute();
$total_children = $stmt->get_result()->fetch_assoc()['total'];

// 2. Total donations made
$total_donations_query = "SELECT COALESCE(SUM(amount), 0) as total 
                          FROM donations 
                          WHERE sponsor_id = ?";
$stmt = $conn->prepare($total_donations_query);
$stmt->bind_param("i", $sponsor_id);
$stmt->execute();
$total_donations = $stmt->get_result()->fetch_assoc()['total'];

// 3. Years of support (from first sponsorship)
$years_support_query = "SELECT MIN(start_date) as first_sponsorship 
                        FROM sponsorships 
                        WHERE sponsor_id = ?";
$stmt = $conn->prepare($years_support_query);
$stmt->bind_param("i", $sponsor_id);
$stmt->execute();
$first_sponsorship_result = $stmt->get_result()->fetch_assoc();
$years_of_support = 0;
if ($first_sponsorship_result['first_sponsorship']) {
    $first_date = new DateTime($first_sponsorship_result['first_sponsorship']);
    $current_date = new DateTime();
    $interval = $first_date->diff($current_date);
    $years_of_support = $interval->y;
}

// 4. Active sponsorships count
$active_sponsorships_query = "SELECT COUNT(*) as active 
                              FROM sponsorships 
                              WHERE sponsor_id = ? AND (end_date IS NULL OR end_date > CURDATE())";
$stmt = $conn->prepare($active_sponsorships_query);
$stmt->bind_param("i", $sponsor_id);
$stmt->execute();
$active_sponsorships = $stmt->get_result()->fetch_assoc()['active'];

// Get children NOT sponsored by this sponsor
$children_query = "SELECT c.* 
                   FROM children c 
                   WHERE c.child_id NOT IN (
                       SELECT child_id 
                       FROM sponsorships 
                       WHERE sponsor_id = ? AND (end_date IS NULL OR end_date > CURDATE())
                   )
                   ORDER BY c.status DESC, c.first_name ASC";
$stmt = $conn->prepare($children_query);
$stmt->bind_param("i", $sponsor_id);
$stmt->execute();
$children_result = $stmt->get_result();

// Prepare data for JSON
$children_data = [];
while ($child = $children_result->fetch_assoc()) {
    // Calculate age
    $dob = new DateTime($child['dob']);
    $today = new DateTime();
    $age = $dob->diff($today)->y;
    
    $child['age'] = $age;
    $children_data[] = $child;
}

// Prepare statistics data
$stats_data = [
    'total_children' => $total_children,
    'total_donations' => number_format($total_donations, 2),
    'years_of_support' => $years_of_support,
    'active_sponsorships' => $active_sponsorships
];

// Convert to JSON for JavaScript
$stats_json = json_encode($stats_data);
$children_json = json_encode($children_data);
$sponsor_json = json_encode($sponsor_data);

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sponsor Home - Dashboard</title>
    <style>
        /* Color Scheme */
        :root {
            --primary-blue: #1128ce;
            --secondary-blue: #2b3ed5;
            --accent-blue: #374ace;
            --primary-red: #fc1f0c;
            --background-white: #e9e9f1;
            --card-grey: #c4a391;
            --text-dark: #211e27;
            --purple-accent: #7462aa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--background-white);
            color: var(--text-dark);
            padding: 20px;
            line-height: 1.6;
        }

        /* Navigation Buttons */
        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-bottom: 50px;
            padding: 30px 0;
        }

        .nav-btn {
            background-color: var(--primary-red);
            color: white;
            border: none;
            padding: 18px 50px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            letter-spacing: 0.5px;
        }

        .nav-btn:hover {
            background-color: #d91a0a;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
        }

        .nav-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
        }

        /* Achievement Section */
        .achievements-section {
            margin-bottom: 40px;
        }

        .achievements-section h2 {
            color: var(--primary-blue);
            font-size: 28px;
            margin-bottom: 20px;
            border-bottom: 3px solid var(--primary-blue);
            padding-bottom: 10px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .stat-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin-top: 5px;
        }

        /* Children Section */
        .children-section {
            margin-bottom: 40px;
        }

        .children-section h2 {
            color: var(--primary-blue);
            font-size: 28px;
            margin-bottom: 20px;
            border-bottom: 3px solid var(--primary-blue);
            padding-bottom: 10px;
        }

        .children-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .child-card {
            background-color: var(--card-grey);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .child-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .child-card:hover::before {
            left: 100%;
        }

        .child-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }

        .child-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .child-name {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-dark);
        }

        .child-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-sponsored {
            background-color: var(--accent-blue);
            color: white;
        }

        .status-unsponsored {
            background-color: var(--primary-red);
            color: white;
        }

        .child-info {
            margin-top: 10px;
        }

        .child-info p {
            margin: 8px 0;
            font-size: 14px;
            color: var(--text-dark);
        }

        .child-info strong {
            color: var(--primary-blue);
        }

        .view-profile-btn {
            width: 100%;
            margin-top: 15px;
            padding: 10px;
            background-color: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .view-profile-btn:hover {
            background-color: var(--secondary-blue);
        }

        /* Image Placeholder Section */
        .image-placeholder {
            margin-top: 40px;
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .image-placeholder img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .image-placeholder p {
            color: var(--text-dark);
            font-size: 16px;
            font-style: italic;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-dark);
            font-size: 18px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-buttons {
                flex-direction: column;
            }

            .nav-btn {
                width: 100%;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .children-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .achievements-section h2,
            .children-section h2 {
                font-size: 22px;
            }

            .stat-card .stat-value {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Buttons -->
    <div class="nav-buttons">
        <button class="nav-btn" onclick="window.location.href='sponser_profile.php'">My Profile</button>
        <button class="nav-btn" onclick="window.location.href='sponsor_home_cal.php'">My Home</button>
    </div>

    <!-- Achievement Statistics Section -->
    <div class="achievements-section">
        <h2>Your Impact</h2>
        <div class="stats-container" id="statsContainer">
            <!-- Stats will be populated by JavaScript -->
        </div>
    </div>

    <!-- Available Children Section -->
    <div class="children-section">
        <h2>Children Available for Sponsorship</h2>
        <div class="children-grid" id="childrenGrid">
            <!-- Children cards will be populated by JavaScript -->
        </div>
    </div>

    <!-- Placeholder Image Section -->
    <div class="image-placeholder">
        <img src="placeholder-image.jpg" alt="Placeholder Image" id="customImage">
        <p>Your custom image will be displayed here</p>
    </div>

    <!-- CRITICAL FIX: Embed PHP data as JavaScript variables -->
    <script>
        // Pass PHP data to JavaScript
        const statsData = <?php echo $stats_json; ?>;
        const childrenData = <?php echo $children_json; ?>;
        const sponsorData = <?php echo $sponsor_json; ?>;
    </script>

    <!-- Include JavaScript file -->
    <script src="sponsor_home.js"></script>
</body>
</html>