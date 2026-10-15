<?php 
if (!isset($base_url)) {
    $base_url = 'http://localhost/lost-and-found';
}
$is_admin_page_footer = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/pages/admin/') !== false);
?>
    <!-- Footer -->
    <?php if (!$is_admin_page_footer && empty($hide_nav_footer)): ?>
        <footer class="bg-primary text-white pt-10 pb-6 mt-auto border-t border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- 3 Columns Layout -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                    <!-- Column 1: Lost & Found -->
                    <div>
                        <div class="mb-3">
                            <img src="<?php echo $base_url; ?>/assets/images/logo-white%20v2.png" alt="Lost & Found" class="h-7 md:h-8 w-auto object-contain">
                        </div>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            ระบบแจ้งของหายและติดตามทรัพย์สินคืน
                        </p>
                    </div>

                    <!-- Column 2: บริการ -->
                    <div>
                        <h4 class="text-base font-semibold text-white mb-3">บริการ</h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <a href="<?php echo $base_url; ?>/pages/report_lost.php" class="text-gray-400 hover:text-white hover:underline transition-colors duration-150">
                                    แจ้งของหาย
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $base_url; ?>/pages/report_found.php" class="text-gray-400 hover:text-white hover:underline transition-colors duration-150">
                                    แจ้งพบของ
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $base_url; ?>/pages/browse.php" class="text-gray-400 hover:text-white hover:underline transition-colors duration-150">
                                    ค้นหาประกาศ
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 3: ข้อมูล -->
                    <div>
                        <h4 class="text-base font-semibold text-white mb-3">ข้อมูล</h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <a href="<?php echo $base_url; ?>/pages/help.php" class="text-gray-400 hover:text-white hover:underline transition-colors duration-150">
                                    ศูนย์ช่วยเหลือ
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $base_url; ?>/pages/faq.php" class="text-gray-400 hover:text-white hover:underline transition-colors duration-150">
                                    คำถามที่พบบ่อย
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $base_url; ?>/pages/privacy.php" class="text-gray-400 hover:text-white hover:underline transition-colors duration-150">
                                    นโยบายความเป็นส่วนตัว
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $base_url; ?>/pages/contact.php" class="text-gray-400 hover:text-white hover:underline transition-colors duration-150">
                                    ติดต่อเจ้าหน้าที่
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom Thin Divider & Copyright -->
                <div class="border-t border-gray-800/80 pt-6 text-center">
                    <p class="text-gray-400 text-sm">
                        &copy; 2026 Lost & Found System. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    <?php endif; ?>
    <!-- Global Password Toggle & Double Form Submission Protection Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password Show/Hide Toggle
            document.querySelectorAll('.password-wrapper').forEach(function(wrapper) {
                const input = wrapper.querySelector('input');
                const btn = wrapper.querySelector('.toggle-password-btn');
                if (!input || !btn) return;

                const iconEye = btn.querySelector('.icon-eye');
                const iconEyeOff = btn.querySelector('.icon-eye-off');

                function updateButtonVisibility() {
                    if (input.value && input.value.length > 0) {
                        btn.classList.remove('hidden');
                    } else {
                        btn.classList.add('hidden');
                    }
                }

                input.addEventListener('input', updateButtonVisibility);
                input.addEventListener('keyup', updateButtonVisibility);
                input.addEventListener('change', updateButtonVisibility);
                updateButtonVisibility();

                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (input.type === 'password') {
                        input.type = 'text';
                        if (iconEye) iconEye.classList.remove('hidden');
                        if (iconEyeOff) iconEyeOff.classList.add('hidden');
                    } else {
                        input.type = 'password';
                        if (iconEye) iconEye.classList.add('hidden');
                        if (iconEyeOff) iconEyeOff.classList.remove('hidden');
                    }
                });
            });

            // Double Form Submission Protection
            document.querySelectorAll('form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    if (e.defaultPrevented) {
                        return;
                    }
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
