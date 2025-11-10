// DOM elements
const loginTitle = document.getElementById('loginTitle');
const loginForms = document.querySelectorAll('.login-form');

// Initialize the page
function init() {
    setupFormValidation();
    hideErrorBannerAfterDelay();
}

// Form validation functions
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function validatePassword(password) {
    return password.length >= 6;
}

function validateStaffId(staffId) {
    const staffIdRegex = /^ST-\d{4}$/;
    return staffIdRegex.test(staffId);
}

function validateAuthCode(code) {
    const authCodeRegex = /^\d{6}$/;
    return authCodeRegex.test(code);
}

function showError(input, message) {
    removeError(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    input.classList.add('error');
    input.parentNode.appendChild(errorDiv);
}

function removeError(input) {
    const errorMessage = input.parentNode.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.remove();
    }
    input.classList.remove('error');
}

function clearFormErrors(form) {
    const inputs = form.querySelectorAll('input, select');
    inputs.forEach(input => removeError(input));
}

// Hide error banner after 5 seconds
function hideErrorBannerAfterDelay() {
    const errorBanner = document.getElementById('errorBanner');
    if (errorBanner && errorBanner.style.display !== 'none') {
        setTimeout(() => {
            errorBanner.style.transition = 'opacity 0.5s ease';
            errorBanner.style.opacity = '0';
            setTimeout(() => {
                errorBanner.style.display = 'none';
                errorBanner.style.opacity = '1';
            }, 500);
        }, 5000);
    }
}

// Setup form validation (CLIENT-SIDE ONLY, then let PHP handle submission)
function setupFormValidation() {
    // User form validation
    const userForm = document.getElementById('userForm');
    if (userForm) {
        userForm.addEventListener('submit', handleUserLogin);
    }
    
    // Staff form validation
    const staffForm = document.getElementById('staffForm');
    if (staffForm) {
        staffForm.addEventListener('submit', handleStaffLogin);
    }
    
    // Admin form validation
    const adminForm = document.getElementById('adminForm');
    if (adminForm) {
        adminForm.addEventListener('submit', handleAdminLogin);
    }
    
    // Real-time validation
    setupRealTimeValidation();
}

function handleUserLogin(e) {
    const email = document.getElementById('userEmail').value.trim();
    const password = document.getElementById('userPassword').value.trim();
    
    // Only validate, don't prevent submission
    if (!validateUserForm(email, password)) {
        e.preventDefault();
        return false;
    }
    
    // Let the form submit naturally to PHP
    return true;
}

function handleStaffLogin(e) {
    const staffId = document.getElementById('staffId').value.trim();
    const email = document.getElementById('staffEmail').value.trim();
    const password = document.getElementById('staffPassword').value.trim();
    
    if (!validateStaffForm(staffId, email, password)) {
        e.preventDefault();
        return false;
    }
    
    return true;
}

function handleAdminLogin(e) {
    const username = document.getElementById('adminUsername').value.trim();
    const password = document.getElementById('adminPassword').value.trim();
    const authCode = document.getElementById('authCode').value.trim();
    
    if (!validateAdminForm(username, password, authCode)) {
        e.preventDefault();
        return false;
    }
    
    return true;
}

function validateUserForm(email, password) {
    let isValid = true;
    
    if (!email) {
        showError(document.getElementById('userEmail'), 'Email is required');
        isValid = false;
    } else if (!validateEmail(email)) {
        showError(document.getElementById('userEmail'), 'Please enter a valid email address');
        isValid = false;
    }
    
    if (!password) {
        showError(document.getElementById('userPassword'), 'Password is required');
        isValid = false;
    } else if (!validatePassword(password)) {
        showError(document.getElementById('userPassword'), 'Password must be at least 6 characters long');
        isValid = false;
    }
    
    return isValid;
}

function validateStaffForm(staffId, email, password) {
    let isValid = true;
    
    if (staffId && !validateStaffId(staffId)) {
        showError(document.getElementById('staffId'), 'Staff ID must be in format ST-XXXX');
        isValid = false;
    }
    
    if (!email) {
        showError(document.getElementById('staffEmail'), 'Email is required');
        isValid = false;
    } else if (!validateEmail(email)) {
        showError(document.getElementById('staffEmail'), 'Please enter a valid email address');
        isValid = false;
    }
    
    if (!password) {
        showError(document.getElementById('staffPassword'), 'Password is required');
        isValid = false;
    } else if (!validatePassword(password)) {
        showError(document.getElementById('staffPassword'), 'Password must be at least 6 characters long');
        isValid = false;
    }
    
    return isValid;
}

function validateAdminForm(username, password, authCode) {
    let isValid = true;
    
    if (!username) {
        showError(document.getElementById('adminUsername'), 'Username is required');
        isValid = false;
    } else if (username.length < 3) {
        showError(document.getElementById('adminUsername'), 'Username must be at least 3 characters');
        isValid = false;
    }
    
    if (!password) {
        showError(document.getElementById('adminPassword'), 'Password is required');
        isValid = false;
    } else if (!validatePassword(password)) {
        showError(document.getElementById('adminPassword'), 'Password must be at least 6 characters long');
        isValid = false;
    }
    
    if (!authCode) {
        showError(document.getElementById('authCode'), 'Authentication code is required');
        isValid = false;
    } else if (!validateAuthCode(authCode)) {
        showError(document.getElementById('authCode'), 'Authentication code must be 6 digits');
        isValid = false;
    }
    
    return isValid;
}

function setupRealTimeValidation() {
    // User form real-time validation
    const userEmail = document.getElementById('userEmail');
    if (userEmail) {
        userEmail.addEventListener('input', function() {
            if (this.value.trim() && validateEmail(this.value.trim())) {
                removeError(this);
            }
        });
    }
    
    const userPassword = document.getElementById('userPassword');
    if (userPassword) {
        userPassword.addEventListener('input', function() {
            if (this.value.trim() && validatePassword(this.value.trim())) {
                removeError(this);
            }
        });
    }
    
    // Staff form real-time validation
    const staffId = document.getElementById('staffId');
    if (staffId) {
        staffId.addEventListener('input', function() {
            if (this.value.trim() && validateStaffId(this.value.trim())) {
                removeError(this);
            }
        });
    }
    
    const staffEmail = document.getElementById('staffEmail');
    if (staffEmail) {
        staffEmail.addEventListener('input', function() {
            if (this.value.trim() && validateEmail(this.value.trim())) {
                removeError(this);
            }
        });
    }
    
    const staffPassword = document.getElementById('staffPassword');
    if (staffPassword) {
        staffPassword.addEventListener('input', function() {
            if (this.value.trim() && validatePassword(this.value.trim())) {
                removeError(this);
            }
        });
    }
    
    // Admin form real-time validation
    const adminUsername = document.getElementById('adminUsername');
    if (adminUsername) {
        adminUsername.addEventListener('input', function() {
            if (this.value.trim() && this.value.trim().length >= 3) {
                removeError(this);
            }
        });
    }
    
    const adminPassword = document.getElementById('adminPassword');
    if (adminPassword) {
        adminPassword.addEventListener('input', function() {
            if (this.value.trim() && validatePassword(this.value.trim())) {
                removeError(this);
            }
        });
    }
    
    const authCode = document.getElementById('authCode');
    if (authCode) {
        authCode.addEventListener('input', function() {
            if (this.value.trim() && validateAuthCode(this.value.trim())) {
                removeError(this);
            }
        });
    }
}

// Add styling for error messages
const style = document.createElement('style');
style.textContent = `
    .error-banner {
        background: rgba(231, 76, 60, 0.9);
        color: white;
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
    }
    
    .error-message {
        color: #e74c3c;
        font-size: 13px;
        margin-top: 6px;
        display: block;
    }
    
    input.error {
        border-color: #e74c3c !important;
        background: rgba(231, 76, 60, 0.05) !important;
    }
`;
document.head.appendChild(style);

// Initialize the application
init();