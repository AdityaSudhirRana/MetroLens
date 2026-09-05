<?php
/**
 * AI Package Inspector - Footer Component
 */
?>
</div><!-- end app-layout -->

<script>
    // Responsive Mobile & Desktop Sidebar Toggle System
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('sidebarToggleBtn') || document.querySelector('.mobile-sidebar-toggle') || document.querySelector('.hamburger-btn');
        const sidebar = document.querySelector('.sidebar');
        
        let backdrop = document.querySelector('.mobile-sidebar-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'mobile-sidebar-backdrop';
            document.body.appendChild(backdrop);
        }

        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (window.innerWidth <= 992) {
                    sidebar.classList.toggle('mobile-open');
                    backdrop.classList.toggle('active');
                } else {
                    sidebar.classList.toggle('collapsed');
                }
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function() {
                if (sidebar) sidebar.classList.remove('mobile-open');
                backdrop.classList.remove('active');
            });
        }

        window.addEventListener('resize', function() {
            if (window.innerWidth > 992 && sidebar) {
                sidebar.classList.remove('mobile-open');
                if (backdrop) backdrop.classList.remove('active');
            }
        });
    });
</script>
</body>
</html>
