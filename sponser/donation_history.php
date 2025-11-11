<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../db_config.php';

$user_id = $_SESSION['user_id'];

// Get sponsor_id from database
$query = "SELECT sponsor_id FROM sponsors WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Sponsor not found. Please make sure this user account is registered as a sponsor.");
}

$sponsor_data = $result->fetch_assoc();
$sponsor_id = intval($sponsor_data['sponsor_id']);
$stmt->close();

// Handle AJAX request for donation data
if (isset($_GET['action']) && $_GET['action'] === 'get_donations') {
    header('Content-Type: application/json');
    
    try {
        // Get all donations with child details
        $donations_query = "
            SELECT 
                d.donation_id,
                d.amount,
                d.donation_date,
                d.payment_method,
                CONCAT(c.first_name, ' ', c.last_name) as child_name,
                c.child_id
            FROM donations d
            INNER JOIN children c ON d.child_id = c.child_id
            WHERE d.sponsor_id = ?
            ORDER BY d.donation_date DESC
        ";

        $stmt = $conn->prepare($donations_query);
        $stmt->bind_param("i", $sponsor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $donations = [];
        $total_amount = 0;
        
        while ($row = $result->fetch_assoc()) {
            $donations[] = [
                'donation_id' => $row['donation_id'],
                'child_name' => $row['child_name'],
                'amount' => $row['amount'],
                'donation_date' => $row['donation_date'],
                'payment_method' => $row['payment_method'],
                'child_id' => $row['child_id']
            ];
            $total_amount += floatval($row['amount']);
        }
        
        $stmt->close();
        $conn->close();
        
        // Calculate stats
        $total_count = count($donations);
        $average_amount = $total_count > 0 ? ($total_amount / $total_count) : 0;
        
        echo json_encode([
            'success' => true,
            'donations' => $donations,
            'stats' => [
                'total_amount' => $total_amount,
                'total_count' => $total_count,
                'average_amount' => $average_amount
            ]
        ]);
        exit();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching donations: ' . $e->getMessage()
        ]);
        exit();
    }
}

// Get sponsor details for display
$stmt = $conn->prepare("SELECT first_name, last_name FROM sponsors WHERE sponsor_id = ?");
$stmt->bind_param("i", $sponsor_id);
$stmt->execute();
$sponsor = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation History</title>
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

        /* Additional ambient glow */
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

        /* Back Button */
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

        /* Header Section */
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
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            font-size: 1rem;
            color: #71717a;
            font-weight: 500;
        }

        /* Summary Cards */
        .summary-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(254, 240, 138, 0.3);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(254, 240, 138, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 48px rgba(254, 240, 138, 0.35);
        }

        .summary-card.primary {
            background: linear-gradient(135deg, rgba(254, 249, 195, 0.8), rgba(253, 230, 138, 0.7));
            border: 1px solid rgba(254, 240, 138, 0.5);
        }

        .summary-card.accent {
            background: linear-gradient(135deg, rgba(254, 243, 199, 0.8), rgba(253, 224, 71, 0.6));
            border: 1px solid rgba(254, 240, 138, 0.5);
        }

        .summary-label {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #71717a;
            margin-bottom: 0.75rem;
        }

        .summary-value {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #18181b;
        }

        /* Filter Section */
        .filter-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(254, 240, 138, 0.3);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(254, 240, 138, 0.2);
        }

        .filter-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 1.5rem;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #71717a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .filter-input {
            padding: 0.875rem 1rem;
            border: 2px solid rgba(254, 240, 138, 0.4);
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            background: rgba(255, 255, 255, 0.7);
            transition: all 0.3s;
        }

        .filter-input:focus {
            outline: none;
            border-color: rgba(254, 240, 138, 0.8);
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 0 0 4px rgba(254, 249, 195, 0.3);
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary {
            background: linear-gradient(135deg, rgba(254, 249, 195, 0.9), rgba(253, 230, 138, 0.8));
            color: #18181b;
            border: 1px solid rgba(254, 240, 138, 0.5);
            box-shadow: 0 4px 12px rgba(254, 240, 138, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(254, 240, 138, 0.5);
            background: linear-gradient(135deg, rgba(254, 249, 195, 1), rgba(253, 230, 138, 0.9));
        }

        .btn-secondary {
            background: rgba(254, 240, 138, 0.2);
            color: #3f3f46;
            border: 2px solid rgba(254, 240, 138, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(254, 240, 138, 0.3);
        }

        /* Donation Table */
        .table-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(254, 240, 138, 0.3);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(254, 240, 138, 0.2);
        }

        .table-header {
            padding: 2rem;
            background: rgba(254, 252, 232, 0.4);
            border-bottom: 2px solid rgba(254, 240, 138, 0.3);
        }

        .table-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #18181b;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: rgba(254, 252, 232, 0.5);
        }

        th {
            padding: 1.25rem 1.5rem;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
            color: #71717a;
        }

        tbody tr {
            border-bottom: 1px solid rgba(254, 240, 138, 0.2);
            transition: all 0.2s;
        }

        tbody tr:hover {
            background: rgba(254, 252, 232, 0.4);
        }

        td {
            padding: 1.25rem 1.5rem;
            color: #18181b;
            font-size: 0.95rem;
        }

        .child-name {
            font-weight: 700;
            color: #3f3f46;
        }

        .amount {
            font-weight: 800;
            color: #18181b;
            font-size: 1.125rem;
        }

        .payment-method {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: rgba(254, 249, 195, 0.5);
            color: #3f3f46;
            border: 1px solid rgba(254, 240, 138, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 0.5rem;
        }

        .empty-message {
            font-size: 1rem;
            color: #71717a;
        }

        .loading {
            text-align: center;
            padding: 3rem 2rem;
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

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .summary-section {
                grid-template-columns: 1fr;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                flex-direction: column;
            }

            .table-container {
                overflow-x: scroll;
            }

            table {
                min-width: 600px;
            }

            .summary-value {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="sponser_profile.php" class="back-btn">← Back to Dashboard</a>

        <div class="page-header">
            <h1 class="page-title">Donation History</h1>
            <p class="page-subtitle">Track all your contributions and support</p>
        </div>

        <!-- Summary Cards -->
        <div class="summary-section">
            <div class="summary-card primary">
                <div class="summary-label">Total Donated</div>
                <div class="summary-value" id="totalDonated">₹0</div>
            </div>
            <div class="summary-card accent">
                <div class="summary-label">Total Donations</div>
                <div class="summary-value" id="totalCount">0</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Average Donation</div>
                <div class="summary-value" id="averageDonation">₹0</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <h3 class="filter-title">Filter Donations</h3>
            <div class="filters">
                <div class="filter-group">
                    <label class="filter-label">From Date</label>
                    <input type="date" id="fromDate" class="filter-input">
                </div>
                <div class="filter-group">
                    <label class="filter-label">To Date</label>
                    <input type="date" id="toDate" class="filter-input">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Child</label>
                    <select id="childFilter" class="filter-input">
                        <option value="">All Children</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Payment Method</label>
                    <select id="methodFilter" class="filter-input">
                        <option value="">All Methods</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="UPI">UPI</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
                <button class="btn btn-secondary" onclick="resetFilters()">Reset</button>
            </div>
        </div>

        <!-- Donation Table -->
        <div class="table-section">
            <div class="table-header">
                <h2 class="table-title">All Donations</h2>
            </div>
            <div class="table-container">
                <table id="donationTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Child Name</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Payment Method</th>
                        </tr>
                    </thead>
                    <tbody id="donationTableBody">
                        <tr>
                            <td colspan="5">
                                <div class="loading">
                                    <div class="loading-spinner"></div>
                                    <p class="loading-text">Loading donations...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const sponsorId = <?php echo $sponsor_id; ?>;
        let allDonations = [];

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Loading donations for sponsor:', sponsorId);
            loadDonations();
        });

        async function loadDonations() {
            try {
                console.log('Fetching donations...');
                // Call the same page with action parameter
                const response = await fetch(`donation_history.php?action=get_donations&sponsor_id=${sponsorId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('Response:', result);

                if (result.success) {
                    allDonations = result.donations;
                    updateSummary(result.stats);
                    populateChildFilter(result.donations);
                    displayDonations(result.donations);
                } else {
                    showError(result.message);
                }
            } catch (error) {
                console.error('Error loading donations:', error);
                showError('Failed to load donation data: ' + error.message);
            }
        }

        function updateSummary(stats) {
            document.getElementById('totalDonated').textContent = `₹${stats.total_amount.toLocaleString('en-IN')}`;
            document.getElementById('totalCount').textContent = stats.total_count;
            const average = stats.total_count > 0 ? (stats.total_amount / stats.total_count).toFixed(0) : 0;
            document.getElementById('averageDonation').textContent = `₹${parseFloat(average).toLocaleString('en-IN')}`;
        }

        function populateChildFilter(donations) {
            const childFilter = document.getElementById('childFilter');
            const uniqueChildren = [...new Set(donations.map(d => d.child_name))];
            
            uniqueChildren.forEach(childName => {
                const option = document.createElement('option');
                option.value = childName;
                option.textContent = childName;
                childFilter.appendChild(option);
            });
        }

        function displayDonations(donations) {
            const tbody = document.getElementById('donationTableBody');
            
            if (donations.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-icon">📭</div>
                                <h3 class="empty-title">No donations found</h3>
                                <p class="empty-message">Try adjusting your filter criteria</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = donations.map((donation, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td class="child-name">${donation.child_name}</td>
                    <td class="amount">₹${parseFloat(donation.amount).toLocaleString('en-IN')}</td>
                    <td>${formatDate(donation.donation_date)}</td>
                    <td><span class="payment-method">${donation.payment_method}</span></td>
                </tr>
            `).join('');
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-IN', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }

        function applyFilters() {
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            const childFilter = document.getElementById('childFilter').value;
            const methodFilter = document.getElementById('methodFilter').value;

            let filtered = allDonations;

            if (fromDate) {
                filtered = filtered.filter(d => new Date(d.donation_date) >= new Date(fromDate));
            }

            if (toDate) {
                filtered = filtered.filter(d => new Date(d.donation_date) <= new Date(toDate));
            }

            if (childFilter) {
                filtered = filtered.filter(d => d.child_name === childFilter);
            }

            if (methodFilter) {
                filtered = filtered.filter(d => d.payment_method === methodFilter);
            }

            displayDonations(filtered);
            
            const totalAmount = filtered.reduce((sum, d) => sum + parseFloat(d.amount), 0);
            const totalCount = filtered.length;
            updateSummary({
                total_amount: totalAmount,
                total_count: totalCount
            });
        }

        function resetFilters() {
            document.getElementById('fromDate').value = '';
            document.getElementById('toDate').value = '';
            document.getElementById('childFilter').value = '';
            document.getElementById('methodFilter').value = '';
            
            displayDonations(allDonations);
            
            const totalAmount = allDonations.reduce((sum, d) => sum + parseFloat(d.amount), 0);
            updateSummary({
                total_amount: totalAmount,
                total_count: allDonations.length
            });
        }

        function showError(message) {
            const tbody = document.getElementById('donationTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-icon">⚠️</div>
                            <h3 class="empty-title">Error Loading Data</h3>
                            <p class="empty-message">${message}</p>
                        </div>
                    </td>
                </tr>
            `;
        }
    </script>
</body>
</html>