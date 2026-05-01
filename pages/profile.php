<?php
$base_url = 'http://localhost/Lost_found';
require_once '../includes/header.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT email, first_name, last_name, phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
    $user = null;
}
?>

<div class="py-12 bg-background flex-grow">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-primary mb-8">ตั้งค่าโปรไฟล์ส่วนตัว</h1>

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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left: Sidebar Info -->
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 text-center">
                    <div class="w-24 h-24 rounded-full bg-accent mx-auto flex items-center justify-center text-white text-3xl font-bold shadow-inner mb-4">
                        <?php echo mb_substr($user['first_name'], 0, 1); ?>
                    </div>
                    <h2 class="text-xl font-bold text-primary"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                    <p class="text-gray-500 text-sm mb-6"><?php echo htmlspecialchars($user['email']); ?></p>
                    
                    <div class="border-t border-gray-100 pt-6 text-left space-y-4">
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            <?php echo $user['phone'] ? htmlspecialchars($user['phone']) : '<span class="text-gray-400 italic">ไม่ได้ระบุเบอร์โทรศัพท์</span>'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Edit Forms -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Section 1: Basic Info -->
                <div class="bg-white p-8 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold text-primary mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        แก้ไขข้อมูลส่วนตัว
                    </h3>
                    <form action="<?php echo $base_url; ?>/actions/profile_action.php" method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ <span class="text-red-500">*</span></label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-accent focus:border-accent outline-none" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">นามสกุล <span class="text-red-500">*</span></label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-accent focus:border-accent outline-none" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล (ไม่สามารถแก้ไขได้)</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-md bg-gray-50 text-gray-500 cursor-not-allowed outline-none" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์ (ไม่สามารถแก้ไขได้)</label>
                            <input type="tel" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-md bg-gray-50 text-gray-500 cursor-not-allowed outline-none" readonly>
                            <p class="text-[11px] text-gray-400 mt-1">เบอร์นี้จะถูกใช้เป็นค่าเริ่มต้นเมื่อคุณแจ้งของหายหรือพบของ</p>
                        </div>
                        <div class="pt-2">
                            <button type="submit" class="px-6 py-2.5 bg-primary text-white font-medium rounded-md hover:bg-secondary transition shadow-sm">บันทึกการเปลี่ยนแปลง</button>
                        </div>
                    </form>
                </div>

                <!-- Section 2: Change Password -->
                <div class="bg-white p-8 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold text-primary mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 00-2 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        เปลี่ยนรหัสผ่าน
                    </h3>
                    <form action="<?php echo $base_url; ?>/actions/profile_action.php" method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="change_password">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่านเดิม</label>
                            <input type="password" name="current_password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-accent focus:border-accent outline-none" required>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่านใหม่</label>
                                <input type="password" name="new_password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-accent focus:border-accent outline-none" required minlength="8">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ยืนยันรหัสผ่านใหม่</label>
                                <input type="password" name="confirm_new_password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-accent focus:border-accent outline-none" required minlength="8">
                            </div>
                        </div>
                        <div class="pt-2">
                            <button type="submit" class="px-6 py-2.5 bg-gray-800 text-white font-medium rounded-md hover:bg-black transition shadow-sm">อัปเดตรหัสผ่าน</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
