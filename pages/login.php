<?php
$base_url = 'http://localhost/Lost_found';
require_once '../includes/header.php';
?>

<div class="py-16 bg-background flex-grow flex items-center justify-center px-4">
    <div class="w-full max-w-lg bg-white p-8 md:p-10 rounded-lg shadow-sm border border-gray-200">
        <h2 class="text-2xl font-bold text-center text-primary mb-2">เข้าสู่ระบบ</h2>
        <p class="text-center text-gray-500 mb-8">ยินดีต้อนรับกลับเข้าสู่ Lost & Found</p>

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
            <input type="hidden" name="action" value="login">
            
            <div>
                <label class="block font-medium text-primary mb-1">อีเมล</label>
                <input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="example@email.com" required>
            </div>
            
            <div>
                <label class="block font-medium text-primary mb-1">รหัสผ่าน</label>
                <input type="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="••••••••" required>
            </div>
            
            <div class="flex justify-between items-center text-sm">
                <label class="flex items-center cursor-pointer text-gray-600">
                    <input type="checkbox" name="remember" class="mr-2 rounded text-primary focus:ring-primary"> จดจำฉันไว้
                </label>
                <a href="#" class="text-accent hover:text-primary transition">ลืมรหัสผ่าน?</a>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-primary text-white font-medium text-lg rounded-md hover:bg-secondary transition shadow-sm">เข้าสู่ระบบ</button>
        </form>

        <p class="text-center mt-6 text-sm text-gray-600">
            ยังไม่มีบัญชีใช่หรือไม่? <a href="<?php echo $base_url; ?>/pages/register.php" class="font-semibold text-accent hover:text-primary transition">สมัครสมาชิก</a>
        </p>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
