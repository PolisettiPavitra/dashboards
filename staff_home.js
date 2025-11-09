// Staff Home Dashboard JavaScript - Enhanced Version

// Function to open dashboard detail pages
function openDashboard(dashboardType) {
    const routes = {
        'children-needing': 'children_needing_sponsors.php',
        'children-having': 'children_having_sponsors.php',
        'total-sponsors': 'total_sponsors.php',
        'fraud-cases': 'fraud_cases.php'
    };
    
    console.log(`Navigating to: ${routes[dashboardType]}`);
    
    // Uncomment this when pages are ready:
    // window.location.href = routes[dashboardType];
    
    // Temporary alert - remove when pages are created
    alert(`This will navigate to ${routes[dashboardType]} - Page to be created`);
}

// Animated number counter function
function animateCounter(element, start, end, duration) {
    let startTime = null;
    
    function animation(currentTime) {
        if (!startTime) startTime = currentTime;
        const progress = Math.min((currentTime - startTime) / duration, 1);
        
        const current = Math.floor(progress * (end - start) + start);
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(animation);
        } else {
            element.textContent = end;
        }
    }
    
    requestAnimationFrame(animation);
}

// Initialize counters on page load
document.addEventListener('DOMContentLoaded', function() {
    // Animate all counter numbers
    const counters = document.querySelectorAll('.count');
    counters.forEach(counter => {
        const target = parseInt(counter.textContent);
        animateCounter(counter, 0, target, 1000);
    });
    
    // Animate stat values in quick stats
    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach(stat => {
        const text = stat.textContent;
        const numericValue = parseInt(text);
        
        if (!isNaN(numericValue)) {
            animateCounter(stat, 0, numericValue, 800);
        }
    });
    
    // Fade in animation for cards
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

// Function to refresh dashboard counts via AJAX
function refreshDashboardCounts() {
    fetch('get_dashboard_counts.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Update counts with animation
            const childrenNeedingEl = document.getElementById('children-needing-count');
            const childrenHavingEl = document.getElementById('children-having-count');
            const totalSponsorsEl = document.getElementById('total-sponsors-count');
            const fraudCasesEl = document.getElementById('fraud-cases-count');
            
            if (data.children_needing !== undefined) {
                const oldValue = parseInt(childrenNeedingEl.textContent);
                animateCounter(childrenNeedingEl, oldValue, data.children_needing, 500);
            }
            
            if (data.children_having !== undefined) {
                const oldValue = parseInt(childrenHavingEl.textContent);
                animateCounter(childrenHavingEl, oldValue, data.children_having, 500);
            }
            
            if (data.total_sponsors !== undefined) {
                const oldValue = parseInt(totalSponsorsEl.textContent);
                animateCounter(totalSponsorsEl, oldValue, data.total_sponsors, 500);
            }
            
            if (data.fraud_cases !== undefined) {
                const oldValue = parseInt(fraudCasesEl.textContent);
                animateCounter(fraudCasesEl, oldValue, data.fraud_cases, 500);
            }
            
            console.log('Dashboard counts refreshed successfully');
        })
        .catch(error => {
            console.error('Error refreshing dashboard counts:', error);
        });
}

// Auto-refresh every 30 seconds (optional - uncomment to enable)
// setInterval(refreshDashboardCounts, 30000);

// Update "Last Updated" timestamp
function updateLastUpdatedTime() {
    const lastUpdatedEl = document.getElementById('last-updated');
    if (!lastUpdatedEl) return;
    
    const now = new Date();
    const minutes = Math.floor((Date.now() - now.setSeconds(0, 0)) / 60000);
    
    let display;
    if (minutes === 0) {
        display = 'Just now';
    } else if (minutes === 1) {
        display = '1m ago';
    } else if (minutes < 60) {
        display = `${minutes}m ago`;
    } else {
        const hours = Math.floor(minutes / 60);
        display = hours === 1 ? '1h ago' : `${hours}h ago`;
    }
    
    lastUpdatedEl.textContent = display;
}

// Update timestamp every minute
setInterval(updateLastUpdatedTime, 60000);

// Add hover effects for cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.dashboard-card');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-5px)';
        });
    });
});

// Keyboard navigation support
document.addEventListener('keydown', function(e) {
    // Press 1-4 to navigate to respective dashboards
    if (e.key >= '1' && e.key <= '4') {
        const dashboards = ['children-needing', 'children-having', 'total-sponsors', 'fraud-cases'];
        const index = parseInt(e.key) - 1;
        if (dashboards[index]) {
            openDashboard(dashboards[index]);
        }
    }
});

// Add visual feedback for card clicks
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.dashboard-card');
    
    cards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Add ripple effect
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.backgroundColor = 'rgba(17, 40, 206, 0.3)';
            ripple.style.pointerEvents = 'none';
            ripple.style.animation = 'ripple-animation 0.6s ease-out';
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });
});

// Add CSS animation for ripple effect
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple-animation {
        from {
            transform: scale(0);
            opacity: 1;
        }
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Export function for generating reports (future feature)
function exportDashboardData() {
    const data = {
        childrenNeeding: document.getElementById('children-needing-count').textContent,
        childrenHaving: document.getElementById('children-having-count').textContent,
        totalSponsors: document.getElementById('total-sponsors-count').textContent,
        fraudCases: document.getElementById('fraud-cases-count').textContent,
        timestamp: new Date().toISOString()
    };
    
    console.log('Dashboard data:', data);
    
    // Convert to CSV format
    const csv = Object.entries(data).map(([key, value]) => `${key},${value}`).join('\n');
    
    // Create download link
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `dashboard_export_${Date.now()}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Print dashboard function
function printDashboard() {
    window.print();
}

// Add print styles
const printStyle = document.createElement('style');
printStyle.textContent = `
    @media print {
        .dashboard-card {
            page-break-inside: avoid;
            box-shadow: none !important;
        }
        
        .dashboard-card:hover {
            transform: none !important;
        }
        
        body {
            background-color: white !important;
        }
    }
`;
document.head.appendChild(printStyle);

console.log('Staff Dashboard JavaScript loaded successfully');
console.log('Keyboard shortcuts: Press 1-4 to navigate to respective dashboards');