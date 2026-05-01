<?php
$base_url = 'http://localhost/Lost_found';
require_once '../includes/header.php';

// ตรวจสอบว่าเข้าสู่ระบบหรือยัง
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนทำการแจ้งของหาย";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}
?>

<div class="py-12 bg-background flex-grow">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white p-8 sm:p-10 rounded-lg shadow-sm border border-gray-200">
            <h2 class="text-2xl font-bold text-center text-primary mb-2">ฟอร์มแจ้งของหาย (Report Lost Item)</h2>
            <p class="text-center text-important mb-8">กรุณากรอกข้อมูลให้ครบถ้วนเพื่อเพิ่มโอกาสในการค้นพบ</p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="p-4 mb-6 text-sm text-red-700 bg-red-100 border border-red-300 rounded-md">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo $base_url; ?>/actions/post_action.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                <input type="hidden" name="type" value="lost">
                
                <!-- ขั้นตอนที่ 1: ข้อมูลพื้นฐาน -->
                <fieldset class="border border-gray-200 p-6 rounded-md bg-gray-50">
                    <legend class="px-2 text-lg font-semibold text-primary bg-white border border-gray-200 rounded px-4 py-1 shadow-sm">ขั้นตอนที่ 1: ข้อมูลพื้นฐาน</legend>
                    <div class="space-y-5 mt-4">
                        <div>
                            <label class="block font-medium text-primary mb-1">ชื่อเรียกทรัพย์สิน <span class="asterisk text-red-500">*</span></label>
                            <input type="text" name="item_name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" placeholder="เช่น กระเป๋าสตางค์, กุญแจรถ" required>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-5">
                            <div class="flex-1">
                                <label class="block font-medium text-primary mb-1">หมวดหมู่ <span class="asterisk text-red-500">*</span></label>
                                <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" required>
                                    <option value="">เลือกหมวดหมู่</option>
                                    <option value="electronics">อุปกรณ์อิเล็กทรอนิกส์</option>
                                    <option value="walletandcash">กระเป๋าสตางค์และเงินสด</option>
                                    <option value="cardanddocument">บัตรและเอกสารสำคัญ</option>
                                    <option value="keyandkeycard">กุญแจและคีย์การ์ด</option>
                                    <option value="bagandluggage">กระเป๋าและสัมภาระ</option>
                                    <option value="clothingandjewelry">เครื่องแต่งกายและเครื่องประดับ</option>
                                    <option value="studymaterialandstationery">อุปกรณ์การเรียนและเครื่องเขียน</option>
                                    <option value="personalbelonging">ของใช้ส่วนตัว</option>
                                    <option value="vehicleandaccessory">ยานพาหนะและอุปกรณ์เสริม</option>
                                    <option value="others">อื่นๆ</option>
                                </select>
                            </div>
                            <div class="flex-1">
                                <label class="block font-medium text-primary mb-1">วันที่หาย (โดยประมาณ) <span class="asterisk text-red-500">*</span></label>
                                <input type="date" name="lost_date" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" required>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- ขั้นตอนที่ 2: รายละเอียดเชิงลึก -->
                <fieldset class="border border-gray-200 p-6 rounded-md bg-gray-50">
                    <legend class="px-2 text-lg font-semibold text-primary bg-white border border-gray-200 rounded px-4 py-1 shadow-sm">ขั้นตอนที่ 2: รายละเอียดเชิงลึก</legend>
                    <div class="space-y-5 mt-4">
                        <div>
                            <label class="block font-medium text-primary mb-1">ลักษณะเฉพาะ (สี, ยี่ห้อ, จุดสังเกต) <span class="asterisk text-red-500">*</span></label>
                            <textarea name="description" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" placeholder="อธิบายลักษณะเพิ่มเติมให้ชัดเจน เช่น รอยขีดข่วน หรือสติกเกอร์ที่ติดอยู่" required></textarea>
                        </div>
                        <div>
                            <label class="block font-medium text-primary mb-1">เลขซีเรียล หรือ ข้อมูลระบุตัวตน (ถ้ามี)</label>
                            <input type="text" name="serial_number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" placeholder="เช่น IMEI โทรศัพท์, เลขบัตร หรือเลขตัวถัง">
                        </div>
                    </div>
                </fieldset>

                <!-- ขั้นตอนที่ 3: สถานที่ -->
                <fieldset class="border border-gray-200 p-6 rounded-md bg-gray-50">
                    <legend class="px-2 text-lg font-semibold text-primary bg-white border border-gray-200 rounded px-4 py-1 shadow-sm">ขั้นตอนที่ 3: สถานที่ที่คาดว่าหาย</legend>
                    <div class="mt-4">
                        <label class="block font-medium text-primary mb-1">สถานที่เกิดเหตุ <span class="asterisk text-red-500">*</span></label>
                        <input type="text" name="location" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" placeholder="เช่น โรงอาหารตึก A, ห้องสมุดชั้น 2" required>
                    </div>
                </fieldset>

                <!-- ขั้นตอนที่ 4: การอัปโหลดรูปภาพและช่องทางติดต่อ -->
                <fieldset class="border border-gray-200 p-6 rounded-md bg-gray-50">
                    <legend class="px-2 text-lg font-semibold text-primary bg-white border border-gray-200 rounded px-4 py-1 shadow-sm">ขั้นตอนที่ 4: รูปภาพและติดต่อ</legend>
                    <div class="space-y-5 mt-4">
                        <div>
                            <label class="block font-medium text-primary mb-1">อัปโหลดรูปภาพ (ถ้ามี)</label>
                            <input type="file" name="item_image" class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white text-sm" accept="image/*">
                        </div>
                        <div>
                            <label class="block font-medium text-primary mb-1">เบอร์โทรศัพท์ติดต่อ <span class="asterisk text-red-500">*</span></label>
                            <input type="tel" name="contact_phone" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" placeholder="เบอร์โทรศัพท์สำหรับติดต่อกลับ" required>
                            <p class="text-xs text-gray-500 mt-2">ข้อมูลนี้จะถูกเก็บเป็นความลับและจำกัดการแสดงผลต่อสาธารณะตามนโยบายความเป็นส่วนตัว</p>
                        </div>
                    </div>
                </fieldset>

                <div class="pt-4 text-center">
                    <button type="submit" class="w-full sm:w-auto sm:min-w-[300px] py-3 px-8 bg-primary text-white font-medium text-lg rounded-md hover:bg-secondary transition shadow-sm">บันทึกประกาศของหาย</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
