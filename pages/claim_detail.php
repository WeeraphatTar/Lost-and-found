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
           i.title as item_title, i.category, i.location, i.storage_location, i.description as item_desc, 
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

<?php if ($is_admin): ?>
<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'claims';
    require_once '../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        
        <!-- Header / Breadcrumbs -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                    <a href="<?php echo $base_url; ?>/pages/admin/admin_dashboard.php" class="hover:text-slate-800">ศูนย์ควบคุมผู้ดูแลระบบ</a>
                    <span>•</span>
                    <a href="<?php echo $base_url; ?>/pages/admin/admin_claims.php" class="hover:text-slate-800">คำร้องขอรับคืน</a>
                    <span>•</span>
                    <span>รายละเอียดคำร้อง #CLM-<?php echo str_pad($claim['id'], 5, '0', STR_PAD_LEFT); ?></span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    รายละเอียดคำร้องขอรับคืน
                </h1>
            </div>
            <div>
                <a href="<?php echo $base_url; ?>/pages/admin/admin_claims.php" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs transition gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    กลับหน้าตรวจสอบและอนุมัติคำร้อง
                </a>
            </div>
        </div>
<?php else: ?>
<div class="py-10 bg-background flex-grow">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <?php
          $backTab = (isset($_GET['tab']) && $_GET['tab'] === 'incoming_claims') ? 'tab=incoming_claims' : 'tab=my_claims';
        ?>
        <div class="mb-4">
          <a href="claims.php?<?= $backTab ?>" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-slate-800 transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            กลับหน้ารายการคำร้อง
          </a>
        </div>
<?php endif; ?>

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
            <div class="p-5 sm:p-6 bg-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center">
                    <span class="bg-indigo-50/80 text-indigo-700 border border-indigo-200/80 font-mono font-semibold text-sm px-3 py-1.5 rounded-lg inline-flex items-center shadow-sm">#CLM-<?php echo str_pad($claim['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    <span class="text-sm text-slate-600 font-medium ml-3">ยื่นคำร้องเมื่อ: <?php echo date('d M Y H:i', strtotime($claim['created_at'])); ?></span>
                </div>

                <div class="flex items-center gap-3">
                    <?php if ($claim['status'] === 'pending' || $claim['status'] === 'under_admin_review'): ?>
                        <span class="inline-flex items-center px-3.5 py-1.5 bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold rounded-full gap-2">
                            <span class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span>
                            รอการตรวจสอบ
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
                    <div class="bg-slate-100 border border-slate-200 rounded-xl p-3.5 flex items-start gap-3 mb-5">
                        <svg class="w-5 h-5 text-slate-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700">คำร้องนี้ถูกยกเลิกแล้ว</h4>
                            <?php if (!empty($claim['cancel_reason'])): ?>
                                <p class="text-xs text-slate-500 mt-0.5">เหตุผล: <?php echo htmlspecialchars($claim['cancel_reason']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Notice Banner for Admin Review -->
                <?php if ($claim['status'] === 'under_admin_review'): ?>
                    <div class="p-4 bg-amber-50/60 border border-amber-200 rounded-xl text-amber-900 flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <div class="text-sm">
                            <h4 class="font-bold mb-0.5"><?php echo $is_admin ? 'คำแนะนำในการพิจารณา' : 'อยู่ระหว่างการตรวจสอบโดย Admin'; ?></h4>
                            <p class="text-xs text-amber-800">
                                <?php if ($is_admin): ?>
                                    กรุณาตรวจสอบความสอดคล้องระหว่างหลักฐานของผู้ขอรับคืนกับข้อมูลของผู้พบของ ก่อนกดอนุมัติหรือปฏิเสธคำร้อง
                                <?php else: ?>
                                    เหตุผลส่ง Admin: <?php echo htmlspecialchars($claim['dispute_reason'] ?? 'ขอให้ Admin ช่วยตรวจสอบหลักฐาน'); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Notice Banner for Rejected Claim -->
                <?php if ($claim['status'] === 'rejected'): ?>
                    <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-900 flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <h4 class="text-sm font-bold text-red-800">คำร้องนี้ถูกปฏิเสธโดยแอดมิน</h4>
                            <p class="text-xs text-red-700 mt-1">
                                เหตุผล: <?php echo !empty($claim['admin_notes']) ? htmlspecialchars($claim['admin_notes']) : 'หลักฐานไม่เพียงพอ หรือไม่ตรงกับสิ่งของจริง'; ?>
                            </p>
                            <?php if (!empty($claim['admin_first'])): ?>
                                <p class="text-[10px] text-red-400 mt-1">
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
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 pb-5 border-b border-gray-200">
                        <div class="flex gap-4 items-start flex-1 min-w-0">
                            <div class="flex flex-col items-center gap-2 w-20 md:w-24 shrink-0">
                                <div class="w-20 h-20 md:w-24 md:h-24 bg-white rounded-xl overflow-hidden border border-slate-200 shadow-sm shrink-0 p-0.5">
                                    <?php if (!empty($claim['item_image'])): ?>
                                        <img src="<?php echo $base_url . '/' . htmlspecialchars($claim['item_image']); ?>" class="w-full h-full object-cover rounded-lg">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $claim['item_id']; ?>" class="bg-white hover:bg-slate-50 border border-slate-300 hover:border-slate-400 text-slate-700 hover:text-slate-900 font-medium text-xs rounded-lg py-1 px-2.5 w-full text-center shadow-2xs transition">
                                    ดูโพสต์
                                </a>
                            </div>
                            <div class="min-w-0 flex-1 flex flex-col justify-start">
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-0.5">ของที่ขอรับคืน</span>
                                <h3 class="font-bold text-gray-900 text-lg leading-tight truncate"><?php echo htmlspecialchars($claim['item_title']); ?></h3>
                                <div class="text-xs text-gray-500 mt-1 max-w-xs md:max-w-sm break-words whitespace-normal leading-relaxed">
                                    <span class="font-medium text-slate-600">สถานที่พบ:</span> <?php echo htmlspecialchars($claim['location']); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Button (แสดงเมื่อ Admin อนุมัติคำร้องแล้วเท่านั้น) -->
                        <?php if ($is_claimant || $is_finder): ?>
                            <?php if (in_array($claim['status'], ['approved', 'meeting_scheduled', 'completed'])): ?>
                                <a href="<?php echo $base_url; ?>/pages/chat.php?item_id=<?php echo $claim['item_id']; ?>&receiver_id=<?php echo $is_finder ? $claim['claimant_id'] : $claim['finder_id']; ?>&ref=claim_detail&claim_id=<?php echo $claim['id']; ?>" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-lg transition border border-blue-600 shadow-2xs flex items-center gap-1.5 whitespace-nowrap justify-center self-stretch sm:self-auto">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                    แชทสนทนา
                                </a>
                            <?php else: ?>
                                <div class="flex flex-col items-center gap-1 self-stretch sm:self-auto">
                                    <button type="button" disabled title="ต้องได้รับการอนุมัติคำร้องจาก Admin ก่อน จึงจะแชทสนทนาได้" class="w-full px-4 py-2.5 bg-slate-100 text-slate-400 font-semibold text-xs rounded-lg border border-slate-200 cursor-not-allowed flex items-center justify-center gap-1.5 whitespace-nowrap opacity-80">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 00-2 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                        แชทสนทนา
                                    </button>
                                    <span class="text-[11px] text-slate-400 font-medium text-center">(รอ Admin อนุมัติ)</span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Clean Key-Value Party Layout -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 text-xs">
                        <div class="space-y-1">
                            <span class="text-gray-400 font-medium block">ผู้ขอรับคืน:</span>
                            <div class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($claim['claimant_first'] . ' ' . $claim['claimant_last']); ?></div>
                            <?php if (!empty($claim['claimant_phone'])): ?>
                                <div class="text-xs text-gray-600">โทร: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($claim['claimant_phone']); ?></span></div>
                            <?php endif; ?>
                        </div>
                        <div class="space-y-1">
                            <span class="text-gray-400 font-medium block">ผู้พบของ:</span>
                            <div class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($claim['finder_first'] . ' ' . $claim['finder_last']); ?></div>
                            <?php if (in_array($claim['status'], ['approved', 'meeting_scheduled', 'completed']) && !empty($claim['finder_phone'])): ?>
                                <div class="text-xs text-gray-600">โทร: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($claim['finder_phone']); ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (($is_finder || $is_admin) && (!empty($claim['secret_description']) || !empty($claim['item_sn']))): ?>
                        <div class="mt-3 p-3 bg-blue-50/70 border border-blue-200 rounded-lg space-y-1">
                            <?php if (!empty($claim['secret_description'])): ?>
                                <div class="flex items-start gap-2 text-xs">
                                    <span class="text-blue-900 font-bold text-xs shrink-0">รายละเอียดลับ:</span>
                                    <span class="text-blue-950 font-medium text-xs break-words"><?php echo htmlspecialchars($claim['secret_description']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($claim['item_sn'])): ?>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="text-blue-900 font-bold text-xs shrink-0">Serial Number ในระบบ:</span>
                                    <span class="font-mono font-bold text-blue-950 bg-white px-2 py-0.5 border border-blue-200 rounded text-[11px]"><?php echo htmlspecialchars($claim['item_sn']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Section: Storage Location & Timeline Stepper (แสดงเมื่ออนุมัติแล้ว) -->
                <?php if (in_array($claim['status'], ['approved', 'meeting_scheduled'])): ?>
                    <?php 
                        $approved_date_str = !empty($claim['admin_action_at']) 
                            ? date('j M Y', strtotime($claim['admin_action_at'])) 
                            : date('j M Y', strtotime($claim['updated_at']));
                    ?>
                    <div class="space-y-4">
                        <!-- 1. กล่องสถานที่เก็บรักษาของในปัจจุบัน -->
                        <?php if (!empty($claim['storage_location'])): ?>
                            <div class="flex items-center gap-2.5 p-3.5 bg-white border border-slate-200 rounded-xl shadow-sm mb-4">
                                <svg class="w-5 h-5 text-slate-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <span class="font-bold text-slate-600 text-sm">สถานที่เก็บรักษาของในปัจจุบัน:</span>
                                <span class="bg-slate-100 text-slate-800 px-3 py-1 rounded-md text-sm font-semibold"><?php echo htmlspecialchars($claim['storage_location']); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- 2. Timeline Stepper แนวนอน (Order Status Timeline Stepper) -->
                        <div class="bg-white border border-slate-200 rounded-xl p-6 mb-5 shadow-sm">
                            <div class="relative flex items-center justify-between max-w-lg mx-auto py-2">
                                
                                <!-- โหนดที่ 1: นัดหมายวันเวลา (กำลังดำเนินการ/ปัจจุบัน) -->
                                <div class="flex flex-col items-center z-10 min-w-[90px] sm:min-w-[110px]">
                                    <div class="w-12 h-12 rounded-full border-2 border-slate-800 bg-white flex items-center justify-center shadow-xs">
                                        <span class="font-bold text-slate-800 text-sm">1</span>
                                    </div>
                                    <span class="font-bold text-slate-800 text-xs sm:text-sm mt-2 text-center">นัดหมายวันเวลา</span>
                                    <span class="text-slate-400 text-[11px] text-center mt-0.5">แชทหรือโทรติดต่อ</span>
                                </div>

                                <!-- เส้นเชื่อม 1 ไป 2 -->
                                <div class="flex-1 h-0.5 bg-slate-200 mx-1 sm:mx-2 -mt-6"></div>

                                <!-- โหนดที่ 2: ส่งมอบสิ่งของ -->
                                <div class="flex flex-col items-center z-10 min-w-[90px] sm:min-w-[110px]">
                                    <div class="w-12 h-12 rounded-full border-2 border-slate-300 bg-white flex items-center justify-center shadow-xs">
                                        <span class="font-bold text-slate-400 text-sm">2</span>
                                    </div>
                                    <span class="font-bold text-slate-600 text-xs sm:text-sm mt-2 text-center">ส่งมอบสิ่งของ</span>
                                    <span class="text-slate-400 text-[11px] text-center mt-0.5">ตามจุดนัดรับ</span>
                                </div>

                                <!-- เส้นเชื่อม 2 ไป 3 -->
                                <div class="flex-1 h-0.5 bg-slate-200 mx-1 sm:mx-2 -mt-6"></div>

                                <!-- โหนดที่ 3: ยืนยันเสร็จสิ้น -->
                                <div class="flex flex-col items-center z-10 min-w-[90px] sm:min-w-[110px]">
                                    <div class="w-12 h-12 rounded-full border-2 border-slate-300 bg-white flex items-center justify-center shadow-xs">
                                        <span class="font-bold text-slate-400 text-sm">3</span>
                                    </div>
                                    <span class="font-bold text-slate-600 text-xs sm:text-sm mt-2 text-center">ยืนยันเสร็จสิ้น</span>
                                    <span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full text-[10px] mt-1 inline-block text-center font-medium">กดยืนยันด้านล่าง</span>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endif; ?>

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

                <!-- Section 4: Action Buttons (Clear Hierarchy: Primary Emerald, Soft Blue Chat, Destructive Ghost Border) -->
                <div class="pt-4 border-t border-gray-200">
                    <div class="flex flex-wrap gap-3 items-center">
                        
                        <!-- 1. แจ้งเตือนสถานะการรอตรวจสอบ / คำแนะนำการตัดสินใจของเจ้าหน้าที่ (Action Guide) -->
                        <?php if (in_array($claim['status'], ['pending', 'under_admin_review'])): ?>
                            <div class="w-full p-4 bg-amber-50/90 border border-amber-200/80 rounded-xl text-amber-900 flex items-start gap-3">
                                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="text-xs space-y-0.5">
                                    <?php if ($is_admin): ?>
                                        <span class="font-bold block text-amber-950 text-sm">คำแนะนำในการพิจารณา:</span>
                                        <p class="text-amber-800 leading-relaxed">กรุณาตรวจสอบความสอดคล้องระหว่างหลักฐานของผู้ขอรับคืนกับข้อมูลของผู้พบของ ก่อนกดอนุมัติหรือปฏิเสธคำร้อง</p>
                                    <?php else: ?>
                                        <span class="font-bold block text-amber-950 text-sm">อยู่ระหว่างการตรวจสอบหลักฐาน</span>
                                        <p class="text-amber-800 leading-relaxed">ระบบกำลังดำเนินการเปรียบเทียบข้อมูลหลักฐานความถูกต้อง จะมีการแจ้งเตือนเมื่อการตรวจสอบเสร็จสิ้น</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- ปุ่มควบคุมสำหรับ Admin บนหน้า claim_detail.php โดยตรง -->
                        <?php if ($is_admin && in_array($claim['status'], ['pending', 'under_admin_review'])): ?>
                            <form action="<?php echo $base_url; ?>/actions/claim_action.php" method="POST" class="w-full space-y-4" onsubmit="return handleAdminDecisionSubmit(this, event)">
                                <input type="hidden" name="action" value="admin_decision">
                                <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                <input type="hidden" name="decision" value="">
                                
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">บันทึกเหตุผลของแอดมิน <span class="text-red-500">*</span></label>
                                    <input type="text" name="admin_notes" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs outline-none focus:border-slate-800 shadow-2xs" placeholder="กรุณาระบุเหตุผลการพิจารณาอนุมัติหรือปฏิเสธคำร้อง...">
                                </div>

                                <div class="flex items-center gap-3 text-sm">
                                    <button type="submit" onclick="this.form.decision.value='approve'" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg transition shadow-xs">
                                        อนุมัติคำร้อง
                                    </button>
                                    <button type="submit" onclick="this.form.decision.value='reject'" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-lg transition shadow-xs">
                                        ปฏิเสธคำร้อง
                                    </button>
                                </div>
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
                                ยกเลิกคำร้อง
                            </button>
                        <?php endif; ?>

                        <!-- 3. ยืนยันการส่งมอบ / ได้รับของสำเร็จ (Emerald Green Primary Button) -->
                        <?php if (in_array($claim['status'], ['approved', 'meeting_scheduled'])): ?>
                            <?php 
                                $confirm_btn_text = $is_claimant ? 'ยืนยันได้รับของแล้ว' : 'ยืนยันการส่งมอบ';
                                $confirm_msg = $is_claimant ? 'คุณยืนยันว่าได้รับสิ่งของถูกต้องเรียบร้อยแล้วใช่หรือไม่?' : 'ยืนยันการส่งมอบสำเร็จใช่หรือไม่?';
                            ?>
                            <form action="<?php echo $base_url; ?>/actions/claim_action.php" method="POST" class="inline" onsubmit="return confirm('<?php echo $confirm_msg; ?>');">
                                <input type="hidden" name="action" value="complete_claim">
                                <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                <button type="submit" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg transition shadow-sm flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <?php echo $confirm_btn_text; ?>
                                </button>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>

<?php if ($is_admin): ?>
    </main>
</div>
<?php else: ?>
    </div>
</div>
<?php endif; ?>

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
        <h3 class="text-base font-bold text-gray-800 mb-1">ยืนยันการยกเลิกคำร้อง</h3>
        <p class="text-xs text-gray-500 mb-4">คุณแน่ใจหรือไม่ว่าต้องการยกเลิกคำร้องนี้?</p>
        
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
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm">ยืนยันยกเลิก</button>
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

<?php require_once '../includes/footer.php'; ?>
