<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
        document.querySelector('.sidebar-overlay').classList.toggle('active');
    }
    
    function closeSidebar() {
        if (window.innerWidth <= 768) {
            document.querySelector('.sidebar').classList.remove('active');
            document.querySelector('.sidebar-overlay').classList.remove('active');
        }
    }
    
    // Close sidebar on resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
    
    // Prevent zoom on double tap
    document.addEventListener('dblclick', function(event) {
        event.preventDefault();
    });
    
    // Touch optimizations
    if ('ontouchstart' in window) {
        document.body.style.cursor = 'pointer';
    }
</script>
</body>
</html>
