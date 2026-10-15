<?php
$base_url = 'http://localhost/lost-and-found';
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
        <div class="bg-white p-8 sm:p-12 rounded-2xl shadow-sm border border-gray-100">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-primary mb-2">ฟอร์มแจ้งพบของ</h2>
                <p class="text-important font-medium">กรุณากรอกข้อมูลเพื่อช่วยส่งคืนของให้กับเจ้าของที่แท้จริง</p>
            </div>

            <!-- Stepper -->
            <div class="mb-12 max-w-2xl mx-auto">
                <div class="flex items-start justify-between relative">
                    <!-- Line Container -->
                    <div class="absolute left-0 top-5 transform -translate-y-1/2 w-full px-5 -z-0">
                        <div class="h-0.5 bg-gray-100 w-full relative">
                            <div id="step-line" class="absolute left-0 top-0 h-full bg-primary transition-all duration-300" style="width: 0%;"></div>
                        </div>
                    </div>
                    
                    <!-- Step 1 -->
                    <div class="step-item flex flex-col items-center relative z-10 w-16 sm:w-20">
                        <div class="step-circle w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold mb-2 shadow-sm transition-all duration-300">1</div>
                        <span class="text-[10px] sm:text-xs font-semibold text-primary text-center leading-tight">ข้อมูลพื้นฐาน</span>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="step-item flex flex-col items-center relative z-10 w-16 sm:w-20">
                        <div class="step-circle w-10 h-10 rounded-full bg-white border-2 border-gray-100 text-gray-400 flex items-center justify-center font-bold mb-2 transition-all duration-300">2</div>
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-400 text-center leading-tight">รายละเอียด</span>
                    </div>
                    
                    <!-- Step 3 -->
                    <div class="step-item flex flex-col items-center relative z-10 w-16 sm:w-20">
                        <div class="step-circle w-10 h-10 rounded-full bg-white border-2 border-gray-100 text-gray-400 flex items-center justify-center font-bold mb-2 transition-all duration-300">3</div>
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-400 text-center leading-tight">สถานที่</span>
                    </div>
                    
                    <!-- Step 4 -->
                    <div class="step-item flex flex-col items-center relative z-10 w-16 sm:w-20">
                        <div class="step-circle w-10 h-10 rounded-full bg-white border-2 border-gray-100 text-gray-400 flex items-center justify-center font-bold mb-2 transition-all duration-300">4</div>
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-400 text-center leading-tight">รูปภาพ & ติดต่อ</span>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="p-4 mb-6 text-sm text-red-700 bg-red-50 border border-red-100 rounded-xl">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form id="reportForm" action="<?php echo $base_url; ?>/actions/post_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="type" value="found">
                
                <!-- ขั้นตอนที่ 1: ข้อมูลพื้นฐาน -->
                <div class="step">
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-2xl font-bold text-primary mb-1">1. ข้อมูลสิ่งของ</h2>
                            <hr class="border-gray-100 mb-6">
                        </div>
                        <div class="space-y-5">
                            <div>
                                <label class="block font-medium text-primary mb-2">ชื่อสิ่งของที่พบ <span class="asterisk text-red-500">*</span></label>
                                <input type="text" name="item_name" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="เช่น กระเป๋าสตางค์, กุญแจรถ" required>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-6">
                                <div class="flex-1 relative">
                                    <label class="block font-medium text-primary mb-2">หมวดหมู่ <span class="asterisk text-red-500">*</span></label>
                                    <select id="category" name="category" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" required>
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
                                    <label class="block font-medium text-primary mb-2">วันที่พบเจอ <span class="asterisk text-red-500">*</span></label>
                                    <input type="date" name="found_date" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end mt-10">
                        <button type="button" onclick="nextStep()" class="px-10 py-3 bg-primary text-white font-bold rounded-xl hover:bg-secondary transition shadow-md hover:shadow-lg">ถัดไป</button>
                    </div>
                </div>

                <!-- ขั้นตอนที่ 2: รายละเอียด -->
                <div class="step hidden">
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-2xl font-bold text-primary mb-1">2. รายละเอียดสิ่งของ</h2>
                            <hr class="border-gray-100 mb-6">
                        </div>
                        <div class="space-y-5">
                            <div>
                                <label class="block font-medium text-primary mb-2">ลักษณะที่พบเห็น <span class="asterisk text-red-500">*</span></label>
                                <textarea name="description" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="เช่น กระเป๋าสีดำ ติดโลโก้ Chanel มีสายสะพาย และมีรอยขีดข่วนบริเวณด้านหน้า" required></textarea>
                                <p class="text-xs text-gray-400 mt-2">ข้อมูลส่วนนี้จะแสดงบนประกาศสาธารณะ</p>
                            </div>
            
                            <div>
                                <label class="block font-medium text-primary mb-2">สีของสิ่งของ <span class="asterisk text-red-500">*</span></label>
                                <input type="text" name="color" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="เช่น ดำ, ดำแดง, ขาวครีม, น้ำเงินเข้ม" required>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-6">
                                <div class="flex-1">
                                    <label class="block font-medium text-primary mb-2">แบรนด์ / ยี่ห้อ (ถ้ามี)</label>
                                    <input type="text" name="brand" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="เช่น Apple, Samsung, Adidas, Nike">
                                </div>
                                <div class="flex-1">
                                    <label class="block font-medium text-primary mb-2">รุ่น (ถ้ามี)</label>
                                    <input type="text" name="model" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="เช่น iPhone 15 Pro, Galaxy S24 Ultra">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block font-medium text-primary mb-2">รายละเอียดลับสำหรับยืนยันเจ้าของ (ซ่อนลับ) <span class="text-red-500">*</span></label>
                                <textarea name="secret_description" rows="2" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="เช่น ของที่อยู่ภายในกระเป๋า รหัสเฉพาะ ตำหนิที่ซ่อนอยู่ หรือรายละเอียดที่มีเพียงเจ้าของตัวจริงเท่านั้นที่ควรทราบ"></textarea>
                                <p class="text-xs text-gray-400 mt-2">จำเป็นต้องระบุจุดสังเกตเฉพาะหรือสิ่งที่มีเพียงเจ้าของตัวจริงเท่านั้นที่ทราบ (ข้อมูลนี้จะไม่แสดงต่อสาธารณะ)</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-10">
                        <button type="button" onclick="prevStep()" class="px-10 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition">ย้อนกลับ</button>
                        <button type="button" onclick="nextStep()" class="px-10 py-3 bg-primary text-white font-bold rounded-xl hover:bg-secondary transition shadow-md hover:shadow-lg">ถัดไป</button>
                    </div>
                </div>

                <!-- ขั้นตอนที่ 3: สถานที่ -->
                <div class="step hidden">
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-2xl font-bold text-primary mb-1">3. สถานที่</h2>
                            <hr class="border-gray-100 mb-6">
                        </div>
                        <div class="space-y-5">
                            <div class="flex flex-col sm:flex-row gap-6">
                                <div class="flex-1 relative">
                                    <label class="block font-medium text-primary mb-2">จังหวัด <span class="asterisk text-red-500">*</span></label>
                                    <select id="province" name="province" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" required>
                                        <option value="">เลือกจังหวัด</option>
                                        <?php
                                        $provinces_file = '../config/province.json';
                                        if (file_exists($provinces_file)) {
                                            $provinces_data = json_decode(file_get_contents($provinces_file), true);
                                            usort($provinces_data, function($a, $b) {
                                                return strcmp($a['name_th'], $b['name_th']);
                                            });
                                            foreach ($provinces_data as $prov) {
                                                echo "<option value=\"" . htmlspecialchars($prov['name_th']) . "\" data-id=\"" . $prov['id'] . "\">" . htmlspecialchars($prov['name_th']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="flex-1 relative">
                                    <label class="block font-medium text-primary mb-2">อำเภอ / เขต <span class="asterisk text-red-500">*</span></label>
                                    <select id="district" name="district" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" required disabled>
                                        <option value="">เลือกอำเภอ / เขต</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block font-medium text-primary mb-2">สถานที่ที่พบ <span class="asterisk text-red-500">*</span></label>
                                <textarea name="location_detail" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="เช่น หอศิลปวัฒนธรรมแห่งกรุงเทพมหานคร, สวนสัตว์เปิดเขาเขียว" required></textarea>
                                <p class="text-xs text-gray-400 mt-2">ระบุสถานที่ที่พบสิ่งของเพื่อช่วยให้เจ้าของค้นหาและตรวจสอบได้ง่ายขึ้น</p>
                            </div>
                            <div>
                                <label class="block font-medium text-primary mb-2">สถานที่เก็บรักษาของปัจจุบัน <span class="asterisk text-red-500">*</span></label>
                                <textarea name="storage_location" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="เช่น ฝากไว้ที่ประชาสัมพันธ์ชั้น 1, เก็บไว้กับตัว, ส่งมอบให้เจ้าหน้าที่รักษาความปลอดภัย" required></textarea>
                                <p class="text-xs text-gray-400 mt-2">ข้อมูลนี้จะช่วยให้เจ้าของทราบว่าควรไปรับสิ่งของคืนจากที่ใด</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-10">
                        <button type="button" onclick="prevStep()" class="px-10 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition">ย้อนกลับ</button>
                        <button type="button" onclick="nextStep()" class="px-10 py-3 bg-primary text-white font-bold rounded-xl hover:bg-secondary transition shadow-md hover:shadow-lg">ถัดไป</button>
                    </div>
                </div>

                <!-- ขั้นตอนที่ 4: รูปภาพและช่องทางติดต่อ -->
                <div class="step hidden">
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-2xl font-bold text-primary mb-1">4. รูปภาพและติดต่อ</h2>
                            <hr class="border-gray-100 mb-6">
                        </div>
                        <div class="space-y-5">
                            <div>
                                <label class="block font-medium text-primary mb-2">อัปโหลดรูปภาพ (ถ้ามี)</label>
                                <div class="mt-1 flex flex-col items-center justify-center p-6 sm:p-8 border-2 border-gray-200 border-dashed rounded-xl bg-white transition-all relative">
                                    <input id="item_image" name="item_image" type="file" class="hidden" accept="image/*" onchange="previewImage(this)">
                                    <input id="cameraInput" type="file" class="hidden" accept="image/*" capture="environment" onchange="handleCameraCapture(this)">

                                    <div id="upload-placeholder" class="flex flex-col items-center justify-center text-center">
                                        <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 mb-3">
                                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="18" height="18" rx="4" ry="4"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21 15 16 10 5 21"></polyline>
                                            </svg>
                                        </div>
                                        <p class="text-base font-semibold text-slate-800">อัปโหลดรูปภาพสิ่งของ</p>
                                        <p class="text-xs text-slate-400 mt-1 mb-4">รองรับไฟล์ PNG, JPG, GIF ไม่เกิน 10MB</p>
                                        
                                        <div class="flex flex-wrap items-center justify-center gap-3">
                                            <button type="button" onclick="document.getElementById('item_image').click()" class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm rounded-xl transition border border-slate-200 hover:border-slate-300 flex items-center gap-2 shadow-2xs">
                                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                                เลือกไฟล์รูปภาพ
                                            </button>
                                            <button type="button" onclick="triggerTakePhoto()" class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm rounded-xl transition border border-slate-200 hover:border-slate-300 flex items-center gap-2 shadow-2xs">
                                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                ถ่ายภาพทันที
                                            </button>
                                        </div>
                                    </div>

                                    <div id="image-preview-container" class="hidden flex flex-col items-center justify-center w-full relative">
                                        <div class="relative">
                                            <img id="image-preview" src="#" alt="Preview" class="max-h-64 object-contain rounded-xl border border-gray-200 shadow-sm mb-3">
                                            <button type="button" onclick="removeImage(event)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1.5 hover:bg-red-600 transition shadow-md focus:outline-none" title="ลบรูปภาพ">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                        <p id="file-name-display" class="text-xs text-gray-500 font-medium"></p>
                                        <div class="flex items-center gap-3 mt-3">
                                            <button type="button" onclick="document.getElementById('item_image').click()" class="text-xs text-accent font-semibold hover:underline flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.5"></path></svg>
                                                เลือกรูปใหม่
                                            </button>
                                            <span class="text-gray-300">|</span>
                                            <button type="button" onclick="triggerTakePhoto()" class="text-xs text-accent font-semibold hover:underline flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                ถ่ายรูปใหม่
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block font-medium text-primary mb-2">เบอร์โทรศัพท์ติดต่อ <span class="asterisk text-red-500">*</span></label>
                                <input type="tel" name="contact_phone" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="เบอร์โทรศัพท์สำหรับติดต่อเพื่อรับของคืน" required>
                                <p class="text-xs text-gray-400 mt-3 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    ข้อมูลนี้จะถูกเก็บเป็นความลับตามนโยบายความเป็นส่วนตัว
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-10">
                        <button type="button" onclick="prevStep()" class="px-10 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition">ย้อนกลับ</button>
                        <button type="submit" class="px-10 py-3 bg-primary text-white font-bold rounded-xl hover:bg-secondary transition shadow-md hover:shadow-lg">บันทึกประกาศพบของ</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Load jQuery and Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Styling Select2 to match tailwind custom input styles */
    .select2-container {
        width: 100% !important;
        display: block !important;
    }
    .select2-container--default .select2-selection--single {
        background-color: #ffffff !important;
        border: 1px solid #E5E7EB !important;
        border-radius: 0.75rem !important; /* rounded-xl */
        height: auto !important;
        padding: 0.75rem 1rem !important; /* py-3 px-4 */
        transition: all 0.2s;
    }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default .select2-selection--single:focus {
        border-color: #3b82f6 !important; /* accent */
        box-shadow: 0 0 0 1px #3b82f6 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #1F2937 !important;
        line-height: 1.5 !important;
        padding-left: 0 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #9CA3AF !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
        top: 0 !important;
        right: 0.75rem !important;
    }
    /* Dropdown container */
    .select2-dropdown {
        border: 1px solid #E5E7EB !important;
        border-radius: 0.75rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        overflow: hidden;
        z-index: 9999 !important;
    }
    /* Force dropdown downwards and limit height */
    .select2-container--open .select2-dropdown {
        top: 100% !important;
        margin-top: 4px !important;
    }
    .select2-results__options {
        max-height: 250px !important; /* Show ~10 items with scroll */
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #3b82f6 !important; /* accent */
    }
    /* Search box inside dropdown */
    .select2-search--dropdown {
        padding: 0.5rem 0.75rem !important;
    }
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #E5E7EB !important;
        border-radius: 0.5rem !important;
        padding: 0.375rem 0.75rem !important;
        outline: none !important;
    }
    .select2-search--dropdown .select2-search__field:focus {
        border-color: #3b82f6 !important;
    }
    .select2-search--dropdown .select2-search__field::placeholder {
        color: #9CA3AF !important; /* text-gray-400 */
        opacity: 1; /* Firefox support */
    }
</style>

<script>
    const provinces = <?php echo file_get_contents('../config/province.json'); ?>;
    const amphures = <?php echo file_get_contents('../config/amphure.json'); ?>;

    const steps = document.querySelectorAll(".step");
    const stepCircles = document.querySelectorAll(".step-circle");
    const stepTexts = document.querySelectorAll(".step-item span");
    const stepLine = document.getElementById("step-line");
    let currentStep = 0;

    $(document).ready(function() {
        // Dependent Dropdown & Select2 Initialization
        $('#province').select2({
            placeholder: 'เลือกจังหวัด',
            allowClear: true,
            dropdownParent: $('#province').parent()
        }).on('select2:open', function() {
            $(this).parent().find('.select2-search__field').attr('placeholder', 'ค้นหาจังหวัด...');
        }).on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const provinceId = selectedOption ? selectedOption.getAttribute('data-id') : null;
            
            const districtSelect = $('#district');
            districtSelect.html('<option value="">เลือกอำเภอ / เขต</option>');
            
            if (!provinceId) {
                districtSelect.prop('disabled', true).trigger('change');
                return;
            }
            
            const filteredAmphures = amphures.filter(amp => amp.province_id == provinceId);
            filteredAmphures.sort((a, b) => a.name_th.localeCompare(b.name_th, 'th'));
            
            filteredAmphures.forEach(amp => {
                const opt = document.createElement('option');
                opt.value = amp.name_th;
                opt.textContent = amp.name_th;
                districtSelect.append(opt);
            });
            districtSelect.prop('disabled', false).trigger('change');
            
            // Clear validation styling if valid
            if (this.checkValidity()) {
                $(this).next('.select2-container').find('.select2-selection').removeClass('border-red-500').css('border-color', '');
            }
        });

        $('#district').select2({
            placeholder: 'เลือกอำเภอ / เขต',
            allowClear: true,
            dropdownParent: $('#district').parent()
        }).on('select2:open', function() {
            $(this).parent().find('.select2-search__field').attr('placeholder', 'ค้นหาอำเภอ / เขต...');
        }).on('change', function() {
            if (this.checkValidity()) {
                $(this).next('.select2-container').find('.select2-selection').removeClass('border-red-500').css('border-color', '');
            }
        });

        $('#category').select2({
            placeholder: 'เลือกหมวดหมู่',
            minimumResultsForSearch: -1,
            dropdownParent: $('#category').parent()
        }).on('change', function() {
            if (this.checkValidity()) {
                $(this).next('.select2-container').find('.select2-selection').removeClass('border-red-500').css('border-color', '');
            }
        });
    });

    function showStep(n) {
        steps.forEach((step, index) => {
            step.classList.toggle("hidden", index !== n);
        });
        
        // Update Stepper UI
        stepCircles.forEach((circle, index) => {
            if (index <= n) {
                // Active or Completed Step
                circle.classList.add("bg-primary", "text-white");
                circle.classList.remove("bg-white", "border-2", "border-gray-100", "text-gray-400");
                circle.innerHTML = index + 1;
            } else {
                // Upcoming Step
                circle.classList.remove("bg-primary", "text-white");
                circle.classList.add("bg-white", "border-2", "border-gray-100", "text-gray-400");
                circle.innerHTML = index + 1;
            }
        });
        
        stepTexts.forEach((text, index) => {
            text.classList.toggle("text-primary", index <= n);
            text.classList.toggle("text-gray-400", index > n);
        });
        
        // Update line width
        const progress = (n / (steps.length - 1)) * 100;
        stepLine.style.width = progress + "%";
    }

    function nextStep() {
        if (validateStep(currentStep)) {
            currentStep++;
            showStep(currentStep);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            // Trigger browser validation UI
            const firstInvalid = steps[currentStep].querySelector(":invalid");
            if (firstInvalid) {
                if (firstInvalid.tagName === 'SELECT' && $(firstInvalid).hasClass('select2-hidden-accessible')) {
                    // Focus Select2 container or show custom validation
                    const select2Container = $(firstInvalid).next('.select2-container');
                    $('html, body').animate({
                        scrollTop: select2Container.offset().top - 100
                    }, 500);
                } else {
                    firstInvalid.reportValidity();
                }
            }
        }
    }

    function prevStep() {
        currentStep--;
        showStep(currentStep);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateStep(n) {
        const currentStepFields = steps[n].querySelectorAll("input[required], select[required], textarea[required]");
        let valid = true;
        currentStepFields.forEach(field => {
            if (!field.checkValidity()) {
                valid = false;
                if (field.tagName === 'SELECT' && $(field).hasClass('select2-hidden-accessible')) {
                    const select2Selection = $(field).next('.select2-container').find('.select2-selection');
                    select2Selection.addClass('border-red-500').css('border-color', '#ef4444');
                }
            } else {
                if (field.tagName === 'SELECT' && $(field).hasClass('select2-hidden-accessible')) {
                    const select2Selection = $(field).next('.select2-container').find('.select2-selection');
                    select2Selection.removeClass('border-red-500').css('border-color', '');
                }
            }
        });
        return valid;
    }

    function previewImage(input) {
        const file = input && input.files ? input.files[0] : null;
        const previewContainer = document.getElementById('image-preview-container');
        const previewImage = document.getElementById('image-preview');
        const placeholder = document.getElementById('upload-placeholder');
        const fileNameDisplay = document.getElementById('file-name-display');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.classList.remove('hidden');
                placeholder.classList.add('hidden');
                if (fileNameDisplay) fileNameDisplay.textContent = file.name;
            }
            reader.readAsDataURL(file);
        } else {
            previewImage.src = '#';
            previewContainer.classList.add('hidden');
            placeholder.classList.remove('hidden');
            if (fileNameDisplay) fileNameDisplay.textContent = '';
        }
    }

    function removeImage(event) {
        if (event) event.stopPropagation();
        const input = document.getElementById('item_image');
        if (input) input.value = '';
        const cameraInput = document.getElementById('cameraInput');
        if (cameraInput) cameraInput.value = '';
        previewImage(input);
    }

    // Camera & Webcam Functions
    let webcamStream = null;

    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
               ('ontouchstart' in window && navigator.maxTouchPoints > 0);
    }

    function triggerTakePhoto() {
        if (isMobileDevice()) {
            const cameraInput = document.getElementById('cameraInput');
            if (cameraInput) cameraInput.click();
        } else {
            openWebcamModal();
        }
    }

    function handleCameraCapture(input) {
        const file = input.files ? input.files[0] : null;
        if (file) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            const itemImageInput = document.getElementById('item_image');
            itemImageInput.files = dataTransfer.files;
            previewImage(itemImageInput);
        }
    }

    async function openWebcamModal() {
        const modal = document.getElementById('webcamModal');
        const video = document.getElementById('webcamVideo');
        const errorContainer = document.getElementById('webcamError');
        
        if (!modal) return;
        modal.classList.remove('hidden');
        if (errorContainer) errorContainer.classList.add('hidden');
        if (video) video.classList.remove('hidden');

        try {
            webcamStream = await navigator.mediaDevices.getUserMedia({ 
                video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' }, 
                audio: false 
            });
            video.srcObject = webcamStream;
        } catch (err) {
            console.error("Camera access error:", err);
            if (video) video.classList.add('hidden');
            if (errorContainer) errorContainer.classList.remove('hidden');
        }
    }

    function closeWebcamModal() {
        const modal = document.getElementById('webcamModal');
        if (webcamStream) {
            webcamStream.getTracks().forEach(track => track.stop());
            webcamStream = null;
        }
        const video = document.getElementById('webcamVideo');
        if (video) video.srcObject = null;
        if (modal) modal.classList.add('hidden');
    }

    function snapPhoto() {
        const video = document.getElementById('webcamVideo');
        const canvas = document.getElementById('webcamCanvas');
        
        if (!video || !webcamStream) return;

        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob((blob) => {
            if (blob) {
                const fileName = 'captured_photo_' + Date.now() + '.jpg';
                const file = new File([blob], fileName, { type: 'image/jpeg' });
                
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                const itemImageInput = document.getElementById('item_image');
                itemImageInput.files = dataTransfer.files;
                
                previewImage(itemImageInput);
                closeWebcamModal();
            }
        }, 'image/jpeg', 0.9);
    }
</script>

<!-- Webcam Modal (Desktop) -->
<div id="webcamModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-5 shadow-2xl relative flex flex-col items-center border border-gray-100">
        <div class="w-full flex items-center justify-between pb-3 mb-3 border-b border-gray-100">
            <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                ถ่ายภาพจากกล้องเว็บแคม
            </h3>
            <button type="button" onclick="closeWebcamModal()" class="text-gray-400 hover:text-gray-600 p-1 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="relative w-full aspect-video bg-slate-900 rounded-xl overflow-hidden mb-4 flex items-center justify-center shadow-inner">
            <video id="webcamVideo" autoplay playsinline class="w-full h-full object-cover"></video>
            <canvas id="webcamCanvas" class="hidden"></canvas>
            <div id="webcamError" class="hidden text-center text-white text-xs p-4">
                <p class="font-bold text-red-400 mb-1">ไม่สามารถเข้าถึงกล้องได้</p>
                <p class="text-gray-300">กรุณาอนุญาตการใช้งานกล้องเว็บแคมในเบราว์เซอร์ของคุณ</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 w-full pt-3 border-t border-gray-100">
            <button type="button" onclick="closeWebcamModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-xs rounded-xl transition">
                ปิดกล้อง / ยกเลิก
            </button>
            <button type="button" onclick="snapPhoto()" class="px-4 py-2 bg-primary hover:bg-secondary text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                กดชัตเตอร์ / บันทึกภาพ
            </button>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
