<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';
require_once '../../config/migrate_admin_tables.php'; // Ensure DB structure exists

if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

// Fetch Admin Logs with pagination & search filter
$search_query = trim($_GET['q'] ?? '');
$action_filter = trim($_GET['action_type'] ?? '');

$sql = "SELECT l.*, u.first_name, u.last_name, u.email 
        FROM admin_logs l 
        JOIN users u ON l.admin_id = u.id 
        WHERE 1=1";
$params = [];

if (!empty($search_query)) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR l.description LIKE ?)";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
}

if (!empty($action_filter)) {
    $sql .= " AND l.action_type = ?";
    $params[] = $action_filter;
}

$sql .= " ORDER BY l.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_logs = count($logs);
?>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'dashboard';
    require_once '../../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-200">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    ประวัติการทำงานของแอดมิน
                </h1>
            </div>

            <a href="<?php echo $base_url; ?>/pages/admin/admin_dashboard.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-slate-200 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 transition shadow-xs self-start sm:self-auto">
                ← กลับหน้าภาพรวมระบบ
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs mb-6">
            <form method="GET" action="" class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-3 w-full sm:w-auto flex-1">
                    <div class="relative w-full sm:w-80">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="ค้นหาชื่อแอดมิน หรือรายละเอียด..." class="w-full pl-9 pr-4 py-2 border border-slate-200 bg-white rounded-xl text-xs outline-none focus:border-slate-800 transition">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition shadow-xs">
                        ค้นหา
                    </button>
                    <?php if (!empty($search_query)): ?>
                        <a href="<?php echo $base_url; ?>/pages/admin/admin_logs.php" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition">ล้างตัวกรอง</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">บันทึกประวัติการทำงานล่าสุด</h3>
                <span class="text-xs text-slate-500">พบทั้งหมด <?php echo number_format($total_logs); ?> รายการ</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/80 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-100 font-bold">
                        <tr>
                            <th class="px-6 py-3.5">วัน-เวลา</th>
                            <th class="px-6 py-3.5">ผู้ดำเนินการ</th>
                            <th class="px-6 py-3.5">ประเภทการทำงาน</th>
                            <th class="px-6 py-3.5">รายละเอียดกิจกรรม</th>
                            <th class="px-6 py-3.5 text-right">หมายเลขไอพี</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-xs">
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                    ยังไม่มีประวัติการทำงานถูกบันทึกในขณะนี้
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $action_badge_map = [
                                'warn_user' => ['label' => 'ตักเตือนผู้ใช้', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
                                'hide_item' => ['label' => 'ซ่อนประกาศ', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
                                'restore_item' => ['label' => 'คืนสถานะประกาศ', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                'dismiss_report' => ['label' => 'ยกเลิกรายงาน', 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
                                'ban_user' => ['label' => 'ระงับบัญชี', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
                                'unban_user' => ['label' => 'ปลดระงับบัญชี', 'class' => 'bg-teal-50 text-teal-700 border-teal-200'],
                                'approve_claim' => ['label' => 'อนุมัติคำร้อง', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                'reject_claim' => ['label' => 'ปฏิเสธคำร้อง', 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
                                'category_add' => ['label' => 'เพิ่มหมวดหมู่', 'class' => 'bg-purple-50 text-purple-700 border-purple-200'],
                                'category_edit' => ['label' => 'แก้ไขหมวดหมู่', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
                                'category_toggle' => ['label' => 'ปรับสถานะหมวดหมู่', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
                            ];
                            ?>
                            <?php foreach ($logs as $log): ?>
                                <?php 
                                    $action_key = $log['action_type'];
                                    $badge = $action_badge_map[$action_key] ?? ['label' => $action_key, 'class' => 'bg-slate-100 text-slate-700 border-slate-200'];
                                ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-500">
                                        <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-900 font-semibold">
                                        <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 border rounded-md font-semibold text-xs <?php echo $badge['class']; ?>">
                                            <?php echo htmlspecialchars($badge['label']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-800">
                                        <?php echo htmlspecialchars($log['description']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap font-mono text-slate-400">
                                        <?php echo htmlspecialchars($log['ip_address'] ?? '127.0.0.1'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
    </main>
</div>

<?php require_once '../../includes/footer.php'; ?>
