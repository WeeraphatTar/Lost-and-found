<?php
$base_url = 'http://localhost/lost-and-found';
require_once '../includes/header.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data & linked social accounts
try {
    $stmt = $pdo->prepare("SELECT email, first_name, last_name, phone, password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $stmt_soc = $pdo->prepare("SELECT provider, provider_user_id FROM user_social_accounts WHERE user_id = ?");
    $stmt_soc->execute([$user_id]);
    $social_rows = $stmt_soc->fetchAll();
    
    $linked_providers = [];
    foreach ($social_rows as $sr) {
        $linked_providers[$sr['provider']] = $sr['provider_user_id'];
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
    $user = null;
    $linked_providers = [];
}
?>

<div class="py-10 bg-background flex-grow">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-800 tracking-tight">ตั้งค่าโปรไฟล์ส่วนตัว</h1>
            <p class="text-sm text-slate-500 mt-1">จัดการข้อมูลบัญชีผู้ใช้ ข้อมูลติดต่อ และความปลอดภัย</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="p-4 mb-6 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center shadow-sm">
                <svg class="w-5 h-5 mr-3 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="p-4 mb-6 text-sm text-rose-800 bg-rose-50 border border-rose-200 rounded-xl flex items-center shadow-sm">
                <svg class="w-5 h-5 mr-3 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left: Sidebar Info (col-span-4 / w-full lg:w-80) -->
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200 text-center">
                    <div class="w-24 h-24 rounded-full bg-[#0f172a] mx-auto flex items-center justify-center text-white text-3xl font-bold shadow-md mb-4 ring-4 ring-slate-100">
                        <?php echo mb_substr($user['first_name'], 0, 1); ?>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                    <p class="text-slate-500 text-sm mb-6 truncate"><?php echo htmlspecialchars($user['email']); ?></p>
                    
                    <div class="border-t border-slate-100 pt-6 text-left space-y-3.5">
                        <div class="flex items-center text-sm text-slate-600">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center mr-3 text-slate-400 flex-shrink-0 border border-slate-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <span class="truncate"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="flex items-center text-sm text-slate-600">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center mr-3 text-slate-400 flex-shrink-0 border border-slate-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            </div>
                            <span><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : '<span class="text-slate-400 italic">ไม่ได้ระบุเบอร์โทรศัพท์</span>'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Edit Forms (col-span-8) -->
            <div class="lg:col-span-8 space-y-8">
                
                <!-- Section 1: Basic Info -->
                <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200">
                    <div class="border-b border-slate-100 pb-4 mb-6">
                        <h3 class="text-lg font-semibold text-slate-800">
                            ข้อมูลส่วนตัว
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">อัปเดตข้อมูลส่วนบุคคลและรูปถ่ายโปรไฟล์ของคุณ</p>
                    </div>
                    
                    <form action="<?php echo $base_url; ?>/actions/profile_action.php" method="POST" class="space-y-5">
                        <?php render_csrf_input(); ?>
                        <input type="hidden" name="action" value="update_profile">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">ชื่อ <span class="text-rose-500">*</span></label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">นามสกุล <span class="text-rose-500">*</span></label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">อีเมล (ไม่สามารถแก้ไขได้)</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-lg bg-slate-50 text-slate-500 text-sm cursor-not-allowed outline-none" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">เบอร์โทรศัพท์ (ไม่สามารถแก้ไขได้)</label>
                            <input type="tel" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-lg bg-slate-50 text-slate-500 text-sm cursor-not-allowed outline-none" readonly>
                            <p class="text-xs text-slate-400 mt-1.5">เบอร์นี้จะถูกใช้เป็นค่าเริ่มต้นเมื่อคุณแจ้งของหายหรือพบของ</p>
                        </div>
                        <div class="pt-3 flex justify-end">
                            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-[#0f172a] hover:bg-slate-800 text-white font-medium text-sm rounded-lg transition shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1">
                                บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Section 2: Social Account Connections -->
                <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200">
                    <div class="border-b border-slate-100 pb-4 mb-6">
                        <h3 class="text-lg font-semibold text-slate-800">
                            เชื่อมต่อบัญชี Social Media
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">จัดการช่องทางเข้าสู่ระบบด่วนผ่านผู้ให้บริการภายนอก</p>
                    </div>
                    <div class="space-y-3.5">
                        <!-- Google Connection -->
                        <div class="flex items-center justify-between p-4 border border-slate-200 rounded-xl bg-slate-50/50 hover:bg-slate-50 transition">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-10 h-10 rounded-lg bg-white border border-slate-200 flex items-center justify-center flex-shrink-0 shadow-xs">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-800 text-sm">Google Account</p>
                                    <p class="text-xs text-slate-500"><?php echo isset($linked_providers['google']) ? 'เชื่อมต่อแล้ว' : 'ยังไม่ได้เชื่อมต่อ'; ?></p>
                                </div>
                            </div>
                            <?php if (isset($linked_providers['google'])): ?>
                                <form action="<?php echo $base_url; ?>/actions/profile_action.php" method="POST">
                                    <?php render_csrf_input(); ?>
                                    <input type="hidden" name="action" value="disconnect_social">
                                    <input type="hidden" name="provider" value="google">
                                    <button type="submit" onclick="return confirm('คุณต้องการยกเลิกการเชื่อมต่อกับ Google ใช่หรือไม่?');" class="px-3.5 py-1.5 text-xs text-rose-600 bg-white border border-rose-200 rounded-lg hover:bg-rose-50 transition font-medium">ยกเลิกการเชื่อมต่อ</button>
                                </form>
                            <?php else: ?>
                                <a href="<?php echo $base_url; ?>/actions/social_auth_action.php?provider=google" class="px-4 py-1.5 text-xs text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition font-medium shadow-xs">เชื่อมต่อ</a>
                            <?php endif; ?>
                        </div>

                        <!-- Facebook Connection -->
                        <div class="flex items-center justify-between p-4 border border-slate-200 rounded-xl bg-slate-50/50 hover:bg-slate-50 transition">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-10 h-10 rounded-lg bg-white border border-slate-200 flex items-center justify-center flex-shrink-0 shadow-xs">
                                    <svg class="w-5 h-5 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-800 text-sm">Facebook Account</p>
                                    <p class="text-xs text-slate-500"><?php echo isset($linked_providers['facebook']) ? 'เชื่อมต่อแล้ว' : 'ยังไม่ได้เชื่อมต่อ'; ?></p>
                                </div>
                            </div>
                            <?php if (isset($linked_providers['facebook'])): ?>
                                <form action="<?php echo $base_url; ?>/actions/profile_action.php" method="POST">
                                    <?php render_csrf_input(); ?>
                                    <input type="hidden" name="action" value="disconnect_social">
                                    <input type="hidden" name="provider" value="facebook">
                                    <button type="submit" onclick="return confirm('คุณต้องการยกเลิกการเชื่อมต่อกับ Facebook ใช่หรือไม่?');" class="px-3.5 py-1.5 text-xs text-rose-600 bg-white border border-rose-200 rounded-lg hover:bg-rose-50 transition font-medium">ยกเลิกการเชื่อมต่อ</button>
                                </form>
                            <?php else: ?>
                                <a href="<?php echo $base_url; ?>/actions/social_auth_action.php?provider=facebook" class="px-4 py-1.5 text-xs text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition font-medium shadow-xs">เชื่อมต่อ</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Change/Set Password -->
                <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200">
                    <div class="border-b border-slate-100 pb-4 mb-6">
                        <h3 class="text-lg font-semibold text-slate-800">
                            <?php echo empty($user['password_hash']) ? 'กำหนดรหัสผ่านสำหรับเข้าสู่ระบบ' : 'เปลี่ยนรหัสผ่าน'; ?>
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">เพื่อความปลอดภัย ควรใช้รหัสผ่านที่มีความยาวอย่างน้อย 8 ตัวอักษร</p>
                    </div>
                    
                    <?php if (empty($user['password_hash'])): ?>
                        <p class="text-xs text-slate-500 mb-5 bg-amber-50 border border-amber-200 p-3 rounded-lg">คุณสมัครใช้งานผ่าน Social Login และยังไม่ได้กำหนดรหัสผ่าน คุณสามารถกำหนดรหัสผ่านเพิ่มเพื่อใช้เข้าสู่ระบบด้วย Email + Password ได้</p>
                    <?php endif; ?>

                    <form action="<?php echo $base_url; ?>/actions/profile_action.php" method="POST" class="space-y-5">
                        <?php render_csrf_input(); ?>
                        <input type="hidden" name="action" value="<?php echo empty($user['password_hash']) ? 'set_initial_password' : 'change_password'; ?>">
                        
                        <?php if (!empty($user['password_hash'])): ?>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">รหัสผ่านเดิม</label>
                                <div class="relative password-wrapper">
                                    <input type="password" name="current_password" class="w-full pl-3.5 pr-11 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition" required>
                                    <button type="button" class="toggle-password-btn hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none p-1 transition">
                                        <svg class="w-5 h-5 icon-eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.573 16.49 16.638 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <svg class="w-5 h-5 icon-eye hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.326 16.157 7.305 19.5 12 19.5c1.84 0 3.568-.45 5.088-1.246M9.88 9.88a3 3 0 104.24 4.24M10.73 5.08A10.43 10.43 0 0112 4.5c4.695 0 8.673 2.926 10.168 7.083a10.495 10.495 0 01-2.28 3.864M3 3l18 18"></path></svg>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">รหัสผ่านใหม่</label>
                                <div class="relative password-wrapper">
                                    <input type="password" name="new_password" class="w-full pl-3.5 pr-11 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition" required minlength="8">
                                    <button type="button" class="toggle-password-btn hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none p-1 transition">
                                        <svg class="w-5 h-5 icon-eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.573 16.49 16.638 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <svg class="w-5 h-5 icon-eye hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.326 16.157 7.305 19.5 12 19.5c1.84 0 3.568-.45 5.088-1.246M9.88 9.88a3 3 0 104.24 4.24M10.73 5.08A10.43 10.43 0 0112 4.5c4.695 0 8.673 2.926 10.168 7.083a10.495 10.495 0 01-2.28 3.864M3 3l18 18"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">ยืนยันรหัสผ่านใหม่</label>
                                <div class="relative password-wrapper">
                                    <input type="password" name="confirm_new_password" class="w-full pl-3.5 pr-11 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition" required minlength="8">
                                    <button type="button" class="toggle-password-btn hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none p-1 transition">
                                        <svg class="w-5 h-5 icon-eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.573 16.49 16.638 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <svg class="w-5 h-5 icon-eye hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.326 16.157 7.305 19.5 12 19.5c1.84 0 3.568-.45 5.088-1.246M9.88 9.88a3 3 0 104.24 4.24M10.73 5.08A10.43 10.43 0 0112 4.5c4.695 0 8.673 2.926 10.168 7.083a10.495 10.495 0 01-2.28 3.864M3 3l18 18"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="pt-3 flex justify-end">
                            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-[#0f172a] hover:bg-slate-800 text-white font-medium text-sm rounded-lg transition shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1">
                                <?php echo empty($user['password_hash']) ? 'บันทึกรหัสผ่าน' : 'อัปเดตรหัสผ่าน'; ?>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
