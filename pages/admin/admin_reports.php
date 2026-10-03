<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'pending';
$allowed_statuses = ['pending', 'action_taken', 'dismissed', 'all'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = 'pending';
}

$query = "SELECT r.*, 
                 i.title as item_title, i.image_path as item_image, i.status as item_status, i.user_id as owner_id,
                 owner.first_name as owner_first, owner.last_name as owner_last, owner.email as owner_email,
                 u.first_name as reporter_first, u.last_name as reporter_last, u.email as reporter_email
          FROM item_reports r
          JOIN items i ON r.item_id = i.id
          JOIN users u ON r.reporter_id = u.id
          LEFT JOIN users owner ON i.user_id = owner.id";

if ($status_filter !== 'all') {
    $query .= " WHERE r.status = :status";
}
$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
if ($status_filter !== 'all') {
    $stmt->bindValue(':status', $status_filter);
}
$stmt->execute();
$reports = $stmt->fetchAll();

// Count stats
$pending_count = $pdo->query("SELECT COUNT(*) FROM item_reports WHERE status = 'pending'")->fetchColumn();
$action_count = $pdo->query("SELECT COUNT(*) FROM item_reports WHERE status = 'action_taken'")->fetchColumn();
$dismiss_count = $pdo->query("SELECT COUNT(*) FROM item_reports WHERE status = 'dismissed'")->fetchColumn();

$reason_map = [
    'spam' => 'โพสต์สแปม หรือรบกวน',
    'fake_info' => 'ข้อมูลเท็จ หรือแอบอ้าง',
    'inappropriate' => 'เนื้อหาไม่เหมาะสม',
    'fraud' => 'สงสัยการทุจริต',
    'other' => 'อื่นๆ'
];
?>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'reports';
    require_once '../../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        
        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-sm font-semibold"><?php echo htmlspecialchars($_SESSION['success']); ?></span>
                </div>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-sm font-semibold"><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Navigation Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                    <span>ศูนย์ควบคุมผู้ดูแลระบบ</span>
                    <span>•</span>
                    <span>จัดการรายงานความไม่เหมาะสม</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    จัดการรายงานความไม่เหมาะสม
                </h1>
            </div>
        </div>

        <!-- Filter Sub-Tabs (Clean Slate Design System) -->
        <?php $all_count = $pending_count + $action_count + $dismiss_count; ?>
        <div class="flex items-center gap-1 mb-6 p-1 bg-slate-100 rounded-xl border border-slate-200/80 overflow-x-auto text-xs font-medium w-fit">
            <a href="admin_reports.php?status=pending" class="px-3.5 py-2 rounded-lg transition flex items-center gap-2 whitespace-nowrap <?php echo $status_filter === 'pending' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/80 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'; ?>">
                <span>รอการตรวจสอบ</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $status_filter === 'pending' ? 'bg-rose-100 text-rose-800 border border-rose-200/80' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $pending_count; ?></span>
            </a>

            <a href="admin_reports.php?status=action_taken" class="px-3.5 py-2 rounded-lg transition flex items-center gap-2 whitespace-nowrap <?php echo $status_filter === 'action_taken' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/80 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'; ?>">
                <span>ดำเนินการแล้ว</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $status_filter === 'action_taken' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200/80' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $action_count; ?></span>
            </a>

            <a href="admin_reports.php?status=dismissed" class="px-3.5 py-2 rounded-lg transition flex items-center gap-2 whitespace-nowrap <?php echo $status_filter === 'dismissed' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/80 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'; ?>">
                <span>ไม่พบความผิด</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $status_filter === 'dismissed' ? 'bg-slate-200 text-slate-700 border border-slate-300/80' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $dismiss_count; ?></span>
            </a>

            <a href="admin_reports.php?status=all" class="px-3.5 py-2 rounded-lg transition flex items-center gap-2 whitespace-nowrap <?php echo $status_filter === 'all' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/80 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'; ?>">
                <span>ทั้งหมด (<?php echo $all_count; ?>)</span>
            </a>
        </div>

        <?php if (empty($reports)): ?>
            <div class="bg-white rounded-xl p-12 text-center border border-slate-200 shadow-xs">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">ไม่มีรายการรายงานในหมวดนี้</h3>
                <p class="text-xs text-slate-500">ระบบไม่พบรายงานปัญหาที่ตรงตามเงื่อนไขในขณะนี้</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($reports as $r): ?>
                    <?php 
                        $reason_label = $reason_map[$r['reason_type']] ?? $r['reason_type'];
                        $img_src = !empty($r['item_image']) ? $base_url . '/' . htmlspecialchars($r['item_image']) : '';
                        $owner_fullname = !empty($r['owner_first']) ? htmlspecialchars($r['owner_first'] . ' ' . $r['owner_last']) : 'ไม่ทราบชื่อ';
                        $report_text = !empty($r['reason_detail']) ? $r['reason_detail'] : (!empty($r['details']) ? $r['details'] : (!empty($r['description']) ? $r['description'] : ''));
                    ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden p-5 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                        
                        <!-- Left Info Content Area -->
                        <div class="flex items-start gap-4 flex-grow min-w-0 w-full lg:w-auto">
                            <!-- Item Thumbnail -->
                            <div class="w-20 h-20 bg-slate-100 rounded-xl overflow-hidden flex-shrink-0 border border-slate-200 shadow-2xs relative">
                                <?php if ($img_src): ?>
                                    <img src="<?php echo $img_src; ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-slate-400">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Detail Content -->
                            <div class="space-y-2 min-w-0 flex-grow">
                                <!-- Top Row: Report Type Badge & Date -->
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="px-2.5 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded-md text-[11px] font-bold flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <?php echo htmlspecialchars($reason_label); ?>
                                    </span>
                                    <span class="text-xs text-slate-400 font-mono">
                                        <?php echo date('d M Y H:i', strtotime($r['created_at'])); ?>
                                    </span>
                                    <?php if ($r['item_status'] === 'hidden'): ?>
                                        <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-md text-[10px] font-bold">ประกาศถูกซ่อนแล้ว</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Item Title -->
                                <h3 class="font-bold text-slate-900 text-base truncate">
                                    <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $r['item_id']; ?>" target="_blank" class="hover:underline flex items-center gap-1.5">
                                        <span><?php echo htmlspecialchars($r['item_title']); ?></span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                    </a>
                                </h3>

                                <!-- Parties Info -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600">
                                    <p>
                                        <span class="text-slate-400">เจ้าของโพสต์:</span> 
                                        <span class="font-semibold text-slate-800"><?php echo $owner_fullname; ?></span>
                                    </p>
                                    <p>
                                        <span class="text-slate-400">ผู้รายงาน:</span> 
                                        <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($r['reporter_first'] . ' ' . $r['reporter_last']); ?></span>
                                    </p>
                                </div>

                                <!-- Reason Details Box -->
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 mt-2 text-sm max-w-2xl">
                                    <span class="font-medium text-slate-800 block mb-1 text-xs">เหตุผลที่รายงาน:</span>
                                    <p class="text-slate-600 leading-relaxed text-xs sm:text-sm"><?php echo !empty($report_text) ? nl2br(htmlspecialchars($report_text)) : 'ไม่ได้ระบุรายละเอียดเพิ่มเติม'; ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Right Action Buttons Area -->
                        <div class="flex items-center gap-2 flex-shrink-0 w-full lg:w-auto justify-start lg:justify-end border-t lg:border-t-0 pt-3 lg:pt-0 border-slate-100 text-xs flex-wrap self-center">
                            <!-- 1. View Item Button -->
                            <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $r['item_id']; ?>" target="_blank" class="px-3 py-1.5 bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 rounded-xl font-semibold transition flex items-center gap-1.5 shadow-2xs">
                                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                ดูประกาศ
                            </a>

                            <!-- 2. Toggle Item Visibility (Hide / Restore Button) -->
                            <?php if ($r['item_status'] === 'hidden'): ?>
                                <form action="<?php echo $base_url; ?>/actions/admin_manage_action.php" method="POST" class="inline" onsubmit="return confirm('ยืนยันปลดการซ่อนประกาศ และคืนสถานะเปิดใช้งานตามปกติใช่หรือไม่?');">
                                    <input type="hidden" name="action" value="restore_item">
                                    <input type="hidden" name="item_id" value="<?php echo $r['item_id']; ?>">
                                    <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold transition flex items-center gap-1.5 shadow-2xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        ยกเลิกการซ่อน
                                    </button>
                                </form>
                            <?php else: ?>
                                <button type="button" 
                                        onclick="openHideModal(<?php echo $r['item_id']; ?>, '<?php echo addslashes(htmlspecialchars($r['item_title'])); ?>', '<?php echo addslashes($owner_fullname); ?>')"
                                        class="px-3 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200/80 rounded-xl font-semibold transition flex items-center gap-1.5 shadow-2xs">
                                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a8.962 8.962 0 012.122-.063c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"></path></svg>
                                    ซ่อนประกาศ
                                </button>
                            <?php endif; ?>

                            <!-- 3. Report Decision Controls -->
                            <?php if ($r['status'] === 'pending'): ?>
                                <button type="button" 
                                        onclick="openWarnModal(<?php echo $r['id']; ?>, <?php echo $r['item_id']; ?>, '<?php echo addslashes(htmlspecialchars($r['item_title'])); ?>', '<?php echo addslashes($owner_fullname); ?>')"
                                        class="px-3 py-1.5 bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200/80 rounded-xl font-semibold transition flex items-center gap-1.5 shadow-2xs">
                                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    ตักเตือนผู้โพสต์
                                </button>

                                <form action="<?php echo $base_url; ?>/actions/admin_manage_action.php" method="POST" class="inline">
                                    <input type="hidden" name="action" value="dismiss_report">
                                    <input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
                                    <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <button type="submit" onclick="return confirm('ยืนยันบันทึกผลว่าไม่พบความผิด และปิดการรายงานนี้ใช่หรือไม่?')" class="px-3 py-1.5 bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-300/80 rounded-xl font-semibold transition flex items-center gap-1.5 shadow-2xs">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        ไม่พบความผิด
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="px-3 py-1.5 rounded-xl text-xs font-semibold border <?php echo $r['status'] === 'action_taken' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'; ?>">
                                    <?php echo $r['status'] === 'action_taken' ? '✓ ดำเนินการแล้ว' : '✖ ไม่พบความผิด (ปิดรายงานแล้ว)'; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- ========================================== -->
<!-- MODAL 1: Warn User Modal                  -->
<!-- ========================================== -->
<div id="warnModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-[9999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 transform transition-all relative">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                    ⚠️
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">ตักเตือนผู้โพสต์ประกาศ</h3>
                    <p class="text-xs text-slate-500">ส่งข้อความแจ้งเตือนอย่างเป็นทางการไปยังผู้โพสต์</p>
                </div>
            </div>
            <button type="button" onclick="closeWarnModal()" class="text-slate-400 hover:text-slate-600 p-1 text-xl font-bold">✕</button>
        </div>

        <form action="<?php echo $base_url; ?>/actions/admin_manage_action.php" method="POST" class="mt-4 space-y-4">
            <input type="hidden" name="action" value="warn_user_report">
            <input type="hidden" name="report_id" id="warn_report_id" value="0">
            <input type="hidden" name="item_id" id="warn_item_id" value="0">

            <div class="bg-amber-50/70 p-3 rounded-xl border border-amber-200/80 text-xs text-amber-900 space-y-1">
                <p><span class="font-bold">ประกาศ:</span> <span id="warn_item_title" class="font-medium"></span></p>
                <p><span class="font-bold">เจ้าของโพสต์:</span> <span id="warn_owner_name" class="font-medium"></span></p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">เลือกแม่แบบข้อความเตือนด่วน</label>
                <select onchange="applyWarnTemplate(this.value)" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-amber-500 focus:outline-none">
                    <option value="">-- เลือกแม่แบบข้อความเตือน --</option>
                    <option value="โปรดตรวจสอบและแก้ไขข้อมูลประกาศให้ถูกต้อง สุภาพ และตรงตามความเป็นจริง">1. ข้อมูลไม่สุภาพ/ไม่ชัดเจน</option>
                    <option value="ภาพประกอบประกาศมีเนื้อหาไม่เหมาะสม กรุณาทำการแก้ไขทันที">2. ภาพประกอบไม่เหมาะสม</option>
                    <option value="ห้ามลงประกาศซ้ำซ้อนหรือสร้างโพสต์สแปมในระบบ">3. โพสต์ซ้ำซ้อน / สแปม</option>
                    <option value="โปรดเพิ่มข้อมูลการติดต่อและสถานที่ที่สามารถยืนยันได้เพื่อความปลอดภัย">4. เพิ่มเติมรายละเอียดสถานที่/ติดต่อ</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">ข้อความตักเตือนระบุเฉพาะ <span class="text-rose-500">*</span></label>
                <textarea name="warning_message" id="warn_message_text" rows="3" required placeholder="พิมพ์ข้อความที่ต้องการแจ้งเตือนไปยังผู้โพสต์..." class="w-full text-xs p-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 focus:border-transparent focus:outline-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeWarnModal()" class="px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl text-xs font-semibold transition">
                    ยกเลิก
                </button>
                <button type="submit" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    ส่งคำเตือนทันที
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 2: Hide Item Modal                  -->
<!-- ========================================== -->
<div id="hideModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-[9999] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 transform transition-all relative">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold">
                    🚫
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">ซ่อนประกาศและแจ้งเตือนผู้โพสต์</h3>
                    <p class="text-xs text-slate-500">ประกาศจะถูกซ่อนออกจากระบบทันที</p>
                </div>
            </div>
            <button type="button" onclick="closeHideModal()" class="text-slate-400 hover:text-slate-600 p-1 text-xl font-bold">✕</button>
        </div>

        <form action="<?php echo $base_url; ?>/actions/admin_manage_action.php" method="POST" class="mt-4 space-y-4">
            <input type="hidden" name="action" value="hide_item">
            <input type="hidden" name="item_id" id="hide_item_id" value="0">
            <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">

            <div class="bg-rose-50/70 p-3 rounded-xl border border-rose-200/80 text-xs text-rose-900 space-y-1">
                <p><span class="font-bold">ประกาศที่จะซ่อน:</span> <span id="hide_item_title" class="font-medium"></span></p>
                <p><span class="font-bold">เจ้าของโพสต์:</span> <span id="hide_owner_name" class="font-medium"></span></p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">เหตุผลในการซ่อนประกาศ</label>
                <select onchange="applyHideTemplate(this.value)" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-rose-500 focus:outline-none mb-2">
                    <option value="">-- เลือกแม่แบบเหตุผล --</option>
                    <option value="เนื้อหาเข้าข่ายสแปม หลอกลวง หรือข้อมูลเท็จ">1. สแปม / ข้อมูลเท็จ</option>
                    <option value="รูปภาพหรือเนื้อหาไม่เหมาะสม ขัดต่อเงื่อนไขการใช้งาน">2. เนื้อหาไม่เหมาะสม</option>
                    <option value="สงสัยว่าอาจเป็นประกาศทุจริต หรือแอบอ้างสิทธิ์ผู้อื่น">3. สงสัยการทุจริต / แอบอ้าง</option>
                </select>
                <textarea name="hide_reason" id="hide_reason_text" rows="3" placeholder="ระบุเหตุผลการซ่อนประกาศเพิ่มเติม (ระบบจะส่งเหตุผลนี้ไปยังผู้โพสต์)..." class="w-full text-xs p-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500 focus:border-transparent focus:outline-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeHideModal()" class="px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl text-xs font-semibold transition">
                    ยกเลิก
                </button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    ยืนยันซ่อนประกาศ
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openWarnModal(reportId, itemId, itemTitle, ownerName) {
    document.getElementById('warn_report_id').value = reportId;
    document.getElementById('warn_item_id').value = itemId;
    document.getElementById('warn_item_title').innerText = itemTitle;
    document.getElementById('warn_owner_name').innerText = ownerName;
    document.getElementById('warn_message_text').value = '';
    document.getElementById('warnModal').classList.remove('hidden');
}

function closeWarnModal() {
    document.getElementById('warnModal').classList.add('hidden');
}

function applyWarnTemplate(val) {
    if (val) {
        document.getElementById('warn_message_text').value = val;
    }
}

function openHideModal(itemId, itemTitle, ownerName) {
    document.getElementById('hide_item_id').value = itemId;
    document.getElementById('hide_item_title').innerText = itemTitle;
    document.getElementById('hide_owner_name').innerText = ownerName;
    document.getElementById('hide_reason_text').value = '';
    document.getElementById('hideModal').classList.remove('hidden');
}

function closeHideModal() {
    document.getElementById('hideModal').classList.add('hidden');
}

function applyHideTemplate(val) {
    if (val) {
        document.getElementById('hide_reason_text').value = val;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>