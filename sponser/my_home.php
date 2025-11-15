<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signup_and_login/login_template.php");
    exit();
}

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../components/sidebar_config.php';

if (!isset($conn)) {
    die("Database connection failed. Please check db_config.php");
}

$user_id = $_SESSION['user_id'];

// Get sponsor information
$query = "SELECT u.user_id, u.username, u.email, 
                 s.sponsor_id, s.first_name, s.last_name
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

// Get quick stats
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM sponsorships WHERE sponsor_id = ? AND (end_date IS NULL OR end_date > CURDATE())");
$stmt->bind_param("i", $sponsor_id);
$stmt->execute();
$sponsored_children = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM children WHERE status = 'Unsponsored'");
$stmt->execute();
$available_children = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$conn->close();

// Initialize sidebar menu for sponsor
$sidebar_menu = initSidebar('sponsor', 'my_home.php');
$logout_path = '../signup_and_login/logout.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Home - Sponsor Dashboard</title>
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
            padding: 3rem 2.5rem;
            position: relative;
            z-index: 1;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 4rem;
            padding: 3rem 0;
        }

        .welcome-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: rgba(0, 0, 0, 0.85);
            letter-spacing: -0.03em;
            margin-bottom: 1rem;
        }

        .welcome-subtitle {
            font-size: 1.5rem;
            color: rgba(0, 0, 0, 0.6);
            font-weight: 400;
        }

        .hero-section {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 32px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            padding: 4rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, rgba(255, 237, 160, 0.2), transparent 70%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-icon {
            font-size: 5rem;
            margin-bottom: 2rem;
            display: inline-block;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.85);
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
        }

        .hero-description {
            font-size: 1.25rem;
            color: rgba(0, 0, 0, 0.6);
            margin-bottom: 3rem;
            line-height: 1.6;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .sponsor-button {
            display: inline-block;
            padding: 1.25rem 3.5rem;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
            border-radius: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(251, 191, 36, 0.3);
            font-family: 'Inter', sans-serif;
        }

        .sponsor-button:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(251, 191, 36, 0.4);
        }

        .sponsor-button:active {
            transform: translateY(-2px);
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: rgba(0, 0, 0, 0.85);
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .stat-label {
            font-size: 1rem;
            color: rgba(0, 0, 0, 0.6);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-section {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            padding: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        }

        .info-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.85);
            margin-bottom: 1.5rem;
            letter-spacing: -0.01em;
        }

        .info-list {
            list-style: none;
            display: grid;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: rgba(255, 255, 255, 0.7);
            transform: translateX(8px);
        }

        .info-item-icon {
            font-size: 1.5rem;
            width: 40px;
            text-align: center;
        }

        .info-item-text {
            font-size: 1rem;
            color: rgba(0, 0, 0, 0.7);
            font-weight: 500;
            flex: 1;
        }

        @media (max-width: 1200px) {
            .main-wrapper.sidebar-open {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 2rem 1.5rem;
            }

            .welcome-title {
                font-size: 2.5rem;
            }

            .hero-section {
                padding: 3rem 2rem;
            }

            .hero-title {
                font-size: 2rem;
            }

            .stats-section {
                grid-template-columns: 1fr;
            }

            .sponsor-button {
                padding: 1rem 2.5rem;
                font-size: 1.125rem;
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

    <div class="main-wrapper" id="mainWrapper">
        <div class="container">
            <div class="welcome-section">
                <h1 class="welcome-title">Welcome, <?php echo htmlspecialchars($user_data['first_name']); ?>! 👋</h1>
                <p class="welcome-subtitle">Your journey of making a difference starts here</p>
            </div>

            <div class="hero-section">
                <div class="hero-content">
                    <div class="hero-icon">💝</div>
                    <h2 class="hero-title">Ready to Change a Life?</h2>
                    <p class="hero-description">
                        Every child deserves a chance at a brighter future. Your sponsorship provides 
                        education, healthcare, and hope to children in need.
                    </p>
                    <a href="../all_children_profiles_sponser.php" class="sponsor-button">
                        Sponsor a Child Now ✨
                    </a>
                </div>
            </div>

            <div class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon">👧👦</div>
                    <div class="stat-number"><?php echo $sponsored_children; ?></div>
                    <div class="stat-label">Children You're Sponsoring</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🌟</div>
                    <div class="stat-number"><?php echo $available_children; ?></div>
                    <div class="stat-label">Children Waiting for Sponsors</div>
                </div>
            </div>

            <div class="info-section">
                <h3 class="info-title">How Your Sponsorship Helps</h3>
                <ul class="info-list">
                    <li class="info-item">
                        <span class="info-item-icon">📚</span>
                        <span class="info-item-text">Provides quality education and school supplies</span>
                    </li>
                    <li class="info-item">
                        <span class="info-item-icon">🏥</span>
                        <span class="info-item-text">Ensures access to healthcare and regular check-ups</span>
                    </li>
                    <li class="info-item">
                        <span class="info-item-icon">🍎</span>
                        <span class="info-item-text">Delivers nutritious meals and healthy food options</span>
                    </li>
                    <li class="info-item">
                        <span class="info-item-icon">🏠</span>
                        <span class="info-item-text">Supports safe housing and living conditions</span>
                    </li>
                    <li class="info-item">
                        <span class="info-item-icon">🎨</span>
                        <span class="info-item-text">Enables participation in recreational activities</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <?php 
    // Include common scripts
    include __DIR__ . '/../components/common_scripts.php';
    ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card');
            
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>