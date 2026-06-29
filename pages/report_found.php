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
        <div class="bg-white p-8 sm:p-12 rounded-2xl shadow-sm border border-gray-100">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-primary mb-2">ฟอร์มแจ้งพบของ</h2>
                <p class="text-important font-medium">กรุณากรอกข้อมูลเพื่อช่วยส่งคืนของให้กับเจ้าของที่แท้จริง</p>
            </div>

            <!-- Stepper -->
            <div class="mb-12 max-w-2xl mx-auto">
                <div class="flex items-center justify-between relative">
                    <!-- Line Container -->
                    <div class="absolute left-0 top-5 transform -translate-y-1/2 w-full px-5 -z-0">
                        <div class="h-0.5 bg-gray-100 w-full relative">
                            <div id="step-line" class="absolute left-0 top-0 h-full bg-primary transition-all duration-300" style="width: 0%;"></div>
                        </div>
                    </div>
                    
                    <!-- Step 1 -->
                    <div class="step-item flex flex-col items-center relative z-10 w-10">
                        <div class="step-circle w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold mb-2 shadow-sm transition-all duration-300">1</div>
                        <span class="text-xs font-semibold text-primary text-center whitespace-nowrap">ข้อมูลพื้นฐาน</span>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="step-item flex flex-col items-center relative z-10 w-10">
                        <div class="step-circle w-10 h-10 rounded-full bg-white border-2 border-gray-100 text-gray-400 flex items-center justify-center font-bold mb-2 transition-all duration-300">2</div>
                        <span class="text-xs font-semibold text-gray-400 text-center whitespace-nowrap">รายละเอียด</span>
                    </div>
                    
                    <!-- Step 3 -->
                    <div class="step-item flex flex-col items-center relative z-10 w-10">
                        <div class="step-circle w-10 h-10 rounded-full bg-white border-2 border-gray-100 text-gray-400 flex items-center justify-center font-bold mb-2 transition-all duration-300">3</div>
                        <span class="text-xs font-semibold text-gray-400 text-center whitespace-nowrap">สถานที่</span>
                    </div>
                    
                    <!-- Step 4 -->
                    <div class="step-item flex flex-col items-center relative z-10 w-10">
                        <div class="step-circle w-10 h-10 rounded-full bg-white border-2 border-gray-100 text-gray-400 flex items-center justify-center font-bold mb-2 transition-all duration-300">4</div>
                        <span class="text-xs font-semibold text-gray-400 text-center whitespace-nowrap">รูปภาพ & ติดต่อ</span>
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
                                <label class="block font-medium text-primary mb-2">รายละเอียดลับสำหรับยืนยันเจ้าของ</label>
                                <textarea name="secret_description" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="เช่น ของที่อยู่ภายในกระเป๋า รหัสเฉพาะ ตำหนิที่ซ่อนอยู่ หรือรายละเอียดที่มีเพียงเจ้าของตัวจริงเท่านั้นที่ควรทราบ"></textarea>
                                <p class="text-xs text-gray-400 mt-2">ข้อมูลนี้จะไม่แสดงต่อสาธารณะ และใช้สำหรับการยืนยันความเป็นเจ้าของเท่านั้น</p>
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
                                <div class="mt-1 flex flex-col items-center justify-center px-6 py-10 border-2 border-gray-200 border-dashed rounded-xl hover:border-accent hover:bg-gray-50 transition-all cursor-pointer group relative" onclick="document.getElementById('item_image').click()">
                                    <div id="upload-placeholder" class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-400 group-hover:text-accent transition-colors mb-4" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="text-center">
                                            <span class="relative rounded-md font-medium text-primary hover:text-accent">
                                                <span class="text-lg">คลิกเพื่ออัปโหลดรูปภาพ</span>
                                                <input id="item_image" name="item_image" type="file" class="sr-only" accept="image/*" onchange="previewImage(this)">
                                            </span>
                                            <p class="text-sm text-gray-500 mt-1">PNG, JPG, GIF ไม่เกิน 10MB</p>
                                        </div>
                                    </div>
                                    <div id="image-preview-container" class="hidden flex flex-col items-center justify-center w-full relative">
                                        <div class="relative">
                                            <img id="image-preview" src="#" alt="Preview" class="max-h-64 object-contain rounded-xl border border-gray-200 shadow-sm mb-4">
                                            <button type="button" onclick="removeImage(event)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1.5 hover:bg-red-600 transition shadow-md focus:outline-none" title="ลบรูปภาพ">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                        <span class="text-sm text-accent font-semibold flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.5"></path></svg>
                                            คลิกเพื่อเปลี่ยนรูปภาพ
                                        </span>
                                        <p id="file-name-display" class="text-xs text-gray-400 mt-2 font-medium"></p>
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
        const file = input.files[0];
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
                fileNameDisplay.textContent = file.name;
            }
            reader.readAsDataURL(file);
        } else {
            previewImage.src = '#';
            previewContainer.classList.add('hidden');
            placeholder.classList.remove('hidden');
            fileNameDisplay.textContent = '';
        }
    }

    function removeImage(event) {
        event.stopPropagation();
        const input = document.getElementById('item_image');
        input.value = '';
        previewImage(input);
    }
</script>

<?php
require_once '../includes/footer.php';
?>
