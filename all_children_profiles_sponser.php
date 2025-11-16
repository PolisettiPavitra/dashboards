<?php
session_start();
require_once __DIR__ . '/db_config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// If logged in, get sponsor info
if ($is_logged_in) {
    require_once __DIR__ . '/components/sidebar_config.php';
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT s.sponsor_id FROM users u
              INNER JOIN sponsors s ON u.user_id = s.user_id
              WHERE u.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $sponsor_data = $result->fetch_assoc();
        $sponsor_id = intval($sponsor_data['sponsor_id']);
    }
    $stmt->close();
    
    // Initialize sidebar for sponsor
    $sidebar_menu = initSidebar('sponsor', 'all_children_profiles_sponser.php');
    $logout_path = 'signup_and_login/logout.php';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sponsor a Child - Transform Lives</title>
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

/* MEDIUM YELLOW SPLASH IN CENTER - Same as owner page */
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

/* Main Wrapper - Fixed for proper header spacing */
.main-wrapper {
    margin-left: 0;
    padding-top: 100px; /* Space for header */
    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 1;
    min-height: calc(100vh - 100px);
}

.main-wrapper.sidebar-open {
    margin-left: 280px;
}

/* When logged in, adjust for fixed header */
body.logged-in .main-wrapper {
    padding-top: 80px; /* Height of logged-in header */
}

/* When not logged in, adjust for non-logged-in header */
body:not(.logged-in) .main-wrapper {
    padding-top: 80px; /* Height of non-logged-in header */
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2.5rem;
    position: relative;
    z-index: 1;
}

/* Hero Section - Glassmorphism */
.hero-section {
    text-align: center;
    margin-bottom: 3rem;
    padding: 3rem 2rem;
    background: rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 32px;
    border: 1px solid rgba(255, 255, 255, 0.7);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.hero-title {
    font-size: 3rem;
    font-weight: 800;
    color: rgba(0, 0, 0, 0.85);
    margin-bottom: 1rem;
    letter-spacing: -0.02em;
}

.hero-subtitle {
    font-size: 1.125rem;
    color: rgba(0, 0, 0, 0.6);
    font-weight: 500;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

/* Filters Section - Glassmorphism */
.filters-section {
    background: rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 28px;
    border: 1px solid rgba(255, 255, 255, 0.7);
    padding: 2.5rem;
    margin-bottom: 3rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.filter-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: rgba(0, 0, 0, 0.5);
    text-transform: uppercase;
    letter-spacing: 1.2px;
}

.filter-input,
.filter-select {
    padding: 1rem 1.25rem;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    font-size: 0.95rem;
    color: rgba(0, 0, 0, 0.85);
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s ease;
}

.filter-input::placeholder {
    color: rgba(0, 0, 0, 0.4);
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: rgba(255, 255, 255, 0.4);
    background: rgba(255, 255, 255, 0.25);
    box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
}

.filter-select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23000000' opacity='0.6' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    padding-right: 3rem;
}

.filter-select option {
    background: rgba(255, 255, 255, 0.95);
    color: rgba(0, 0, 0, 0.85);
}

.filter-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.3);
}

.filter-button {
    padding: 1rem 2rem;
    border-radius: 14px;
    border: none;
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    color: rgba(0, 0, 0, 0.85);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Inter', sans-serif;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.filter-button:hover {
    background: rgba(255, 255, 255, 0.35);
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.filter-button:active {
    transform: translateY(-1px);
}

.reset-button {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.reset-button:hover {
    background: rgba(255, 255, 255, 0.15);
}

/* Results Info */
.results-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1rem 0;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.results-info.show {
    opacity: 1;
}

.results-count {
    font-size: 1rem;
    color: rgba(0, 0, 0, 0.6);
    font-weight: 500;
}

.results-count strong {
    color: rgba(0, 0, 0, 0.85);
    font-weight: 700;
}

/* Children Grid */
.children-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
    min-height: 400px;
}

.child-card {
    background: rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.7);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
    cursor: pointer;
    position: relative;
    opacity: 0;
    transform: translateY(20px);
}

.child-card.loaded {
    opacity: 1;
    transform: translateY(0);
}

.child-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at top left, rgba(255, 237, 160, 0.15), transparent 70%);
    opacity: 0;
    transition: opacity 0.4s ease;
    pointer-events: none;
}

.child-card:hover {
    transform: translateY(-12px);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12),
                inset 0 1px 0 rgba(255, 255, 255, 1);
    border-color: rgba(255, 237, 160, 0.8);
}

.child-card:hover::before {
    opacity: 1;
}

.child-image {
    width: 100%;
    height: 300px;
    background: linear-gradient(135deg, rgba(255, 237, 160, 0.3), rgba(254, 249, 195, 0.2));
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.child-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.child-card:hover .child-image img {
    transform: scale(1.05);
}

.child-image .initials {
    font-size: 5rem;
    font-weight: 800;
    color: rgba(0, 0, 0, 0.3);
}

.child-info {
    padding: 1.5rem;
}

.child-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: rgba(0, 0, 0, 0.85);
    margin-bottom: 0.75rem;
    letter-spacing: -0.01em;
}

.child-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.child-detail {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    color: rgba(0, 0, 0, 0.6);
    font-weight: 500;
}

.view-profile-btn {
    width: 100%;
    padding: 1rem;
    border-radius: 14px;
    border: none;
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    color: rgba(0, 0, 0, 0.85);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Inter', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.view-profile-btn:hover {
    background: rgba(255, 255, 255, 0.35);
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

/* Loading Indicator */
.loading-indicator {
    text-align: center;
    padding: 3rem;
    display: none;
}

.loading-indicator.show {
    display: block;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(0, 0, 0, 0.1);
    border-top-color: rgba(0, 0, 0, 0.3);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    font-size: 1rem;
    color: rgba(0, 0, 0, 0.6);
    font-weight: 500;
}

/* No Results */
.no-results {
    text-align: center;
    padding: 5rem 2rem;
    display: none;
    background: rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 28px;
    border: 1px solid rgba(255, 255, 255, 0.7);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.no-results.show {
    display: block;
}

.no-results-icon {
    font-size: 5rem;
    margin-bottom: 1.5rem;
    opacity: 0.3;
}

.no-results-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: rgba(0, 0, 0, 0.85);
    margin-bottom: 0.75rem;
    letter-spacing: -0.01em;
}

.no-results-text {
    font-size: 1.125rem;
    color: rgba(0, 0, 0, 0.6);
    font-weight: 400;
}

/* Error Message */
.error-message {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #991b1b;
    padding: 1rem 1.5rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    display: none;
    backdrop-filter: blur(10px);
    font-weight: 500;
}

.error-message.show {
    display: block;
}

@media (max-width: 1200px) {
    .main-wrapper.sidebar-open {
        margin-left: 0;
    }

    .children-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .container {
        padding: 1.5rem;
    }

    .hero-title {
        font-size: 2.25rem;
    }

    .hero-subtitle {
        font-size: 1rem;
    }

    .filters-grid {
        grid-template-columns: 1fr;
    }

    .filter-actions {
        flex-direction: column;
    }

    .filter-button,
    .reset-button {
        width: 100%;
    }

    .children-grid {
        grid-template-columns: 1fr;
    }

    .results-info {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    body.logged-in .main-wrapper,
    body:not(.logged-in) .main-wrapper {
        padding-top: 70px;
    }
}
    </style>
</head>
<body<?php echo $is_logged_in ? ' class="logged-in"' : ''; ?>>
    <?php if ($is_logged_in): ?>
        <?php 
        include __DIR__ . '/components/header.php';
        include __DIR__ . '/components/sidebar.php'; 
        ?>
    <?php else: ?>
        <?php include __DIR__ . '/components/non_loggedin_header.php'; ?>
    <?php endif; ?>

    <div class="main-wrapper" id="mainWrapper">
        <div class="container">
            <div class="hero-section">
                <h1 class="hero-title">Transform a Child's Future</h1>
                <p class="hero-subtitle">Every child deserves a chance to thrive. Find a child to sponsor and make a lasting impact on their life.</p>
            </div>

            <div class="error-message" id="errorMessage"></div>

            <div class="filters-section">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Search by Name</label>
                        <input type="text" id="searchName" class="filter-input" placeholder="Enter child's name...">
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Age Range</label>
                        <select id="ageFilter" class="filter-select">
                            <option value="">All Ages</option>
                            <option value="0-5">0-5 years</option>
                            <option value="6-10">6-10 years</option>
                            <option value="11-15">11-15 years</option>
                            <option value="16-18">16-18 years</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Sort By</label>
                        <select id="sortBy" class="filter-select">
                            <option value="name_asc">Name (A-Z)</option>
                            <option value="name_desc">Name (Z-A)</option>
                            <option value="age_asc">Age (Youngest First)</option>
                            <option value="age_desc">Age (Oldest First)</option>
                            <option value="newest">Newest First</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button class="filter-button reset-button" onclick="resetFilters()">Reset</button>
                    <button class="filter-button" onclick="applyFilters()">Apply Filters</button>
                </div>
            </div>

            <div class="results-info" id="resultsInfo">
                <div class="results-count">
                    Showing <strong id="childrenCount">0</strong> children
                </div>
            </div>

            <div class="children-grid" id="childrenGrid"></div>

            <div class="loading-indicator" id="loadingIndicator">
                <div class="loading-spinner"></div>
                <p class="loading-text">Loading children...</p>
            </div>

            <div class="no-results" id="noResults">
                <div class="no-results-icon">🔍</div>
                <h2 class="no-results-title">No Children Found</h2>
                <p class="no-results-text">Try adjusting your filters to see more results</p>
            </div>
        </div>
    </div>

    <?php if ($is_logged_in): ?>
        <?php include __DIR__ . '/components/common_scripts.php'; ?>
    <?php endif; ?>

    <script>
        let currentPage = 0;
        let isLoading = false;
        let hasMore = true;
        let currentFilters = {};
        let totalChildren = 0;

        // Calculate age from date of birth
        function calculateAge(dob) {
            const birthDate = new Date(dob);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            return age;
        }

        // Create child card HTML
        function createChildCard(child) {
            const age = calculateAge(child.dob);
            const initials = child.first_name.charAt(0) + child.last_name.charAt(0);
            
            let imageHTML = '';
            if (child.profile_picture && child.profile_picture !== '') {
                imageHTML = `<img src="${child.profile_picture}" alt="${child.first_name} ${child.last_name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                             <span class="initials" style="display:none;">${initials}</span>`;
            } else {
                imageHTML = `<span class="initials">${initials}</span>`;
            }

            return `
                <div class="child-card" onclick="viewProfile(${child.child_id})">
                    <div class="child-image">
                        ${imageHTML}
                    </div>
                    <div class="child-info">
                        <h3 class="child-name">${child.first_name} ${child.last_name}</h3>
                        <div class="child-details">
                            <div class="child-detail">
                                <span>${age} years old</span>
                            </div>
                            <div class="child-detail">
                                <span>${child.gender}</span>
                            </div>
                        </div>
                        <button class="view-profile-btn" onclick="event.stopPropagation(); viewProfile(${child.child_id})">
                            <span>View Profile</span>
                            <span>→</span>
                        </button>
                    </div>
                </div>
            `;
        }

        // Show error message
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.classList.add('show');
            setTimeout(() => {
                errorDiv.classList.remove('show');
            }, 5000);
        }

        // Update results count
        function updateResultsInfo() {
            const resultsInfo = document.getElementById('resultsInfo');
            const countElement = document.getElementById('childrenCount');
            
            if (totalChildren > 0) {
                countElement.textContent = totalChildren;
                resultsInfo.classList.add('show');
            } else {
                resultsInfo.classList.remove('show');
            }
        }

        // Load children
        async function loadChildren(reset = false) {
            if (isLoading || (!hasMore && !reset)) return;

            isLoading = true;
            const loadingIndicator = document.getElementById('loadingIndicator');
            const childrenGrid = document.getElementById('childrenGrid');
            const noResults = document.getElementById('noResults');

            if (reset) {
                currentPage = 0;
                hasMore = true;
                totalChildren = 0;
                childrenGrid.innerHTML = '';
                noResults.classList.remove('show');
            }

            loadingIndicator.classList.add('show');

            try {
                const params = new URLSearchParams({
                    page: currentPage,
                    ...currentFilters
                });

                const response = await fetch(`get_children.php?${params}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();

                if (data.success) {
                    if (data.children.length === 0 && currentPage === 0) {
                        noResults.classList.add('show');
                        totalChildren = 0;
                    } else {
                        data.children.forEach((child, index) => {
                            const cardHTML = createChildCard(child);
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = cardHTML;
                            const cardElement = tempDiv.firstElementChild;
                            
                            childrenGrid.appendChild(cardElement);
                            
                            setTimeout(() => {
                                cardElement.classList.add('loaded');
                            }, index * 50);
                        });

                        if (currentPage === 0) {
                            totalChildren = data.children.length;
                        } else {
                            totalChildren += data.children.length;
                        }

                        hasMore = data.has_more !== false && data.children.length === 9;
                        currentPage++;
                    }
                    
                    updateResultsInfo();
                } else {
                    showError(data.message || 'Failed to load children');
                }
            } catch (error) {
                console.error('Error loading children:', error);
                showError('Failed to load children. Please try again.');
            } finally {
                isLoading = false;
                loadingIndicator.classList.remove('show');
            }
        }

        // Apply filters
        function applyFilters() {
            const searchValue = document.getElementById('searchName').value.trim();
            const ageValue = document.getElementById('ageFilter').value;
            const sortValue = document.getElementById('sortBy').value;

            currentFilters = {};
            
            if (searchValue) {
                currentFilters.search = searchValue;
            }
            if (ageValue) {
                currentFilters.age_range = ageValue;
            }
            if (sortValue) {
                currentFilters.sort = sortValue;
            }

            loadChildren(true);
        }

        // Reset filters
        function resetFilters() {
            document.getElementById('searchName').value = '';
            document.getElementById('ageFilter').value = '';
            document.getElementById('sortBy').value = 'name_asc';
            currentFilters = {};
            loadChildren(true);
        }

        // View child profile
        function viewProfile(childId) {
            window.location.href = `child_profile.php?child_id=${childId}`;
        }

        // Infinite scroll
        function handleScroll() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;

            if (scrollTop + windowHeight >= documentHeight - 500 && hasMore && !isLoading) {
                loadChildren();
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadChildren();

            window.addEventListener('scroll', handleScroll);

            // Enter key to apply filters
            document.getElementById('searchName').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    applyFilters();
                }
            });
        });
    </script>
</body>
</html>