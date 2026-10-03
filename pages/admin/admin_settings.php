<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

// Check or Create `system_settings` table dynamically if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Insert Default Values if empty
    $defaultSettings = [
        'system_name' => 'Lost & Found',
        'contact_email' => 'admin@lostandfound.com',
        'contact_phone' => '02-123-4567'
    ];

    $checkStmt = $pdo->query("SELECT COUNT(*) FROM system_settings");
    if ($checkStmt->fetchColumn() == 0) {
        $insertStmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :val)");
        foreach ($defaultSettings as $k => $v) {
            $insertStmt->execute([':key' => $k, ':val' => $v]);
        }
    }
} catch (PDOException $e) {
    // Continue gracefully if database creates fail
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $settingsToUpdate = [
        'system_name' => trim($_POST['system_name'] ?? 'Lost & Found'),
        'contact_email' => trim($_POST['contact_email'] ?? ''),
        'contact_phone' => trim($_POST['contact_phone'] ?? '')
    ];

    try {
        $updateStmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE setting_value = :val_update");
        foreach ($settingsToUpdate as $k => $v) {
            $updateStmt->execute([
                ':key' => $k,
                ':val' => $v,
                ':val_update' => $v
            ]);
        }
        $_SESSION['success'] = "บันทึกการตั้งค่าระบบเรียบร้อยแล้ว";
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    }
    
    header("Location: admin_settings.php");
    exit;
}

// Load current settings from database
$settings = [
    'system_name' => 'Lost & Found',
    'contact_email' => 'admin@lostandfound.com',
    'contact_phone' => '02-123-4567'
];

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Fallback to defaults
}
?>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'settings';
    require_once '../../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-500 uppercase tracking-wider mb-1 font-semibold">
                    <span>ศูนย์ควบคุมผู้ดูแลระบบ</span>
                    <span>•</span>
                    <span>ตั้งค่าระบบ</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                    ตั้งค่าระบบทั่วไป
                </h1>
            </div>
        </div>

        <!-- Notification Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-medium flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-medium flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <form action="admin_settings.php" method="POST" class="space-y-6 w-full pb-12">
            
            <!-- Card 1: ข้อมูลทั่วไปของระบบ (General Info) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 md:p-8 shadow-xs space-y-6">
                <div class="flex items-center gap-3.5 border-b border-slate-100 pb-5">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center flex-shrink-0 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0v-4a1 1 0 011-1h2a1 1 0 011 1v4m-4 0h4"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">ข้อมูลทั่วไปของระบบ</h3>
                        <p class="text-sm text-slate-500">กำหนดชื่อระบบ และข้อมูลช่องทางติดต่อส่วนกลางขององค์กร</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">ชื่อระบบ (System Name)</label>
                        <input type="text" name="system_name" value="<?php echo htmlspecialchars($settings['system_name']); ?>" required class="w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-800 transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">อีเมลติดต่อส่วนกลาง (Contact Email)</label>
                        <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email']); ?>" required class="w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-800 transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">เบอร์โทรศัพท์ติดต่อ (Contact Phone)</label>
                        <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone']); ?>" required class="w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-800 transition">
                    </div>
                </div>
            </div>

            <!-- Submit Button Section -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" name="save_settings" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>บันทึกการตั้งค่า</span>
                </button>
            </div>

        </form>

    </main>
</div>

<?php require_once '../../includes/footer.php'; ?>
