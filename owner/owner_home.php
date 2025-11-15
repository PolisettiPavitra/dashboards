<?php
session_start();
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../components/sidebar_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../signup_and_login/login.html");
    exit();
}

// Get owner information from session
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, phone_no, user_role FROM users WHERE user_id = ? AND user_role IN ('Owner', 'Admin')");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User is not an Owner/Admin, redirect to login
    session_destroy();
    header("Location: ../signup_and_login/login.html");
    exit();
}

$owner = $result->fetch_assoc();
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
$staff_total = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM donations");
$stmt->execute();
$total_donated = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Get initials
$words = explode(' ', $owner['username']);
if (count($words) >= 2) {
    $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
} else {
    $initials = strtoupper(substr($owner['username'], 0, 2));
}

// Initialize sidebar menu for owner dashboard
$sidebar_menu = initSidebar('owner', 'owner_home.php');

// Set logout path
$logout_path = '../signup_and_login/logout.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #ffffff;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* MEDIUM YELLOW SPLASH IN CENTER */
        body::before {
            content: '';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 1000px;
            height: 1000px;
            background: radial-gradient(circle at center, 
                rgba(254, 243, 199, 0.6) 0%,
                rgba(253, 230, 138, 0.55) 15%,
                rgba(252, 211, 77, 0.5) 25%, 
                rgba(251, 191, 36, 0.45) 35%,
                rgba(251, 191, 36, 0.35) 45%,
                rgba(252, 211, 77, 0.25) 55%,
                transparent 70%);
            pointer-events: none;
            z-index: 0;
            filter: blur(60px);
            animation: pulseAura 6s ease-in-out infinite;
        }

        @keyframes pulseAura {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0.7;
            }
            50% {
                transform: translate(-50%, -50%) scale(1.1);
                opacity: 0.85;
            }
        }

        body::after {
            content: '';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 600px;
            height: 600px;
            background: radial-gradient(circle at center, 
                rgba(251, 191, 36, 0.5) 0%,
                rgba(252, 211, 77, 0.4) 20%, 
                rgba(252, 211, 77, 0.3) 40%,
                transparent 65%);
            pointer-events: none;
            z-index: 0;
            filter: blur(40px);
        }

        /* Main Content */
        .main-wrapper {
            margin-left: 0;
            margin-top: 80px;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-wrapper.sidebar-open {
            margin-left: 280px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2.5rem;
            position: relative;
            z-index: 1;
        }

        .banner-section {
            width: 100%;
            height: 320px;
            background: rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 32px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            margin-bottom: 2.5rem;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .banner-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .live-indicator {
            position: absolute;
            top: 24px;
            right: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
            color: rgba(0, 0, 0, 0.7);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .live-dot {
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.6);
            animation: livePulse 2s ease-in-out infinite;
        }

        .live-dot.updating {
            background: #f59e0b;
            box-shadow: 0 0 12px rgba(245, 158, 11, 0.6);
            animation: updatePulse 0.8s ease-in-out infinite;
        }

        @keyframes livePulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.6;
                transform: scale(0.85);
            }
        }

        @keyframes updatePulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.2);
            }
        }

        /* Main Content Grid */
        .main-content {
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 2.5rem;
            align-items: start;
        }

        /* Profile Section - Glassmorphism */
        .profile-section {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 32px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            padding: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .profile-picture-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.9);
            background: linear-gradient(135deg, rgba(255, 237, 160, 0.5), rgba(254, 249, 195, 0.4));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: rgba(0, 0, 0, 0.65);
            font-weight: 700;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .profile-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.16);
        }

        .profile-name {
            font-size: 1.75rem;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.85);
            text-align: center;
            letter-spacing: -0.02em;
        }

        .profile-details {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .detail-field {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .detail-label {
            font-size: 0.75rem;
            color: rgba(0, 0, 0, 0.5);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        .detail-value {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(10px);
            padding: 1rem 1.5rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            color: rgba(0, 0, 0, 0.85);
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        /* Dashboard Cards Section */
        .dashboard-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            padding: 2.5rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            position: relative;
            overflow: hidden;
            min-height: 220px;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at top left, rgba(255, 237, 160, 0.15), transparent 70%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12),
                        inset 0 1px 0 rgba(255, 255, 255, 1);
            border-color: rgba(255, 237, 160, 0.8);
        }

        .dashboard-card:hover::before {
            opacity: 1;
        }

        .dashboard-card.fraud-card {
            border-color: rgba(239, 68, 68, 0.3);
        }

        .dashboard-card.fraud-card:hover {
            border-color: rgba(239, 68, 68, 0.6);
        }

        .dashboard-card.fraud-card::before {
            background: radial-gradient(circle at top left, rgba(239, 68, 68, 0.1), transparent 70%);
        }

        .dashboard-card.donation-card {
            border-color: rgba(59, 130, 246, 0.3);
        }

        .dashboard-card.donation-card:hover {
            border-color: rgba(59, 130, 246, 0.6);
        }

        .dashboard-card.donation-card::before {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.1), transparent 70%);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.85);
            margin-bottom: 1rem;
            letter-spacing: -0.01em;
        }

        .card-description {
            font-size: 0.9rem;
            color: rgba(0, 0, 0, 0.6);
            line-height: 1.6;
            margin-bottom: auto;
            font-weight: 400;
        }

        .card-stats {
            padding-top: 1.5rem;
            margin-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            font-size: 2.5rem;
            color: rgba(0, 0, 0, 0.85);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .dashboard-card.fraud-card .card-stats {
            color: #ef4444;
        }

        .dashboard-card.donation-card .card-stats {
            color: #3b82f6;
        }

        .card-stats-label {
            font-size: 0.75rem;
            color: rgba(0, 0, 0, 0.5);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: block;
            margin-bottom: 0.5rem;
        }

        .count-number {
            transition: all 0.3s ease;
        }

        .count-number.updating {
            color: #f59e0b;
            transform: scale(1.1);
        }

        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .profile-section {
                max-width: 600px;
                margin: 0 auto;
            }

            .main-wrapper.sidebar-open {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }

            .banner-section {
                height: 220px;
                border-radius: 24px;
            }

            .dashboard-section {
                grid-template-columns: 1fr;
            }

            .main-content {
                gap: 2rem;
            }

            .profile-picture {
                width: 120px;
                height: 120px;
                font-size: 2.5rem;
            }

            .card-stats {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php 
// FIXED: Header first, then Sidebar
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar.php';
    ?>

    <!-- Main Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <div class="container">
            <div class="banner-section">
                <img src="../sponser/image.png" alt="Owner Dashboard" class="banner-image">
                <div class="live-indicator">
                    <div class="live-dot" id="live-dot"></div>
                    <span id="live-status">Live</span>
                </div>
            </div>

            <div class="main-content">
                <div class="profile-section">
                    <div class="profile-picture-container">
                        <div class="profile-picture">
                            <span><?php echo $initials; ?></span>
                        </div>
                        <div class="profile-name">
                            <?php echo htmlspecialchars($owner['username']); ?>
                        </div>
                    </div>

                    <div class="profile-details">
                        <div class="detail-field">
                            <label class="detail-label">Username</label>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($owner['username']); ?>
                            </div>
                        </div>

                        <div class="detail-field">
                            <label class="detail-label">Email Address</label>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($owner['email']); ?>
                            </div>
                        </div>

                        <div class="detail-field">
                            <label class="detail-label">Phone Number</label>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($owner['phone_no']); ?>
                            </div>
                        </div>

                        <div class="detail-field">
                            <label class="detail-label">Role</label>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($owner['user_role']); ?>
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
                            <div class="count-number" id="children-needing-count"><?php echo $children_needing_sponsors; ?></div>
                        </div>
                    </div>

                    <div class="dashboard-card" onclick="openDashboard('children-having')">
                        <h3 class="card-title">Children Having Sponsors</h3>
                        <p class="card-description">Track all children currently under active sponsorship</p>
                        <div class="card-stats">
                            <span class="card-stats-label">Sponsored</span>
                            <div class="count-number" id="children-having-count"><?php echo $children_having_sponsors; ?></div>
                        </div>
                    </div>

                    <div class="dashboard-card" onclick="openDashboard('total-sponsors')">
                        <h3 class="card-title">Total Sponsors</h3>
                        <p class="card-description">View complete list of all registered sponsors in the system</p>
                        <div class="card-stats">
                            <span class="card-stats-label">Active Sponsors</span>
                            <div class="count-number" id="total-sponsors-count"><?php echo $total_sponsors; ?></div>
                        </div>
                    </div>

                    <div class="dashboard-card fraud-card" onclick="openDashboard('fraud-cases')">
                        <h3 class="card-title">Fraud Cases</h3>
                        <p class="card-description">Review and manage flagged accounts requiring investigation</p>
                        <div class="card-stats">
                            <span class="card-stats-label">Flagged Accounts</span>
                            <div class="count-number" id="fraud-cases-count"><?php echo $fraud_cases; ?></div>
                        </div>
                    </div>

                    <div class="dashboard-card" onclick="openDashboard('staff-total')">
                        <h3 class="card-title">Total Staff</h3>
                        <p class="card-description">Manage and oversee all staff members in the organization</p>
                        <div class="card-stats">
                            <span class="card-stats-label">Staff Members</span>
                            <div class="count-number" id="staff-total-count"><?php echo $staff_total; ?></div>
                        </div>
                    </div>

                    <div class="dashboard-card donation-card" onclick="openDashboard('total-donations')">
                        <h3 class="card-title">Total Donations</h3>
                        <p class="card-description">Track and monitor all monetary contributions received</p>
                        <div class="card-stats">
                            <span class="card-stats-label">Amount Donated</span>
                            <div class="count-number" id="total-donations-count">₹<?php echo number_format($total_donated, 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php 
    // Include common scripts
    include __DIR__ . '/../components/common_scripts.php';
    ?>

    <script>
        // Function to open dashboard detail pages
        function openDashboard(type) {
            const routes = {
                'children-needing': '../staff/children_needing_sponsors.php',
                'children-having': '../staff/children_having_sponsors.php',
                'total-sponsors': '../staff/total_sponsors.php',
                'fraud-cases': '../staff/fraud_cases.php',
                'staff-total': 'staff_management.php',
                'total-donations': 'donations.php'
            };
            
            window.location.href = routes[type];
        }

        // Function to refresh dashboard counts
        function refreshDashboardCounts() {
            const liveDot = document.getElementById('live-dot');
            const liveStatus = document.getElementById('live-status');
            
            // Show updating state
            liveDot.classList.add('updating');
            liveStatus.textContent = 'Updating...';
            
            fetch('get_dashboard_counts.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Update counts with animation
                        updateCount('children-needing-count', data.children_needing);
                        updateCount('children-having-count', data.children_having);
                        updateCount('total-sponsors-count', data.total_sponsors);
                        updateCount('fraud-cases-count', data.fraud_cases);
                        updateCount('staff-total-count', data.staff_total);
                        updateCount('total-donations-count', '₹' + parseFloat(data.total_donated).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
                        
                        console.log('Dashboard updated at:', data.timestamp);
                    }
                    
                    // Reset to live state
                    setTimeout(() => {
                        liveDot.classList.remove('updating');
                        liveStatus.textContent = 'Live';
                    }, 500);
                })
                .catch(error => {
                    console.error('Error refreshing counts:', error);
                    liveDot.classList.remove('updating');
                    liveStatus.textContent = 'Error';
                    setTimeout(() => {
                        liveStatus.textContent = 'Live';
                    }, 3000);
                });
        }

        // Function to update count with animation
        function updateCount(elementId, newValue) {
            const element = document.getElementById(elementId);
            const currentValue = element.textContent.replace(/[^0-9.]/g, '');
            const cleanNewValue = String(newValue).replace(/[^0-9.]/g, '');
            
            if (currentValue !== cleanNewValue) {
                element.classList.add('updating');
                
                setTimeout(() => {
                    element.textContent = newValue;
                    setTimeout(() => {
                        element.classList.remove('updating');
                    }, 300);
                }, 150);
            }
        }

        // Page load animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.dashboard-card');
            
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Start auto-refresh after 3 seconds
            setTimeout(() => {
                refreshDashboardCounts();
                // Auto-refresh every 30 seconds
                setInterval(refreshDashboardCounts, 30000);
            }, 3000);
        });
    </script>
</body>
</html>