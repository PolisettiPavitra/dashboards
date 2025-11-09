<?php
session_start();
require_once __DIR__ . '/../db_config.php';

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
    header("Location: login.php");
    exit();
}

$staff = $result->fetch_assoc();
$stmt->close();

// Get dashboard statistics
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM children WHERE status = 'Unsponsored'");
$stmt->execute();
$children_needing_sponsors = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM children WHERE status = 'Sponsored'");
$stmt->execute();
$children_having_sponsors = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM sponsors");
$stmt->execute();
$total_sponsors = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM sponsors WHERE is_flagged = 1");
$stmt->execute();
$fraud_cases = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Get initials
$words = explode(' ', $staff['username']);
if (count($words) >= 2) {
    $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
} else {
    $initials = strtoupper(substr($staff['username'], 0, 2));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #FCF3CF 0%, #F9E79F 50%, #F4D03F 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(244, 208, 63, 0.4) 0%, rgba(249, 231, 159, 0.2) 40%, transparent 70%);
            filter: blur(80px);
            z-index: 0;
            pointer-events: none;
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0.6;
            }
            50% {
                transform: translate(-50%, -50%) scale(1.1);
                opacity: 0.8;
            }
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .banner-section {
            width: 100%;
            height: 350px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.1));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .banner-text {
            color: rgba(0, 0, 0, 0.3);
            font-size: 1.5rem;
            font-weight: 300;
            text-align: center;
        }

        .main-content {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 2rem;
            align-items: stretch;
        }

        .profile-section {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.2));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 2.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .profile-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at top right, rgba(244, 208, 63, 0.1), transparent);
            border-radius: 25px;
            pointer-events: none;
        }

        .profile-picture-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
        }

        .profile-picture {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.8);
            background: linear-gradient(135deg, rgba(244, 208, 63, 0.3), rgba(249, 231, 159, 0.3));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: rgba(0, 0, 0, 0.6);
            font-weight: 600;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .profile-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: rgba(0, 0, 0, 0.8);
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .profile-details {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .detail-field {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .detail-label {
            font-size: 0.75rem;
            color: rgba(0, 0, 0, 0.5);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .detail-value {
            background: rgba(255, 255, 255, 0.6);
            padding: 0.8rem 1.2rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            font-size: 1rem;
            color: rgba(0, 0, 0, 0.8);
            font-weight: 500;
        }

        .dashboard-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 1.5rem;
            height: 100%;
        }

        .dashboard-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.2));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at top left, rgba(244, 208, 63, 0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            border-color: rgba(243, 156, 18, 0.6);
        }

        .dashboard-card:hover::before {
            opacity: 1;
        }

        .dashboard-card.fraud-card {
            border-color: rgba(252, 31, 12, 0.3);
        }

        .dashboard-card.fraud-card:hover {
            border-color: rgba(252, 31, 12, 0.6);
        }

        .dashboard-card.fraud-card::before {
            background: radial-gradient(circle at top left, rgba(252, 31, 12, 0.1), transparent);
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: rgba(0, 0, 0, 0.8);
            margin-bottom: 0.8rem;
        }

        .card-description {
            font-size: 0.85rem;
            color: rgba(0, 0, 0, 0.6);
            line-height: 1.6;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }

        .card-stats {
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            font-size: 1.3rem;
            color: rgba(0, 0, 0, 0.8);
            font-weight: 700;
            margin-top: auto;
        }

        .dashboard-card.fraud-card .card-stats {
            color: #fc1f0c;
        }

        .card-stats-label {
            font-size: 0.75rem;
            color: rgba(0, 0, 0, 0.5);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 0.3rem;
        }

        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .profile-section {
                max-width: 500px;
                margin: 0 auto;
            }

            .dashboard-section {
                height: auto;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .banner-section {
                height: 250px;
            }

            .dashboard-section {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
            }

            .main-content {
                gap: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="banner-section">
            <div class="banner-text">Staff Dashboard</div>
        </div>

        <div class="main-content">
            <div class="profile-section">
                <div class="profile-picture-container">
                    <div class="profile-picture">
                        <span><?php echo $initials; ?></span>
                    </div>
                    <div class="profile-name">
                        <?php echo htmlspecialchars($staff['username']); ?>
                    </div>
                </div>

                <div class="profile-details">
                    <div class="detail-field">
                        <label class="detail-label">Username</label>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($staff['username']); ?>
                        </div>
                    </div>

                    <div class="detail-field">
                        <label class="detail-label">Email Address</label>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($staff['email']); ?>
                        </div>
                    </div>

                    <div class="detail-field">
                        <label class="detail-label">Phone Number</label>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($staff['phone_no']); ?>
                        </div>
                    </div>

                    <div class="detail-field">
                        <label class="detail-label">Role</label>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($staff['user_role']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="dashboard-card" onclick="openDashboard('children-needing')">
                    <h3 class="card-title">Children Needing Sponsors</h3>
                    <p class="card-description">View and manage children waiting for sponsorship opportunities</p>
                    <div class="card-stats">
                        <span class="card-stats-label">Unsponsored</span>
                        <div><?php echo $children_needing_sponsors; ?></div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="openDashboard('children-having')">
                    <h3 class="card-title">Children Having Sponsors</h3>
                    <p class="card-description">Track all children currently under active sponsorship</p>
                    <div class="card-stats">
                        <span class="card-stats-label">Sponsored</span>
                        <div><?php echo $children_having_sponsors; ?></div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="openDashboard('total-sponsors')">
                    <h3 class="card-title">Total Sponsors</h3>
                    <p class="card-description">View complete list of all registered sponsors in the system</p>
                    <div class="card-stats">
                        <span class="card-stats-label">Active Sponsors</span>
                        <div><?php echo $total_sponsors; ?></div>
                    </div>
                </div>

                <div class="dashboard-card fraud-card" onclick="openDashboard('fraud-cases')">
                    <h3 class="card-title">Fraud Cases</h3>
                    <p class="card-description">Review and manage flagged accounts requiring investigation</p>
                    <div class="card-stats">
                        <span class="card-stats-label">Flagged Accounts</span>
                        <div><?php echo $fraud_cases; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openDashboard(type) {
            // Add your navigation logic here
            console.log('Opening dashboard:', type);
            // Example: window.location.href = type + '.php';
        }
    </script>
</body>
</html>