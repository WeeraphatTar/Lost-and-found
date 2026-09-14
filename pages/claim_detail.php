<?php
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อน";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';
$claim_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($claim_id <= 0) {
    header("Location: claims.php");
    exit;
}

// ดึงข้อมูล Claim + Item + Users
$sql = "
    SELECT c.*, 
           i.title as item_title, i.category, i.location, i.description as item_desc, 
           i.secret_description, i.serial_number as item_sn, i.image_path as item_image, i.status as item_status, i.user_id as item_owner_id,
           u_c.first_name as claimant_first, u_c.last_name as claimant_last, u_c.email as claimant_email, u_c.phone as claimant_phone,
           u_f.first_name as finder_first, u_f.last_name as finder_last, u_f.email as finder_email, u_f.phone as finder_phone,
           u_a.first_name as admin_first, u_a.last_name as admin_last
    FROM claims c
    JOIN items i ON c.item_id = i.id
    JOIN users u_c ON c.claimant_id = u_c.id
    JOIN users u_f ON c.finder_id = u_f.id
    LEFT JOIN users u_a ON c.admin_id = u_a.id
    WHERE c.id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$claim_id]);
$claim = $stmt->fetch();

if (!$claim) {
    $_SESSION['error'] = "ไม่พบคำร้องขอ Claim นี้ในระบบ";
    echo "<script>window.location.href = '".$base_url."/pages/claims.php';</script>";
    exit;
}

$is_claimant = ($claim['claimant_id'] == $user_id);
$is_finder = ($claim['finder_id'] == $user_id);
$is_admin = ($user_role === 'admin');

if (!$is_claimant && !$is_finder && !$is_admin) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าดูคำร้องนี้";
    echo "<script>window.location.href = '".$base_url."/pages/claims.php';</script>";
    exit;
}
?>

<div class="py-10 bg-background flex-grow">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Back Link -->
        <?php
        $ref_param = isset($_GET['ref']) ? trim($_GET['ref']) : '';
        $is_from_admin = ($ref_param === 'admin_claims' || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' && strpos($_SERVER['HTTP_REFERER'] ?? '', 'admin_claims') !== false));
        $back_to_claims_url = $is_from_admin ? $base_url . "/pages/admin/admin_claims.php" : $base_url . "/pages/claims.php";
        $back_to_claims_label = $is_from_admin ? "กลับหน้าจัดการคำร้อง (Admin)" : "กลับหน้ารายการคำร้อง";
        ?>
        <a href="<?php echo $back_to_claims_url; ?>" class="inline-flex items-center text-primary hover:text-accent font-medium mb-6 transition text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <?php echo $back_to_claims_label; ?>
        </a>

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

        <!-- Main Card: Clean White Design -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            
            <!-- Header Banner: Clean White Header -->
            <div class="p-6 sm:p-8 bg-white border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="font-mono text-sm font-bold text-blue-700 bg-blue-50 px-3.5 py-1.5 rounded-md border border-blue-200/80 shadow-2xs">#CLM-<?php echo str_pad($claim['id'], 5, '0', STR_PAD_LEFT); ?></span>
                        <span class="text-xs text-slate-600">• ยื่นคำร้องเมื่อ: <?php echo date('d M Y H:i', strtotime($claim['created_at'])); ?></span>
                    </div>
                    <h1 class="text-2xl font-bold text-primary">รายละเอียดคำร้องขอรับคืน</h1>
                </div>

                <div class="flex items-center gap-3">
                    <?php if ($claim['status'] === 'pending' || $claim['status'] === 'under_admin_review'): ?>
                        <span class="inline-flex items-center px-3.5 py-1.5 bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold rounded-full gap-2">
                            <span class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span>
                            รอ Admin ตรวจสอบหลักฐาน
                        </span>
                    <?php elseif (in_array($claim['status'], ['approved', 'meeting_scheduled'])): ?>
                        <span class="inline-flex items-center px-3.5 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-full gap-2">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                            อนุมัติแล้ว
                        </span>
                    <?php elseif ($claim['status'] === 'completed'): ?>
                        <span class="inline-flex items-center px-3.5 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-full gap-2">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                            ส่งมอบสำเร็จ
                        </span>
                    <?php elseif ($claim['status'] === 'cancelled_mismatch'): ?>
                        <span class="inline-flex items-center px-3.5 py-1.5 bg-gray-50 border border-gray-200 text-gray-700 text-xs font-semibold rounded-full gap-2">
                            <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                            ยกเลิกคำร้อง
                        </span>
                    <?php elseif ($claim['status'] === 'rejected'): ?>
                        <span class="inline-flex items-center px-3.5 py-1.5 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-full gap-2">
                            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                            ปฏิเสธคำร้อง
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Content Body: Vertically Stacked Sections -->
            <div class="p-6 sm:p-8 space-y-6">
                
                <!-- Notice Banner for Mismatch Cancellation -->
                <?php if ($claim['status'] === 'cancelled_mismatch'): ?>
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <div class="text-sm">
                            <h4 class="font-bold mb-0.5">การยื่นคำร้องถูกยกเลิกเรียบร้อยแล้ว</h4>
                            <p class="text-xs text-gray-600">
                                เหตุผล: <?php echo htmlspecialchars($claim['cancel_reason'] ?? 'ไม่ระบุเหตุผล'); ?><br>
                                <span class="font-medium text-emerald-700">ระบบเปิดประกาศสิ่งของชิ้นนี้กลับสู่สถานะปกติเรียบร้อยแล้ว</span>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Notice Banner for Admin Review -->
                <?php if ($claim['status'] === 'under_admin_review'): ?>
                    <div class="p-4 bg-amber-50/60 border border-amber-200 rounded-xl text-amber-900 flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <div class="text-sm">
                            <h4 class="font-bold mb-0.5">อยู่ระหว่างการตรวจสอบโดย Admin</h4>
                            <p class="text-xs text-amber-800">
                                เหตุผลส่ง Admin: <?php echo htmlspecialchars($claim['dispute_reason'] ?? 'ขอให้ Admin ช่วยตรวจสอบหลักฐาน'); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Notice Banner for Rejected Claim -->
                <?php if ($claim['status'] === 'rejected'): ?>
                    <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-900 flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="text-sm">
                            <h4 class="font-bold mb-0.5 text-red-800">คำร้องนี้ถูกปฏิเสธโดย Admin</h4>
                            <p class="text-xs text-red-700">
                                เหตุผลที่ Admin ปฏิเสธ: <strong class="font-semibold"><?php echo !empty($claim['admin_notes']) ? htmlspecialchars($claim['admin_notes']) : 'หลักฐานไม่เพียงพอ หรือไม่ตรงกับสิ่งของจริง'; ?></strong>
                            </p>
                            <?php if (!empty($claim['admin_first'])): ?>
                                <p class="text-[11px] text-red-600 mt-1">
                                    ดำเนินการโดย: Admin <?php echo htmlspecialchars($claim['admin_first'] . ' ' . $claim['admin_last']); ?>
                                    <?php if (!empty($claim['admin_action_at'])): ?>
                                        เมื่อ <?php echo date('d M Y H:i', strtotime($claim['admin_action_at'])); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Section 1: Item & Parties Overview -->
                <div class="bg-gray-50/60 p-5 rounded-xl border border-gray-200">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-5 border-b border-gray-200">
                        <div class="flex gap-4 items-center">
                            <div class="w-16 h-16 bg-white rounded-xl overflow-hidden flex-shrink-0 border border-gray-200 p-0.5">
                                <?php if (!empty($claim['item_image'])): ?>
                                    <img src="<?php echo $base_url . '/' . htmlspecialchars($claim['item_image']); ?>" class="w-full h-full object-cover rounded-lg">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-0.5">ของที่ขอรับคืน</span>
                                <h3 class="font-bold text-gray-900 text-lg leading-tight"><?php echo htmlspecialchars($claim['item_title']); ?></h3>
                                <div class="text-xs text-gray-500 mt-1 flex items-center gap-3">
                                    <span>สถานที่พบ: <strong class="text-gray-700 font-medium"><?php echo htmlspecialchars($claim['location']); ?></strong></span>
                                    <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $claim['item_id']; ?>" class="text-accent font-semibold hover:underline">ดูประกาศ</a>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Button -->
                        <a href="<?php echo $base_url; ?>/pages/chat.php?item_id=<?php echo $claim['item_id']; ?>&receiver_id=<?php echo $is_finder ? $claim['claimant_id'] : $claim['finder_id']; ?>&ref=claim_detail&claim_id=<?php echo $claim['id']; ?>" class="px-4 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold text-xs rounded-lg transition border border-blue-200/80 shadow-2xs flex items-center gap-1.5 whitespace-nowrap self-stretch sm:self-auto justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            แชทสนทนา
                        </a>
                    </div>

                    <!-- Clean Key-Value Party Layout -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 text-xs">
                        <div class="space-y-1">
                            <span class="text-gray-400 font-medium block">ผู้ขอรับคืน:</span>
                            <div class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($claim['claimant_first'] . ' ' . $claim['claimant_last']); ?></div>
                        </div>
                        <div class="space-y-1">
                            <span class="text-gray-400 font-medium block">ผู้พบของ:</span>
                            <div class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($claim['finder_first'] . ' ' . $claim['finder_last']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Submitted Evidence (Clean Read-only Typography Layout, No Nested Gray Box) -->
                <div class="border border-gray-200 rounded-xl p-6 bg-white space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-3">
                        หลักฐานที่ยื่นยืนยันความเป็นเจ้าของ
                    </h3>

                    <div class="space-y-4 text-sm">
                        <div class="space-y-1.5">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">รายละเอียดหลักฐาน</span>
                            <p class="text-gray-800 whitespace-pre-wrap leading-relaxed text-sm"><?php echo htmlspecialchars($claim['proof_description']); ?></p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100 text-xs">
                            <?php if (!empty($claim['provided_serial_number'])): ?>
                                <div>
                                    <span class="text-gray-400 font-medium block mb-1">Serial Number / IMEI:</span>
                                    <span class="font-mono font-bold text-gray-900 text-sm bg-gray-50 px-2.5 py-1 rounded border border-gray-200 inline-block"><?php echo htmlspecialchars($claim['provided_serial_number']); ?></span>
                                </div>
                            <?php endif; ?>

                            <div>
                                <span class="text-gray-400 font-medium block mb-1">เบอร์ติดต่อ:</span>
                                <span class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($claim['contact_phone']); ?></span>
                            </div>
                        </div>

                        <?php if (!empty($claim['proof_image'])): ?>
                            <div class="pt-3 border-t border-gray-100">
                                <span class="text-xs font-semibold text-gray-400 block mb-2">รูปถ่ายหลักฐานเพิ่มเติม</span>
                                <a href="<?php echo $base_url . '/' . htmlspecialchars($claim['proof_image']); ?>" target="_blank" class="inline-block border border-gray-200 rounded-xl overflow-hidden max-w-xs bg-gray-50 hover:opacity-90 transition p-1 shadow-2xs">
                                    <img src="<?php echo $base_url . '/' . htmlspecialchars($claim['proof_image']); ?>" class="max-h-48 object-contain rounded-lg">
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section 3: Finder Secret Description (แสดงเฉพาะ Finder หรือ Admin) -->
                <?php if (($is_finder || $is_admin) && (!empty($claim['secret_description']) || !empty($claim['item_sn']))): ?>
                    <div class="border border-slate-200 rounded-xl p-5 bg-slate-50/80">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                            ข้อมูลลับในระบบสำหรับเปรียบเทียบ (Secret Verification Data)
                        </h3>
                        <div class="text-xs space-y-2 text-slate-700">
                            <?php if (!empty($claim['secret_description'])): ?>
                                <p><span class="font-semibold text-slate-900">คำอธิบายลับ:</span> <?php echo htmlspecialchars($claim['secret_description']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($claim['item_sn'])): ?>
                                <p><span class="font-semibold text-slate-900">Serial Number ในระบบ:</span> <span class="font-mono font-bold text-slate-900 bg-white px-2.5 py-0.5 border border-slate-200 rounded"><?php echo htmlspecialchars($claim['item_sn']); ?></span></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Section 4: Action Buttons (Clear Hierarchy: Primary Emerald, Soft Blue Chat, Destructive Ghost Border) -->
                <div class="pt-4 border-t border-gray-200">
                    <div class="flex flex-wrap gap-3 items-center">
                        
                        <!-- 1. แจ้งเตือนสถานะการรอตรวจสอบ (Amber Alert Banner): ปรับรูปแบบเป็นกล่องแจ้งเตือนข้อมูล ไม่ใช่ทรงปุ่มกด -->
                        <?php if (in_array($claim['status'], ['pending', 'under_admin_review'])): ?>
                            <div class="w-full p-4 bg-amber-50/90 border border-amber-200/80 rounded-xl text-amber-900 flex items-start gap-3">
                                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="text-xs space-y-0.5">
                                    <span class="font-bold block text-amber-950 text-sm">อยู่ระหว่างการตรวจสอบหลักฐานโดย Admin</span>
                                    <p class="text-amber-800 leading-relaxed">เจ้าหน้าที่กำลังเปรียบเทียบข้อมูลหลักฐานความถูกต้อง ระบบจะแจ้งเตือนให้ทราบทันทีเมื่อผลการตรวจสอบเสร็จสิ้น</p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- ปุ่มควบคุมสำหรับ Admin บนหน้า claim_detail.php โดยตรง -->
                        <?php if ($is_admin && in_array($claim['status'], ['pending', 'under_admin_review'])): ?>
                            <form action="<?php echo $base_url; ?>/actions/claim_action.php" method="POST" class="inline-flex gap-2">
                                <input type="hidden" name="action" value="admin_decision">
                                <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                <input type="hidden" name="admin_notes" value="ตรวจสอบหลักฐานโดย Admin ผ่านหน้ารายละเอียดคำร้อง">
                                <button type="submit" name="decision" value="approve" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg transition shadow-sm">
                                    อนุมัติคำร้อง
                                </button>
                                <button type="submit" name="decision" value="reject" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold text-xs rounded-lg transition shadow-sm">
                                    ปฏิเสธคำร้อง
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- 2. ปุ่มยกเลิกคำร้อง (Ghost Border Style) -->
                        <?php 
                        $can_cancel_mismatch = false;
                        if ($is_claimant && in_array($claim['status'], ['pending', 'approved', 'meeting_scheduled', 'under_admin_review'])) {
                            $can_cancel_mismatch = true;
                        } elseif (in_array($claim['status'], ['approved', 'meeting_scheduled'])) {
                            $can_cancel_mismatch = true;
                        }
                        ?>

                        <?php if ($can_cancel_mismatch): ?>
                            <button onclick="openMismatchModal()" class="px-5 py-2.5 bg-white hover:bg-red-50 text-red-600 font-semibold text-xs rounded-lg transition border border-red-200 shadow-2xs">
                                <?php echo ($is_claimant && $claim['status'] === 'pending') ? 'ถอนคำร้อง' : 'ยกเลิกคำร้อง'; ?>
                            </button>
                        <?php endif; ?>

                        <!-- 3. ยืนยันการส่งมอบสำเร็จ (Emerald Green Primary Button) -->
                        <?php if (in_array($claim['status'], ['approved', 'meeting_scheduled'])): ?>
                            <form action="<?php echo $base_url; ?>/actions/claim_action.php" method="POST" class="inline" onsubmit="return confirm('ยืนยันการส่งมอบสำเร็จใช่หรือไม่?');">
                                <input type="hidden" name="action" value="complete_claim">
                                <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                <button type="submit" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg transition shadow-sm flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    ยืนยันการส่งมอบ
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- 4. ปุ่มเข้าหน้า Admin (ถ้าเป็น admin) -->
                        <?php if ($is_admin): ?>
                            <a href="<?php echo $base_url; ?>/pages/admin/admin_claims.php" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-sm rounded-lg transition shadow-sm ml-auto">
                                จัดการคำร้อง (Admin)
                            </a>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Modal 1: ส่ง Admin ตรวจสอบ -->
<div id="disputeModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl relative">
        <h3 class="text-base font-bold text-gray-800 mb-1">ส่งเรื่องให้ Admin ตรวจสอบ</h3>
        <p class="text-xs text-gray-500 mb-4">ระบุเหตุผลการคัดค้านเพื่อให้ Admin เปรียบเทียบหลักฐาน</p>
        
        <form action="<?php echo $base_url; ?>/actions/claim_action.php" method="POST" class="space-y-3">
            <input type="hidden" name="action" value="dispute_claim">
            <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
            
            <textarea name="dispute_reason" rows="3" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs outline-none focus:ring-2 focus:ring-accent" placeholder="เช่น หลักฐานรูปภาพไม่ตรง หรือคำอธิบายขัดแย้ง..." required></textarea>
            
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeDisputeModal()" class="px-4 py-2 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-primary hover:bg-slate-800 rounded-lg shadow-sm">ส่งตรวจสอบ</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: ยกเลิกเนื่องจากของไม่ตรงกัน (Cancel Mismatch) -->
<div id="mismatchModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl relative">
        <h3 class="text-base font-bold text-gray-800 mb-1">ยกเลิกคำร้องสิ่งของไม่ตรงกัน</h3>
        <p class="text-xs text-gray-500 mb-4">เมื่อยกเลิก ระบบจะคืนสถานะประกาศสิ่งของกลับเป็น Open ทันที</p>
        
        <form action="<?php echo $base_url; ?>/actions/claim_action.php" method="POST" class="space-y-3" onsubmit="return handleCancelSubmit(this);">
            <input type="hidden" name="action" value="cancel_mismatch">
            <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
            <input type="hidden" id="final_cancel_reason" name="cancel_reason" value="">
            
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">เหตุผลการยกเลิก:</label>
                <select id="cancel_reason_select" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs mb-2 outline-none focus:ring-2 focus:ring-accent" onchange="toggleOtherReason(this)">
                    <option value="ตรวจสอบสิ่งของจริงแล้วไม่ใช่ของผู้ขอ Claim (ลักษณะไม่ตรง)">ตรวจสอบสิ่งของจริงแล้วไม่ใช่ของผู้ขอ Claim (ลักษณะไม่ตรง)</option>
                    <option value="สภาพสิ่งของหรือตำหนิไม่ตรงตามที่เข้าใจ">สภาพสิ่งของหรือตำหนิไม่ตรงตามที่เข้าใจ</option>
                    <option value="ตกลงยกเลิกด้วยความยินยอมของทั้งสองฝ่าย">ตกลงยกเลิกด้วยความยินยอมของทั้งสองฝ่าย</option>
                    <option value="other">อื่นๆ (ระบุสาเหตุ)</option>
                </select>

                <div id="other_reason_container" class="hidden mt-2">
                    <textarea id="custom_cancel_reason" rows="3" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs outline-none focus:ring-2 focus:ring-accent" placeholder="ระบุเหตุผลเพิ่มเติม..."></textarea>
                </div>
            </div>
            
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeMismatchModal()" class="px-4 py-2 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-slate-700 hover:bg-slate-800 rounded-lg shadow-sm">ยืนยันการยกเลิก</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDisputeModal() { document.getElementById('disputeModal').classList.remove('hidden'); }
function closeDisputeModal() { document.getElementById('disputeModal').classList.add('hidden'); }
function openMismatchModal() { document.getElementById('mismatchModal').classList.remove('hidden'); }
function closeMismatchModal() { document.getElementById('mismatchModal').classList.add('hidden'); }

function toggleOtherReason(select) {
    const container = document.getElementById('other_reason_container');
    if (select.value === 'other') {
        container.classList.remove('hidden');
        document.getElementById('custom_cancel_reason').setAttribute('required', 'required');
    } else {
        container.classList.add('hidden');
        document.getElementById('custom_cancel_reason').removeAttribute('required');
    }
}

function handleCancelSubmit(form) {
    const select = document.getElementById('cancel_reason_select');
    const finalReasonInput = document.getElementById('final_cancel_reason');
    if (select.value === 'other') {
        const customText = document.getElementById('custom_cancel_reason').value.trim();
        if (!customText) {
            alert('กรุณาระบุเหตุผลการยกเลิก');
            return false;
        }
        finalReasonInput.value = customText;
    } else {
        finalReasonInput.value = select.value;
    }
    return true;
}
</script>

<?php require_once '../includes/footer.php'; ?>
