// Global variables
let allChildren = [];
let currentFilter = 'all';

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Fetch and render children data
    fetchSponsoredChildren();
    
    // Setup event listeners
    setupEventListeners();
});

// Fetch sponsored children from API
async function fetchSponsoredChildren() {
    const loadingState = document.getElementById('loadingState');
    const childrenGrid = document.getElementById('childrenGrid');
    const emptyState = document.getElementById('emptyState');
    
    try {
        // Show loading state
        loadingState.style.display = 'block';
        childrenGrid.style.display = 'none';
        emptyState.style.display = 'none';
        
        // Fetch data from API
        const response = await fetch(`get_sponsored_children.php?sponsor_id=${SPONSOR_ID}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Failed to fetch data');
        }
        
        // Store children data
        allChildren = result.data || [];
        
        // Update statistics
        updateStatistics(result.stats);
        
        // Hide loading, show grid or empty state
        loadingState.style.display = 'none';
        
        if (allChildren.length === 0) {
            emptyState.style.display = 'block';
        } else {
            childrenGrid.style.display = 'grid';
            renderChildrenCards(allChildren);
        }
        
    } catch (error) {
        console.error('Error fetching sponsored children:', error);
        loadingState.style.display = 'none';
        emptyState.style.display = 'block';
        
        // Update empty state with error message
        document.querySelector('.empty-title').textContent = 'Error Loading Data';
        document.querySelector('.empty-message').textContent = error.message || 'Please try again later.';
    }
}

// Update statistics display
function updateStatistics(stats) {
    if (!stats) return;
    
    document.getElementById('totalCount').textContent = stats.total_count || 0;
    document.getElementById('activeCount').textContent = stats.active_count || 0;
}

// Render children cards
function renderChildrenCards(children) {
    const childrenGrid = document.getElementById('childrenGrid');
    
    if (!children || children.length === 0) {
        childrenGrid.innerHTML = '<p class="empty-state">No children match your search criteria.</p>';
        return;
    }
    
    childrenGrid.innerHTML = children.map(child => `
        <div class="child-card" data-gender="${child.gender}" data-name="${child.first_name} ${child.last_name}">
            <div class="child-photo">
                ${child.initials || '??'}
            </div>
            <div class="child-info">
                <div class="child-name">${child.first_name} ${child.last_name}</div>
                <div class="child-details">
                    <div class="detail-row">
                        <span class="detail-label">Age:</span>
                        <span class="detail-value">${child.age} years</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Gender:</span>
                        <span class="detail-value">${child.gender}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">${child.status}</span>
                    </div>
                </div>
                <div class="sponsorship-info">
                    <div class="sponsorship-date">
                        Sponsored since ${child.start_date_formatted}
                    </div>
                    <div class="sponsorship-date">
                        Duration: ${child.sponsorship_duration}
                    </div>
                </div>
                <button class="view-btn" onclick="viewChildProfile(${child.child_id})">
                    View Profile
                </button>
            </div>
        </div>
    `).join('');
}

// Setup event listeners
function setupEventListeners() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', handleSearch);
    
    // Filter buttons
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active state
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Apply filter
            currentFilter = this.dataset.filter;
            applyFilters();
        });
    });
}

// Handle search
function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase().trim();
    applyFilters(searchTerm);
}

// Apply filters and search
function applyFilters(searchTerm = '') {
    const searchInput = document.getElementById('searchInput');
    const currentSearchTerm = searchTerm || searchInput.value.toLowerCase().trim();
    
    let filteredChildren = allChildren;
    
    // Apply gender filter
    if (currentFilter !== 'all') {
        filteredChildren = filteredChildren.filter(child => child.gender === currentFilter);
    }
    
    // Apply search filter
    if (currentSearchTerm) {
        filteredChildren = filteredChildren.filter(child => {
            const fullName = `${child.first_name} ${child.last_name}`.toLowerCase();
            return fullName.includes(currentSearchTerm);
        });
    }
    
    // Render filtered results
    renderChildrenCards(filteredChildren);
    
    // Show empty state if no results
    const childrenGrid = document.getElementById('childrenGrid');
    const emptyState = document.getElementById('emptyState');
    
    if (filteredChildren.length === 0) {
        childrenGrid.style.display = 'none';
        emptyState.style.display = 'block';
        document.querySelector('.empty-title').textContent = 'No Children Found';
        document.querySelector('.empty-message').textContent = 'Try adjusting your search or filter criteria.';
    } else {
        childrenGrid.style.display = 'grid';
        emptyState.style.display = 'none';
    }
}

// View child profile
function viewChildProfile(childId) {
    if (!childId) {
        console.error('Invalid child ID');
        return;
    }
    
    // Redirect to child profile page
    window.location.href = `child_profile.php?child_id=${childId}`;
}

// Console log for debugging
console.log('Sponsored Children page loaded');
console.log('Sponsor ID:', SPONSOR_ID);