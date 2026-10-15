<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';
require_once '../../helpers/category_helper.php';

// Strict Admin Guard
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/index.php';</script>";
    exit;
}

$claim_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($claim_id <= 0) {
    header("Location: admin_claims.php");
    exit;
}

$sql = "
    SELECT c.*, 
           i.id as item_id, i.title as item_title, i.category, i.secret_description, i.serial_number as item_sn, 
           i.image_path as item_image, i.status as item_status, i.location as item_location, i.storage_location,
           i.created_at as item_created_at, i.type as item_type,
           u_c.id as claimant_user_id, u_c.first_name as claimant_first, u_c.last_name as claimant_last, u_c.email as claimant_email, u_c.phone as claimant_phone,
           u_f.id as finder_user_id, u_f.first_name as finder_first, u_f.last_name as finder_last, u_f.email as finder_email, u_f.phone as finder_phone
    FROM claims c
    JOIN items i ON c.item_id = i.id
    JOIN users u_c ON c.user_id = u_c.id
    JOIN users u_f ON i.user_id = u_f.id
    WHERE c.id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$claim_id]);
$claim = $stmt->fetch();

if (!$claim) {
    $_SESSION['error'] = "ไม่พบข้อมูลคำร้องขอรับคืนที่อ้างถึง";
    echo "<script>window.location.href = '".$base_url."/pages/admin/admin_claims.php';</script>";
    exit;
}

$categories_map = get_active_categories($pdo);
$cat_label = $categories_map[$claim['category']] ?? $claim['category'];
$proof_text = !empty($claim['description']) ? $claim['description'] : (!empty($claim['proof_description']) ? $claim['proof_description'] : 'ไม่ได้ระบุรายละเอียดหลักฐาน');
?>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'claims';
    require_once '../../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        
        <!-- Header & Breadcrumb -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                    <a href="admin_dashboard.php" class="hover:text-slate-800">ศูนย์ควบคุมผู้ดูแลระบบ</a>
                    <span>•</span>
                    <a href="admin_claims.php" class="hover:text-slate-800">ตรวจสอบคำร้องขอรับคืน</a>
                    <span>•</span>
                    <span>รายละเอียดคำร้อง <span class="text-indigo-600 font-semibold">#CLM-<?php echo str_pad($claim['id'], 3, '0', STR_PAD_LEFT); ?></span></span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                    <span>คำร้องขอรับสิ่งของคืน</span>
                </h1>
            </div>

            <div class="flex items-center gap-2">
                <a href="admin_claims.php" class="px-4 py-2 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition flex items-center gap-1.5 shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    กลับหน้าตรวจสอบคำร้อง
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0"></path></svg>
                <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Claim Summary Top Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs p-4 sm:p-5 mb-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">คำร้อง <span class="text-indigo-600 font-semibold">#CLM-<?php echo str_pad($claim['id'], 3, '0', STR_PAD_LEFT); ?></span></h2>
                    <p class="text-xs text-slate-500 mt-1">
                        ยื่นคำร้องเมื่อ: <span class="font-medium text-slate-700"><?php echo date('d M Y H:i', strtotime($claim['created_at'])); ?></span>
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <?php if (in_array($claim['status'], ['approved', 'meeting_scheduled', 'completed'])): ?>
                        <span class="px-3.5 py-1.5 bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold rounded-full flex items-center gap-2 text-sm">
                            <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full"></span>
                            <span><?php echo ($claim['status'] === 'completed' || in_array($claim['item_status'], ['resolved', 'closed'])) ? 'ส่งมอบสำเร็จ' : 'อนุมัติคำร้องแล้ว'; ?></span>
                        </span>
                    <?php elseif ($claim['status'] === 'rejected'): ?>
                        <span class="px-3.5 py-1.5 bg-rose-50 text-rose-800 border border-rose-200 font-semibold rounded-full flex items-center gap-2 text-sm">
                            <span class="w-2.5 h-2.5 bg-rose-500 rounded-full"></span>
                            <span>ปฏิเสธคำร้องแล้ว</span>
                        </span>
                    <?php elseif ($claim['status'] === 'cancelled'): ?>
                        <span class="px-3.5 py-1.5 bg-slate-100 text-slate-700 border border-slate-200 font-semibold rounded-full flex items-center gap-2 text-sm">
                            <span class="w-2.5 h-2.5 bg-slate-400 rounded-full"></span>
                            <span>ยกเลิกคำร้อง</span>
                        </span>
                    <?php else: ?>
                        <span class="px-3.5 py-1.5 bg-amber-50 text-amber-800 border border-amber-200 font-semibold rounded-full flex items-center gap-2 text-sm">
                            <span class="w-2.5 h-2.5 bg-amber-500 rounded-full animate-pulse"></span>
                            <span><?php echo $claim['status'] === 'under_admin_review' ? 'มีข้อพิพาท / รอ Admin ตรวจสอบ' : 'รอการตรวจสอบ'; ?></span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column: Item & Claimant & Evidence Details (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- 1. Item Details Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="px-5 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-xs sm:text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            ข้อมูลสิ่งของที่ประกาศ
                        </h3>
                        <span class="text-xs sm:text-sm font-medium text-slate-500">หมวดหมู่: <strong class="text-slate-800"><?php echo htmlspecialchars($cat_label); ?></strong></span>
                    </div>

                    <div class="p-4 sm:p-5 flex flex-col sm:flex-row gap-4 sm:gap-5 items-start">
                        <div class="shrink-0 w-24 sm:w-28">
                            <div class="w-24 h-24 sm:w-28 sm:h-28 bg-slate-100 rounded-xl overflow-hidden border border-slate-200 p-0.5">
                                <?php if (!empty($claim['item_image'])): ?>
                                    <img src="<?php echo $base_url . '/' . htmlspecialchars($claim['item_image']); ?>" class="w-full h-full object-cover rounded-lg">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-slate-400">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $claim['item_id']; ?>" target="_blank" class="w-full bg-white border border-slate-200 text-slate-700 text-xs py-1.5 rounded-lg hover:bg-slate-50 text-center transition flex items-center justify-center gap-1 mt-2 font-medium shadow-2xs">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                <span>ดูโพสต์</span>
                            </a>
                        </div>

                        <div class="space-y-3 flex-1 text-xs sm:text-sm">
                            <div>
                                <h4 class="font-bold text-slate-900 text-base leading-tight mb-1">
                                    <?php echo htmlspecialchars($claim['item_title']); ?>
                                </h4>
                                <span class="px-2.5 py-0.5 rounded text-xs font-semibold <?php echo $claim['item_type'] === 'lost' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200'; ?>">
                                    <?php echo $claim['item_type'] === 'lost' ? 'ประกาศของหาย' : 'ประกาศพบของ'; ?>
                                </span>
                            </div>

                            <div class="space-y-2.5">
                                <div>
                                    <span class="text-xs font-medium text-slate-500 block mb-1">สถานที่พบ</span>
                                    <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/80 text-sm text-slate-800 font-medium leading-relaxed">
                                        <?php echo htmlspecialchars($claim['item_location']); ?>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-xs font-medium text-slate-500 block mb-1">สถานที่จัดเก็บ</span>
                                    <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/80 text-sm text-slate-800 font-medium leading-relaxed">
                                        <?php echo !empty($claim['storage_location']) ? htmlspecialchars($claim['storage_location']) : 'ไม่ได้ระบุ'; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($claim['secret_description']) || !empty($claim['item_sn'])): ?>
                                <div class="space-y-2 pt-1 text-xs sm:text-sm">
                                    <?php if (!empty($claim['secret_description'])): ?>
                                        <div>
                                            <span class="font-bold text-slate-600 text-xs">จุดสังเกตซ่อน/ข้อมูลเจ้าของ (จากผู้พบ)</span>
                                            <p class="text-slate-800 font-medium bg-amber-50/80 p-2.5 sm:p-3 rounded-lg border border-amber-200/80 text-xs sm:text-sm mt-1"><?php echo htmlspecialchars($claim['secret_description']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($claim['item_sn'])): ?>
                                        <div>
                                            <span class="font-bold text-slate-600 text-xs">Serial Number ในประกาศ</span>
                                            <span class="font-mono bg-slate-100 px-2 py-0.5 rounded font-bold text-slate-800 ml-1 text-xs sm:text-sm"><?php echo htmlspecialchars($claim['item_sn']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="pt-2.5 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-slate-600 text-xs sm:text-sm">
                                <div>
                                    ผู้พบของ: 
                                    <a href="admin_user_detail.php?id=<?php echo $claim['finder_user_id']; ?>" class="font-bold text-slate-800 hover:text-blue-600 underline">
                                        <?php echo htmlspecialchars(trim($claim['finder_first'] . ' ' . $claim['finder_last'])); ?>
                                    </a>
                                    (<?php echo htmlspecialchars($claim['finder_email']); ?>)
                                </div>
                                <a href="admin_messages.php?user_id=<?php echo $claim['finder_user_id']; ?>&item_id=<?php echo $claim['item_id']; ?>&claim_id=<?php echo $claim['id']; ?>&ref=claim_detail" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-semibold rounded-lg text-xs transition inline-flex items-center gap-1.5 shadow-2xs">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                    แชทกับผู้พบของ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Claimant Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="px-5 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-xs sm:text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            ข้อมูลผู้ขอรับคืน
                        </h3>
                    </div>

                    <div class="p-4 sm:p-5 space-y-4 text-xs sm:text-sm">
                        
                        <!-- User Profile Info -->
                        <div class="bg-slate-50 p-3.5 sm:p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <span class="font-bold text-slate-500 text-xs block uppercase mb-1">ผู้ยื่นขอรับคืน</span>
                                <div>
                                    <a href="admin_user_detail.php?id=<?php echo $claim['claimant_user_id']; ?>" class="font-bold text-slate-900 text-sm sm:text-base hover:text-blue-600 transition inline-flex items-center gap-1.5">
                                        <span><?php echo htmlspecialchars(trim($claim['claimant_first'] . ' ' . $claim['claimant_last'])); ?></span>
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                    </a>
                                </div>
                                <div class="text-slate-600 text-xs sm:text-sm">อีเมล: <span class="font-medium text-slate-800"><?php echo htmlspecialchars($claim['claimant_email']); ?></span></div>
                                <div class="text-slate-600 text-xs sm:text-sm">โทร : <span class="font-medium text-slate-800"><?php echo htmlspecialchars($claim['contact_phone']); ?></span></div>
                            </div>

                            <div class="shrink-0">
                                <a href="admin_messages.php?user_id=<?php echo $claim['claimant_user_id']; ?>&item_id=<?php echo $claim['item_id']; ?>&claim_id=<?php echo $claim['id']; ?>&ref=claim_detail" class="px-3 py-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 font-semibold rounded-lg text-xs transition inline-flex items-center gap-1.5 shadow-2xs">
                                    <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                    แชทกับผู้ยื่นคำร้อง
                                </a>
                            </div>
                        </div>

                        <!-- Evidence Description -->
                        <div>
                            <span class="font-bold text-slate-700 block text-xs sm:text-sm mb-1.5">รายละเอียดหลักฐานที่ยื่น</span>
                            <div class="p-3 sm:p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-slate-800 text-xs sm:text-sm leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($proof_text)); ?>
                            </div>
                        </div>

                        <?php if (!empty($claim['provided_serial_number'])): ?>
                            <div>
                                <span class="font-bold text-slate-700 block text-xs sm:text-sm mb-1.5">Serial Number / เลขเครื่องที่ระบุ</span>
                                <span class="font-mono font-bold text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded-lg border border-slate-200 inline-block text-xs sm:text-sm">
                                    <?php echo htmlspecialchars($claim['provided_serial_number']); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Proof Image -->
                        <?php if (!empty($claim['proof_image'])): ?>
                            <div>
                                <span class="font-bold text-slate-700 block text-xs sm:text-sm mb-2">รูปภาพหลักฐานแนบเพิ่มเติม</span>
                                <a href="<?php echo $base_url . '/' . htmlspecialchars($claim['proof_image']); ?>" target="_blank" class="inline-block group relative">
                                    <img src="<?php echo $base_url . '/' . htmlspecialchars($claim['proof_image']); ?>" class="w-48 h-48 object-cover rounded-xl border border-slate-200 group-hover:opacity-90 transition shadow-2xs">
                                    <span class="absolute bottom-2 right-2 bg-slate-900/80 text-white text-xs px-2 py-1 rounded font-semibold backdrop-blur-xs">คลิกเพื่อขยายใหญ่</span>
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Dispute Reason if sent to admin -->
                        <?php if (!empty($claim['dispute_reason'])): ?>
                            <div class="p-3 sm:p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-amber-900">
                                <span class="font-bold block text-xs sm:text-sm text-amber-800 mb-1">เหตุผลการส่ง Admin ตรวจสอบข้อพิพาท</span>
                                <p class="text-xs sm:text-sm text-amber-900 leading-relaxed"><?php echo htmlspecialchars($claim['dispute_reason']); ?></p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>

            <!-- Right Column: Admin Action & Audit Log Card (1 col) -->
            <div class="space-y-6">
                
                <!-- Decision / Audit Log Card -->
                <?php $is_decided = !in_array($claim['status'], ['pending', 'under_admin_review']); ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="px-5 py-3 bg-slate-50/80 border-b border-slate-200">
                        <h3 class="text-xs sm:text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                            <?php if ($is_decided): ?>
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>ผลการพิจารณาคำร้อง</span>
                            <?php else: ?>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>พิจารณาคำร้อง</span>
                            <?php endif; ?>
                        </h3>
                    </div>

                    <div class="p-4 sm:p-5 space-y-4 text-xs sm:text-sm">
                        
                        <?php if ($claim['status'] === 'pending' || $claim['status'] === 'under_admin_review'): ?>
                            <!-- Admin Form for Decision -->
                            <form action="<?php echo $base_url; ?>/actions/claim_action.php" method="POST" class="space-y-4" onsubmit="return handleAdminDecisionSubmit(this, event)">
                                <input type="hidden" name="action" value="admin_decision">
                                <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                <input type="hidden" name="decision" value="">

                                <div>
                                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                                        เหตุผลการพิจารณา <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea name="admin_notes" rows="3" class="w-full p-3 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm outline-none focus:border-slate-800 transition" placeholder="ระบุเหตุผลในการอนุมัติหรือปฏิเสธคำร้องนี้..."></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-3 pt-1">
                                    <button type="submit" onclick="this.form.decision.value='approve'" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition shadow-xs text-xs sm:text-sm flex items-center justify-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        อนุมัติคำร้อง
                                    </button>

                                    <button type="submit" onclick="this.form.decision.value='reject'" class="w-full py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg transition shadow-xs text-xs sm:text-sm flex items-center justify-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        ปฏิเสธคำร้อง
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- Audit Log Display for Completed/Approved/Rejected -->
                            <div>
                                <span class="text-xs font-medium text-slate-500 mb-1.5 block">เหตุผลประกอบการพิจารณา</span>
                                <div class="bg-slate-50/80 border border-slate-200/70 rounded-xl p-3.5 text-sm text-slate-700 leading-relaxed min-h-[90px]">
                                    <?= !empty($claim['admin_notes']) ? nl2br(htmlspecialchars($claim['admin_notes'])) : (!empty($claim['admin_note']) ? nl2br(htmlspecialchars($claim['admin_note'])) : 'ไม่ได้ระบุเหตุผล') ?>
                                </div>

                                <?php if (!empty($claim['updated_at'])): ?>
                                    <div class="pt-3 border-t border-slate-100 flex items-center gap-1.5 text-xs text-slate-400 mt-3">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0"></path></svg>
                                        <span>บันทึกผลเมื่อ: <strong class="font-mono text-slate-600 font-semibold"><?php echo date('d M Y H:i', strtotime($claim['updated_at'])); ?></strong></span>
                                    </div>
                                <?php endif; ?>

                                <?php 
                                    $is_handover_ready = (in_array($claim['status'], ['approved', 'meeting_scheduled']) && !in_array($claim['item_status'], ['resolved', 'closed']));
                                ?>
                                <?php if ($is_handover_ready): ?>
                                    <div class="mt-4 pt-4 border-t border-slate-200">
                                        <form action="<?php echo $base_url; ?>/actions/claim_action.php" method="POST" onsubmit="return confirm('ยืนยันว่าได้ส่งมอบสิ่งของคืนให้แก่ผู้ขอรับคืนเรียบร้อยแล้วใช่หรือไม่?');">
                                            <input type="hidden" name="action" value="admin_complete_claim">
                                            <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                            <button type="submit" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition shadow-xs text-xs sm:text-sm flex items-center justify-center gap-2">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"></path></svg>
                                                <span>ยืนยันการส่งมอบสิ่งของแล้ว</span>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>

        </div>

    </main>
</div>

<script>
function handleAdminDecisionSubmit(form, e) {
    const notesInput = form.querySelector('[name="admin_notes"]');
    const notesValue = notesInput ? notesInput.value.trim() : '';
    const decisionInput = form.querySelector('[name="decision"]');
    const decision = decisionInput ? decisionInput.value : '';

    const resetButtons = () => {
        const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitButtons.forEach(function(btn) {
            btn.dataset.submitting = 'false';
            btn.style.opacity = '';
            btn.style.pointerEvents = '';
            btn.disabled = false;
        });
    };

    if (!notesValue) {
        alert('กรุณากรอกบันทึกเหตุผลก่อนดำเนินการอนุมัติหรือปฏิเสธ');
        if (notesInput) {
            notesInput.focus();
        }
        if (e && e.preventDefault) e.preventDefault();
        resetButtons();
        return false;
    }

    let isConfirmed = false;
    if (decision === 'approve') {
        isConfirmed = confirm('ยืนยันอนุมัติคำร้องนี้ใช่หรือไม่? ระบบจะทำการปฏิเสธคำร้องอื่นๆ ของสิ่งของชิ้นนี้ให้อัตโนมัติ');
    } else if (decision === 'reject') {
        isConfirmed = confirm('ยืนยันปฏิเสธคำร้องนี้ใช่หรือไม่?');
    } else {
        isConfirmed = true;
    }

    if (!isConfirmed) {
        if (e && e.preventDefault) e.preventDefault();
        resetButtons();
        return false;
    }

    return true;
}
</script>

<?php require_once '../../includes/footer.php'; ?>
