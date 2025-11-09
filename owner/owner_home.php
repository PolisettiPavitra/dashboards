<?php
session_start();
require_once __DIR__ . '/../db_config.php';

$_SESSION['user_id'] = 5;

if(!isset($_SESSION['user_id']))
{
    header("Location: login.php");
    exit();
}

//get owner info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, phone_no, user_role FROM users WHERE user_id = ? AND user_role = 'Owner'");
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

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE user_role = 'Staff'");
$stmt->execute();
$Staff_Total = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM donations");
$stmt->execute();
$total_donated = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
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

        .profile-header {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.2));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 2rem 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 2rem;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at top left, rgba(244, 208, 63, 0.15), transparent);
            pointer-events: none;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.8);
            background: linear-gradient(135deg, rgba(244, 208, 63, 0.4), rgba(249, 231, 159, 0.4));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: rgba(0, 0, 0, 0.6);
            font-weight: 700;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }

        .profile-content {
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.85);
            margin-bottom: 0.5rem;
        }

        .profile-info-row {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            color: rgba(0, 0, 0, 0.7);
        }

        .info-label {
            font-weight: 600;
            color: rgba(0, 0, 0, 0.5);
        }

        .quote-section {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.15));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 2rem 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .quote-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, rgba(244, 208, 63, 0.1), transparent);
            pointer-events: none;
        }

        .quote-text {
            font-size: 1.3rem;
            font-weight: 500;
            color: rgba(0, 0, 0, 0.75);
            font-style: italic;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .quote-text::before {
            content: '"';
            font-size: 3rem;
            color: rgba(244, 208, 63, 0.4);
            position: absolute;
            top: -1rem;
            left: -1rem;
            font-family: Georgia, serif;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
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
            min-height: 200px;
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
            border: 2px solid rgba(252, 31, 12, 0.3);
        }

        .dashboard-card.fraud-card:hover {
            border-color: rgba(252, 31, 12, 0.6);
        }

        .dashboard-card.donation-card {
            border: 2px solid rgba(55, 74, 206, 0.3);
        }

        .dashboard-card.donation-card:hover {
            border-color: rgba(55, 74, 206, 0.6);
        }



        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: rgba(0, 0, 0, 0.8);
            margin-bottom: 0.8rem;
            position: relative;
            z-index: 1;
        }

        .card-description {
            font-size: 0.85rem;
            color: rgba(0, 0, 0, 0.6);
            line-height: 1.6;
            margin-bottom: 1.5rem;
            flex-grow: 1;
            position: relative;
            z-index: 1;
        }

        .card-stats {
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            font-size: 2.5rem;
            color: rgba(0, 0, 0, 0.8);
            font-weight: 700;
            margin-top: auto;
            position: relative;
            z-index: 1;
        }

        .dashboard-card.fraud-card .card-stats {
            color: #fc1f0c;
        }

        .dashboard-card.donation-card .card-stats {
            color: #374ace;
            font-size: 1.8rem;
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

        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }

            .profile-info-row {
                flex-direction: column;
                gap: 0.5rem;
                align-items: center;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .card-stats {
                font-size: 2rem;
            }

            .dashboard-card.donation-card .card-stats {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php 
                $words = explode(' ', $staff['username']);
                if (count($words) >= 2) {
                    echo strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                } else {
                    echo strtoupper(substr($staff['username'], 0, 2));
                }
                ?>
            </div>
            <div class="profile-content">
                <div class="profile-name"><?php echo htmlspecialchars($staff['username']); ?></div>
                <div class="profile-info-row">
                    <div class="info-item">
                        <span class="info-label">Role:</span>
                        <span><?php echo htmlspecialchars($staff['user_role']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span><?php echo htmlspecialchars($staff['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone:</span>
                        <span><?php echo htmlspecialchars($staff['phone_no']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="quote-section">
            <p class="quote-text">Every child deserves a champion – an adult who will never give up on them, who understands the power of connection and insists that they become the best they can possibly be.</p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card" onclick="openDashboard('children-needing')">
                <h3 class="card-title">Children Needing Sponsors</h3>
                <p class="card-description">View children waiting for sponsors</p>
                <div class="card-stats">
                    <span class="card-stats-label">Total Children</span>
                    <div><?php echo $children_needing_sponsors; ?></div>
                </div>
            </div>

            <div class="dashboard-card" onclick="openDashboard('children-having')">
                <h3 class="card-title">Children Having Sponsors</h3>
                <p class="card-description">Track currently sponsored children</p>
                <div class="card-stats">
                    <span class="card-stats-label">Total Sponsored</span>
                    <div><?php echo $children_having_sponsors; ?></div>
                </div>
            </div>

            <div class="dashboard-card" onclick="openDashboard('total-sponsors')">
                <h3 class="card-title">Total Sponsors</h3>
                <p class="card-description">View all registered sponsors</p>
                <div class="card-stats">
                    <span class="card-stats-label">Active Sponsors</span>
                    <div><?php echo $total_sponsors; ?></div>
                </div>
            </div>

            <div class="dashboard-card fraud-card" onclick="openDashboard('fraud-cases')">
                <h3 class="card-title">Fraud Cases</h3>
                <p class="card-description">Review flagged sponsors</p>
                <div class="card-stats">
                    <span class="card-stats-label">Flagged Accounts</span>
                    <div><?php echo $fraud_cases; ?></div>
                </div>
            </div>

            <div class="dashboard-card" onclick="openDashboard('staff-total')">
                <h3 class="card-title">Total Staff</h3>
                <p class="card-description">Manage staff members</p>
                <div class="card-stats">
                    <span class="card-stats-label">Staff Members</span>
                    <div><?php echo $Staff_Total; ?></div>
                </div>
            </div>

            <div class="dashboard-card donation-card" onclick="openDashboard('total-donations')">
                <h3 class="card-title">Total Amount Donated</h3>
                <p class="card-description">Track all donations received</p>
                <div class="card-stats">
                    <span class="card-stats-label">Total Donations</span>
                    <div>₹<?php echo number_format($total_donated, 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <script src="owner_home.js"></script>
</body>
</html>