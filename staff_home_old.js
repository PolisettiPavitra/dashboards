// Staff Home Dashboard JavaScript

// Function to open dashboard detail pages
function openDashboard(dashboardType) {
    // Placeholder for navigation - you'll create these pages later
    const routes = {
        'children-needing': 'children_needing_sponsors.php',
        'children-having': 'children_having_sponsors.php',
        'total-sponsors': 'total_sponsors.php',
        'fraud-cases': 'fraud_cases.php'
    };
    
    // For now, just alert (replace with actual navigation later)
    console.log(`Navigating to: ${routes[dashboardType]}`);
    alert(`This will navigate to ${routes[dashboardType]} - Page to be created`);
    
    // Uncomment this when pages are ready:
    // window.location.href = routes[dashboardType];
}

// Optional: Add animation effect when page loads
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.dashboard-card');
    
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Optional: Function to refresh dashboard counts (if you want real-time updates)
function refreshDashboardCounts() {
    fetch('get_dashboard_counts.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('children-needing-count').textContent = data.children_needing;
            document.getElementById('children-having-count').textContent = data.children_having;
            document.getElementById('total-sponsors-count').textContent = data.total_sponsors;
            document.getElementById('fraud-cases-count').textContent = data.fraud_cases;
        })
        .catch(error => console.error('Error refreshing counts:', error));
}

// Optional: Auto-refresh every 30 seconds (uncomment if needed)
// setInterval(refreshDashboardCounts, 30000);