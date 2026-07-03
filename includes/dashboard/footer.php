        </main> <!-- End content-body -->

        <!-- Page Footer -->
        <footer class="footer">
            <div>
                © 2026 <span>SIPARK-GBK</span>. Hak Cipta Dilindungi.
            </div>
            <div>
                Versi 1.0.0
            </div>
        </footer>
    </div> <!-- End main-wrapper -->
</div> <!-- End app-container -->

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Custom JS Scripts -->
<script src="<?= BASE_URL ?>assets/js/app.js"></script>

<!-- Session Toast Notification Trigger -->
<?php
if (isset($_SESSION['toast'])) {
    $toast = $_SESSION['toast'];
    $title = addslashes($toast['title']);
    $message = addslashes($toast['message']);
    $type = addslashes($toast['type']);
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('$title', '$message', '$type');
        });
    </script>";
    unset($_SESSION['toast']);
}
?>
</body>
</html>