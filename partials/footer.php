    </div><!-- #app-root -->

<script>
// ============ Sidebar Toggle (Mobile) ============
// This script runs AFTER all components are rendered, so #kopi-sidebar exists
(function() {
    var toggle  = document.getElementById('sidebar-toggle');
    var overlay = document.getElementById('sidebar-overlay');
    var sidebar = document.getElementById('kopi-sidebar');

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        if (toggle) toggle.classList.add('active');
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
        if (toggle) toggle.classList.remove('active');
    }

    if (toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            closeSidebar();
        });
    }

    // Close sidebar when clicking a link inside it (mobile)
    if (sidebar) {
        var links = sidebar.querySelectorAll('a');
        for (var i = 0; i < links.length; i++) {
            links[i].addEventListener('click', closeSidebar);
        }
    }
})();
</script>
</body>
</html>