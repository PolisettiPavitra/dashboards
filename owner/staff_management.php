<?php
session_start();

// Test data (remove when login is implemented)
$_SESSION['user_id'] = 1;

require_once __DIR__ . '/../db_config.php';

// Handle AJAX request for staff members
if (isset($_GET['action']) && $_GET['action'] === 'get_staff') {
    header('Content-Type: application/json');
    
    try {
        // Get all staff members (Staff role only, not Owner)
        $staff_query = "
            SELECT 
                u.user_id,
                u.username,
                u.email,
                u.user_role,
                u.phone_no,
                u.created_at,
                COUNT(DISTINCT s.sponsorship_id) as total_sponsorships,
                COUNT(DISTINCT d.donation_id) as total_donations
            FROM users u
            LEFT JOIN sponsorships s ON u.user_id = s.sponsor_id AND u.user_role = 'Sponsor'
            LEFT JOIN donations d ON u.user_id = d.sponsor_id
            WHERE u.user_role = 'Staff'
            GROUP BY u.user_id
            ORDER BY u.created_at DESC
        ";

        $stmt = $conn->prepare($staff_query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $staff_members = [];
        $staff_count = 0;
        
        while ($row = $result->fetch_assoc()) {
            // Format dates
            $row['created_at_formatted'] = date('F j, Y', strtotime($row['created_at']));
            
            // Calculate days employed - FIXED VERSION
            try {
                $joined = new DateTime($row['created_at']);
                $now = new DateTime();
                $interval = $joined->diff($now);
                
                // Get total days - handle false case
                $days = ($interval->days !== false) ? (int)$interval->days : 0;
                
                // If created today, show at least 1 day
                if ($days === 0) {
                    $days = 1;
                }
                
                $months = floor($days / 30);
                $years = floor($days / 365);
                
                if ($years > 0) {
                    $row['employment_duration'] = $years . ' year' . ($years > 1 ? 's' : '');
                    $row['duration_category'] = $years >= 2 ? '2+' : '1-2';
                } else if ($months >= 6) {
                    $row['employment_duration'] = $months . ' month' . ($months > 1 ? 's' : '');
                    $row['duration_category'] = '6-12';
                } else if ($months > 0) {
                    $row['employment_duration'] = $months . ' month' . ($months > 1 ? 's' : '');
                    $row['duration_category'] = '0-6';
                } else {
                    $row['employment_duration'] = $days . ' day' . ($days > 1 ? 's' : '');
                    $row['duration_category'] = '0-6';
                }
            } catch (Exception $e) {
                // Fallback if date parsing fails
                $row['employment_duration'] = 'N/A';
                $row['duration_category'] = '0-6';
            }
            
            // Generate initials
            $row['initials'] = strtoupper(substr($row['username'], 0, 2));
            
            // Count staff
            $staff_count++;
            
            $staff_members[] = $row;
        }
        
        $stmt->close();
        $conn->close();
        
        // Calculate stats
        $total_count = count($staff_members);
        
        echo json_encode([
            'success' => true,
            'data' => $staff_members,
            'stats' => [
                'total_count' => $total_count,
                'staff_count' => $staff_count
            ]
        ]);
        exit();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching staff: ' . $e->getMessage()
        ]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Bright Yellow Splash/Aura Effects */
        body::before {
            content: '';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 150%;
            height: 150%;
            background: radial-gradient(circle at center, rgba(255, 237, 160, 0.8) 0%, rgba(254, 249, 195, 0.6) 15%, rgba(255, 253, 240, 0.4) 30%, transparent 60%);
            pointer-events: none;
            z-index: 0;
            filter: blur(80px);
        }

        body::after {
            content: '';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255, 243, 176, 0.5) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
            filter: blur(120px);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(254, 240, 138, 0.4);
            color: #3f3f46;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px rgba(254, 240, 138, 0.15);
        }

        .back-btn:hover {
            transform: translateX(-5px);
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 8px 16px rgba(254, 240, 138, 0.3);
            border-color: rgba(254, 240, 138, 0.6);
        }

        .page-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(254, 240, 138, 0.3);
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(254, 240, 138, 0.2);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #18181b;
            letter-spacing: -0.02em;
        }

        .page-subtitle {
            font-size: 1rem;
            color: #71717a;
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(254, 240, 138, 0.3);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(254, 240, 138, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 48px rgba(254, 240, 138, 0.35);
        }

        .stat-card.primary {
            background: linear-gradient(135deg, rgba(254, 249, 195, 0.8), rgba(253, 230, 138, 0.7));
            border: 1px solid rgba(254, 240, 138, 0.5);
        }

        .stat-label {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #71717a;
            margin-bottom: 0.75rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #18181b;
        }

        .controls-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(254, 240, 138, 0.3);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(254, 240, 138, 0.2);
        }

        .controls-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1.5rem;
            align-items: center;
        }

        .search-box {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid rgba(254, 240, 138, 0.4);
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            background: rgba(255, 255, 255, 0.7);
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: rgba(254, 240, 138, 0.8);
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 0 0 4px rgba(254, 249, 195, 0.3);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            opacity: 0.5;
        }

        .filter-buttons {
            display: flex;
            gap: 0.75rem;
        }

        .filter-btn {
            padding: 0.875rem 1.5rem;
            border: 2px solid rgba(254, 240, 138, 0.4);
            background: rgba(255, 255, 255, 0.7);
            color: #3f3f46;
            border-radius: 12px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .filter-btn:hover {
            background: rgba(255, 255, 255, 1);
            border-color: rgba(254, 240, 138, 0.6);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, rgba(254, 249, 195, 0.9), rgba(253, 230, 138, 0.8));
            color: #18181b;
            border-color: rgba(254, 240, 138, 0.6);
            box-shadow: 0 4px 12px rgba(254, 240, 138, 0.4);
        }

        .staff-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .staff-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(254, 240, 138, 0.3);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(254, 240, 138, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .staff-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(254, 240, 138, 0.4);
            background: rgba(255, 255, 255, 1);
            border-color: rgba(254, 240, 138, 0.5);
        }

        .staff-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, rgba(254, 249, 195, 0.9), rgba(253, 230, 138, 0.8));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #3f3f46;
            font-weight: 700;
            box-shadow: 0 8px 24px rgba(254, 240, 138, 0.4);
        }

        .staff-info {
            text-align: center;
        }

        .staff-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 0.5rem;
            letter-spacing: -0.01em;
        }

        .staff-role {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid rgba(34, 197, 94, 0.2);
            margin-bottom: 1rem;
        }



        .staff-details {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: rgba(254, 252, 232, 0.5);
            border-radius: 16px;
            border: 1px solid rgba(254, 240, 138, 0.3);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.95rem;
        }

        .detail-label {
            color: #71717a;
            font-weight: 600;
        }

        .detail-value {
            color: #18181b;
            font-weight: 700;
            text-align: right;
            word-break: break-word;
            max-width: 60%;
        }

        .loading {
            text-align: center;
            padding: 4rem 2rem;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(254, 240, 138, 0.3);
            border-top: 4px solid rgba(253, 230, 138, 0.8);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            font-size: 1.1rem;
            color: #71717a;
            font-weight: 600;
        }

        .empty-state {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(254, 240, 138, 0.3);
            border-radius: 24px;
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(254, 240, 138, 0.2);
        }

        .empty-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 0.75rem;
        }

        .empty-message {
            font-size: 1rem;
            color: #71717a;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .controls-grid {
                grid-template-columns: 1fr;
            }

            .filter-buttons {
                width: 100%;
                justify-content: center;
            }

            .staff-grid {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-btn">
            ← Back to Dashboard
        </a>

        <div class="page-header">
            <h1 class="page-title">Total Staff</h1>
            <p class="page-subtitle">View and manage all staff members in the organization</p>
        </div>

        <div class="stats-container">
            <div class="stat-card primary">
                <div class="stat-label">Total Staff</div>
                <div class="stat-value" id="totalCount">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Members</div>
                <div class="stat-value" id="staffCount">0</div>
            </div>
        </div>

        <div class="controls-section">
            <div class="controls-grid">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" 
                           class="search-input" 
                           id="searchInput" 
                           placeholder="Search by name or email...">
                </div>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="0-6">0-6 Months</button>
                    <button class="filter-btn" data-filter="6-12">6-12 Months</button>
                    <button class="filter-btn" data-filter="1-2">1-2 Years</button>
                    <button class="filter-btn" data-filter="2+">2+ Years</button>
                </div>
            </div>
        </div>

        <div class="loading" id="loadingState">
            <div class="loading-spinner"></div>
            <p class="loading-text">Loading staff members...</p>
        </div>

        <div class="staff-grid" id="staffGrid" style="display: none;"></div>

        <div class="empty-state" id="emptyState" style="display: none;">
            <div class="empty-icon">No Staff</div>
            <h2 class="empty-title">No Staff Members Found</h2>
            <p class="empty-message">No staff members match your search criteria.</p>
        </div>
    </div>

    <script>
        let allStaff = [];
        let currentFilter = 'all';

        document.addEventListener('DOMContentLoaded', function() {
            fetchStaff();
            setupEventListeners();
        });

        async function fetchStaff() {
            const loadingState = document.getElementById('loadingState');
            const staffGrid = document.getElementById('staffGrid');
            const emptyState = document.getElementById('emptyState');
            
            try {
                loadingState.style.display = 'block';
                staffGrid.style.display = 'none';
                emptyState.style.display = 'none';
                
                const response = await fetch(`?action=get_staff`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Failed to fetch data');
                }
                
                allStaff = result.data || [];
                updateStatistics(result.stats);
                
                loadingState.style.display = 'none';
                
                if (allStaff.length === 0) {
                    emptyState.style.display = 'block';
                } else {
                    staffGrid.style.display = 'grid';
                    renderStaffCards(allStaff);
                }
                
            } catch (error) {
                console.error('Error fetching staff:', error);
                loadingState.style.display = 'none';
                emptyState.style.display = 'block';
                document.querySelector('.empty-title').textContent = 'Error Loading Data';
                document.querySelector('.empty-message').textContent = error.message || 'Please try again later.';
            }
        }

        function updateStatistics(stats) {
            if (!stats) return;
            document.getElementById('totalCount').textContent = stats.total_count || 0;
            document.getElementById('staffCount').textContent = stats.staff_count || 0;
        }

        function renderStaffCards(staff) {
            const staffGrid = document.getElementById('staffGrid');
            
            if (!staff || staff.length === 0) {
                staffGrid.innerHTML = '<p class="empty-state">No staff members match your search criteria.</p>';
                return;
            }
            
            staffGrid.innerHTML = staff.map(member => `
                <div class="staff-card" data-role="${member.user_role}" data-name="${member.username}" data-email="${member.email}" data-duration="${member.duration_category}">
                    <div class="staff-photo">
                        ${member.initials || '??'}
                    </div>
                    <div class="staff-info">
                        <div class="staff-name">${member.username}</div>
                        <span class="staff-role ${member.user_role.toLowerCase()}">${member.user_role}</span>
                        <div class="staff-details">
                            <div class="detail-row">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value">${member.email || 'N/A'}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Phone:</span>
                                <span class="detail-value">${member.phone_no || 'N/A'}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Joined:</span>
                                <span class="detail-value">${member.created_at_formatted}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Duration:</span>
                                <span class="detail-value">${member.employment_duration}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function setupEventListeners() {
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', handleSearch);
            
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.dataset.filter;
                    applyFilters();
                });
            });
        }

        function handleSearch(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            applyFilters(searchTerm);
        }

        function applyFilters(searchTerm = '') {
            const searchInput = document.getElementById('searchInput');
            const currentSearchTerm = searchTerm || searchInput.value.toLowerCase().trim();
            
            let filteredStaff = allStaff;
            
            // Apply duration filter
            if (currentFilter !== 'all') {
                filteredStaff = filteredStaff.filter(member => member.duration_category === currentFilter);
            }
            
            // Apply search filter
            if (currentSearchTerm) {
                filteredStaff = filteredStaff.filter(member => {
                    const username = member.username.toLowerCase();
                    const email = (member.email || '').toLowerCase();
                    return username.includes(currentSearchTerm) || email.includes(currentSearchTerm);
                });
            }
            
            renderStaffCards(filteredStaff);
            
            const staffGrid = document.getElementById('staffGrid');
            const emptyState = document.getElementById('emptyState');
            
            if (filteredStaff.length === 0) {
                staffGrid.style.display = 'none';
                emptyState.style.display = 'block';
                document.querySelector('.empty-title').textContent = 'No Staff Members Found';
                document.querySelector('.empty-message').textContent = 'Try adjusting your search or filter criteria.';
            } else {
                staffGrid.style.display = 'grid';
                emptyState.style.display = 'none';
            }
        }
    </script>
</body>
</html>