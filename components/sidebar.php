<?php
// components/sidebar.php
// Reusable sidebar component
// Usage: Set $sidebar_menu array before including this file
?>
<!DOCTYPE html>
<style>
    /* Overlay */
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(4px);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 99;
    }

    .overlay.active {
        opacity: 1;
        visibility: visible;
    }

    /* Sidebar */
    .sidebar {
        position: fixed;
        top: 0;
        left: -280px;
        width: 280px;
        height: 100vh;
        background: linear-gradient(180deg, 
            #4A90A4 0%,
            #5FA4B8 20%,
            #7CB8C8 40%,
            #B8C9A8 60%,
            #D4B896 80%,
            #E89C6F 100%);
        border-right: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
        padding: 2rem 0;
        z-index: 101;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-y: auto;
    }

    .sidebar.open {
        left: 0;
    }

    .sidebar-header {
        padding: 0 1.5rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        margin-bottom: 1.5rem;
    }

    .sidebar-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #ffffff;
    }

    .sidebar-menu {
        list-style: none;
        padding: 0 1rem;
    }

    .menu-section {
        margin-bottom: 2rem;
    }

    .section-label {
        padding: 0 1.25rem;
        margin-bottom: 0.75rem;
        font-size: 0.7rem;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.6);
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }

    .menu-item {
        margin-bottom: 0.5rem;
    }

    .menu-link {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        text-decoration: none;
        color: rgba(255, 255, 255, 0.85);
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .menu-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.15);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .menu-link:hover {
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        transform: translateX(5px);
    }

    .menu-link:hover::before {
        opacity: 1;
    }

    .menu-link.active {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.4);
    }
</style>

<!-- Overlay -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2 class="sidebar-title">Navigation</h2>
    </div>
    <ul class="sidebar-menu">
        <?php if (isset($sidebar_menu) && is_array($sidebar_menu)): ?>
            <?php foreach ($sidebar_menu as $section): ?>
                <li class="menu-section">
                    <div class="section-label"><?php echo htmlspecialchars($section['label']); ?></div>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($section['items'] as $item): ?>
                            <li class="menu-item">
                                <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                                   class="menu-link <?php echo ($item['active'] ?? false) ? 'active' : ''; ?>">
                                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</nav>