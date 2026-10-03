<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';
require_once '../../helpers/category_helper.php';

if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$target_user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($target_user_id <= 0) {
    $_SESSION['error'] = "ไม่พบสมาชิกที่ระบุ";
    echo "<script>window.location.href = '".$base_url."/pages/admin/admin_users.php';</script>";
    exit;
}

// Fetch Target User Info
$stmt = $pdo->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM items WHERE user_id = u.id AND type = 'lost') as lost_count,
           (SELECT COUNT(*) FROM items WHERE user_id = u.id AND type = 'found') as found_count,
           (SELECT COUNT(*) FROM claims WHERE claimant_id = u.id) as claim_count
    FROM users u 
    WHERE u.id = ?
");
$stmt->execute([$target_user_id]);
$target_user = $stmt->fetch();

if (!$target_user) {
    $_SESSION['error'] = "ไม่พบข้อมูลสมาชิกในระบบ";
    echo "<script>window.location.href = '".$base_url."/pages/admin/admin_users.php';</script>";
    exit;
}

// Active sub-tab for posts/activity
$tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'all_posts';
$allowed_tabs = ['all_posts', 'lost', 'found', 'claims'];
if (!in_array($tab, $allowed_tabs)) {
    $tab = 'all_posts';
}

// Fetch Target User's Posted Items
$items_sql = "SELECT i.*, 
                     (SELECT COUNT(*) FROM claims WHERE item_id = i.id) as item_claim_count
              FROM items i 
              WHERE i.user_id = ?";

if ($tab === 'lost') {
    $items_sql .= " AND i.type = 'lost'";
} elseif ($tab === 'found') {
    $items_sql .= " AND i.type = 'found'";
}
$items_sql .= " ORDER BY i.created_at DESC";

$stmt_items = $pdo->prepare($items_sql);
$stmt_items->execute([$target_user_id]);
$user_items = $stmt_items->fetchAll();

// Fetch Target User's Claims Submitted (If claims tab is active)
$user_claims = [];
if ($tab === 'claims') {
    $claims_sql = "SELECT c.*, i.title as item_title, i.image_path as item_image, i.type as item_type, i.status as item_status
                   FROM claims c
                   JOIN items i ON c.item_id = i.id
                   WHERE c.claimant_id = ?
                   ORDER BY c.created_at DESC";
    $stmt_claims = $pdo->prepare($claims_sql);
    $stmt_claims->execute([$target_user_id]);
    $user_claims = $stmt_claims->fetchAll();
}

$categories = get_active_categories($pdo);
$is_self = ($target_user['id'] == $_SESSION['user_id']);
$is_banned = !empty($target_user['is_banned']);
?>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'users';
    require_once '../../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        
        <!-- Header & Breadcrumbs -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                    <a href="<?php echo $base_url; ?>/pages/admin/admin_dashboard.php" class="hover:text-slate-800">ศูนย์ควบคุมผู้ดูแลระบบ</a>
                    <span>•</span>
                    <a href="<?php echo $base_url; ?>/pages/admin/admin_users.php" class="hover:text-slate-800">จัดการบัญชีผู้ใช้งาน</a>
                    <span>•</span>
                    <span>โปรไฟล์ผู้ใช้ #USR-<?php echo str_pad($target_user['id'], 5, '0', STR_PAD_LEFT); ?></span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    โปรไฟล์และประวัติการโพสต์สมาชิก
                </h1>
            </div>
            <div>
                <a href="<?php echo $base_url; ?>/pages/admin/admin_users.php" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs transition gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    กลับหน้าจัดการสมาชิก
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium flex items-center">
                <svg class="w-5 h-5 mr-3 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm font-medium flex items-center">
                <svg class="w-5 h-5 mr-3 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Section 1: User Profile Header Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-6 mb-6">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                
                <div class="flex items-center gap-5">
                    <!-- Avatar -->
                    <div class="w-20 h-20 bg-slate-900 text-white rounded-2xl flex items-center justify-center font-bold text-3xl shadow-md flex-shrink-0">
                        <?php echo mb_substr($target_user['first_name'], 0, 1, 'UTF-8'); ?>
                    </div>
                    
                    <!-- User Details -->
                    <div class="space-y-1">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h2 class="text-xl font-bold text-slate-900 leading-tight">
                                <?php echo htmlspecialchars($target_user['first_name'] . ' ' . $target_user['last_name']); ?>
                            </h2>

                            <?php if ($target_user['role'] === 'admin'): ?>
                                <span class="px-2.5 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 rounded-md font-bold text-xs">Admin</span>
                            <?php else: ?>
                                <span class="px-2.5 py-0.5 bg-slate-100 text-slate-700 border border-slate-200 rounded-md font-medium text-xs">User</span>
                            <?php endif; ?>

                            <?php if ($is_banned): ?>
                                <span class="px-2.5 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded-full font-semibold text-xs inline-flex items-center gap-1" title="<?php echo htmlspecialchars($target_user['ban_reason'] ?? ''); ?>">
                                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                                    ถูกระงับสิทธิ์
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-semibold text-xs inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                    บัญชีปกติ
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-4 text-xs text-slate-500 flex-wrap pt-1">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <?php echo htmlspecialchars($target_user['email']); ?>
                            </span>
                            <span>•</span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                <?php echo !empty($target_user['phone']) ? htmlspecialchars($target_user['phone']) : 'ไม่ได้ระบุเบอร์โทร'; ?>
                            </span>
                            <span>•</span>
                            <span class="text-slate-400">
                                สมาชิกเมื่อ: <?php echo date('d M Y', strtotime($target_user['created_at'])); ?>
                            </span>
                        </div>

                        <?php if ($is_banned && !empty($target_user['ban_reason'])): ?>
                            <div class="mt-2 p-2.5 bg-rose-50 border border-rose-200/80 rounded-xl text-xs text-rose-800">
                                <strong class="font-semibold">สาเหตุที่ถูกระงับ:</strong> <?php echo htmlspecialchars($target_user['ban_reason']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Controls -->
                <div class="flex items-center gap-2 self-stretch md:self-auto justify-end">
                    <?php if (!$is_self): ?>
                        <a href="<?php echo $base_url; ?>/pages/chat.php?receiver_id=<?php echo $target_user['id']; ?>" class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold text-xs rounded-xl border border-blue-200 transition shadow-2xs flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            ส่งข้อความ
                        </a>

                        <?php if ($is_banned): ?>
                            <button type="button" onclick="openUnbanModal(<?php echo $target_user['id']; ?>, '<?php echo htmlspecialchars(addslashes($target_user['first_name'] . ' ' . $target_user['last_name'])); ?>')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold transition shadow-2xs text-xs flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                                ปลดระงับ
                            </button>
                        <?php else: ?>
                            <button type="button" onclick="openBanModal(<?php echo $target_user['id']; ?>, '<?php echo htmlspecialchars(addslashes($target_user['first_name'] . ' ' . $target_user['last_name'])); ?>')" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-semibold transition shadow-2xs text-xs flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                ระงับสิทธิ์
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>

            <!-- User Stats Summary -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-6 border-t border-slate-100">
                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                    <span class="text-[11px] font-bold text-slate-500 block uppercase">ประกาศของหาย</span>
                    <span class="text-xl font-extrabold text-rose-600"><?php echo $target_user['lost_count']; ?></span>
                    <span class="text-xs text-slate-400 font-medium"> รายการ</span>
                </div>
                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                    <span class="text-[11px] font-bold text-slate-500 block uppercase">ประกาศพบของ</span>
                    <span class="text-xl font-extrabold text-blue-600"><?php echo $target_user['found_count']; ?></span>
                    <span class="text-xs text-slate-400 font-medium"> รายการ</span>
                </div>
                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                    <span class="text-[11px] font-bold text-slate-500 block uppercase">ยื่นขอรับคืน (Claims)</span>
                    <span class="text-xl font-extrabold text-emerald-600"><?php echo $target_user['claim_count']; ?></span>
                    <span class="text-xs text-slate-400 font-medium"> รายการ</span>
                </div>
                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80">
                    <span class="text-[11px] font-bold text-slate-500 block uppercase">รวมกิจกรรมทั้งหมด</span>
                    <span class="text-xl font-extrabold text-slate-800"><?php echo $target_user['lost_count'] + $target_user['found_count'] + $target_user['claim_count']; ?></span>
                    <span class="text-xs text-slate-400 font-medium"> รายการ</span>
                </div>
            </div>
        </div>

        <!-- Section 2: Activity Tabs Navigation -->
        <div class="flex items-center gap-1 overflow-x-auto text-xs font-medium p-1 bg-slate-100 rounded-xl border border-slate-200/80 mb-6">
            <a href="?id=<?php echo $target_user_id; ?>&tab=all_posts" 
               class="px-4 py-2 rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap <?php echo $tab === 'all_posts' ? 'bg-white text-slate-900 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'; ?>">
                <span>โพสต์ประกาศทั้งหมด</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] <?php echo $tab === 'all_posts' ? 'bg-slate-100 text-slate-800' : 'bg-slate-200 text-slate-600'; ?>">
                    <?php echo $target_user['lost_count'] + $target_user['found_count']; ?>
                </span>
            </a>

            <a href="?id=<?php echo $target_user_id; ?>&tab=lost" 
               class="px-4 py-2 rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap <?php echo $tab === 'lost' ? 'bg-white text-rose-700 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'; ?>">
                <span>ประกาศของหาย</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] <?php echo $tab === 'lost' ? 'bg-rose-100 text-rose-800' : 'bg-slate-200 text-slate-600'; ?>">
                    <?php echo $target_user['lost_count']; ?>
                </span>
            </a>

            <a href="?id=<?php echo $target_user_id; ?>&tab=found" 
               class="px-4 py-2 rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap <?php echo $tab === 'found' ? 'bg-white text-blue-700 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'; ?>">
                <span>ประกาศของที่เก็บได้</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] <?php echo $tab === 'found' ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-600'; ?>">
                    <?php echo $target_user['found_count']; ?>
                </span>
            </a>

            <a href="?id=<?php echo $target_user_id; ?>&tab=claims" 
               class="px-4 py-2 rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap <?php echo $tab === 'claims' ? 'bg-white text-emerald-700 font-bold shadow-2xs' : 'text-slate-600 hover:text-slate-900'; ?>">
                <span>ประวัติการยื่น Claim</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] <?php echo $tab === 'claims' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'; ?>">
                    <?php echo $target_user['claim_count']; ?>
                </span>
            </a>
        </div>

        <!-- Section 3: Content List -->
        <?php if ($tab === 'claims'): ?>
            <!-- Claims List -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <?php if (empty($user_claims)): ?>
                    <div class="p-12 text-center text-slate-500 font-medium">
                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        ผู้ใช้งานนี้ยังไม่มีประวัติการยื่นคำร้องขอรับคืน
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($user_claims as $claim): ?>
                            <div class="p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-slate-50/60 transition">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 bg-slate-100 rounded-xl overflow-hidden flex-shrink-0 border border-slate-200 p-0.5">
                                        <?php if (!empty($claim['item_image'])): ?>
                                            <img src="<?php echo $base_url . '/' . htmlspecialchars($claim['item_image']); ?>" class="w-full h-full object-cover rounded-lg">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-slate-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-mono text-[11px] font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200/80">#CLM-<?php echo str_pad($claim['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                            <span class="text-xs text-slate-400">• ยื่นเมื่อ: <?php echo date('d M Y H:i', strtotime($claim['created_at'])); ?></span>
                                        </div>
                                        <h4 class="font-bold text-slate-900 text-sm leading-tight">
                                            <?php echo htmlspecialchars($claim['item_title']); ?>
                                        </h4>
                                        <p class="text-xs text-slate-500 mt-1 line-clamp-1">
                                            หลักฐานที่ยื่น: <?php echo htmlspecialchars($claim['proof_description']); ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 self-end sm:self-center">
                                    <?php if (in_array($claim['status'], ['pending', 'under_admin_review'])): ?>
                                        <span class="px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-full text-xs font-semibold">รอตรวจสอบ</span>
                                    <?php elseif (in_array($claim['status'], ['approved', 'meeting_scheduled'])): ?>
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-xs font-semibold">อนุมัติแล้ว</span>
                                    <?php elseif ($claim['status'] === 'completed'): ?>
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-xs font-semibold">ส่งมอบสำเร็จ</span>
                                    <?php elseif ($claim['status'] === 'rejected'): ?>
                                        <span class="px-2.5 py-1 bg-red-50 text-red-700 border border-red-200 rounded-full text-xs font-semibold">ปฏิเสธแล้ว</span>
                                    <?php elseif ($claim['status'] === 'cancelled_mismatch'): ?>
                                        <span class="px-2.5 py-1 bg-gray-50 text-gray-700 border border-gray-200 rounded-full text-xs font-semibold">ยกเลิกแล้ว</span>
                                    <?php endif; ?>

                                    <a href="<?php echo $base_url; ?>/pages/claim_detail.php?id=<?php echo $claim['id']; ?>&ref=admin_claims" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-semibold text-xs transition shadow-2xs whitespace-nowrap">
                                        ดูรายละเอียดคำร้อง
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Posted Items Grid / List -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <?php if (empty($user_items)): ?>
                    <div class="p-12 text-center text-slate-500 font-medium">
                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        ผู้ใช้งานนี้ยังไม่มีรายการโพสต์<?php echo $tab === 'lost' ? 'ของหาย' : ($tab === 'found' ? 'ของที่เก็บได้' : ''); ?>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($user_items as $item): ?>
                            <?php 
                                $cat_label = isset($categories[$item['category']]) ? $categories[$item['category']] : 'อื่นๆ';
                                $is_lost = ($item['type'] === 'lost');
                            ?>
                            <div class="p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-slate-50/60 transition">
                                <div class="flex items-center gap-4">
                                    <!-- Image -->
                                    <div class="w-16 h-16 bg-slate-100 rounded-xl overflow-hidden flex-shrink-0 border border-slate-200 p-0.5">
                                        <?php if (!empty($item['image_path'])): ?>
                                            <img src="<?php echo $base_url . '/' . htmlspecialchars($item['image_path']); ?>" class="w-full h-full object-cover rounded-lg">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-slate-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Details -->
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <?php if ($is_lost): ?>
                                                <span class="px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded font-bold text-[10px]">ของหาย</span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded font-bold text-[10px]">พบของ</span>
                                            <?php endif; ?>

                                            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[10px] font-semibold"><?php echo htmlspecialchars($cat_label); ?></span>
                                            <span class="text-xs text-slate-400">• โพสต์เมื่อ: <?php echo date('d M Y H:i', strtotime($item['created_at'])); ?></span>
                                        </div>

                                        <h4 class="font-bold text-slate-900 text-base leading-tight">
                                            <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $item['id']; ?>" class="hover:text-blue-600 transition">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </a>
                                        </h4>

                                        <div class="text-xs text-slate-500 flex items-center gap-4 flex-wrap">
                                            <span>สถานที่: <strong class="text-slate-700 font-medium"><?php echo htmlspecialchars($item['location']); ?></strong></span>
                                            <span>วันที่เกิดเหตุ: <strong class="text-slate-700 font-medium"><?php echo date('d M Y', strtotime($item['event_date'])); ?></strong></span>
                                            <?php if (!empty($item['item_claim_count'])): ?>
                                                <span class="text-blue-700 font-semibold bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                                    <?php echo $item['item_claim_count']; ?> คำร้อง request
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Badge & Action Button -->
                                <div class="flex items-center gap-3 self-end sm:self-center">
                                    <?php if ($item['status'] === 'open'): ?>
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-xs font-semibold">
                                            <?php echo $is_lost ? 'กำลังตามหา' : 'ยังไม่มีผู้รับ'; ?>
                                        </span>
                                    <?php elseif (in_array($item['status'], ['pending_claim', 'meeting'])): ?>
                                        <span class="px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-full text-xs font-semibold">
                                            <?php echo $is_lost ? 'รอรับของ' : 'รอส่งมอบ'; ?>
                                        </span>
                                    <?php elseif ($item['status'] === 'completed'): ?>
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-xs font-semibold">
                                            <?php echo $is_lost ? 'คืนของสำเร็จ' : 'ส่งมอบสำเร็จ'; ?>
                                        </span>
                                    <?php elseif ($item['status'] === 'hidden'): ?>
                                        <span class="px-2.5 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-full text-xs font-semibold">
                                            ถูกซ่อน
                                        </span>
                                    <?php endif; ?>

                                    <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $item['id']; ?>" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-semibold text-xs transition shadow-2xs whitespace-nowrap">
                                        ดูประกาศ
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- Ban / Unban Modals -->
<div id="banModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden transform transition-all">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                ระงับสิทธิ์ผู้ใช้งาน
            </h3>
            <button onclick="closeBanModal()" class="text-slate-400 hover:text-slate-600 font-bold text-sm">✕</button>
        </div>
        <form action="<?php echo $base_url; ?>/actions/admin_manage_action.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="ban_user">
            <input type="hidden" name="user_id" id="ban_user_id" value="">
            
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ผู้ใช้ที่จะระงับสิทธิ์</label>
                <div id="ban_user_name" class="font-bold text-sm text-slate-900 bg-slate-50 p-2.5 rounded-lg border border-slate-200"></div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">เหตุผลการระงับสิทธิ์</label>
                <input type="text" name="ban_reason" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 outline-none focus:border-slate-800 transition" placeholder="ระบุเหตุผลการระงับสิทธิ์..." required>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 text-xs">
                <button type="button" onclick="closeBanModal()" class="px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl font-semibold transition">
                    ยกเลิก
                </button>
                <button type="submit" class="px-4 py-2 bg-rose-600 text-white hover:bg-rose-700 rounded-xl font-semibold transition shadow-xs">
                    ยืนยันระงับสิทธิ์
                </button>
            </div>
        </form>
    </div>
</div>

<div id="unbanModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden transform transition-all">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                ปลดล็อกการระงับสิทธิ์ผู้ใช้งาน
            </h3>
            <button onclick="closeUnbanModal()" class="text-slate-400 hover:text-slate-600 font-bold text-sm">✕</button>
        </div>
        <form action="<?php echo $base_url; ?>/actions/admin_manage_action.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="unban_user">
            <input type="hidden" name="user_id" id="unban_user_id" value="">
            
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ผู้ใช้งานที่ต้องการปลดระงับ</label>
                <div id="unban_user_name" class="font-bold text-sm text-slate-900 bg-slate-50 p-2.5 rounded-lg border border-slate-200"></div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                คุณยืนยันที่จะปลดระงับสิทธิ์การใช้งานบัญชีนี้ใช่หรือไม่? เมื่อปลดระงับแล้ว สมาชิกจะสามารถเข้าสู่ระบบและทำรายการต่างๆ ในระบบได้ตามปกติ
            </p>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 text-xs">
                <button type="button" onclick="closeUnbanModal()" class="px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl font-semibold transition">
                    ยกเลิก
                </button>
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white hover:bg-emerald-700 rounded-xl font-semibold transition shadow-xs">
                    ยืนยันปลดระงับ
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openBanModal(userId, userName) {
    document.getElementById('ban_user_id').value = userId;
    document.getElementById('ban_user_name').innerText = userName;
    document.getElementById('banModal').classList.remove('hidden');
}
function closeBanModal() {
    document.getElementById('banModal').classList.add('hidden');
}

function openUnbanModal(userId, userName) {
    document.getElementById('unban_user_id').value = userId;
    document.getElementById('unban_user_name').innerText = userName;
    document.getElementById('unbanModal').classList.remove('hidden');
}
function closeUnbanModal() {
    document.getElementById('unbanModal').classList.add('hidden');
}
</script>

<?php require_once '../../includes/footer.php'; ?>
