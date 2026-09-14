<?php
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนเข้าใช้งานหน้ารายการคำร้อง";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'my_claims';

// ดึงรายการคำร้องที่ผู้ใช้ยื่นขอ Claim (In Claims)
$my_claims_stmt = $pdo->prepare("
    SELECT c.*, i.title as item_title, i.image_path as item_image, i.location, u.first_name as finder_first, u.last_name as finder_last
    FROM claims c
    JOIN items i ON c.item_id = i.id
    JOIN users u ON c.finder_id = u.id
    WHERE c.claimant_id = ?
    ORDER BY c.created_at DESC
");
$my_claims_stmt->execute([$user_id]);
$my_claims = $my_claims_stmt->fetchAll();

// ดึงรายการคำร้องที่ผู้อื่นยื่นขอ Claim สิ่งของที่เราเก็บได้ (Incoming Claims)
$incoming_claims_stmt = $pdo->prepare("
    SELECT c.*, i.title as item_title, i.image_path as item_image, i.location, u.first_name as claimant_first, u.last_name as claimant_last
    FROM claims c
    JOIN items i ON c.item_id = i.id
    JOIN users u ON c.claimant_id = u.id
    WHERE c.finder_id = ?
    ORDER BY c.created_at DESC
");
$incoming_claims_stmt->execute([$user_id]);
$incoming_claims = $incoming_claims_stmt->fetchAll();

function get_status_badge($status) {
    switch ($status) {
        case 'pending':
        case 'under_admin_review':
            return '<span class="px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 text-xs font-semibold rounded-full flex items-center gap-1.5"><span class="w-2 h-2 bg-amber-500 rounded-full"></span> รอ Admin ตรวจสอบ</span>';
        case 'approved':
        case 'meeting_scheduled':
            return '<span class="px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-semibold rounded-full flex items-center gap-1.5"><span class="w-2 h-2 bg-emerald-500 rounded-full"></span> อนุมัติแล้ว</span>';
        case 'completed':
            return '<span class="px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-semibold rounded-full flex items-center gap-1.5"><span class="w-2 h-2 bg-emerald-500 rounded-full"></span> ส่งมอบสำเร็จ</span>';
        case 'cancelled_mismatch':
            return '<span class="px-3 py-1 bg-gray-50 text-gray-700 border border-gray-200 text-xs font-semibold rounded-full flex items-center gap-1.5"><span class="w-2 h-2 bg-gray-400 rounded-full"></span> ยกเลิกคำร้อง</span>';
        case 'rejected':
            return '<span class="px-3 py-1 bg-red-50 text-red-700 border border-red-200 text-xs font-semibold rounded-full flex items-center gap-1.5"><span class="w-2 h-2 bg-red-500 rounded-full"></span> ปฏิเสธคำร้อง</span>';
        default:
            return '<span class="px-3 py-1 bg-gray-50 text-gray-700 border border-gray-200 text-xs font-semibold rounded-full">ไม่ระบุ</span>';
    }
}
?>

<div class="py-10 bg-background flex-grow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-primary">รายการคำร้องขอรับของคืน</h1>
                <p class="text-sm text-gray-500 mt-1">ติดตามสถานะคำร้องยืนยันความเป็นเจ้าของ และจัดการนัดส่งมอบสิ่งของ</p>
            </div>

            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="<?php echo $base_url; ?>/pages/admin/admin_claims.php" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold rounded-lg transition shadow-sm self-start md:self-auto">
                    จัดการ Claim (Admin)
                </a>
            <?php endif; ?>
        </div>

        <!-- Flash Message Notification -->
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

        <!-- Full-width 50/50 Segmented Tabs Navigation -->
        <div class="bg-gray-100/80 p-1.5 rounded-2xl mb-8 border border-gray-200/80 grid grid-cols-2 gap-1.5">
            <a href="?tab=my_claims" class="py-3 px-4 font-semibold text-sm rounded-xl transition flex items-center justify-center gap-2.5 text-center <?php echo ($tab === 'my_claims') ? 'bg-white text-primary shadow-sm border border-gray-200/60' : 'text-gray-500 hover:text-gray-800 hover:bg-white/50'; ?>">
                <span>คำร้องของฉัน</span>
                <span class="px-2 py-0.5 text-xs rounded-full <?php echo ($tab === 'my_claims') ? 'bg-blue-50 text-blue-700 font-bold' : 'bg-gray-200 text-gray-600'; ?>"><?php echo count($my_claims); ?></span>
            </a>
            <a href="?tab=incoming_claims" class="py-3 px-4 font-semibold text-sm rounded-xl transition flex items-center justify-center gap-2.5 text-center <?php echo ($tab === 'incoming_claims') ? 'bg-white text-primary shadow-sm border border-gray-200/60' : 'text-gray-500 hover:text-gray-800 hover:bg-white/50'; ?>">
                <span>คำร้องที่ได้รับ</span>
                <span class="px-2 py-0.5 text-xs rounded-full <?php echo ($tab === 'incoming_claims') ? 'bg-emerald-50 text-emerald-700 font-bold' : 'bg-gray-200 text-gray-600'; ?>"><?php echo count($incoming_claims); ?></span>
            </a>
        </div>

        <!-- Tab 1: My Submitted Claims -->
        <?php if ($tab === 'my_claims'): ?>
            <?php if (empty($my_claims)): ?>
                <div class="bg-white rounded-2xl p-12 text-center border border-gray-200 shadow-sm">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-1">ยังไม่มีรายการคำร้อง</h3>
                    <p class="text-sm text-gray-500 mb-6">คุณยังไม่ได้ยื่นคำร้องขอรับสิ่งของคืนสำหรับประกาศใดๆ</p>
                    <a href="<?php echo $base_url; ?>/pages/browse.php" class="px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition">เรียกดูประกาศสิ่งของที่พบ</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($my_claims as $claim): ?>
                        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center mb-4 gap-2 border-b border-gray-100 pb-3">
                                    <?php echo get_status_badge($claim['status']); ?>
                                    <span class="text-xs text-gray-400 font-medium"><?php echo date('d M Y H:i', strtotime($claim['created_at'])); ?></span>
                                </div>
                                <div class="flex gap-4 items-start mb-3">
                                    <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0 border border-gray-100">
                                        <?php if (!empty($claim['item_image'])): ?>
                                            <img src="<?php echo $base_url . '/' . htmlspecialchars($claim['item_image']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="overflow-hidden space-y-1">
                                        <h3 class="font-bold text-primary truncate text-base leading-tight"><?php echo htmlspecialchars($claim['item_title']); ?></h3>
                                        <p class="text-xs text-gray-500 truncate">สถานที่: <?php echo htmlspecialchars($claim['location']); ?></p>
                                        <p class="text-xs text-gray-600">ผู้พบของ: <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($claim['finder_first'] . ' ' . $claim['finder_last']); ?></span></p>
                                    </div>
                                </div>
                                
                                <!-- Clean Evidence Teaser without heavy gray box -->
                                <div class="text-xs text-gray-500 mb-4 pt-2 border-t border-gray-50">
                                    <span class="font-medium text-gray-600">หลักฐานที่ยื่น:</span>
                                    <span class="text-gray-700 truncate inline-block max-w-full align-bottom ml-1"><?php echo htmlspecialchars($claim['proof_description']); ?></span>
                                </div>

                                <?php if ($claim['status'] === 'rejected' && !empty($claim['admin_notes'])): ?>
                                    <div class="text-xs text-red-700 bg-red-50 border border-red-100 p-2.5 rounded-xl mb-4">
                                        <span class="font-bold block text-red-800 mb-0.5">เหตุผลที่ Admin ปฏิเสธ:</span>
                                        <span class="text-red-700"><?php echo htmlspecialchars($claim['admin_notes']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-2.5">
                                <a href="<?php echo $base_url; ?>/pages/chat.php?item_id=<?php echo $claim['item_id']; ?>&receiver_id=<?php echo $claim['finder_id']; ?>&ref=claims&tab=my_claims" class="px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold text-xs rounded-xl border border-blue-200/80 transition flex items-center gap-1.5 shadow-2xs">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                    แชทสนทนา
                                </a>
                                <a href="<?php echo $base_url; ?>/pages/claim_detail.php?id=<?php echo $claim['id']; ?>" class="px-4 py-2 bg-primary hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition shadow-sm">
                                    ดูรายละเอียด
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <!-- Tab 2: Incoming Claims on My Found Items -->
        <?php else: ?>
            <?php if (empty($incoming_claims)): ?>
                <div class="bg-white rounded-2xl p-12 text-center border border-gray-200 shadow-sm">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-1">ยังไม่มีคำร้องเข้ามา</h3>
                    <p class="text-sm text-gray-500">ยังไม่มีผู้ใดยื่นคำร้องขอรับสิ่งของคืนในประกาศที่คุณพบไว้</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($incoming_claims as $claim): ?>
                        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center mb-4 gap-2 border-b border-gray-100 pb-3">
                                    <?php echo get_status_badge($claim['status']); ?>
                                    <span class="text-xs text-gray-400 font-medium"><?php echo date('d M Y H:i', strtotime($claim['created_at'])); ?></span>
                                </div>
                                <div class="flex gap-4 items-start mb-3">
                                    <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0 border border-gray-100">
                                        <?php if (!empty($claim['item_image'])): ?>
                                            <img src="<?php echo $base_url . '/' . htmlspecialchars($claim['item_image']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="overflow-hidden space-y-1">
                                        <h3 class="font-bold text-primary truncate text-base leading-tight"><?php echo htmlspecialchars($claim['item_title']); ?></h3>
                                        <p class="text-xs text-gray-600">ผู้ยื่นคำร้อง: <span class="font-semibold text-emerald-700"><?php echo htmlspecialchars($claim['claimant_first'] . ' ' . $claim['claimant_last']); ?></span></p>
                                        <p class="text-xs text-gray-500">เบอร์ติดต่อ: <?php echo htmlspecialchars($claim['contact_phone']); ?></p>
                                    </div>
                                </div>
                                
                                <!-- Clean Evidence Teaser without heavy gray box -->
                                <div class="text-xs text-gray-500 mb-4 pt-2 border-t border-gray-50">
                                    <span class="font-medium text-gray-600">รายละเอียดหลักฐาน:</span>
                                    <span class="text-gray-700 truncate inline-block max-w-full align-bottom ml-1"><?php echo htmlspecialchars($claim['proof_description']); ?></span>
                                </div>

                                <?php if ($claim['status'] === 'rejected' && !empty($claim['admin_notes'])): ?>
                                    <div class="text-xs text-red-700 bg-red-50 border border-red-100 p-2.5 rounded-xl mb-4">
                                        <span class="font-bold block text-red-800 mb-0.5">เหตุผลที่ Admin ปฏิเสธ:</span>
                                        <span class="text-red-700"><?php echo htmlspecialchars($claim['admin_notes']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-2.5">
                                <a href="<?php echo $base_url; ?>/pages/chat.php?item_id=<?php echo $claim['item_id']; ?>&receiver_id=<?php echo $claim['claimant_id']; ?>&ref=claims&tab=incoming_claims" class="px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold text-xs rounded-xl border border-blue-200/80 transition flex items-center gap-1.5 shadow-2xs">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                    แชทสนทนา
                                </a>
                                <a href="<?php echo $base_url; ?>/pages/claim_detail.php?id=<?php echo $claim['id']; ?>" class="px-4 py-2 bg-primary hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition shadow-sm">
                                    ดูรายละเอียด
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
