<?php
$base_url = 'http://localhost/lost-and-found';
require_once '../includes/header.php';
require_once '../config/database.php';

$raw_token = trim($_GET['token'] ?? '');
$is_token_valid = false;
$error_message = '';

if (!empty($raw_token)) {
    $token_hash = hash('sha256', $raw_token);
    try {
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT id, email, expires_at, used FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > ?");
        $stmt->execute([$token_hash, $now]);
        $reset_record = $stmt->fetch();

        if ($reset_record) {
            $is_token_valid = true;
        } else {
            $error_message = "ลิงก์รีเซ็ตรหัสผ่านนี้ไม่ถูกต้อง หมดอายุแล้ว หรือถูกใช้งานไปแล้ว";
        }
    } catch (PDOException $e) {
        $error_message = "เกิดข้อผิดพลาดในการตรวจสอบสิทธิ์";
    }
} else {
    $error_message = "ไม่พบรหัสอ้างอิงสำหรับรีเซ็ตรหัสผ่าน";
}
?>

<div class="py-16 bg-background flex-grow flex items-center justify-center px-4">
    <div class="w-full max-w-lg bg-white p-8 md:p-10 rounded-lg shadow-sm border border-gray-200">
        
        <?php if (!$is_token_valid): ?>
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-primary mb-2">ลิงก์ไม่สามารถใช้งานได้</h2>
                <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
                <a href="<?php echo $base_url; ?>/pages/forgot_password.php" class="inline-block py-3 px-6 bg-primary text-white font-medium rounded-md hover:bg-secondary transition">ส่งคำขอรีเซ็ตรหัสผ่านใหม่</a>
            </div>
        <?php else: ?>

            <h2 class="text-2xl font-bold text-center text-primary mb-2">ตั้งรหัสผ่านใหม่</h2>
            <p class="text-center text-gray-500 mb-8">กรุณากำหนดรหัสผ่านใหม่สำหรับบัญชีของคุณ</p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="p-4 mb-6 text-sm text-red-700 bg-red-100 border border-red-300 rounded-md">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo $base_url; ?>/actions/reset_password_action.php" method="POST" class="space-y-5">
                <?php render_csrf_input(); ?>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($raw_token, ENT_QUOTES, 'UTF-8'); ?>">

                <div>
                    <label class="block font-medium text-primary mb-1">รหัสผ่านใหม่ <span class="asterisk text-red-500">*</span></label>
                    <div class="relative password-wrapper">
                        <input type="password" name="password" class="w-full pl-4 pr-11 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="รหัสผ่านอย่างน้อย 8 ตัวอักษร" required minlength="8">
                        <button type="button" class="toggle-password-btn hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1 transition">
                            <svg class="w-5 h-5 icon-eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.573 16.49 16.638 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <svg class="w-5 h-5 icon-eye hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.326 16.157 7.305 19.5 12 19.5c1.84 0 3.568-.45 5.088-1.246M9.88 9.88a3 3 0 104.24 4.24M10.73 5.08A10.43 10.43 0 0112 4.5c4.695 0 8.673 2.926 10.168 7.083a10.495 10.495 0 01-2.28 3.864M3 3l18 18"></path></svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-primary mb-1">ยืนยันรหัสผ่านใหม่ <span class="asterisk text-red-500">*</span></label>
                    <div class="relative password-wrapper">
                        <input type="password" name="confirm_password" class="w-full pl-4 pr-11 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" required minlength="8">
                        <button type="button" class="toggle-password-btn hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1 transition">
                            <svg class="w-5 h-5 icon-eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.573 16.49 16.638 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <svg class="w-5 h-5 icon-eye hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.326 16.157 7.305 19.5 12 19.5c1.84 0 3.568-.45 5.088-1.246M9.88 9.88a3 3 0 104.24 4.24M10.73 5.08A10.43 10.43 0 0112 4.5c4.695 0 8.673 2.926 10.168 7.083a10.495 10.495 0 01-2.28 3.864M3 3l18 18"></path></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-primary text-white font-medium text-lg rounded-md hover:bg-secondary transition shadow-sm">เปลี่ยนรหัสผ่าน</button>
            </form>

        <?php endif; ?>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
