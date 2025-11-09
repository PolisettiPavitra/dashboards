<?php
session_start();
require_once 'db_config.php';

// Test cases - Remove these in production
$_SESSION['user_id'] = 4;

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get staff information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, phone_no, user_role FROM users WHERE user_id = ? AND user_role = 'Staff'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User is not staff or doesn't exist
    header("Location: login.php");
    exit();
}

$staff = $result->fetch_assoc();
$stmt->close();

// Get dashboard statistics
// 1. Children needing sponsors
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM children WHERE status = 'Unsponsored'");
$stmt->execute();
$children_needing_sponsors = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// 2. Children having sponsors
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM children WHERE status = 'Sponsored'");
$stmt->execute();
$children_having_sponsors = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// 3. Total sponsors
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM sponsors");
$stmt->execute();
$total_sponsors = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// 4. Fraud cases
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM sponsors WHERE is_flagged = 1");
$stmt->execute();
$fraud_cases = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Home - Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ffffff;
            color: #211e27;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .profile-section {
            background-color: #e9e9f1;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .profile-icon {
            width: 100px;
            height: 100px;
            background-color: #374ace;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 32px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .profile-info {
            flex: 1;
        }

        .profile-info h2 {
            color: #211e27;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .profile-info p {
            color: #211e27;
            margin: 5px 0;
            font-size: 16px;
        }

        .profile-info strong {
            font-weight: 600;
        }

        .dashboard-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .dashboard-title h1 {
            color: #1128ce;
            font-size: 36px;
            margin-bottom: 10px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            margin-bottom: 50px;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .dashboard-card {
            background-color: #e9e9f1;
            border-radius: 12px;
            padding: 50px 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            min-height: 250px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .dashboard-card h3 {
            color: #1128ce;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .dashboard-card .count {
            color: #211e27;
            font-size: 48px;
            font-weight: bold;
            margin: 10px 0;
        }

        .dashboard-card.fraud-card .count {
            color: #fc1f0c;
        }

        .dashboard-card p {
            color: #211e27;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-section">
            <div class="profile-icon">
                <?php 
                // Get initials from username
                $words = explode(' ', $staff['username']);
                if (count($words) >= 2) {
                    echo strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                } else {
                    echo strtoupper(substr($staff['username'], 0, 2));
                }
                ?>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($staff['username']); ?></h2>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($staff['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($staff['phone_no']); ?></p>
            </div>
        </div>

        <div class="dashboard-title">
            <h1>Dashboard</h1>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card" data-card="children-needing" onclick="openDashboard('children-needing')">
                <h3>Child Needing Sponsors</h3>
                <div class="count" id="children-needing-count"><?php echo $children_needing_sponsors; ?></div>
                <p>Click to view details</p>
            </div>

            <div class="dashboard-card" data-card="children-having" onclick="openDashboard('children-having')">
                <h3>Child Having Sponsors</h3>
                <div class="count" id="children-having-count"><?php echo $children_having_sponsors; ?></div>
                <p>Click to view details</p>
            </div>

            <div class="dashboard-card" data-card="total-sponsors" onclick="openDashboard('total-sponsors')">
                <h3>Total Sponsors</h3>
                <div class="count" id="total-sponsors-count"><?php echo $total_sponsors; ?></div>
                <p>Click to view details</p>
            </div>

            <div class="dashboard-card fraud-card" data-card="fraud-cases" onclick="openDashboard('fraud-cases')">
                <h3>Fraud Cases</h3>
                <div class="count" id="fraud-cases-count"><?php echo $fraud_cases; ?></div>
                <p>Click to view details</p>
            </div>
        </div>
    </div>

    <script src="staff_home.js"></script>
</body>
</html>