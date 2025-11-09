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

// 5. Total children count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM children");
$stmt->execute();
$total_children = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// 6. Active sponsorships count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM sponsorships WHERE status = 'Active'");
$stmt->execute();
$active_sponsorships = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// 7. Get 7-day trend for unsponsored children
$stmt = $conn->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM children 
    WHERE status = 'Unsponsored' 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute();
$unsponsored_trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 8. Get 30-day sponsorship growth
$stmt = $conn->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM children 
    WHERE status = 'Sponsored' 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute();
$sponsored_growth = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 9. Get 6-month new sponsors trend
$stmt = $conn->prepare("
    SELECT DATE_FORMAT(u.created_at, '%Y-%m') as month, COUNT(*) as count
    FROM sponsors s
    JOIN users u ON s.user_id = u.user_id
    WHERE u.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(u.created_at, '%Y-%m')
    ORDER BY month ASC
");
$stmt->execute();
$new_sponsors_trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 10. Get fraud cases trend (last 30 days)
$stmt = $conn->prepare("
    SELECT DATE(u.created_at) as date, COUNT(*) as count
    FROM sponsors s
    JOIN users u ON s.user_id = u.user_id
    WHERE s.is_flagged = 1 
    AND u.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(u.created_at)
    ORDER BY date ASC
");
$stmt->execute();
$fraud_trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 11. Gender breakdown of unsponsored children
$stmt = $conn->prepare("
    SELECT gender, COUNT(*) as count 
    FROM children 
    WHERE status = 'Unsponsored'
    GROUP BY gender
");
$stmt->execute();
$gender_breakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 12. Sponsorship rate calculation
$sponsorship_rate = $total_children > 0 ? round(($children_having_sponsors / $total_children) * 100, 1) : 0;

// 13. Active sponsors count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM sponsors WHERE is_flagged = 0");
$stmt->execute();
$active_sponsors = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Calculate week-over-week changes
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM children 
    WHERE status = 'Unsponsored' 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$stmt->execute();
$new_unsponsored_week = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM children 
    WHERE status = 'Sponsored' 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$stmt->execute();
$new_sponsored_week = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Home - Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .profile-section {
            background-color: #e9e9f1;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
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

        .quick-stats {
            background: linear-gradient(135deg, #1128ce 0%, #374ace 100%);
            border-radius: 12px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 4px 12px rgba(17, 40, 206, 0.2);
        }

        .quick-stat-item {
            text-align: center;
            color: #ffffff;
        }

        .quick-stat-item .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .quick-stat-item .stat-label {
            font-size: 13px;
            opacity: 0.9;
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
            gap: 30px;
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
            padding: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .card-title {
            flex: 1;
        }

        .card-title h3 {
            color: #1128ce;
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .card-title .count {
            color: #211e27;
            font-size: 48px;
            font-weight: bold;
            margin: 5px 0;
        }

        .dashboard-card.fraud-card .count {
            color: #fc1f0c;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge.positive {
            background-color: #d4edda;
            color: #155724;
        }

        .badge.negative {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge.warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .chart-container {
            margin-top: 20px;
            height: 80px;
            position: relative;
        }

        .chart-container canvas {
            max-height: 80px;
        }

        .progress-ring-container {
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .progress-ring {
            position: relative;
            width: 80px;
            height: 80px;
        }

        .progress-ring svg {
            transform: rotate(-90deg);
        }

        .progress-ring-circle {
            fill: none;
            stroke: #d1d1e0;
            stroke-width: 8;
        }

        .progress-ring-progress {
            fill: none;
            stroke: #1128ce;
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.5s ease;
        }

        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 18px;
            font-weight: bold;
            color: #211e27;
        }

        .progress-label {
            font-size: 14px;
            color: #211e27;
        }

        .status-dots {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            font-size: 14px;
        }

        .status-dot {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .dot.green {
            background-color: #28a745;
        }

        .dot.red {
            background-color: #fc1f0c;
        }

        .card-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #d1d1e0;
            font-size: 13px;
            color: #666;
        }

        .mini-pie-container {
            margin-top: 15px;
            height: 100px;
            position: relative;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dashboard-card {
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }

        .dashboard-card:nth-child(1) { animation-delay: 0.1s; }
        .dashboard-card:nth-child(2) { animation-delay: 0.2s; }
        .dashboard-card:nth-child(3) { animation-delay: 0.3s; }
        .dashboard-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-section">
            <div class="profile-icon">
                <?php 
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

        <div class="quick-stats">
            <div class="quick-stat-item">
                <div class="stat-value"><?php echo $total_children; ?></div>
                <div class="stat-label">📈 Total Children</div>
            </div>
            <div class="quick-stat-item">
                <div class="stat-value"><?php echo $active_sponsorships; ?></div>
                <div class="stat-label">🤝 Active Sponsorships</div>
            </div>
            <div class="quick-stat-item">
                <div class="stat-value"><?php echo $sponsorship_rate; ?>%</div>
                <div class="stat-label">💯 Sponsorship Rate</div>
            </div>
            <div class="quick-stat-item">
                <div class="stat-value" id="last-updated">Just now</div>
                <div class="stat-label">📅 Last Updated</div>
            </div>
        </div>

        <div class="dashboard-title">
            <h1>Dashboard</h1>
        </div>

        <div class="dashboard-grid">
            <!-- Card 1: Children Needing Sponsors -->
            <div class="dashboard-card" onclick="openDashboard('children-needing')">
                <div class="card-header">
                    <div class="card-title">
                        <h3>Children Needing Sponsors</h3>
                        <div class="count" id="children-needing-count"><?php echo $children_needing_sponsors; ?></div>
                    </div>
                    <?php if ($new_unsponsored_week > 0): ?>
                        <span class="badge negative">+<?php echo $new_unsponsored_week; ?> this week</span>
                    <?php else: ?>
                        <span class="badge positive">No new cases</span>
                    <?php endif; ?>
                </div>
                
                <div class="chart-container">
                    <canvas id="unsponsoredTrendChart"></canvas>
                </div>
                
                <?php if (count($gender_breakdown) > 0): ?>
                <div class="mini-pie-container">
                    <canvas id="genderBreakdownChart"></canvas>
                </div>
                <?php endif; ?>
                
                <div class="card-footer">
                    Click to view all unsponsored children
                </div>
            </div>

            <!-- Card 2: Children Having Sponsors -->
            <div class="dashboard-card" onclick="openDashboard('children-having')">
                <div class="card-header">
                    <div class="card-title">
                        <h3>Children Having Sponsors</h3>
                        <div class="count" id="children-having-count"><?php echo $children_having_sponsors; ?></div>
                    </div>
                    <?php if ($new_sponsored_week > 0): ?>
                        <span class="badge positive">↑ +<?php echo $new_sponsored_week; ?> this week</span>
                    <?php endif; ?>
                </div>
                
                <div class="chart-container">
                    <canvas id="sponsoredGrowthChart"></canvas>
                </div>
                
                <div class="progress-ring-container">
                    <div class="progress-ring">
                        <svg width="80" height="80">
                            <circle class="progress-ring-circle" cx="40" cy="40" r="32"></circle>
                            <circle class="progress-ring-progress" cx="40" cy="40" r="32" 
                                    stroke-dasharray="201" stroke-dashoffset="<?php echo 201 - (201 * $sponsorship_rate / 100); ?>"></circle>
                        </svg>
                        <div class="progress-text"><?php echo $sponsorship_rate; ?>%</div>
                    </div>
                    <div class="progress-label">
                        <strong>Sponsorship Rate</strong><br>
                        <?php echo $children_having_sponsors; ?> of <?php echo $total_children; ?> children
                    </div>
                </div>
                
                <div class="card-footer">
                    Click to view all sponsored children
                </div>
            </div>

            <!-- Card 3: Total Sponsors -->
            <div class="dashboard-card" onclick="openDashboard('total-sponsors')">
                <div class="card-header">
                    <div class="card-title">
                        <h3>Total Sponsors</h3>
                        <div class="count" id="total-sponsors-count"><?php echo $total_sponsors; ?></div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="newSponsorsChart"></canvas>
                </div>
                
                <div class="status-dots">
                    <div class="status-dot">
                        <span class="dot green"></span>
                        <span>Active: <?php echo $active_sponsors; ?></span>
                    </div>
                    <div class="status-dot">
                        <span class="dot red"></span>
                        <span>Flagged: <?php echo $fraud_cases; ?></span>
                    </div>
                </div>
                
                <div class="card-footer">
                    Click to view all sponsors
                </div>
            </div>

            <!-- Card 4: Fraud Cases -->
            <div class="dashboard-card fraud-card" onclick="openDashboard('fraud-cases')">
                <div class="card-header">
                    <div class="card-title">
                        <h3>Fraud Cases</h3>
                        <div class="count" id="fraud-cases-count"><?php echo $fraud_cases; ?></div>
                    </div>
                    <?php if ($fraud_cases > 10): ?>
                        <span class="badge negative">⚠️ High Alert</span>
                    <?php elseif ($fraud_cases > 5): ?>
                        <span class="badge warning">⚠️ Monitor</span>
                    <?php else: ?>
                        <span class="badge positive">✓ Low Risk</span>
                    <?php endif; ?>
                </div>
                
                <div class="chart-container">
                    <canvas id="fraudTrendChart"></canvas>
                </div>
                
                <div class="card-footer" style="color: #fc1f0c; font-weight: 600;">
                    ⚠️ Click to review flagged sponsors
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart data from PHP
        const unsponsoredTrendData = <?php echo json_encode($unsponsored_trend); ?>;
        const sponsoredGrowthData = <?php echo json_encode($sponsored_growth); ?>;
        const newSponsorsTrendData = <?php echo json_encode($new_sponsors_trend); ?>;
        const fraudTrendData = <?php echo json_encode($fraud_trend); ?>;
        const genderBreakdownData = <?php echo json_encode($gender_breakdown); ?>;

        // Chart.js default settings
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.plugins.legend.display = false;
        Chart.defaults.plugins.tooltip.enabled = true;

        // 1. Unsponsored Children Trend (Sparkline)
        const unsponsoredCtx = document.getElementById('unsponsoredTrendChart').getContext('2d');
        new Chart(unsponsoredCtx, {
            type: 'line',
            data: {
                labels: unsponsoredTrendData.map(d => d.date),
                datasets: [{
                    data: unsponsoredTrendData.map(d => d.count),
                    borderColor: '#fc1f0c',
                    backgroundColor: 'rgba(252, 31, 12, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { display: false },
                    y: { display: false }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.parsed.y} children`
                        }
                    }
                }
            }
        });

        // 2. Sponsored Children Growth (Area Chart)
        const sponsoredCtx = document.getElementById('sponsoredGrowthChart').getContext('2d');
        new Chart(sponsoredCtx, {
            type: 'line',
            data: {
                labels: sponsoredGrowthData.map(d => d.date),
                datasets: [{
                    data: sponsoredGrowthData.map(d => d.count),
                    borderColor: '#1128ce',
                    backgroundColor: 'rgba(17, 40, 206, 0.2)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { display: false },
                    y: { display: false }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.parsed.y} children`
                        }
                    }
                }
            }
        });

        // 3. New Sponsors Bar Chart
        const sponsorsCtx = document.getElementById('newSponsorsChart').getContext('2d');
        new Chart(sponsorsCtx, {
            type: 'bar',
            data: {
                labels: newSponsorsTrendData.map(d => d.month),
                datasets: [{
                    data: newSponsorsTrendData.map(d => d.count),
                    backgroundColor: '#374ace',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { display: false },
                    y: { display: false }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.parsed.y} new sponsors`
                        }
                    }
                }
            }
        });

        // 4. Fraud Trend Line Chart
        const fraudCtx = document.getElementById('fraudTrendChart').getContext('2d');
        new Chart(fraudCtx, {
            type: 'line',
            data: {
                labels: fraudTrendData.map(d => d.date),
                datasets: [{
                    data: fraudTrendData.map(d => d.count),
                    borderColor: '#fc1f0c',
                    backgroundColor: 'rgba(252, 31, 12, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#fc1f0c'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { display: false },
                    y: { display: false }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.parsed.y} fraud cases`
                        }
                    }
                }
            }
        });

        // 5. Gender Breakdown Pie Chart (if data exists)
        if (genderBreakdownData.length > 0) {
            const genderCtx = document.getElementById('genderBreakdownChart').getContext('2d');
            new Chart(genderCtx, {
                type: 'doughnut',
                data: {
                    labels: genderBreakdownData.map(d => d.gender),
                    datasets: [{
                        data: genderBreakdownData.map(d => d.count),
                        backgroundColor: ['#1128ce', '#374ace', '#fc1f0c'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.label}: ${context.parsed}`
                            }
                        }
                    }
                }
            });
        }

        // Navigation function
        function openDashboard(dashboardType) {
            const routes = {
                'children-needing': 'children_needing_sponsors.php',
                'children-having': 'children_having_sponsors.php',
                'total-sponsors': 'total_sponsors.php',
                'fraud-cases': 'fraud_cases.php'
            };
            
            console.log(`Navigating to: ${routes[dashboardType]}`);
            // Uncomment when pages are ready:
            // window.location.href = routes[dashboardType];
            alert(`This will navigate to ${routes[dashboardType]} - Page to be created`);
        }

        // Update "Last Updated" timestamp
        function updateTimestamp() {
            const now = new Date();
            const minutes = Math.floor((Date.now() - now.setSeconds(0, 0)) / 60000);
            const display = minutes === 0 ? 'Just now' : `${minutes}m ago`;
            document.getElementById('last-updated').textContent = display;
        }
        
        setInterval(updateTimestamp, 60000);
    </script>
</body>
</html>