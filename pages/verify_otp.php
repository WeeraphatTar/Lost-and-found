<?php
$base_url = 'http://localhost/lost-and-found';
require_once '../includes/header.php';
require_once '../config/auth_config.php';

// Check if there is an email waiting for OTP verification
$email = $_SESSION['pending_verification_email'] ?? '';

if (empty($email)) {
    header("Location: " . $base_url . "/pages/login.php");
    exit;
}

// Calculate remaining cooldown seconds for JS timer
$last_resend_time = $_SESSION['last_otp_resend_time'] ?? 0;
$time_passed = time() - $last_resend_time;
$initial_cooldown_left = max(0, OTP_RESEND_COOLDOWN_SECONDS - $time_passed);

// Mask email for privacy (e.g., ex***@email.com)
$parts = explode('@', $email);
$masked_email = (strlen($parts[0]) > 2 ? substr($parts[0], 0, 2) . str_repeat('*', strlen($parts[0]) - 2) : $parts[0]) . '@' . ($parts[1] ?? '');
?>

<div class="py-16 bg-background flex-grow flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-white p-8 md:p-10 rounded-lg shadow-sm border border-gray-200 text-center">
        
        <div class="w-16 h-16 bg-blue-50 text-accent rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-primary mb-2">ยืนยันบัญชีผู้ใช้</h2>
        <p class="text-gray-600 text-sm mb-6">
            ระบบได้ส่งรหัส OTP 6 หลักไปยังอีเมล<br>
            <span class="font-semibold text-primary"><?php echo htmlspecialchars($masked_email, ENT_QUOTES, 'UTF-8'); ?></span>
        </p>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="p-4 mb-6 text-sm text-green-700 bg-green-100 border border-green-300 rounded-md text-left">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="p-4 mb-6 text-sm text-red-700 bg-red-100 border border-red-300 rounded-md text-left">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Form Verify OTP -->
        <form action="<?php echo $base_url; ?>/actions/verify_otp_action.php" method="POST" class="space-y-6">
            <?php render_csrf_input(); ?>

            <div>
                <input type="text" name="otp_code" maxlength="6" pattern="[0-9]{6}" autocomplete="off" autofocus class="w-full text-center text-3xl font-bold tracking-[10px] px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/20" placeholder="••••••" required>
                <p class="text-xs text-gray-400 mt-2">กรุณากรอกรหัสตัวเลข 6 หลัก (รหัสมีอายุ <?php echo OTP_EXPIRY_MINUTES; ?> นาที)</p>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-primary text-white font-medium text-lg rounded-md hover:bg-secondary transition shadow-sm">ยืนยันรหัส OTP</button>
        </form>

        <!-- Resend OTP Form with 60s Countdown Timer -->
        <div class="mt-6 pt-6 border-t border-gray-100 flex items-center justify-between text-sm">
            <span class="text-gray-500">ไม่ได้รับรหัส OTP?</span>
            <form action="<?php echo $base_url; ?>/actions/resend_otp_action.php" method="POST" id="resend-otp-form" class="inline">
                <?php render_csrf_input(); ?>
                <button type="submit" id="resend-otp-btn" class="text-accent font-semibold hover:underline focus:outline-none disabled:text-gray-400 disabled:no-underline disabled:cursor-not-allowed transition">
                    ส่งรหัสอีกครั้ง <span id="cooldown-timer-text"></span>
                </button>
            </form>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let secondsLeft = <?php echo (int)$initial_cooldown_left; ?>;
    const btn = document.getElementById('resend-otp-btn');
    const timerText = document.getElementById('cooldown-timer-text');

    function updateCooldown() {
        if (secondsLeft > 0) {
            btn.disabled = true;
            timerText.textContent = '(' + secondsLeft + 's)';
            secondsLeft--;
            setTimeout(updateCooldown, 1000);
        } else {
            btn.disabled = false;
            timerText.textContent = '';
        }
    }

    if (secondsLeft > 0) {
        updateCooldown();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
