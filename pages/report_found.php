<?php
$base_url = 'http://localhost/Lost_found';
require_once '../includes/header.php';

// ตรวจสอบว่าเข้าสู่ระบบหรือยัง
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนทำการแจ้งพบของ";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}
?>

<div class="py-12 bg-background flex-grow">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white p-8 sm:p-10 rounded-lg shadow-sm border border-gray-200">
            <h2 class="text-2xl font-bold text-center text-primary mb-2">ฟอร์มแจ้งพบของ (Report Found Item)</h2>
            <p class="text-center text-important mb-8">กรุณากรอกข้อมูลเพื่อช่วยส่งคืนของให้กับเจ้าของที่แท้จริง</p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="p-4 mb-6 text-sm text-red-700 bg-red-100 border border-red-300 rounded-md">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo $base_url; ?>/actions/post_action.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                <input type="hidden" name="type" value="found">
                
                <!-- ขั้นตอนที่ 1: ข้อมูลพื้นฐาน -->
                <fieldset class="border border-gray-200 p-6 rounded-md bg-gray-50">
                    <legend class="px-2 text-lg font-semibold text-primary bg-white border border-gray-200 rounded px-4 py-1 shadow-sm">ขั้นตอนที่ 1: ข้อมูลสิ่งของ</legend>
                    <div class="space-y-5 mt-4">
                        <div>
                            <label class="block font-medium text-primary mb-1">ชื่อเรียกทรัพย์สินที่พบ <span class="asterisk text-red-500">*</span></label>
                            <input type="text" name="item_name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" placeholder="เช่น กระเป๋าสตางค์, กุญแจรถ" required>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-5">
                            <div class="flex-1">
                                <label class="block font-medium text-primary mb-1">หมวดหมู่ <span class="asterisk text-red-500">*</span></label>
                                <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" required>
                                    <option value="">เลือกหมวดหมู่</option>
                                    <option value="electronics">อุปกรณ์อิเล็กทรอนิกส์</option>
                                    <option value="documents">เอกสารสำคัญ</option>
                                    <option value="keys">กุญแจ</option>
                                    <option value="wallet">กระเป๋าสตางค์</option>
                                    <option value="accessories">กระเป๋าและเครื่องแต่งกาย</option>
                                    <option value="vehicles">ยานพาหนะ</option>
                                    <option value="others">อื่นๆ</option>
                                </select>
                            </div>
                            <div class="flex-1">
                                <label class="block font-medium text-primary mb-1">วันที่พบเจอ <span class="asterisk text-red-500">*</span></label>
                                <input type="date" name="found_date" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" required>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- ขั้นตอนที่ 2: รายละเอียดเชิงลึก -->
                <fieldset class="border border-gray-200 p-6 rounded-md bg-gray-50">
                    <legend class="px-2 text-lg font-semibold text-primary bg-white border border-gray-200 rounded px-4 py-1 shadow-sm">ขั้นตอนที่ 2: รายละเอียด</legend>
                    <div class="space-y-5 mt-4">
                        <div>
                            <label class="block font-medium text-primary mb-1">ลักษณะที่พบเห็น (เปิดเผยต่อสาธารณะ) <span class="asterisk text-red-500">*</span></label>
                            <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" placeholder="อธิบายลักษณะทั่วไปที่ใครๆ ก็มองเห็นได้ เช่น สี รูปทรง" required></textarea>
                        </div>
                        <div>
                            <label class="block font-medium text-primary mb-1">รายละเอียดลับ (Secret Details)</label>
                            <textarea name="secret_description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" placeholder="สิ่งที่จะมีแต่เจ้าของเท่านั้นที่รู้ เช่น ของที่อยู่ข้างใน, รหัสผ่าน เพื่อใช้ยืนยันตัวตนทีหลัง (ไม่แสดงผลสาธารณะ)"></textarea>
                        </div>
                    </div>
                </fieldset>

                <!-- ขั้นตอนที่ 3: สถานที่ -->
                <fieldset class="border border-gray-200 p-6 rounded-md bg-gray-50">
                    <legend class="px-2 text-lg font-semibold text-primary bg-white border border-gray-200 rounded px-4 py-1 shadow-sm">ขั้นตอนที่ 3: สถานที่</legend>
                    <div class="space-y-5 mt-4">
                        <div>
                            <label class="block font-medium text-primary mb-1">สถานที่ที่พบเจอ <span class="asterisk text-red-500">*</span></label>
                            <input type="text" name="location" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" placeholder="ระบุตำแหน่งที่พบให้ชัดเจน" required>
                        </div>
                        <div>
                            <label class="block font-medium text-primary mb-1">สถานที่เก็บรักษาของปัจจุบัน <span class="asterisk text-red-500">*</span></label>
                            <input type="text" name="storage_location" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" placeholder="เช่น นำไปฝากไว้ที่ฝ่ายธุรการ, เก็บไว้กับตัว" required>
                        </div>
                    </div>
                </fieldset>

                <!-- ขั้นตอนที่ 4: การอัปโหลดรูปภาพและช่องทางติดต่อ -->
                <fieldset class="border border-gray-200 p-6 rounded-md bg-gray-50">
                    <legend class="px-2 text-lg font-semibold text-primary bg-white border border-gray-200 rounded px-4 py-1 shadow-sm">ขั้นตอนที่ 4: รูปภาพและติดต่อ</legend>
                    <div class="space-y-5 mt-4">
                        <div>
                            <label class="block font-medium text-primary mb-1">อัปโหลดรูปภาพ (ถ้ามี)</label>
                            <input type="file" name="item_image" class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white text-sm" accept="image/*">
                            <p class="text-xs text-gray-500 mt-2">*คำแนะนำ: หากเป็นเอกสารสำคัญที่มีข้อมูลส่วนบุคคล เช่น บัตรประชาชน กรุณาเบลอข้อมูลส่วนบุคคลออกก่อน</p>
                        </div>
                        <div>
                            <label class="block font-medium text-primary mb-1">เบอร์โทรศัพท์ติดต่อ <span class="asterisk text-red-500">*</span></label>
                            <input type="tel" name="contact_phone" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" placeholder="เบอร์โทรศัพท์สำหรับติดต่อเพื่อรับของคืน" required>
                        </div>
                    </div>
                </fieldset>

                <div class="pt-4 text-center">
                    <button type="submit" class="w-full sm:w-auto sm:min-w-[300px] py-3 px-8 bg-primary text-white font-medium text-lg rounded-md hover:bg-secondary transition shadow-sm">บันทึกประกาศพบของ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
