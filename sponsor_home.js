// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Render statistics
    renderStatistics();
    
    // Render children cards
    renderChildrenCards();
});

// Render Achievement Statistics
function renderStatistics() {
    const statsContainer = document.getElementById('statsContainer');
    
    if (!statsData) {
        statsContainer.innerHTML = '<p class="empty-state">No statistics available</p>';
        return;
    }
    
    const stats = [
        {
            title: 'Children Sponsored',
            value: statsData.total_children,
            icon: '👶'
        },
        {
            title: 'Total Donations',
            value: '₹' + statsData.total_donations,
            icon: '💰'
        },
        {
            title: 'Years of Support',
            value: statsData.years_of_support + (statsData.years_of_support === 1 ? ' Year' : ' Years'),
            icon: '📅'
        },
        {
            title: 'Active Sponsorships',
            value: statsData.active_sponsorships,
            icon: '⭐'
        }
    ];
    
    statsContainer.innerHTML = stats.map(stat => `
        <div class="stat-card">
            <h3>${stat.icon} ${stat.title}</h3>
            <div class="stat-value">${stat.value}</div>
        </div>
    `).join('');
}

// Render Children Cards
function renderChildrenCards() {
    const childrenGrid = document.getElementById('childrenGrid');
    
    if (!childrenData || childrenData.length === 0) {
        childrenGrid.innerHTML = '<p class="empty-state">No children available for sponsorship at this time.</p>';
        return;
    }
    
    childrenGrid.innerHTML = childrenData.map(child => {
        const statusClass = child.status === 'Sponsored' ? 'status-sponsored' : 'status-unsponsored';
        
        return `
            <div class="child-card" onclick="openChildProfile(${child.child_id})">
                <div class="child-card-header">
                    <span class="child-name">${child.first_name} ${child.last_name}</span>
                    <span class="child-status ${statusClass}">${child.status}</span>
                </div>
                <div class="child-info">
                    <p><strong>Age:</strong> ${child.age} years</p>
                    <p><strong>Gender:</strong> ${child.gender}</p>
                </div>
                <button class="view-profile-btn" onclick="event.stopPropagation(); openChildProfile(${child.child_id})">
                    View Full Profile
                </button>
            </div>
        `;
    }).join('');
}

// Open Child Profile
function openChildProfile(childId) {
    if (!childId) {
        console.error('Invalid child ID');
        return;
    }
    
    // Redirect to child profile page with child ID
    window.location.href = `child_profile.php?child_id=${childId}`;
}

// Add smooth scroll behavior
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add animation on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe all cards
setTimeout(() => {
    document.querySelectorAll('.stat-card, .child-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
    });
}, 100);

// Console log for debugging
console.log('Sponsor Dashboard Loaded');
console.log('Stats:', statsData);
console.log('Children:', childrenData);
console.log('Sponsor:', sponsorData);