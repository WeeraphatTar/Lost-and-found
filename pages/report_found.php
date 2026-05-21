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
                                <label class="block font-medium text-primary mb-2">ชื่อเรียกทรัพย์สินที่พบ <span class="asterisk text-red-500">*</span></label>
                                <input type="text" name="item_name" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="เช่น กระเป๋าสตางค์, กุญแจรถ" required>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-6">
                                <div class="flex-1">
                                    <label class="block font-medium text-primary mb-2">หมวดหมู่ <span class="asterisk text-red-500">*</span></label>
                                    <select name="category" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" required>
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
                            <h2 class="text-2xl font-bold text-primary mb-1">2. รายละเอียด</h2>
                            <hr class="border-gray-100 mb-6">
                        </div>
                        <div class="space-y-5">
                            <div>
                                <label class="block font-medium text-primary mb-2">ลักษณะที่พบเห็น (เปิดเผยต่อสาธารณะ) <span class="asterisk text-red-500">*</span></label>
                                <textarea name="description" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="อธิบายลักษณะทั่วไปที่ใครๆ ก็มองเห็นได้ เช่น สี รูปทรง" required></textarea>
                            </div>
                            <div>
                                <label class="block font-medium text-primary mb-2">รายละเอียดลับ (Secret Details)</label>
                                <textarea name="secret_description" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="สิ่งที่จะมีแต่เจ้าของเท่านั้นที่รู้ เช่น ของที่อยู่ข้างใน, รหัสผ่าน"></textarea>
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
                            <div>
                                <label class="block font-medium text-primary mb-2">สถานที่ที่พบเจอ <span class="asterisk text-red-500">*</span></label>
                                <input type="text" name="location" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="ระบุตำแหน่งที่พบให้ชัดเจน" required>
                            </div>
                            <div>
                                <label class="block font-medium text-primary mb-2">สถานที่เก็บรักษาของปัจจุบัน <span class="asterisk text-red-500">*</span></label>
                                <input type="text" name="storage_location" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all" placeholder="เช่น นำไปฝากไว้ที่ฝ่ายธุรการ, เก็บไว้กับตัว" required>
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
                                <div class="mt-1 flex flex-col items-center justify-center px-6 py-10 border-2 border-gray-200 border-dashed rounded-xl hover:border-accent hover:bg-gray-50 transition-all cursor-pointer group" onclick="document.getElementById('item_image').click()">
                                    <svg class="h-12 w-12 text-gray-400 group-hover:text-accent transition-colors mb-4" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="text-center">
                                        <label for="item_image" class="relative cursor-pointer rounded-md font-medium text-primary hover:text-accent focus-within:outline-none">
                                            <span class="text-lg">คลิกเพื่ออัปโหลดรูปภาพ</span>
                                            <input id="item_image" name="item_image" type="file" class="sr-only" accept="image/*" onchange="const fileName = this.files[0] ? this.files[0].name : ''; document.getElementById('file-name-display').textContent = fileName; document.getElementById('file-name-display').classList.toggle('hidden', !fileName);">
                                        </label>
                                        <p class="text-sm text-gray-500 mt-1">PNG, JPG, GIF ไม่เกิน 10MB</p>
                                        <p id="file-name-display" class="text-sm text-accent mt-2 font-medium hidden"></p>
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

<script>
    const steps = document.querySelectorAll(".step");
    const stepCircles = document.querySelectorAll(".step-circle");
    const stepTexts = document.querySelectorAll(".step-item span");
    const stepLine = document.getElementById("step-line");
    let currentStep = 0;

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
            if (firstInvalid) firstInvalid.reportValidity();
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
            }
        });
        return valid;
    }
</script>

<?php
require_once '../includes/footer.php';
?>
