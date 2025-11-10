<?php
session_start();

// For testing - remove this line in production
$_SESSION['user_id'] = 1;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../db_config.php';

// Check if database connection exists
if (!isset($conn)) {
    die("Database connection failed. Please check db_config.php");
}

$user_id = $_SESSION['user_id'];

// Get sponsor information
$query = "SELECT u.user_id, u.username, u.email, u.phone_no, u.created_at, 
                 s.sponsor_id, s.first_name, s.last_name, s.dob, s.address, s.profile_picture
          FROM users u
          INNER JOIN sponsors s ON u.user_id = s.user_id
          WHERE u.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Sponsor not found. Please make sure this user account is registered as a sponsor.");
}

$user_data = $result->fetch_assoc();
$sponsor_id = intval($user_data['sponsor_id']);
$stmt->close();

// Additional validation
if ($sponsor_id <= 0) {
    die("Invalid sponsor ID. Please contact administrator.");
}

// Get dashboard statistics
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM sponsorships WHERE sponsor_id = ? AND (end_date IS NULL OR end_date > CURDATE())");
$stmt->bind_param("i", $sponsor_id);
$stmt->execute();
$sponsored_children = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE sponsor_id = ?");
$stmt->bind_param("i", $sponsor_id);
$stmt->execute();
$total_donated = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

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
$new_reports = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Check if timeline_events table exists
$table_check = $conn->query("SHOW TABLES LIKE 'timeline_events'");
if ($table_check && $table_check->num_rows > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM timeline_events WHERE sponsor_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stmt->bind_param("i", $sponsor_id);
    $stmt->execute();
    $recent_events = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
} else {
    $recent_events = 0;
}

$conn->close();

// Calculate initials
$initials = strtoupper(substr($user_data['first_name'], 0, 1) . substr($user_data['last_name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sponsor Dashboard</title>
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

        /* BRIGHT YELLOW SPLASH IN CENTER */
        body::before {
            content: '';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 1000px;
            height: 1000px;
            background: radial-gradient(circle at center, 
                #FEF3C7 0%,
                #FDE68A 15%,
                #FCD34D 25%, 
                #FBBF24 35%,
                rgba(251, 191, 36, 0.6) 45%,
                rgba(252, 211, 77, 0.3) 55%,
                transparent 70%);
            pointer-events: none;
            z-index: 0;
            filter: blur(60px);
            animation: pulseAura 6s ease-in-out infinite;
        }

        @keyframes pulseAura {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0.9;
            }
            50% {
                transform: translate(-50%, -50%) scale(1.1);
                opacity: 1;
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
                #FBBF24 0%,
                #FCD34D 20%, 
                rgba(252, 211, 77, 0.7) 40%,
                transparent 65%);
            pointer-events: none;
            z-index: 0;
            filter: blur(40px);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2.5rem;
            position: relative;
            z-index: 1;
        }

        /* Banner Section */
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

        /* Profile Section */
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
            overflow: hidden;
        }

        .profile-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.16);
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
            min-height: 240px;
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
    <div class="container">
        <div class="banner-section">
            <img src="image.png" alt="Sponsor Dashboard" class="banner-image">
            <div class="live-indicator">
                <div class="live-dot" id="live-dot"></div>
                <span id="live-status">Live</span>
            </div>
        </div>

        <div class="main-content">
            <div class="profile-section">
                <div class="profile-picture-container">
                    <div class="profile-picture">
                        <?php if (!empty($user_data['profile_picture']) && file_exists($user_data['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($user_data['profile_picture']); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <span><?php echo $initials; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="profile-name">
                        <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
                    </div>
                </div>

                <div class="profile-details">
                    <div class="detail-field">
                        <label class="detail-label">Full Name</label>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
                        </div>
                    </div>

                    <div class="detail-field">
                        <label class="detail-label">Email Address</label>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($user_data['email']); ?>
                        </div>
                    </div>

                    <div class="detail-field">
                        <label class="detail-label">Phone Number</label>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($user_data['phone_no']); ?>
                        </div>
                    </div>

                    <div class="detail-field">
                        <label class="detail-label">Member Since</label>
                        <div class="detail-value">
                            <?php echo date('F j, Y', strtotime($user_data['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="dashboard-card" onclick="openDashboard('sponsored-children')">
                    <h3 class="card-title">Sponsored Children</h3>
                    <p class="card-description">View and manage all the children you are currently sponsoring</p>
                    <div class="card-stats">
                        <span class="card-stats-label">Total Sponsored</span>
                        <div class="count-number" id="sponsored-children-count"><?php echo $sponsored_children; ?></div>
                    </div>
                </div>

                <div class="dashboard-card donation-card" onclick="openDashboard('donation-history')">
                    <h3 class="card-title">Donation History</h3>
                    <p class="card-description">Track all your donations and payment records</p>
                    <div class="card-stats">
                        <span class="card-stats-label">Total Donated</span>
                        <div class="count-number" id="total-donations-count">₹<?php echo number_format($total_donated, 2); ?></div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="openDashboard('reports-updates')">
                    <h3 class="card-title">Reports & Updates</h3>
                    <p class="card-description">Read progress reports and updates about sponsored children</p>
                    <div class="card-stats">
                        <span class="card-stats-label">New Reports</span>
                        <div class="count-number" id="reports-count"><?php echo $new_reports; ?></div>
                    </div>
                </div>

                <div class="dashboard-card" onclick="openDashboard('timeline')">
                    <h3 class="card-title">Timeline</h3>
                    <p class="card-description">View your sponsorship journey and important milestones</p>
                    <div class="card-stats">
                        <span class="card-stats-label">Recent Events</span>
                        <div class="count-number" id="timeline-events-count"><?php echo $recent_events; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Store sponsor ID globally
        window.SPONSOR_ID = <?php echo $sponsor_id; ?>;

        // Function to open dashboard detail pages
        function openDashboard(type) {
            const routes = {
                'sponsored-children': 'sponsored_children.php',
                'donation-history': 'donation_history.php',
                'reports-updates': 'reports_updates.php',
                'timeline': 'timeline.php'
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
            
            fetch(`get_sponsor_dashboard_counts.php?sponsor_id=${window.SPONSOR_ID}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Update counts with animation
                        updateCount('sponsored-children-count', data.sponsored_children);
                        updateCount('total-donations-count', '₹' + parseFloat(data.total_donated).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
                        updateCount('reports-count', data.new_reports);
                        updateCount('timeline-events-count', data.recent_events);
                        
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