<style>
     body {
        padding-top: 90px;
    }
      .public-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        z-index: 1000;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    }

    .public-navbar {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0.75rem 2.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
    }

    .public-logo {
        display: flex;
        align-items: center;
        gap: 1rem;
        text-decoration: none;
    }

    .public-logo img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 12px;
    }

    .public-nav-menu {
        display: flex;
        list-style: none;
        gap: 2.5rem;
        margin: 0;
        padding: 0;
        flex: 1;
        justify-content: center;
    }

    .public-nav-item {
        position: relative;
    }

    .public-nav-link {
        text-decoration: none;
        color: rgba(0, 0, 0, 0.75);
        font-size: 0.95rem;
        font-weight: 500;
        padding: 0.5rem 0;
        display: flex;
        align-items: center;
        gap: 0.375rem;
        transition: color 0.3s ease;
        white-space: nowrap;
    }

    .public-nav-link:hover {
        color: #f59e0b;
    }

    .public-dropdown-arrow {
        font-size: 0.7rem;
        transition: transform 0.3s ease;
    }

    .public-nav-item:hover .public-dropdown-arrow {
        transform: rotate(180deg);
    }

    .public-dropdown-menu {
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%) translateY(10px);
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        border-radius: 12px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        min-width: 200px;
        padding: 0.75rem 0;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 100;
    }

    .public-nav-item:hover .public-dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

    .public-dropdown-link {
        display: block;
        padding: 0.75rem 1.5rem;
        color: rgba(0, 0, 0, 0.75);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .public-dropdown-link:hover {
        background: rgba(251, 191, 36, 0.1);
        color: #f59e0b;
    }

    .public-login-btn {
        padding: 0.75rem 2rem;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: white;
        border: none;
        border-radius: 24px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
    }

    .public-login-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(251, 191, 36, 0.4);
    }

    .public-mobile-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: rgba(0, 0, 0, 0.75);
    }

    @media (max-width: 1024px) {
        .public-nav-menu {
            gap: 1.5rem;
        }

        .public-navbar {
            padding: 1rem 1.5rem;
        }
    }

    @media (max-width: 768px) {
        .public-mobile-toggle {
            display: block;
        }

        .public-nav-menu {
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            flex-direction: column;
            padding: 1.5rem;
            gap: 0;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }

        .public-nav-menu.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .public-nav-item {
            width: 100%;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .public-nav-item:last-child {
            border-bottom: none;
        }

        .public-nav-link {
            padding: 1rem 0;
            width: 100%;
        }

        .public-dropdown-menu {
            position: static;
            transform: none;
            box-shadow: none;
            border: none;
            background: rgba(251, 191, 36, 0.05);
            margin-top: 0.5rem;
            padding: 0.5rem 0;
        }

        .public-nav-item:hover .public-dropdown-menu {
            transform: none;
        }

        .public-dropdown-link {
            padding: 0.625rem 1rem;
        }
    }
</style>

<header class="public-header">
    <nav class="public-navbar">
        <a href="index.html" class="public-logo">
            <img src="image_01.png" alt="Logo">
        </a>
        
        <ul class="public-nav-menu" id="publicNavMenu">
            <li class="public-nav-item">
                <a href="index.html" class="public-nav-link">Home</a>
            </li>
            
            <li class="public-nav-item">
                <a href="#" class="public-nav-link">
                    Who We Are
                    <span class="public-dropdown-arrow">▼</span>
                </a>
                <div class="public-dropdown-menu">
                    <a href="about-us.html" class="public-dropdown-link">About Us</a>
                    <a href="how-we-work.html" class="public-dropdown-link">How We Work</a>
                    <a href="our-journey.html" class="public-dropdown-link">Our Journey</a>
                    <a href="success-stories.html" class="public-dropdown-link">Success Stories</a>
                    <a href="team.html" class="public-dropdown-link">Team</a>
                </div>
            </li>
            
            <li class="public-nav-item">
                <a href="#" class="public-nav-link">
                    Donate
                    <span class="public-dropdown-arrow">▼</span>
                </a>
                <div class="public-dropdown-menu">
                    <a href="all_children_profiles_sponser.php" class="public-dropdown-link">Sponsor a Child</a>
                    <a href="education.html" class="public-dropdown-link">Education</a>
                    <a href="where-most-needed.html" class="public-dropdown-link">Where Most Needed</a>
                </div>
            </li>
            
            <li class="public-nav-item">
                <a href="#" class="public-nav-link">
                    Media & Resources
                    <span class="public-dropdown-arrow">▼</span>
                </a>
                <div class="public-dropdown-menu">
                    <a href="gallery.html" class="public-dropdown-link">Gallery</a>
                    <a href="faq.html" class="public-dropdown-link">FAQ</a>
                    <a href="financial-accountability.html" class="public-dropdown-link">Financial Accountability</a>
                </div>
            </li>
            
            <li class="public-nav-item">
                <a href="#" class="public-nav-link">
                    Get in Touch
                    <span class="public-dropdown-arrow">▼</span>
                </a>
                <div class="public-dropdown-menu">
                    <a href="contact-us.html" class="public-dropdown-link">Contact Us</a>
                    <a href="careers.html" class="public-dropdown-link">Careers</a>
                </div>
            </li>
        </ul>
        
        <a href="signup_and_login/login_template.php" class="public-login-btn">Login</a>
        
        <button class="public-mobile-toggle" id="publicMobileToggle">☰</button>
    </nav>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileToggle = document.getElementById('publicMobileToggle');
        const navMenu = document.getElementById('publicNavMenu');
        
        if (mobileToggle && navMenu) {
            mobileToggle.addEventListener('click', function() {
                navMenu.classList.toggle('active');
                mobileToggle.textContent = navMenu.classList.contains('active') ? '✕' : '☰';
            });

            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.public-navbar')) {
                    navMenu.classList.remove('active');
                    mobileToggle.textContent = '☰';
                }
            });

            // Prevent menu close when clicking inside
            navMenu.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        }
    });
</script>