<?php
// components/common_scripts.php
// Shared JavaScript functions for sidebar toggle and other common functionality
?>
<script>
    // Sidebar toggle functionality
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const header = document.getElementById('header');
        const mainWrapper = document.getElementById('mainWrapper');
        const hamburger = document.getElementById('hamburger');
        const overlay = document.getElementById('overlay');
        
        sidebar.classList.toggle('open');
        header.classList.toggle('sidebar-open');
        mainWrapper.classList.toggle('sidebar-open');
        hamburger.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const hamburger = document.getElementById('hamburger');
        
        if (sidebar.classList.contains('open') && 
            !sidebar.contains(event.target) && 
            !hamburger.contains(event.target)) {
            if (window.innerWidth <= 1200) {
                toggleSidebar();
            }
        }
    });
</script>