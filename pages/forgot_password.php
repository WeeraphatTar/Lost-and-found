<?php
$base_url = 'http://localhost/lost-and-found';
require_once '../includes/header.php';
?>

<div class="py-16 bg-background flex-grow flex items-center justify-center px-4">
    <div class="w-full max-w-lg bg-white p-8 md:p-10 rounded-lg shadow-sm border border-gray-200">
        <h2 class="text-2xl font-bold text-center text-primary mb-2">ลืมรหัสผ่าน?</h2>
        <p class="text-center text-gray-500 mb-8">กรุณากรอกอีเมลที่ใช้ลงทะเบียน เพื่อรับลิงก์รีเซ็ตรหัสผ่าน</p>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="p-4 mb-6 text-sm text-indigo-900 bg-indigo-50 border border-indigo-200 rounded-md flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <div class="leading-relaxed">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="p-4 mb-6 text-sm text-red-700 bg-red-100 border border-red-300 rounded-md">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo $base_url; ?>/actions/forgot_password_action.php" method="POST" class="space-y-5">
            <?php render_csrf_input(); ?>

            <div>
                <label class="block font-medium text-primary mb-1">อีเมลของคุณ <span class="asterisk text-red-500">*</span></label>
                <input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="example@email.com" required>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-primary text-white font-medium text-lg rounded-md hover:bg-secondary transition shadow-sm">ส่งคำขอรีเซ็ตรหัสผ่าน</button>
        </form>

        <p class="text-center mt-6 text-sm text-gray-600">
            นึกรหัสผ่านได้แล้ว? <a href="<?php echo $base_url; ?>/pages/login.php" class="font-semibold text-accent hover:text-primary transition">กลับไปหน้าเข้าสู่ระบบ</a>
        </p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
