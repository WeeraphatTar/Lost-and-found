<?php
$base_url = 'http://localhost/lost-and-found';
require_once '../includes/header.php';
?>

<div class="py-16 bg-background flex-grow flex items-center justify-center px-4">
    <div class="w-full max-w-lg bg-white p-8 md:p-10 rounded-lg shadow-sm border border-gray-200">
        <h2 class="text-2xl font-bold text-center text-primary mb-2">สมัครสมาชิก</h2>
        <p class="text-center text-gray-500 mb-8">สร้างบัญชีเพื่อเริ่มใช้งานระบบ Lost & Found</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="p-4 mb-6 text-sm text-red-700 bg-red-100 border border-red-300 rounded-md">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo $base_url; ?>/actions/auth_action.php" method="POST" class="space-y-5">
            <input type="hidden" name="action" value="register">
            
            <div class="flex flex-col sm:flex-row gap-5">
                <div class="flex-1">
                    <label class="block font-medium text-primary mb-1">ชื่อ <span class="asterisk text-red-500">*</span></label>
                    <input type="text" name="first_name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" required>
                </div>
                <div class="flex-1">
                    <label class="block font-medium text-primary mb-1">นามสกุล <span class="asterisk text-red-500">*</span></label>
                    <input type="text" name="last_name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" required>
                </div>
            </div>

            <div>
                <label class="block font-medium text-primary mb-1">อีเมล <span class="asterisk text-red-500">*</span></label>
                <input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="example@email.com" required>
            </div>

            <div>
                <label class="block font-medium text-primary mb-1">เบอร์โทรศัพท์ <span class="asterisk text-red-500">*</span></label>
                <input type="tel" name="phone" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="08x-xxx-xxxx" required>
            </div>
            
            <div>
                <label class="block font-medium text-primary mb-1">รหัสผ่าน <span class="asterisk text-red-500">*</span></label>
                <input type="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="รหัสผ่านอย่างน้อย 8 ตัวอักษร" required minlength="8">
            </div>

            <div>
                <label class="block font-medium text-primary mb-1">ยืนยันรหัสผ่าน <span class="asterisk text-red-500">*</span></label>
                <input type="password" name="confirm_password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="กรอกรหัสผ่านอีกครั้ง" required minlength="8">
            </div>
            
            <div class="text-sm text-gray-600 pt-2">
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox" required class="mt-1 mr-2 rounded text-primary focus:ring-primary"> 
                    <span>ฉันยอมรับ <a href="#" class="text-accent hover:underline">ข้อตกลงการใช้งาน</a> และ <a href="#" class="text-accent hover:underline">นโยบายความเป็นส่วนตัว</a></span>
                </label>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-primary text-white font-medium text-lg rounded-md hover:bg-secondary transition shadow-sm mt-2">สมัครสมาชิก</button>
        </form>

        <p class="text-center mt-6 text-sm text-gray-600">
            มีบัญชีผู้ใช้แล้ว? <a href="<?php echo $base_url; ?>/pages/login.php" class="font-semibold text-accent hover:text-primary transition">เข้าสู่ระบบ</a>
        </p>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
