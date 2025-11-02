    <footer>
        <p>© <?php echo date('Y'); ?> Hệ thống Quản lý Học sinh - Demo giao diện</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Highlight active menu
        document.addEventListener('DOMContentLoaded', function() {
            const currentUrl = window.location.href;
            const menuLinks = document.querySelectorAll('.sidebar-container a');
            
            menuLinks.forEach(link => {
                if (currentUrl.includes(link.getAttribute('href'))) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
