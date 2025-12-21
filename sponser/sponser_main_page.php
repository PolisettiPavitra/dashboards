<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../signup_and_login/login.php");
    exit();
}

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../components/sidebar_config.php';
require_once __DIR__ . '/../includes/fraud_services.php';
require_once __DIR__ . '/../includes/sponsor_access_check.php';

if (!isset($conn)) {
    die("Database connection failed. Please check db_config.php");
}

$user_id = $_SESSION['user_id'];

$query = "SELECT s.sponsor_id, u.user_id, u.username, u.email, u.phone_no, u.user_role, u.created_at
          FROM users u
          INNER JOIN sponsors s ON u.user_id = s.user_id
          WHERE u.user_id = ? AND u.user_role = 'Sponsor'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $check_role = $conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
    $check_role->bind_param("i", $user_id);
    $check_role->execute();
    $role_result = $check_role->get_result();
    
    if ($role_result->num_rows > 0) {
        $user_role = $role_result->fetch_assoc()['user_role'];
        switch ($user_role) {
            case 'Staff':
                header("Location: ../staff/staff_home_old.php");
                break;
            case 'Owner':
            case 'Admin':
                header("Location: ../owner/owner_home.php");
                break;
            default:
                header("Location: ../signup_and_login/login.php");
        }
    } else {
        header("Location: ../signup_and_login/login.php");
    }
    exit();
}

$sponsor = $result->fetch_assoc();
$sponsor_id = $sponsor['sponsor_id'];
$stmt->close();

// Get fraud status using access check
$access_data = checkSponsorAccessRestrictions($conn, basename(__FILE__));
$donation_status = $access_data['donation_status'];

// Get fraud status details
$flag_status = getSponsorFlagStatus($conn, $sponsor_id);
$is_flagged = $flag_status && ($flag_status['is_flagged'] || $flag_status['fraud_case_id']);

// Determine status
$is_blocked = ($donation_status['status'] === 'blocked');
$is_frozen = ($donation_status['status'] === 'frozen');
$is_restricted = ($donation_status['status'] === 'restricted');
$is_under_review = ($donation_status['status'] === 'under_review');

// Get case ID and check for appeals
$case_id = null;
$case_status = null;
$has_pending_appeal = false;

if ($is_flagged && $flag_status['fraud_case_id']) {
    $case_id = $flag_status['fraud_case_id'];
    $case_status = $flag_status['case_status'];
    
    $stmt = $conn->prepare("
        SELECT appeal_id FROM fraud_appeals 
        WHERE fraud_case_id = ? AND status = 'pending'
    ");
    $stmt->bind_param('i', $case_id);
    $stmt->execute();
    $has_pending_appeal = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

// For blocked users, make EXTRA sure we get the case_id
if ($is_blocked && !$case_id) {
    $stmt = $conn->prepare("
        SELECT fraud_case_id, status 
        FROM fraud_cases 
        WHERE sponsor_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param('i', $sponsor_id);
    $stmt->execute();
    $case_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($case_result) {
        $case_id = $case_result['fraud_case_id'];
        $case_status = $case_result['status'];
        
        // Check if appeal exists
        $stmt = $conn->prepare("
            SELECT appeal_id FROM fraud_appeals 
            WHERE fraud_case_id = ? AND status = 'pending'
        ");
        $stmt->bind_param('i', $case_id);
        $stmt->execute();
        $has_pending_appeal = $stmt->get_result()->num_rows > 0;
        $stmt->close();
    }
}

// Calculate initials
$words = explode(' ', $sponsor['username']);
if (count($words) >= 2) {
    $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
} else {
    $initials = strtoupper(substr($sponsor['username'], 0, 2));
}

$sidebar_menu = initSidebar('sponsor', 'sponser_main_page.php');
$logout_path = '../signup_and_login/logout.php';

// Alert config for frozen/restricted/under_review
$alert_configs = [
    'under_review' => [
        'color' => '#f59e0b',
        'bg' => 'rgba(245, 158, 11, 0.12)',
        'border' => 'rgba(245, 158, 11, 0.3)',
        'icon' => '⚠️',
        'title' => 'Account Under Review',
        'message' => 'Your account is being reviewed. You can continue using the platform.'
    ],
    'restricted' => [
        'color' => '#ea580c',
        'bg' => 'rgba(234, 88, 12, 0.12)',
        'border' => 'rgba(234, 88, 12, 0.3)',
        'icon' => '🚧',
        'title' => 'Account Restricted',
        'message' => 'Donation limit: ₹3,000/month applies.'
    ],
    'frozen' => [
        'color' => '#dc2626',
        'bg' => 'rgba(220, 38, 38, 0.12)',
        'border' => 'rgba(220, 38, 38, 0.3)',
        'icon' => '❄️',
        'title' => 'Account Frozen',
        'message' => 'Donations are temporarily disabled.'
    ],
    'blocked' => [
        'color' => '#991b1b',
        'bg' => 'rgba(153, 27, 27, 0.15)',
        'border' => 'rgba(153, 27, 27, 0.4)',
        'icon' => '🚫',
        'title' => 'Account Blocked',
        'message' => 'Please submit an appeal to restore access.'
    ]
];

$alert_config = $alert_configs[$case_status] ?? $alert_configs['under_review'];

$conn->close();
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

        /* Hide sidebar and adjust layout for blocked users */
        <?php if ($is_blocked): ?>
        #sidebar {
            display: none !important;
        }
        
        #header {
            display: none !important;
        }
        
        .main-wrapper {
            margin-left: 0 !important;
            margin-top: 0 !important;
        }
        <?php endif; ?>

        /* BLOCKED USER FULL SCREEN OVERLAY */
        .blocked-overlay {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 3px solid #dc2626;
            border-radius: 32px;
            padding: 4rem;
            margin: 4rem auto;
            max-width: 900px;
            box-shadow: 0 20px 60px rgba(220, 38, 38, 0.3);
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .blocked-icon {
            font-size: 5rem;
            margin-bottom: 2rem;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .blocked-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #dc2626;
            margin-bottom: 1rem;
        }

        .blocked-message {
            font-size: 1.1rem;
            color: #3f3f46;
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        .blocked-reason-box {
            background: rgba(220, 38, 38, 0.1);
            border: 2px solid rgba(220, 38, 38, 0.3);
            border-radius: 16px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }

        .blocked-reason-title {
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .blocked-reason-text {
            color: #71717a;
            line-height: 1.6;
        }

        .appeal-section {
            background: rgba(254, 249, 195, 0.3);
            border: 2px solid rgba(254, 240, 138, 0.5);
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .appeal-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 1rem;
        }

        .appeal-btn {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
            text-decoration: none;
            display: inline-block;
        }

        .appeal-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(220, 38, 38, 0.5);
            color: white;
        }

        .appeal-btn:disabled,
        .appeal-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .logout-link {
            display: inline-block;
            margin-top: 2rem;
            color: #71717a;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .logout-link:hover {
            color: #18181b;
        }

        /* COMPACT FRAUD BANNER (for frozen/restricted/under_review) */
        .fraud-alert-banner {
            position: static;
            margin: 80px 0 0 0;
            padding: 0;
            z-index: 10;
        }

        .fraud-alert-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2.5rem;
            background: <?php echo $is_flagged && !$is_blocked ? $alert_config['bg'] : 'transparent'; ?>;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid <?php echo $is_flagged && !$is_blocked ? $alert_config['border'] : 'transparent'; ?>;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
        }

        .fraud-alert-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }

        .fraud-alert-icon {
            font-size: 1.75rem;
            line-height: 1;
        }

        .fraud-alert-text {
            flex: 1;
        }

        .fraud-alert-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: <?php echo $is_flagged && !$is_blocked ? $alert_config['color'] : 'inherit'; ?>;
            margin-bottom: 0.25rem;
        }

        .fraud-alert-message {
            font-size: 0.85rem;
            color: rgba(0, 0, 0, 0.7);
            font-weight: 500;
        }

        .fraud-alert-btn {
            padding: 0.65rem 1.5rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            border: none;
        }

        .fraud-alert-btn-primary {
            background: <?php echo $alert_config['color']; ?>;
            color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .fraud-alert-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            color: white;
        }

        .fraud-alert-btn-disabled {
            background: rgba(0, 0, 0, 0.1);
            color: rgba(0, 0, 0, 0.5);
            cursor: not-allowed;
        }

        .main-wrapper {
            margin-left: 0;
            margin-top: 0;
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
            min-height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 4rem;
            padding: 2rem 0;
        }

        .welcome-title {
            font-size: 3rem;
            font-weight: 800;
            color: rgba(0, 0, 0, 0.85);
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .welcome-subtitle {
            font-size: 1.25rem;
            color: rgba(0, 0, 0, 0.6);
            font-weight: 500;
        }

        .action-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 3rem;
            max-width: 1000px;
            margin: 0 auto;
            flex: 1;
            align-items: center;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 32px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            padding: 4rem 3rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            position: relative;
            overflow: hidden;
            min-height: 400px;
        }

        .action-card::before {
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

        .action-card:hover {
            transform: translateY(-16px);
            box-shadow: 0 24px 72px rgba(0, 0, 0, 0.15),
                        inset 0 1px 0 rgba(255, 255, 255, 1);
            border-color: rgba(255, 237, 160, 0.9);
        }

        .action-card:hover::before {
            opacity: 1;
        }

        .action-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255, 237, 160, 0.6), rgba(254, 249, 195, 0.5));
            border: 3px solid rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .action-card:hover .action-icon {
            transform: scale(1.1);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.16);
        }

        .action-title {
            font-size: 2rem;
            font-weight: 800;
            color: rgba(0, 0, 0, 0.85);
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
            position: relative;
            z-index: 1;
        }

        .action-description {
            font-size: 1rem;
            color: rgba(0, 0, 0, 0.6);
            line-height: 1.6;
            text-align: center;
            max-width: 300px;
            position: relative;
            z-index: 1;
        }

        .preview-label {
            position: absolute;
            top: 24px;
            right: 24px;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            font-size: 0.75rem;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.6);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @media (max-width: 1200px) {
            .main-wrapper.sidebar-open {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .fraud-alert-content {
                flex-direction: column;
                text-align: center;
                padding: 1rem 1.5rem;
            }

            .container {
                padding: 1.5rem;
            }

            .blocked-overlay {
                padding: 2rem;
                margin: 2rem 1rem;
            }

            .blocked-title {
                font-size: 1.75rem;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .welcome-subtitle {
                font-size: 1rem;
            }

            .action-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .action-card {
                min-height: 320px;
                padding: 3rem 2rem;
            }

            .action-icon {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }

            .action-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Only show header and sidebar for non-blocked users
    if (!$is_blocked):
        include __DIR__ . '/../components/header.php';
        include __DIR__ . '/../components/sidebar.php';
    endif;
    ?>

    <div class="main-wrapper" id="mainWrapper">
        <?php if ($is_blocked): ?>
            <!-- ============================================ -->
            <!-- BLOCKED USER VIEW - FULL SCREEN OVERLAY -->
            <!-- ============================================ -->
            <div class="container">
                <div class="blocked-overlay">
                    <div class="blocked-icon">🚫</div>
                    <h1 class="blocked-title">Account Blocked</h1>
                    <p class="blocked-message">
                        Your account has been temporarily blocked due to suspicious activity or policy violations.
                        Access to all features has been restricted.
                    </p>

                    <?php if ($donation_status['message']): ?>
                    <div class="blocked-reason-box">
                        <div class="blocked-reason-title">Reason for Block:</div>
                        <div class="blocked-reason-text">
                            <?php echo htmlspecialchars($donation_status['message']); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="appeal-section">
                        <h2 class="appeal-title">📝 Submit an Appeal</h2>
                        
                        <?php if ($has_pending_appeal): ?>
                            <div style="padding: 1.5rem; background: rgba(59, 130, 246, 0.1); border-radius: 12px; color: #1e40af;">
                                <strong>⏳ Your appeal is pending review</strong>
                                <p style="margin-top: 0.5rem; font-size: 0.9rem;">
                                    Our team is reviewing your case. You'll be notified once a decision is made.
                                </p>
                            </div>
                        <?php elseif ($case_id): ?>
                            <p style="color: #71717a; margin-bottom: 1rem; line-height: 1.6;">
                                If you believe this block is a mistake or have additional information to provide, 
                                please click below to submit an appeal to our review team.
                            </p>
                            <a href="sponsor_appeal_form.php?case_id=<?php echo $case_id; ?>" class="appeal-btn">
                                ✍️ Write an Appeal
                            </a>
                        <?php else: ?>
                            <div style="padding: 1.5rem; background: rgba(239, 68, 68, 0.1); border-radius: 12px; color: #dc2626;">
                                <strong>⚠️ Unable to load case information</strong>
                                <p style="margin-top: 0.5rem; font-size: 0.9rem;">
                                    Please contact support for assistance. Case ID could not be retrieved.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <a href="<?php echo $logout_path; ?>" class="logout-link">
                        ← Logout
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- ============================================ -->
            <!-- NORMAL DASHBOARD (Frozen/Restricted/Normal) -->
            <!-- ============================================ -->
            
            <?php if ($is_flagged && $case_status !== 'cleared'): ?>
            <div class="fraud-alert-banner">
                <div class="fraud-alert-content">
                    <div class="fraud-alert-left">
                        <div class="fraud-alert-icon"><?php echo $alert_config['icon']; ?></div>
                        <div class="fraud-alert-text">
                            <div class="fraud-alert-title"><?php echo $alert_config['title']; ?></div>
                            <div class="fraud-alert-message"><?php echo $alert_config['message']; ?></div>
                        </div>
                    </div>
                    <?php if ($has_pending_appeal): ?>
                        <button class="fraud-alert-btn fraud-alert-btn-disabled" disabled>
                            ⏳ Appeal Pending
                        </button>
                    <?php else: ?>
                        <a href="sponsor_appeal_form.php?case_id=<?php echo $case_id; ?>" class="fraud-alert-btn fraud-alert-btn-primary">
                            ✍️ Submit Appeal
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="container">
                <div class="welcome-section">
                    <h1 class="welcome-title">Welcome, <?php echo htmlspecialchars($sponsor['username']); ?>!</h1>
                    <p class="welcome-subtitle">Choose an option to get started</p>
                </div>

                <div class="action-container">
                    <a href="sponser_profile.php" class="action-card">
                        <div class="action-icon">👤</div>
                        <h2 class="action-title">My Profile</h2>
                        <p class="action-description">View and manage your personal information and account settings</p>
                    </a>

                    <a href="my_home.php" class="action-card">
                        <div class="preview-label">Preview</div>
                        <div class="action-icon">🏠</div>
                        <h2 class="action-title">My Home</h2>
                        <p class="action-description">Access your dashboard, calendar, and sponsorship overview</p>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php 
    if (!$is_blocked):
        include __DIR__ . '/../components/common_scripts.php';
    endif;
    ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.action-card');
            
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });
        });
    </script>
</body>
</html>