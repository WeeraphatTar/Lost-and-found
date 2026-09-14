<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/index.php';</script>";
    exit;
}

// ดึงคำร้องทั้งหมดที่อยู่ระหว่าง Admin Review หรือรอการตรวจสอบ
$sql = "
    SELECT c.*, 
           i.title as item_title, i.category, i.secret_description, i.serial_number as item_sn, i.image_path as item_image,
           u_c.first_name as claimant_first, u_c.last_name as claimant_last, u_c.email as claimant_email,
           u_f.first_name as finder_first, u_f.last_name as finder_last, u_f.email as finder_email
    FROM claims c
    JOIN items i ON c.item_id = i.id
    JOIN users u_c ON c.claimant_id = u_c.id
    JOIN users u_f ON c.finder_id = u_f.id
    ORDER BY (c.status = 'under_admin_review') DESC, c.created_at DESC
";
$claims = $pdo->query($sql)->fetchAll();
?>

<div class="py-10 bg-background flex-grow font-sans">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 border-b border-gray-200 pb-5">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">ระบบจัดการสิทธิ์ผู้ดูแล</span>
                <h1 class="text-2xl sm:text-3xl font-bold text-primary">ตรวจสอบและอนุมัติคำร้อง</h1>
                <p class="text-sm text-gray-500 mt-1">ตรวจสอบหลักฐานเปรียบเทียบข้อมูลเพื่ออนุมัติการส่งคืนสิ่งของ</p>
            </div>
            <a href="<?php echo $base_url; ?>/pages/dashboard.php" class="px-4 py-2 bg-gray-100 text-gray-700 font-medium text-sm rounded-lg hover:bg-gray-200 transition self-start sm:self-auto whitespace-nowrap">
                กลับ Dashboard
            </a>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($claims)): ?>
            <div class="bg-white rounded-2xl p-12 text-center border border-gray-200 shadow-sm">
                <h3 class="text-lg font-bold text-gray-800 mb-1">ไม่มีคำร้องค้างตรวจสอบ</h3>
                <p class="text-sm text-gray-500">ไม่มีรายการคำร้องที่ค้างรอ Admin ในขณะนี้</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($claims as $c): ?>
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        
                        <!-- Header Banner -->
                        <div class="p-5 border-b border-gray-100 flex flex-wrap justify-between items-center gap-4">
                            <!-- Left: Item & Status Info -->
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="font-mono text-xs font-bold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-md border border-blue-200/80 shadow-2xs">#CLM-<?php echo str_pad($c['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                <h3 class="font-bold text-primary text-base sm:text-lg"><?php echo htmlspecialchars($c['item_title']); ?></h3>
                                
                                <?php if ($c['status'] === 'under_admin_review' || $c['status'] === 'pending'): ?>
                                    <span class="px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 font-semibold rounded-full flex items-center gap-1.5 text-xs">
                                        <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                                        รอ Admin ตรวจสอบหลักฐาน
                                    </span>
                                <?php elseif (in_array($c['status'], ['approved', 'meeting_scheduled', 'completed'])): ?>
                                    <span class="px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold rounded-full flex items-center gap-1.5 text-xs">
                                        <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                        <?php echo $c['status'] === 'completed' ? 'ส่งมอบสำเร็จ' : 'อนุมัติแล้ว'; ?>
                                    </span>
                                <?php elseif ($c['status'] === 'rejected'): ?>
                                    <span class="px-3 py-1 bg-red-50 text-red-700 border border-red-200 font-semibold rounded-full flex items-center gap-1.5 text-xs">
                                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                        ปฏิเสธคำร้อง
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-gray-50 text-gray-700 border border-gray-200 font-medium rounded-full flex items-center gap-1.5 text-xs">
                                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                                        ยกเลิกคำร้อง
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Right: Action Buttons -->
                            <div class="flex items-center gap-2.5 text-xs">
                                <a href="<?php echo $base_url; ?>/pages/chat.php?item_id=<?php echo $c['item_id']; ?>&claim_id=<?php echo $c['id']; ?>&ref=admin_claims" class="px-3.5 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200/80 rounded-xl font-semibold transition flex items-center gap-1.5 shadow-sm">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 10h.01M16 12h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                    ดูประวัติแชท
                                </a>
                                <a href="<?php echo $base_url; ?>/pages/claim_detail.php?id=<?php echo $c['id']; ?>&ref=admin_claims" class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 rounded-xl font-semibold transition flex items-center gap-1.5 shadow-sm">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    ดูคำร้องเต็ม
                                </a>
                            </div>
                        </div>

                        <!-- Body Grid -->
                        <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6 text-sm">
                            
                            <!-- Left: Claimant Info -->
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ผู้ขอรับคืน</div>
                                <div class="font-bold text-gray-900 mb-3"><?php echo htmlspecialchars($c['claimant_first'] . ' ' . $c['claimant_last']); ?> <span class="text-gray-500 font-normal">(<?php echo htmlspecialchars($c['claimant_email']); ?>)</span></div>
                                
                                <div class="bg-white p-3 rounded-lg border border-gray-200 space-y-2 text-xs text-gray-700">
                                    <div><span class="font-bold text-gray-500">หลักฐานที่ยื่น:</span> <?php echo htmlspecialchars($c['proof_description']); ?></div>
                                    <?php if (!empty($c['provided_serial_number'])): ?>
                                        <div><span class="font-bold text-gray-500">Serial Number ที่ระบุ:</span> <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded font-bold text-gray-800"><?php echo htmlspecialchars($c['provided_serial_number']); ?></span></div>
                                    <?php endif; ?>
                                    <div><span class="font-bold text-gray-500">เบอร์ติดต่อ:</span> <?php echo htmlspecialchars($c['contact_phone']); ?></div>
                                </div>
                            </div>

                            <!-- Right: Finder & Secret Info -->
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ผู้พบของ</div>
                                <div class="font-bold text-gray-900 mb-3"><?php echo htmlspecialchars($c['finder_first'] . ' ' . $c['finder_last']); ?> <span class="text-gray-500 font-normal">(<?php echo htmlspecialchars($c['finder_email']); ?>)</span></div>
                                
                                <div class="bg-white p-3 rounded-lg border border-gray-200 space-y-2 text-xs text-gray-700">
                                    <div><span class="font-bold text-gray-500">คำอธิบายลับในระบบ:</span> <?php echo !empty($c['secret_description']) ? htmlspecialchars($c['secret_description']) : 'ไม่ได้ระบุไว้'; ?></div>
                                    <?php if (!empty($c['item_sn'])): ?>
                                        <div><span class="font-bold text-gray-500">Serial Number ในประกาศ:</span> <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded font-bold text-gray-800"><?php echo htmlspecialchars($c['item_sn']); ?></span></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>

                        <!-- Admin Action Controls -->
                        <?php if (in_array($c['status'], ['pending', 'under_admin_review'])): ?>
                            <div class="p-6 bg-gray-50/70 border-t border-gray-200">
                                <?php if (!empty($c['dispute_reason'])): ?>
                                    <div class="mb-4">
                                        <span class="text-xs font-bold text-gray-500 uppercase">เหตุผลส่ง Admin:</span>
                                        <p class="text-sm font-medium text-gray-800 mt-1"><?php echo htmlspecialchars($c['dispute_reason']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <form action="<?php echo $base_url; ?>/actions/claim_action.php" method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="admin_decision">
                                    <input type="hidden" name="claim_id" value="<?php echo $c['id']; ?>">
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">บันทึกของ Admin (Admin Notes):</label>
                                        <input type="text" name="admin_notes" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="ระบุเหตุผลการอนุมัติหรือปฏิเสธเพื่อบันทึกในระบบ..." required>
                                    </div>

                                    <div class="flex items-center gap-3 text-sm">
                                        <button type="submit" name="decision" value="approve" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition shadow-sm">
                                            อนุมัติคำร้อง
                                        </button>

                                        <button type="submit" name="decision" value="reject" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition shadow-sm">
                                            ปฏิเสธคำร้อง
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
