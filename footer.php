    <?php if (!isset($hide_footer) || !$hide_footer): ?>
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">
                <i data-lucide="utensils" class="icon-sm"></i>
                Restaurant QR Ordering System &copy; <?php echo date('Y'); ?>
            </p>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/restaurant-qrcode/assets/js/main.js"></script>
    
    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Initialize Lucide Icons (Safe) -->
    <script>
        if (typeof initializeLucideIcons === 'function') {
            initializeLucideIcons();
        }
    </script>
</body>
</html>
