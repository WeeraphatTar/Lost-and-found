<?php
$base_url = 'http://localhost/lost-and-found';
$hide_nav_footer = true;
require_once '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-[#f1f5f9] py-10 px-4">
    <div class="w-full max-w-lg bg-white p-8 md:p-10 rounded-2xl shadow-lg border border-gray-200/80">
        <a href="<?php echo $base_url; ?>/index.php" class="block">
            <img src="<?php echo $base_url; ?>/assets/images/logo-navy%20v2%20nobg.png" alt="Lost & Found" class="h-20 md:h-24 w-auto mx-auto block mb-2.5 object-contain">
        </a>
        <h2 class="text-2xl font-bold text-center text-primary leading-tight">เข้าสู่ระบบ</h2>
        <p class="text-center text-gray-500 text-sm mt-1 mb-5 leading-normal">ยินดีต้อนรับกลับเข้าสู่ Lost & Found</p>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="p-4 mb-6 text-sm text-green-700 bg-green-100 border border-green-300 rounded-md">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="p-4 mb-6 text-sm text-red-700 bg-red-100 border border-red-300 rounded-md">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo $base_url; ?>/actions/auth_action.php" method="POST" class="space-y-5">
            <?php render_csrf_input(); ?>
            <input type="hidden" name="action" value="login">
            
            <div>
                <label class="block font-medium text-primary mb-1">อีเมล</label>
                <input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="example@email.com" required>
            </div>
            
            <div>
                <label class="block font-medium text-primary mb-1">รหัสผ่าน</label>
                <div class="relative password-wrapper">
                    <input type="password" name="password" class="w-full pl-4 pr-11 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="••••••••" required>
                    <button type="button" class="toggle-password-btn hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1 transition">
                        <svg class="w-5 h-5 icon-eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.573 16.49 16.638 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <svg class="w-5 h-5 icon-eye hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.326 16.157 7.305 19.5 12 19.5c1.84 0 3.568-.45 5.088-1.246M9.88 9.88a3 3 0 104.24 4.24M10.73 5.08A10.43 10.43 0 0112 4.5c4.695 0 8.673 2.926 10.168 7.083a10.495 10.495 0 01-2.28 3.864M3 3l18 18"></path></svg>
                    </button>
                </div>
            </div>
            
            <div class="flex justify-between items-center text-sm">
                <label class="flex items-center cursor-pointer text-gray-600">
                    <input type="checkbox" name="remember" class="mr-2 rounded text-primary focus:ring-primary"> จดจำฉันไว้
                </label>
                <a href="<?php echo $base_url; ?>/pages/forgot_password.php" class="text-accent hover:text-primary transition">ลืมรหัสผ่าน?</a>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-primary text-white font-medium text-lg rounded-md hover:bg-secondary transition shadow-sm">เข้าสู่ระบบ</button>
        </form>

        <!-- Divider -->
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
            <div class="relative flex justify-center text-sm"><span class="px-4 bg-white text-gray-400">หรือเข้าสู่ระบบด้วย</span></div>
        </div>

        <!-- Social Login Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <a href="<?php echo $base_url; ?>/actions/social_auth_action.php?provider=google" class="flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
                <span>Google</span>
            </a>
            <a href="<?php echo $base_url; ?>/actions/social_auth_action.php?provider=facebook" class="flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                <svg class="w-5 h-5 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                <span>Facebook</span>
            </a>
        </div>

        <p class="text-center mt-6 text-sm text-gray-600">
            ยังไม่มีบัญชีใช่หรือไม่? <a href="<?php echo $base_url; ?>/pages/register.php" class="font-semibold text-accent hover:text-primary transition">สมัครสมาชิก</a>
        </p>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
