<?php
session_start();

// Test data (remove when login is implemented)
$_SESSION['user_id'] = 1;
$sponsor_id = 14;

require_once 'db_config.php';

// Get sponsor details
$stmt = $conn->prepare("SELECT first_name, last_name FROM sponsors WHERE sponsor_id = ?");
$stmt->bind_param("i", $sponsor_id);
$stmt->execute();
$sponsor = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation History</title>
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
            padding: 2rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 600;
            color: #1128ce;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            font-size: 1rem;
            color: #7462aa;
        }

        .back-btn {
            display: inline-block;
            background-color: #e9e9f1;
            color: #211e27;
            padding: 0.6rem 1.2rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background-color: #c4a391;
            transform: translateX(-3px);
        }

        /* Summary Cards */
        .summary-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background: linear-gradient(135deg, #1128ce, #374ace);
            padding: 1.8rem;
            border-radius: 10px;
            color: white;
            box-shadow: 0 4px 15px rgba(17, 40, 206, 0.2);
        }

        .summary-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
        }

        .summary-card.accent {
            background: linear-gradient(135deg, #fc1f0c, #ff4433);
        }

        /* Filter Section */
        .filter-section {
            background-color: #e9e9f1;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .filter-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1128ce;
            margin-bottom: 1rem;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .filter-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #211e27;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-input {
            padding: 0.7rem;
            border: 2px solid #c4a391;
            border-radius: 5px;
            font-size: 0.9rem;
            background-color: white;
            transition: all 0.3s;
        }

        .filter-input:focus {
            outline: none;
            border-color: #374ace;
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #1128ce;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0d1fa3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #c4a391;
            color: #211e27;
        }

        .btn-secondary:hover {
            background-color: #a88d7d;
        }

        /* Donation Table */
        .table-section {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .table-header {
            background-color: #e9e9f1;
            padding: 1.5rem;
            border-bottom: 2px solid #c4a391;
        }

        .table-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1128ce;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #374ace;
            color: white;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #e9e9f1;
            transition: all 0.2s;
        }

        tbody tr:hover {
            background-color: #f8f8fc;
        }

        td {
            padding: 1rem;
            color: #211e27;
        }

        .child-name {
            font-weight: 600;
            color: #1128ce;
        }

        .amount {
            font-weight: 700;
            color: #fc1f0c;
            font-size: 1.1rem;
        }

        .payment-method {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            background-color: #e9e9f1;
            color: #7462aa;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7462aa;
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #7462aa;
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .summary-section {
                grid-template-columns: 1fr;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: scroll;
            }

            table {
                min-width: 600px;
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
            <div class="summary-card">
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
                            <td colspan="5" class="loading">Loading donations...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const sponsorId = <?php echo $sponsor_id; ?>;
        let allDonations = [];

        // Load donations on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadDonations();
        });

        async function loadDonations() {
            try {
                const response = await fetch(`get_donation_data.php?sponsor_id=${sponsorId}`);
                const result = await response.json();

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
                showError('Failed to load donation data');
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
                        <td colspan="5" class="empty-state">
                            <div class="empty-icon">📭</div>
                            <div>No donations found</div>
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
            
            // Update summary for filtered data
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
            
            // Recalculate summary from all donations
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
                    <td colspan="5" class="empty-state">
                        <div class="empty-icon">⚠️</div>
                        <div>${message}</div>
                    </td>
                </tr>
            `;
        }
    </script>
</body>
</html>