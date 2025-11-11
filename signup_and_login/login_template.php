<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sponsor a Child</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="logo">
                    <div class="logo-placeholder">
                        <img src="../image_01.png" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                </div>
                
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="../index.html" class="nav-link direct-link">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle">
                            Who We Are
                            <span class="dropdown-arrow">▼</span>
                        </a>
                        <div class="dropdown-menu">
                            <a href="../about-us.html" class="dropdown-link">About Us</a>
                            <a href="../how-we-work.html" class="dropdown-link">How We Work</a>
                            <a href="../our-journey.html" class="dropdown-link">Our Journey</a>
                            <a href="../success-stories.html" class="dropdown-link">Success Stories</a>
                            <a href="../team.html" class="dropdown-link">Team</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle">
                            Donate
                            <span class="dropdown-arrow">▼</span>
                        </a>
                        <div class="dropdown-menu">
                            <a href="../sponsor-child.html" class="dropdown-link">Sponsor a Child</a>
                            <a href="../education.html" class="dropdown-link">Education</a>
                            <a href="../where-most-needed.html" class="dropdown-link">Where Most Needed</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle">
                            Media & Resources
                            <span class="dropdown-arrow">▼</span>
                        </a>
                        <div class="dropdown-menu">
                            <a href="../gallery.html" class="dropdown-link">Gallery</a>
                            <a href="../faq.html" class="dropdown-link">FAQ</a>
                            <a href="../financial-accountability.html" class="dropdown-link">Financial Accountability</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle">
                            Get in Touch
                            <span class="dropdown-arrow">▼</span>
                        </a>
                        <div class="dropdown-menu">
                            <a href="../contact-us.html" class="dropdown-link">Contact Us</a>
                            <a href="../careers.html" class="dropdown-link">Careers</a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
        
    <main class="main-content">
        <div class="login-container">
            <h1 class="login-title" id="loginTitle">User Login</h1>
            
            <!-- User Login Form -->
            <form class="login-form active" id="userForm">
                <div class="form-group">
                    <label for="userEmail">Email</label>
                    <input type="email" id="userEmail" name="email" required autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="userPassword">Password</label>
                    <input type="password" id="userPassword" name="password" required autocomplete="current-password">
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="rememberMe" name="rememberMe">
                    <label for="rememberMe">Remember me</label>
                </div>
                
                <button type="submit" class="login-btn" id="loginBtn">
                    <span class="btn-text">Login</span>
                    <span class="btn-spinner" style="display: none;">Logging in...</span>
                </button>
                
                <p class="signup-link">
                    Don't have an account? <a href="signup.html" class="signup-link-btn">Sign up</a>
                </p>
            </form>
        </div>
    </main>

    <!-- Custom Alert Modal -->
    <div class="alert-overlay" id="alertOverlay">
        <div class="alert-modal">
            <div class="alert-icon">⚠️</div>
            <h3 class="alert-title" id="alertTitle">Invalid Credentials</h3>
            <p class="alert-message" id="alertMessage"></p>
            <button class="alert-btn" id="alertOkBtn">OK</button>
        </div>
    </div>

    <!-- Forgot Password Popup -->
    <div class="forgot-overlay" id="forgotOverlay">
        <div class="forgot-modal">
            <button class="close-btn" id="closeForgotBtn">×</button>
            <div class="forgot-icon">🔑</div>
            <h3 class="forgot-title">Having Trouble Logging In?</h3>
            <p class="forgot-description">Did you forget your password?</p>
            <a href="forgot_password.html" class="forgot-reset-btn">Forgot Password</a>
            <button class="forgot-cancel-btn" id="cancelForgotBtn">Try Again</button>
        </div>
    </div>

    <!-- Info Sections -->
    <section class="info-sections">
        <div class="container">
            <div class="info-grid">
                <div class="info-section">
                    <h3>Who We Are</h3>
                    <ul>
                        <li>About Us</li>
                        <li>How We Work</li>
                        <li>Our Journey</li>
                        <li>Success Stories</li>
                        <li>Team</li>
                    </ul>
                </div>
                <div class="info-section">
                    <h3>Donate</h3>
                    <ul>
                        <li>Sponsor Child</li>
                        <li>Education</li>
                        <li>Where Our Money Needed</li>
                    </ul>
                </div>
                <div class="info-section">
                    <h3>Media & Resources</h3>
                    <ul>
                        <li>Gallery</li>
                        <li>Help</li>
                        <li>Financial Transparency</li>
                    </ul>
                </div>
                <div class="info-section">
                    <h3>Get in Touch</h3>
                    <ul>
                        <li>Contact Us</li>
                        <li>Careers</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sponsor a Child Organization. All rights reserved.</p>
        </div>
    </footer>

    <script src="login.js"></script>
    <script>
        let attemptCount = 0;
        
        const form = document.getElementById('userForm');
        const loginBtn = document.getElementById('loginBtn');
        const btnText = loginBtn.querySelector('.btn-text');
        const btnSpinner = loginBtn.querySelector('.btn-spinner');
        const alertOverlay = document.getElementById('alertOverlay');
        const forgotOverlay = document.getElementById('forgotOverlay');
        const alertOkBtn = document.getElementById('alertOkBtn');
        const closeForgotBtn = document.getElementById('closeForgotBtn');
        const cancelForgotBtn = document.getElementById('cancelForgotBtn');
        const alertTitle = document.getElementById('alertTitle');
        const alertMessage = document.getElementById('alertMessage');

        // Form submission with AJAX
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            loginBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline';

            const formData = new FormData(form);

            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Login successful - redirect to dashboard
                    window.location.href = data.redirect;
                } else {
                    // Login failed
                    attemptCount = data.attempts || 0;
                    
                    if (attemptCount === 1) {
                        alertTitle.textContent = 'Invalid Credentials';
                        alertMessage.textContent = 'The email or password you entered is incorrect. Please try again.';
                        alertOverlay.classList.add('show');
                    } else if (attemptCount >= 2) {
                        alertTitle.textContent = 'Multiple Failed Attempts';
                        alertMessage.textContent = `You've entered incorrect credentials ${attemptCount} times. Would you like to reset your password?`;
                        alertOverlay.classList.add('show');
                        
                        // Show forgot password popup after alert is closed
                        alertOkBtn.onclick = function() {
                            alertOverlay.classList.remove('show');
                            setTimeout(() => {
                                forgotOverlay.classList.add('show');
                            }, 300);
                        };
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alertTitle.textContent = 'Connection Error';
                alertMessage.textContent = 'Unable to connect to server. Please try again.';
                alertOverlay.classList.add('show');
            }

            loginBtn.disabled = false;
            btnText.style.display = 'inline';
            btnSpinner.style.display = 'none';
        });

        // Close alert (for first attempt)
        alertOkBtn.addEventListener('click', function() {
            if (attemptCount < 2) {
                alertOverlay.classList.remove('show');
            }
        });

        // Close forgot password popup
        closeForgotBtn.addEventListener('click', function() {
            forgotOverlay.classList.remove('show');
        });

        cancelForgotBtn.addEventListener('click', function() {
            forgotOverlay.classList.remove('show');
        });

        // Close on overlay click
        alertOverlay.addEventListener('click', function(e) {
            if (e.target === alertOverlay && attemptCount < 2) {
                alertOverlay.classList.remove('show');
            }
        });

        forgotOverlay.addEventListener('click', function(e) {
            if (e.target === forgotOverlay) {
                forgotOverlay.classList.remove('show');
            }
        });
    </script>
    
    <style>
        /* Alert Modal Styles */
        .alert-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .alert-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .alert-modal {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 204, 0, 0.3);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        
        .alert-overlay.show .alert-modal {
            transform: scale(1);
        }
        
        .alert-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .alert-title {
            font-size: 24px;
            font-weight: 700;
            color: #1d1d1f;
            margin-bottom: 16px;
        }
        
        .alert-message {
            font-size: 16px;
            color: #6e6e73;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .alert-btn {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: #1d1d1f;
            border: none;
            padding: 14px 40px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }
        
        .alert-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
        }
        
        .btn-spinner {
            display: none;
        }
        
        /* Forgot Password Popup Styles */
        .forgot-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .forgot-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .forgot-modal {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(30px);
            border-radius: 24px;
            padding: 50px 40px;
            max-width: 450px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 70px rgba(255, 204, 0, 0.4);
            border: 2px solid rgba(255, 204, 0, 0.5);
            position: relative;
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }
        
        .forgot-overlay.show .forgot-modal {
            transform: scale(1);
        }
        
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 32px;
            color: #6e6e73;
            cursor: pointer;
            transition: color 0.3s ease;
            line-height: 1;
        }
        
        .close-btn:hover {
            color: #1d1d1f;
        }
        
        .forgot-icon {
            font-size: 72px;
            margin-bottom: 20px;
            animation: bounce 2s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .forgot-title {
            font-size: 28px;
            font-weight: 700;
            color: #1d1d1f;
            margin-bottom: 16px;
        }
        
        .forgot-description {
            font-size: 16px;
            color: #6e6e73;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .forgot-reset-btn {
            display: block;
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: #1d1d1f;
            padding: 16px 40px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
        }
        
        .forgot-reset-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.5);
        }
        
        .forgot-cancel-btn {
            background: transparent;
            color: #6e6e73;
            border: 2px solid rgba(255, 204, 0, 0.3);
            padding: 12px 40px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .forgot-cancel-btn:hover {
            background: rgba(255, 204, 0, 0.1);
            border-color: rgba(255, 204, 0, 0.5);
            color: #1d1d1f;
        }
    </style>
</body>
</html>