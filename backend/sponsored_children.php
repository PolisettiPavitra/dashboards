<?php
// TEMPORARY: For testing without login - REMOVE THIS AFTER IMPLEMENTING LOGIN
session_start();
$_SESSION['user_id'] = 1; // Change to a valid user_id from your database
$sponsor_id = 14; // Test sponsor ID

// Include database connection
require_once 'db_config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sponsored Children</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header Section */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid #374ace;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            background: linear-gradient(135deg, #1128ce, #2b3ed5);
            color: white;
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            transform: translateX(-5px);
            box-shadow: 0 5px 15px rgba(17, 40, 206, 0.3);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 600;
            color: #1128ce;
        }

        .stats-summary {
            display: flex;
            gap: 2rem;
            font-size: 0.95rem;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, rgba(233, 233, 241, 0.8), rgba(233, 233, 241, 0.4));
            border-radius: 8px;
            border: 2px solid #e9e9f1;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1128ce;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #7462aa;
            font-weight: 500;
        }

        /* Search and Filter Section */
        .controls-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 2px solid #e9e9f1;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #374ace;
            box-shadow: 0 0 0 3px rgba(55, 74, 206, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: #7462aa;
            font-size: 1.1rem;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .filter-btn {
            padding: 0.7rem 1.2rem;
            border: 2px solid #374ace;
            background-color: white;
            color: #374ace;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: linear-gradient(135deg, #1128ce, #2b3ed5);
            color: white;
            border-color: #1128ce;
        }

        /* Children Grid */
        .children-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .child-card {
            background: linear-gradient(135deg, rgba(233, 233, 241, 0.8), rgba(233, 233, 241, 0.4));
            border: 2px solid #e9e9f1;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s;
            cursor: pointer;
        }

        .child-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(17, 40, 206, 0.15);
            border-color: #374ace;
        }

        .child-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            border: 4px solid #374ace;
            background: linear-gradient(135deg, #c4a391, #e9e9f1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #7462aa;
            font-weight: 600;
        }

        .child-photo img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .child-info {
            text-align: center;
        }

        .child-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #211e27;
            margin-bottom: 0.5rem;
        }

        .child-details {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            margin: 1rem 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
        }

        .detail-label {
            color: #7462aa;
            font-weight: 500;
        }

        .detail-value {
            color: #211e27;
            font-weight: 600;
        }

        .sponsorship-info {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #c4a391;
        }

        .sponsorship-date {
            font-size: 0.85rem;
            color: #7462aa;
            text-align: center;
        }

        .view-btn {
            width: 100%;
            margin-top: 1rem;
            padding: 0.7rem;
            background-color: #fc1f0c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .view-btn:hover {
            background-color: #d91a09;
            transform: scale(1.02);
        }

        /* Loading and Empty States */
        .loading {
            text-align: center;
            padding: 3rem;
            font-size: 1.2rem;
            color: #7462aa;
        }

        .loading-spinner {
            border: 4px solid #e9e9f1;
            border-top: 4px solid #374ace;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.5rem;
            color: #211e27;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .empty-message {
            font-size: 1rem;
            color: #7462aa;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .stats-summary {
                width: 100%;
                justify-content: space-around;
            }

            .controls-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-buttons {
                width: 100%;
                justify-content: center;
            }

            .children-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="page-header">
            <div class="header-left">
                <a href="sponser_profile.php" class="back-btn">
                    ← Back
                </a>
                <h1 class="page-title">Sponsored Children</h1>
            </div>
            <div class="stats-summary">
                <div class="stat-item">
                    <span class="stat-value" id="totalCount">0</span>
                    <span class="stat-label">Total Sponsored</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="activeCount">0</span>
                    <span class="stat-label">Active</span>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="controls-section">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" 
                       class="search-input" 
                       id="searchInput" 
                       placeholder="Search by child name...">
            </div>
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="Male">Boys</button>
                <button class="filter-btn" data-filter="Female">Girls</button>
            </div>
        </div>

        <!-- Loading State -->
        <div class="loading" id="loadingState">
            <div class="loading-spinner"></div>
            <p>Loading sponsored children...</p>
        </div>

        <!-- Children Grid -->
        <div class="children-grid" id="childrenGrid" style="display: none;">
            <!-- Child cards will be dynamically inserted here -->
        </div>

        <!-- Empty State -->
        <div class="empty-state" id="emptyState" style="display: none;">
            <div class="empty-icon">📭</div>
            <h2 class="empty-title">No Children Found</h2>
            <p class="empty-message">You haven't sponsored any children yet.</p>
        </div>
    </div>

    <script>
        // Pass PHP sponsor_id to JavaScript
        const SPONSOR_ID = <?php echo $sponsor_id; ?>;
    </script>
    <script src="sponsored_children.js"></script>
</body>
</html>