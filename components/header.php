<?php
// components/header.php
// Reusable header component with logo and logout button
?>
<!DOCTYPE html>
<style>
    /* Header Styles */
    .header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 80px;
        background: rgba(255, 255, 255, 0.5);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 2rem;
        z-index: 100;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .header.sidebar-open {
        left: 280px;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .hamburger {
        width: 40px;
        height: 40px;
        background: transparent;
        border: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .hamburger:hover span {
        background: rgba(0, 0, 0, 0.8);
    }

    .hamburger span {
        width: 24px;
        height: 2.5px;
        background: rgba(0, 0, 0, 0.6);
        border-radius: 2px;
        transition: all 0.3s ease;
    }

    .hamburger.active span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }

    .hamburger.active span:nth-child(2) {
        opacity: 0;
    }

    .hamburger.active span:nth-child(3) {
        transform: rotate(-45deg) translate(7px, -7px);
    }

    .logo {
        height: 45px;
        padding: 0 1.25rem;
        background: transparent;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: rgba(0, 0, 0, 0.75);
        font-size: 1.25rem;
        letter-spacing: 0.5px;
    }

    .logout-btn {
        padding: 0.75rem 1.5rem;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 12px;
        color: #ef4444;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .logout-btn:hover {
        background: rgba(239, 68, 68, 0.2);
        border-color: rgba(239, 68, 68, 0.5);
        transform: translateY(-2px);
    }

    @media (max-width: 1200px) {
        .header.sidebar-open {
            left: 0;
        }
    }

    @media (max-width: 768px) {
        .header {
            padding: 0 1rem;
        }
    }
</style>

<header class="header" id="header">
    <div class="header-left">
        <div class="hamburger" id="hamburger" onclick="toggleSidebar()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="logo">LOGO</div>
    </div>
    <a href="#" onclick="handleLogout(); return false;" class="logout-btn">Logout</a>
</header>

<script>
function handleLogout() {
    // Show confirmation dialog
    if (confirm('Are you sure you want to logout?')) {
        // Redirect to logout.php
        window.location.href = '../signup_and_login/logout.php';
    }
}

// Hamburger toggle function
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const header = document.getElementById('header');
    const hamburger = document.getElementById('hamburger');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
    
    if (header) {
        header.classList.toggle('sidebar-open');
    }
    
    if (hamburger) {
        hamburger.classList.toggle('active');
    }
    
    if (mainContent) {
        mainContent.classList.toggle('sidebar-open');
    }
}
</script>