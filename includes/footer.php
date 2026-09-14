    <!-- Footer -->
    <footer class="bg-primary text-white py-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-400 text-sm">&copy; <?php echo date('Y'); ?> Lost & Found System. All rights reserved.</p>
        </div>
    <!-- Global Double Form Submission Protection Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    if (form.checkValidity()) {
                        const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                        submitButtons.forEach(function(btn) {
                            if (btn.dataset.submitting === 'true') {
                                e.preventDefault();
                                return false;
                            }
                            btn.dataset.submitting = 'true';
                            btn.style.opacity = '0.7';
                            btn.style.pointerEvents = 'none';
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
