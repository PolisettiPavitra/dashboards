// Global variable to store selected file
let selectedFile = null;

// Modal Functions
function openModal() {
    document.getElementById('uploadModal').classList.add('active');
}

function closeModal() {
    document.getElementById('uploadModal').classList.remove('active');
    resetUpload();
}

function resetUpload() {
    selectedFile = null;
    document.getElementById('fileInput').value = '';
    document.getElementById('previewImage').style.display = 'none';
    document.getElementById('saveBtn').disabled = true;
}

// File Upload Handlers
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        if (!file.type.match('image/(png|jpeg|jpg)')) {
            alert('Please select a valid image file (PNG, JPG, or JPEG)');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            return;
        }
        selectedFile = file;
        previewImage(file);
        document.getElementById('saveBtn').disabled = false;
    }
}

function previewImage(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const previewImg = document.getElementById('previewImage');
        previewImg.src = e.target.result;
        previewImg.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

// Drag and Drop Handlers
const uploadArea = document.getElementById('uploadArea');
if (uploadArea) {
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            if (!file.type.match('image/(png|jpeg|jpg)')) {
                alert('Please select a valid image file (PNG, JPG, or JPEG)');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }
            selectedFile = file;
            previewImage(file);
            document.getElementById('saveBtn').disabled = false;
        }
    });
}

// Save Profile Picture
function saveProfilePicture() {
    if (!selectedFile) {
        alert('Please select an image first');
        return;
    }

    const formData = new FormData();
    formData.append('profile_picture', selectedFile);

    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Uploading...';

    fetch('upload_profile_picture.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const profilePicture = document.getElementById('profilePicture');
            if (profilePicture) {
                profilePicture.innerHTML = `<img src="${data.image_path}?t=${new Date().getTime()}" alt="Profile Picture">`;
            }
            alert('Profile picture updated successfully!');
            closeModal();
            location.reload();
        } else {
            alert('Error uploading image: ' + data.message);
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Changes';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while uploading the image');
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';
    });
}

// Close modal when clicking outside
window.addEventListener('click', (e) => {
    const modal = document.getElementById('uploadModal');
    if (e.target === modal) {
        closeModal();
    }
});

// ======================= 
// Dashboard Counts
// =======================

// Update Sponsored Children Count
async function updateDashboardCounts() {
    try {
        console.log('Fetching sponsored children count...');
        
        const response = await fetch('get_sponsored_children.php');
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Full response received:', result);
        
        if (result.success) {
            const childrenCountEl = document.getElementById('childrenCount');
            if (childrenCountEl) {
                const totalCount = result.stats.total_count;
                childrenCountEl.textContent = `Total Sponsored: ${totalCount}`;
                console.log('✓ Dashboard count updated to:', totalCount);
            } else {
                console.error('✗ childrenCount element not found in DOM');
            }
        } else {
            console.error('✗ API returned error:', result.message);
            const childrenCountEl = document.getElementById('childrenCount');
            if (childrenCountEl) {
                childrenCountEl.textContent = 'Error loading count';
            }
        }
    } catch (error) {
        console.error('✗ Error updating dashboard counts:', error);
        const childrenCountEl = document.getElementById('childrenCount');
        if (childrenCountEl) {
            childrenCountEl.textContent = 'Total Sponsored: 0';
        }
    }
}

// Update Donation Stats
async function updateDonationStats() {
    try {
        console.log('Fetching donation stats...');
        
        // Use sponsor_id = 14 for testing
        const response = await fetch('get_donation_data.php?sponsor_id=14');
        console.log('Donation response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Donation response received:', result);
        
        if (result.success) {
            const totalDonationsEl = document.getElementById('totalDonations');
            if (totalDonationsEl) {
                const totalAmount = result.stats.total_amount;
                totalDonationsEl.textContent = `Total Donated: ₹${totalAmount.toLocaleString('en-IN')}`;
                console.log('✓ Donation stats updated to:', totalAmount);
            } else {
                console.error('✗ totalDonations element not found in DOM');
            }
        } else {
            console.error('✗ Donation API returned error:', result.message);
            const totalDonationsEl = document.getElementById('totalDonations');
            if (totalDonationsEl) {
                totalDonationsEl.textContent = 'Error loading donations';
            }
        }
    } catch (error) {
        console.error('✗ Error updating donation stats:', error);
        const totalDonationsEl = document.getElementById('totalDonations');
        if (totalDonationsEl) {
            totalDonationsEl.textContent = 'Total Donated: ₹0';
        }
    }
}

// Initialize dashboard counts when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for dashboard elements...');
    
    // Update children count
    const childrenCountEl = document.getElementById('childrenCount');
    if (childrenCountEl) {
        console.log('Children count element found, updating...');
        updateDashboardCounts();
    }
    
    // Update donation stats
    const totalDonationsEl = document.getElementById('totalDonations');
    if (totalDonationsEl) {
        console.log('Donation stats element found, updating...');
        updateDonationStats();
    }
    
    if (!childrenCountEl && !totalDonationsEl) {
        console.log('Not on dashboard page or elements not found');
    }
});