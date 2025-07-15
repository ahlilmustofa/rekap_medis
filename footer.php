<?php
// footer.php
// File ini berisi penutup tag HTML dan skrip JavaScript

// Pastikan session sudah dimulai (jika belum di header.php)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
            </div> <!-- Penutup container-fluid dari header.php -->
        </div> <!-- Penutup page-content-wrapper dari header.php -->
    </div> <!-- Penutup wrapper dari header.php -->

    <!-- Bootstrap JS dan dependensi Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.getElementById('wrapper').classList.toggle('toggled');
            document.getElementById('sidebar-wrapper').classList.toggle('toggled');
        });
    </script>
</body>
</html>
