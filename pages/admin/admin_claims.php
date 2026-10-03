<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/index.php';</script>";
    exit;
}

$tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'pending';
$allowed_tabs = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($tab, $allowed_tabs)) {
    $tab = 'pending';
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build Query
$sql = "
    SELECT c.*, 
           i.title as item_title, i.category, i.secret_description, i.serial_number as item_sn, i.image_path as item_image, i.status as item_status, i.location as item_location, i.created_at as item_created_at,
           u_c.first_name as claimant_first, u_c.last_name as claimant_last, u_c.email as claimant_email,
           u_f.first_name as finder_first, u_f.last_name as finder_last, u_f.email as finder_email
    FROM claims c
    JOIN items i ON c.item_id = i.id
    JOIN users u_c ON c.claimant_id = u_c.id
    JOIN users u_f ON c.finder_id = u_f.id
    WHERE 1=1
";

$params = [];

if (!empty($search)) {
    $sql .= " AND (i.title LIKE :search OR u_c.first_name LIKE :search OR u_c.last_name LIKE :search OR c.id LIKE :search_id)";
    $params[':search'] = '%' . $search . '%';
    $params[':search_id'] = '%' . ltrim($search, '#CLM-clm-') . '%';
}

if ($tab === 'pending') {
    $sql .= " AND c.status IN ('pending', 'under_admin_review')";
} elseif ($tab === 'approved') {
    $sql .= " AND c.status IN ('approved', 'meeting_scheduled', 'completed')";
} elseif ($tab === 'rejected') {
    $sql .= " AND c.status IN ('rejected', 'cancelled_mismatch')";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$claims = $stmt->fetchAll();

// Counts for sub-tabs
$pending_count = (int)$pdo->query("SELECT COUNT(*) FROM claims WHERE status IN ('pending', 'under_admin_review')")->fetchColumn();
$approved_count = (int)$pdo->query("SELECT COUNT(*) FROM claims WHERE status IN ('approved', 'meeting_scheduled', 'completed')")->fetchColumn();
$rejected_count = (int)$pdo->query("SELECT COUNT(*) FROM claims WHERE status IN ('rejected', 'cancelled_mismatch')")->fetchColumn();
$total_count = (int)$pdo->query("SELECT COUNT(*) FROM claims")->fetchColumn();
?>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'claims';
    require_once '../../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                    <span>ศูนย์ควบคุมผู้ดูแลระบบ</span>
                    <span>•</span>
                    <span>ตรวจสอบคำร้องขอรับคืน</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    ตรวจสอบและอนุมัติคำร้อง
                </h1>
            </div>
        </div>

        <!-- Search & Sub-Tabs Navigation -->
        <div class="flex flex-col md:flex-row gap-4 items-stretch md:items-center justify-between mb-6">
            
            <!-- Filter Sub-Tabs (Clean Slate Dashboard Design System) -->
            <div class="flex items-center gap-1 overflow-x-auto text-xs font-medium p-1 bg-slate-100 rounded-xl border border-slate-200/80">
                <a href="admin_claims.php?tab=pending" class="px-3.5 py-2 rounded-lg transition flex items-center gap-2 whitespace-nowrap <?php echo $tab === 'pending' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/80 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'; ?>">
                    <span>รอการตรวจสอบ</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $tab === 'pending' ? 'bg-amber-100 text-amber-800 border border-amber-200/80' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $pending_count; ?></span>
                </a>

                <a href="admin_claims.php?tab=approved" class="px-3.5 py-2 rounded-lg transition flex items-center gap-2 whitespace-nowrap <?php echo $tab === 'approved' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/80 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'; ?>">
                    <span>อนุมัติแล้ว</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $tab === 'approved' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200/80' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $approved_count; ?></span>
                </a>

                <a href="admin_claims.php?tab=rejected" class="px-3.5 py-2 rounded-lg transition flex items-center gap-2 whitespace-nowrap <?php echo $tab === 'rejected' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/80 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'; ?>">
                    <span>ปฏิเสธแล้ว</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $tab === 'rejected' ? 'bg-rose-100 text-rose-800 border border-rose-200/80' : 'bg-slate-200 text-slate-700'; ?>"><?php echo $rejected_count; ?></span>
                </a>

                <a href="admin_claims.php?tab=all" class="px-3.5 py-2 rounded-lg transition flex items-center gap-2 whitespace-nowrap <?php echo $tab === 'all' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/80 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'; ?>">
                    <span>ทั้งหมด (<?php echo $total_count; ?>)</span>
                </a>
            </div>

            <!-- Search Bar -->
            <form action="admin_claims.php" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                <div class="relative w-full md:w-64">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ค้นหา #CLM-XXXXX, ชื่อของ..." class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs outline-none focus:border-slate-800 shadow-2xs">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </form>


        </div>

<?php
// Grouping logic for pending tab OR normal flat list for other tabs
$grouped_items = [];
if ($tab === 'pending') {
    foreach ($claims as $c) {
        $item_id = $c['item_id'];
        if (!isset($grouped_items[$item_id])) {
            $grouped_items[$item_id] = [
                'item_id' => $item_id,
                'item_title' => $c['item_title'],
                'category' => $c['category'],
                'secret_description' => $c['secret_description'],
                'item_sn' => $c['item_sn'],
                'item_image' => $c['item_image'],
                'item_status' => $c['item_status'],
                'item_location' => $c['item_location'],
                'item_created_at' => $c['item_created_at'],
                'finder_first' => $c['finder_first'],
                'finder_last' => $c['finder_last'],
                'finder_email' => $c['finder_email'],
                'claims' => []
            ];
        }
        $grouped_items[$item_id]['claims'][] = $c;
    }
}
?>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($claims)): ?>
            <div class="bg-white rounded-xl p-12 text-center border border-slate-200 shadow-xs">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">ไม่มีรายการคำร้องในหมวดนี้</h3>
                <p class="text-xs text-slate-500">ระบบไม่พบคำร้องขอ Claim ที่ตรงตามเงื่อนไขในขณะนี้</p>
            </div>
        <?php elseif ($tab === 'pending'): ?>
            <!-- Grouped by Item View (Pending Tab) -->
            <div class="space-y-5">
                <?php foreach ($grouped_items as $item_id => $group): 
                    $claims_count = count($group['claims']);
                    $accordion_id = "item_accordion_" . $item_id;
                ?>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                        
                        <!-- Item Master Card Header -->
                        <div class="p-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-slate-50/50">
                            <div class="flex items-center gap-4 flex-1 min-w-0">
                                <div class="w-12 h-12 bg-white rounded-lg overflow-hidden border border-slate-200 shrink-0 p-0.5 shadow-2xs">
                                    <?php if (!empty($group['item_image'])): ?>
                                        <img src="<?php echo $base_url . '/' . htmlspecialchars($group['item_image']); ?>" class="w-full h-full object-cover rounded-md">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-slate-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-bold text-slate-900 text-base leading-tight truncate"><?php echo htmlspecialchars($group['item_title']); ?></h3>
                                        <?php if ($claims_count > 1): ?>
                                            <span class="inline-flex items-center text-xs font-medium px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                                                มีผู้ยื่นเคลม <?php echo $claims_count; ?> รายการ
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        ผู้พบ: <span class="font-medium text-slate-700"><?php echo htmlspecialchars(trim($group['finder_first'] . ' ' . $group['finder_last'])); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Toggle Accordion Button -->
                            <button type="button" onclick="toggleAccordion('<?php echo $accordion_id; ?>')" class="w-full md:w-auto px-4 py-2 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 transition flex items-center justify-center gap-2 shadow-2xs">
                                <span>ดูคำร้องทั้งหมด (<?php echo $claims_count; ?>)</span>
                                <svg id="icon_<?php echo $accordion_id; ?>" class="w-4 h-4 text-slate-500 transition-transform duration-200 transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Collapsible Claims List (Collapsed by default for all) -->
                        <div id="<?php echo $accordion_id; ?>" class="hidden border-t border-slate-200 p-5 bg-slate-100/50 space-y-4">
                            <?php foreach ($group['claims'] as $c): ?>
                                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs space-y-4">
                                    
                                    <!-- Sub-card Header -->
                                    <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                                        <div class="flex items-center gap-2.5">
                                            <span class="bg-indigo-50 text-indigo-700 border border-indigo-200/80 font-mono font-semibold text-xs px-2.5 py-1 rounded-md shadow-2xs">#CLM-<?php echo str_pad($c['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                            <span class="text-xs text-slate-500 font-medium">ยื่นเมื่อ <?php echo date('d M Y H:i', strtotime($c['created_at'])); ?></span>
                                            <?php if ($c['status'] === 'under_admin_review'): ?>
                                                <span class="px-2.5 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 font-semibold rounded-full text-[11px]">มีข้อพิพาท/ส่ง Admin</span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?php echo $base_url; ?>/pages/claim_detail.php?id=<?php echo $c['id']; ?>&ref=admin_claims" class="text-xs text-slate-600 hover:text-slate-900 font-semibold hover:underline inline-flex items-center gap-1">
                                            ดูรายละเอียดเต็ม &rarr;
                                        </a>
                                    </div>

                                    <!-- Sub-card Content Grid (Top aligned 3 columns) -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start text-xs">
                                        <div class="md:col-span-2 space-y-2">
                                            <div>
                                                <span class="font-bold text-slate-500 block mb-0.5">ผู้ขอรับคืน:</span>
                                                <span class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($c['claimant_first'] . ' ' . $c['claimant_last']); ?></span>
                                                <span class="text-slate-500 font-normal"> (<?php echo htmlspecialchars($c['claimant_email']); ?>)</span>
                                            </div>
                                            <div>
                                                <span class="font-bold text-slate-500">เบอร์ติดต่อ:</span>
                                                <span class="text-slate-800 font-medium"><?php echo htmlspecialchars($c['contact_phone']); ?></span>
                                            </div>
                                            <div>
                                                <span class="font-bold text-slate-500">หลักฐานที่ยื่น:</span>
                                                <p class="text-slate-700 mt-0.5 bg-slate-50 p-2.5 rounded-lg border border-slate-100"><?php echo htmlspecialchars($c['proof_description']); ?></p>
                                            </div>
                                            <?php if (!empty($c['provided_serial_number'])): ?>
                                                <div>
                                                    <span class="font-bold text-slate-500">Serial Number ที่ระบุ:</span>
                                                    <span class="font-mono bg-slate-100 px-2 py-0.5 rounded font-bold text-slate-800 ml-1"><?php echo htmlspecialchars($c['provided_serial_number']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($c['dispute_reason'])): ?>
                                                <div class="bg-amber-50 border border-amber-200 p-2.5 rounded-lg text-amber-900 mt-2">
                                                    <span class="font-bold block text-[11px] text-amber-800 mb-0.5">เหตุผลส่ง Admin:</span>
                                                    <span><?php echo htmlspecialchars($c['dispute_reason']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Right: Proof Image (Top Aligned) -->
                                        <div class="md:col-span-1 flex flex-col items-start md:items-end self-start">
                                            <?php if (!empty($c['proof_image'])): ?>
                                                <a href="<?php echo $base_url . '/' . htmlspecialchars($c['proof_image']); ?>" target="_blank" class="inline-block">
                                                    <img src="<?php echo $base_url . '/' . htmlspecialchars($c['proof_image']); ?>" class="w-28 h-28 object-cover rounded-xl border border-slate-200 hover:opacity-90 transition shadow-2xs">
                                                </a>
                                                <span class="font-medium text-slate-500 mt-1.5 block text-[11px] text-center w-28">รูปภาพหลักฐาน</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Form Decision -->
                                    <div class="pt-3 border-t border-slate-100 bg-slate-50/70 p-4 rounded-lg">
                                        <form action="<?php echo $base_url; ?>/actions/claim_action.php" method="POST" class="space-y-3" onsubmit="return handleAdminDecisionSubmit(this, event)">
                                            <input type="hidden" name="action" value="admin_decision">
                                            <input type="hidden" name="claim_id" value="<?php echo $c['id']; ?>">
                                            <input type="hidden" name="decision" value="">
                                            
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1">บันทึกเหตุผลของแอดมิน <span class="text-red-500">*</span></label>
                                                <input type="text" name="admin_notes" class="w-full px-3.5 py-2 bg-white border border-slate-200 rounded-lg text-xs outline-none focus:border-slate-800 shadow-2xs" placeholder="กรุณาระบุเหตุผลการพิจารณาอนุมัติหรือปฏิเสธคำร้อง...">
                                            </div>

                                            <div class="flex items-center gap-3">
                                                <button type="submit" onclick="this.form.decision.value='approve'" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition shadow-2xs text-xs">
                                                    อนุมัติคำร้อง
                                                </button>

                                                <button type="submit" onclick="this.form.decision.value='reject'" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg transition shadow-2xs text-xs">
                                                    ปฏิเสธคำร้อง
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Normal Flat List View for Approved/Rejected/All Tabs -->
            <div class="space-y-6">
                <?php foreach ($claims as $c): ?>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                        
                        <!-- Header Banner -->
                        <div class="p-5 border-b border-slate-100 flex flex-wrap justify-between items-center gap-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="bg-indigo-50/80 text-indigo-700 border border-indigo-200/80 font-mono font-semibold text-xs px-2.5 py-1 rounded-md inline-flex items-center shadow-2xs">#CLM-<?php echo str_pad($c['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-bold text-slate-900 text-base sm:text-lg"><?php echo htmlspecialchars($c['item_title']); ?></h3>
                                        
                                        <?php if ($c['status'] === 'under_admin_review' || $c['status'] === 'pending'): ?>
                                            <span class="px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 font-semibold rounded-full flex items-center gap-1.5 text-xs">
                                                <span class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span>
                                                รอพิจารณา
                                            </span>
                                        <?php elseif (in_array($c['status'], ['approved', 'meeting_scheduled', 'completed'])): ?>
                                            <span class="px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold rounded-full flex items-center gap-1.5 text-xs">
                                                <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                                <?php echo $c['status'] === 'completed' ? 'ส่งมอบสำเร็จ' : 'อนุมัติแล้ว'; ?>
                                            </span>
                                        <?php elseif ($c['status'] === 'rejected'): ?>
                                            <span class="px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 font-semibold rounded-full flex items-center gap-1.5 text-xs">
                                                <span class="w-2 h-2 bg-rose-500 rounded-full"></span>
                                                ปฏิเสธคำร้อง
                                            </span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 bg-slate-50 text-slate-700 border border-slate-200 font-medium rounded-full flex items-center gap-1.5 text-xs">
                                                <span class="w-2 h-2 bg-slate-400 rounded-full"></span>
                                                ยกเลิกคำร้อง
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        ผู้พบ: <span class="font-medium text-slate-700"><?php echo htmlspecialchars(trim($c['finder_first'] . ' ' . $c['finder_last'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2.5 text-xs">
                                <a href="<?php echo $base_url; ?>/pages/claim_detail.php?id=<?php echo $c['id']; ?>&ref=admin_claims" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-semibold transition flex items-center gap-1.5 shadow-xs">
                                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    ดูคำร้องเต็ม
                                </a>
                            </div>
                        </div>

                        <!-- Body Grid -->
                        <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6 text-sm">
                            <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200">
                                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">ผู้ขอรับคืน</div>
                                <div class="font-bold text-slate-900 mb-3"><?php echo htmlspecialchars($c['claimant_first'] . ' ' . $c['claimant_last']); ?> <span class="text-slate-500 font-normal">(<?php echo htmlspecialchars($c['claimant_email']); ?>)</span></div>
                                
                                <div class="bg-white p-3 rounded-lg border border-slate-200 space-y-2 text-xs text-slate-700">
                                    <div><span class="font-bold text-slate-500">หลักฐานที่ยื่น:</span> <?php echo htmlspecialchars($c['proof_description']); ?></div>
                                    <?php if (!empty($c['provided_serial_number'])): ?>
                                        <div><span class="font-bold text-slate-500">Serial Number ที่ระบุ:</span> <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded font-bold text-slate-800"><?php echo htmlspecialchars($c['provided_serial_number']); ?></span></div>
                                    <?php endif; ?>
                                    <div><span class="font-bold text-slate-500">เบอร์ติดต่อ:</span> <?php echo htmlspecialchars($c['contact_phone']); ?></div>
                                </div>
                            </div>

                            <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200">
                                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">ผู้พบของ</div>
                                <div class="font-bold text-slate-900 mb-3"><?php echo htmlspecialchars($c['finder_first'] . ' ' . $c['finder_last']); ?> <span class="text-slate-500 font-normal">(<?php echo htmlspecialchars($c['finder_email']); ?>)</span></div>
                                
                                <div class="bg-white p-3 rounded-lg border border-slate-200 space-y-2 text-xs text-slate-700">
                                    <div><span class="font-bold text-slate-500">ข้อมูลยืนยันความเป็นเจ้าของ:</span> <?php echo !empty($c['secret_description']) ? htmlspecialchars($c['secret_description']) : 'ไม่ได้ระบุไว้'; ?></div>
                                    <?php if (!empty($c['item_sn'])): ?>
                                        <div><span class="font-bold text-slate-500">Serial Number ในประกาศ:</span> <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded font-bold text-slate-800"><?php echo htmlspecialchars($c['item_sn']); ?></span></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
function toggleAccordion(id) {
    const el = document.getElementById(id);
    const icon = document.getElementById('icon_' + id);
    if (!el) return;
    
    if (el.classList.contains('hidden')) {
        el.classList.remove('hidden');
        if (icon) icon.classList.add('rotate-180');
    } else {
        el.classList.add('hidden');
        if (icon) icon.classList.remove('rotate-180');
    }
}

function handleAdminDecisionSubmit(form, e) {
    const notesInput = form.querySelector('[name="admin_notes"]');
    const notesValue = notesInput ? notesInput.value.trim() : '';
    const decision = form.querySelector('[name="decision"]') ? form.querySelector('[name="decision"]').value : '';

    if (!notesValue) {
        alert('กรุณากรอกบันทึกเหตุผลก่อนดำเนินการอนุมัติหรือปฏิเสธ');
        if (notesInput) {
            notesInput.focus();
        }
        return false;
    }

    if (decision === 'approve') {
        return confirm('ยืนยันอนุมัติคำร้องนี้ใช่หรือไม่? ระบบจะทำการปฏิเสธคำร้องอื่นๆ ของสิ่งของชิ้นนี้ให้อัตโนมัติ');
    } else if (decision === 'reject') {
        return confirm('ยืนยันปฏิเสธคำร้องนี้ใช่หรือไม่?');
    }
    return true;
}
</script>

<?php require_once '../../includes/footer.php'; ?>
