<?php
$base_url = 'http://localhost/lost-and-found';
require_once '../includes/header.php';
require_once '../config/database.php';

// 1. ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนทำการยื่นคำร้องขอ Claim";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if ($item_id <= 0) {
    $_SESSION['error'] = "ไม่พบรายการประกาศที่คุณต้องการยื่น Claim";
    echo "<script>window.location.href = '".$base_url."/pages/browse.php';</script>";
    exit;
}

try {
    // 2. ดึงข้อมูลประกาศและข้อมูลผู้โพสต์
    $stmt = $pdo->prepare("SELECT i.*, u.first_name, u.last_name, u.email FROM items i JOIN users u ON i.user_id = u.id WHERE i.id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();

    if (!$item) {
        $_SESSION['error'] = "ไม่พบรายการประกาศนี้ในระบบ";
        echo "<script>window.location.href = '".$base_url."/pages/browse.php';</script>";
        exit;
    }

    // 3. ตรวจสอบเงื่อนไข: ต้องเป็นประกาศประเภท found, สถานะ open, และผู้ใช้ไม่ใช่เจ้าของประกาศเอง
    if ($item['type'] !== 'found') {
        $_SESSION['error'] = "สามารถยื่น Claim ได้เฉพาะประกาศประเภท 'พบสิ่งของ' เท่านั้น";
        echo "<script>window.location.href = '".$base_url."/pages/item_detail.php?id={$item_id}';</script>";
        exit;
    }

    if ($item['status'] !== 'open') {
        $_SESSION['error'] = "ประกาศนี้ไม่อยู่ในสถานะเปิดรับ Claim";
        echo "<script>window.location.href = '".$base_url."/pages/item_detail.php?id={$item_id}';</script>";
        exit;
    }

    if ($item['user_id'] == $user_id) {
        $_SESSION['error'] = "คุณไม่สามารถยื่น Claim บนประกาศที่คุณโพสต์เองได้";
        echo "<script>window.location.href = '".$base_url."/pages/item_detail.php?id={$item_id}';</script>";
        exit;
    }

    // 4. ตรวจสอบว่าเคยยื่น Claim บนประกาศนี้ค้างไว้อยู่หรือไม่
    $claim_check_stmt = $pdo->prepare("SELECT id, status FROM claims WHERE item_id = ? AND claimant_id = ? AND status IN ('pending', 'approved', 'under_admin_review', 'meeting_scheduled') ORDER BY id DESC LIMIT 1");
    $claim_check_stmt->execute([$item_id, $user_id]);
    $existing_claim = $claim_check_stmt->fetch();

    if ($existing_claim) {
        $_SESSION['warning'] = "คุณมีคำร้องขอ Claim บนประกาศนี้อยู่แล้ว";
        echo "<script>window.location.href = '".$base_url."/pages/claim_detail.php?id={$existing_claim['id']}';</script>";
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Database Error: " . $e->getMessage();
    echo "<script>window.location.href = '".$base_url."/pages/browse.php';</script>";
    exit;
}

// Category Mapping
$categories = [
    'electronics' => 'อุปกรณ์อิเล็กทรอนิกส์',
    'walletandcash' => 'กระเป๋าสตางค์และเงินสด', 
    'cardanddocument' => 'บัตรและเอกสารสำคัญ',
    'keyandkeycard' => 'กุญแจและคีย์การ์ด',
    'bagandluggage' => 'กระเป๋าและสัมภาระ',
    'clothingandjewelry' => 'เครื่องแต่งกายและเครื่องประดับ',
    'studymaterialandstationery' => 'อุปกรณ์การเรียนและเครื่องเขียน',
    'personalbelonging' => 'ของใช้ส่วนตัว',
    'vehicleandaccessory' => 'ยานพาหนะและอุปกรณ์เสริม',
    'others' => 'อื่นๆ'
];
$category_label = isset($categories[$item['category']]) ? $categories[$item['category']] : 'อื่นๆ';
$found_date = date('d M Y', strtotime($item['event_date']));
?>

<div class="py-10 bg-background flex-grow">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Navigation Back -->
        <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $item_id; ?>" class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-primary mb-6 transition">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            ย้อนกลับไปหน้ารายละเอียดสิ่งของ
        </a>

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-primary">ยื่นคำร้องขอ Claim สิ่งของ</h1>
            <p class="text-slate-500 text-sm mt-1">กรุณาระบุรายละเอียดและแนบหลักฐานความเป็นเจ้าของให้ชัดเจน เพื่อส่งให้ผู้พบของตรวจสอบ</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Left Column: Item Summary Card (4 cols on lg) -->
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">สิ่งของที่คุณกำลังจะ Claim</div>
                    
                    <!-- Item Image -->
                    <div class="aspect-video bg-slate-100 rounded-xl overflow-hidden mb-4 border border-slate-100 flex items-center justify-center p-1">
                        <?php if (!empty($item['image_path'])): ?>
                            <img src="<?php echo $base_url . '/' . htmlspecialchars($item['image_path']); ?>" alt="รูปภาพสิ่งของ" class="w-full h-full object-contain">
                        <?php else: ?>
                            <div class="text-slate-400 text-xs flex flex-col items-center">
                                <svg class="w-10 h-10 mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                ไม่มีรูปภาพประกอบ
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Item Title -->
                    <h3 class="font-bold text-slate-900 text-base mb-3 leading-snug"><?php echo htmlspecialchars($item['title']); ?></h3>
                    
                    <div class="space-y-2 text-xs text-slate-600 border-t border-slate-100 pt-3">
                        <div class="flex justify-between">
                            <span class="text-slate-400">หมวดหมู่:</span>
                            <span class="font-medium text-slate-700"><?php echo $category_label; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">สถานที่พบ:</span>
                            <span class="font-medium text-slate-700 text-right max-w-[160px] truncate"><?php echo htmlspecialchars($item['location_detail']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">วันที่พบเจอ:</span>
                            <span class="font-medium text-slate-700"><?php echo $found_date; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">ผู้พบสิ่งของ:</span>
                            <span class="font-medium text-slate-700"><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Guidelines Card -->
                <div class="bg-blue-50/70 rounded-2xl border border-blue-100 p-5 text-xs text-blue-900 space-y-2">
                    <div class="font-bold text-blue-900 flex items-center gap-1.5 text-sm mb-1">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        คำแนะนำในการยื่น Claim
                    </div>
                    <p>• ระบุตำหนิเฉพาะ รอยขีดข่วน หรือสิ่งของย่อยที่อยู่ข้างใน ซึ่งรู้เฉพาะเจ้าของจริงเท่านั้น</p>
                    <p>• หากมี Serial Number, IMEI หรือรหัสประจำตัว ให้กรอกเพื่อช่วยยืนยันความถูกต้อง</p>
                    <p>• การแนบรูปถ่ายคู่กับสิ่งของ ใบเสร็จรับเงิน หรือกล่องสินค้าจะช่วยให้ได้รับการอนุมัติเร็วขึ้น</p>
                </div>
            </div>

            <!-- Right Column: Claim Form (8 cols on lg) -->
            <div class="lg:col-span-8">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                    
                    <form action="<?php echo $base_url; ?>/actions/claim_action.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="action" value="submit_claim">
                        <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">

                        <!-- 1. Proof Description -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                รายละเอียดหลักฐานยืนยันความเป็นเจ้าของ <span class="text-red-500">*</span>
                            </label>
                            <p class="text-xs text-slate-500 mb-2">อธิบายจุดสังเกตเฉพาะ ตำหนิ ข้อมูลลับ หรือสิ่งของภายในที่สามารถพิสูจน์ความเป็นเจ้าของได้</p>
                            <textarea name="proof_description" rows="5" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-accent focus:bg-white outline-none transition text-sm leading-relaxed" placeholder="เช่น ด้านหลังกระเป๋ามีรอยถลอกสีดำ ข้อมูลบัตรในกระเป๋าชื่อ... หรือรหัส PIN ล็อคหน้าจอ..." required></textarea>
                        </div>

                        <!-- 2. Serial Number & Contact Phone -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-1.5">เลข Serial Number / IMEI (ถ้ามี)</label>
                                <input type="text" name="provided_serial_number" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-accent focus:bg-white outline-none transition text-sm font-mono" placeholder="เช่น S/N, IMEI, ID Card">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-1.5">
                                    เบอร์โทรศัพท์ติดต่อกลับ <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($_SESSION['user_phone'] ?? ''); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-accent focus:bg-white outline-none transition text-sm" placeholder="08XXXXXXXX" required>
                            </div>
                        </div>

                        <!-- 3. Image Upload with Live Preview -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-800 mb-1.5">แนบรูปถ่ายหลักฐานเพิ่มเติม (ถ้ามี)</label>
                            <p class="text-xs text-slate-500 mb-2.5">เช่น รูปถ่ายของคุณคู่กับสิ่งของ ใบเสร็จรับเงิน หรือภาพซอฟต์แวร์ยืนยันตัวตน (JPG, PNG, WEBP)</p>
                            
                            <div class="border-2 border-dashed border-slate-200 rounded-2xl p-4 bg-slate-50/50 hover:bg-slate-50 transition text-center">
                                <input type="file" id="proof_image_input" name="proof_image" accept="image/*" class="hidden" onchange="previewImage(this)">
                                
                                <label for="proof_image_input" class="cursor-pointer block py-3">
                                    <div id="upload_prompt" class="space-y-2">
                                        <svg class="w-8 h-8 mx-auto text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <div class="text-xs font-semibold text-accent">คลิกเพื่ออัปโหลดรูปภาพหลักฐาน</div>
                                        <div class="text-[11px] text-slate-400">รองรับไฟล์ภาพขนาดไม่เกิน 5MB</div>
                                    </div>
                                    
                                    <div id="image_preview_container" class="hidden flex flex-col items-center">
                                        <img id="image_preview" src="#" alt="ตัวอย่างรูปภาพ" class="max-h-48 rounded-xl object-contain border border-slate-200 shadow-sm mb-2">
                                        <span class="text-xs text-emerald-600 font-semibold flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            เลือกรูปภาพเรียบร้อยแล้ว (คลิกอีกครั้งเพื่อเปลี่ยนรูป)
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- 4. Action Buttons -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                            <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $item_id; ?>" class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                                ยกเลิก
                            </a>
                            <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition shadow-sm inline-flex items-center">
                                ยืนยันส่งคำร้อง
                            </button>
                        </div>
                    </form>

                </div>
            </div>

        </div>

    </div>
</div>

<script>
function previewImage(input) {
    const prompt = document.getElementById('upload_prompt');
    const container = document.getElementById('image_preview_container');
    const preview = document.getElementById('image_preview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            prompt.classList.add('hidden');
            container.classList.remove('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
