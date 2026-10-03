<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}
?>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'items_lost';
    require_once '../../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                    <span>ศูนย์ควบคุมผู้ดูแลระบบ</span>
                    <span>•</span>
                    <a href="<?php echo $base_url; ?>/pages/admin/admin_itemslost.php" class="hover:underline">รายการของหาย</a>
                    <span>•</span>
                    <span class="text-slate-700">บันทึกรับแจ้งของหาย</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    บันทึกรับแจ้งของหาย (สำหรับแอดมิน)
                </h1>
            </div>

            <a href="<?php echo $base_url; ?>/pages/admin/admin_itemslost.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-xl font-semibold transition text-xs flex items-center gap-1.5 self-start md:self-auto shadow-2xs">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                ย้อนกลับหน้ารายการ
            </a>
        </div>

        <!-- Alert Notification -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-medium flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0"></path></svg>
                    <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Card Container -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 md:p-8 max-w-5xl mx-auto">
            <form id="adminReportForm" action="<?php echo $base_url; ?>/actions/post_action.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                <input type="hidden" name="type" value="lost">
                <input type="hidden" name="redirect_url" value="<?php echo $base_url; ?>/pages/admin/admin_itemslost.php">

                <!-- Section 1: ข้อมูลสิ่งของ -->
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs">1</span>
                        ข้อมูลสิ่งของที่หาย
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">ชื่อสิ่งของที่หาย <span class="text-rose-500">*</span></label>
                            <input type="text" name="item_name" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 outline-none focus:border-slate-800 focus:bg-white transition" placeholder="เช่น กระเป๋าสตางค์, กุญแจรถ, โทรศัพท์มือถือ" required>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">หมวดหมู่ <span class="text-rose-500">*</span></label>
                            <select id="category" name="category" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 outline-none focus:border-slate-800 focus:bg-white transition" required>
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

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">วันที่หาย (โดยประมาณ) <span class="text-rose-500">*</span></label>
                            <input type="date" name="lost_date" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 outline-none focus:border-slate-800 focus:bg-white transition" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">สีของสิ่งของ <span class="text-rose-500">*</span></label>
                            <input type="text" name="color" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 outline-none focus:border-slate-800 focus:bg-white transition" placeholder="เช่น ดำ, ดำแดง, ขาวครีม" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">แบรนด์ / ยี่ห้อ (ถ้ามี)</label>
                            <input type="text" name="brand" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 outline-none focus:border-slate-800 focus:bg-white transition" placeholder="เช่น Apple, Samsung, Adidas">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">รุ่น (ถ้ามี)</label>
                            <input type="text" name="model" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 outline-none focus:border-slate-800 focus:bg-white transition" placeholder="เช่น iPhone 15 Pro">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">เลขซีเรียล / S/N (ถ้ามี)</label>
                            <input type="text" name="serial_number" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 outline-none focus:border-slate-800 focus:bg-white transition" placeholder="เช่น IMEI โทรศัพท์, หมายเลขเครื่อง">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">รายละเอียดเพิ่มเติม <span class="text-rose-500">*</span></label>
                            <textarea name="description" rows="3" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 outline-none focus:border-slate-800 focus:bg-white transition resize-none" placeholder="ระบุลักษณะเฉพาะ รอยขีดข่วน ตำหนิ หรือสติกเกอร์ที่สังเกตได้" required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 2: สถานที่ที่หาย -->
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs">2</span>
                        สถานที่ที่คาดว่าหาย
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">จังหวัด <span class="text-rose-500">*</span></label>
                            <select id="province" name="province" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 outline-none focus:border-slate-800 focus:bg-white transition" required>
                                <option value="">เลือกจังหวัด</option>
                                <?php
                                $provinces_file = '../../config/province.json';
                                if (file_exists($provinces_file)) {
                                    $provinces_data = json_decode(file_get_contents($provinces_file), true);
                                    usort($provinces_data, fn($a, $b) => strcmp($a['name_th'], $b['name_th']));
                                    foreach ($provinces_data as $prov) {
                                        echo "<option value=\"" . htmlspecialchars($prov['name_th']) . "\" data-id=\"" . $prov['id'] . "\">" . htmlspecialchars($prov['name_th']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">อำเภอ / เขต <span class="text-rose-500">*</span></label>
                            <select id="district" name="district" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 outline-none focus:border-slate-800 focus:bg-white transition" required disabled>
                                <option value="">เลือกอำเภอ / เขต</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">รายละเอียดสถานที่ที่คาดว่าหาย <span class="text-rose-500">*</span></label>
                            <textarea name="location_detail" rows="2" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 outline-none focus:border-slate-800 focus:bg-white transition resize-none" placeholder="เช่น บริเวณศูนย์อาหารชั้น 1, ห้องเรียน 302 อาคาร 5" required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 3: รูปภาพ & ข้อมูลติดต่อ -->
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs">3</span>
                        รูปภาพประกอบ & ช่องทางติดต่อ
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">รูปภาพสิ่งของ (ถ้ามี)</label>
                            <div class="p-4 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50 hover:bg-slate-100/50 transition cursor-pointer relative" onclick="document.getElementById('item_image').click()">
                                <input id="item_image" name="item_image" type="file" class="sr-only" accept="image/*" onchange="previewImage(this)">
                                
                                <div id="upload-placeholder" class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 flex-shrink-0">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-800">คลิกเพื่ออัปโหลดรูปภาพ</span>
                                        <p class="text-[11px] text-slate-400">รองรับไฟล์ PNG, JPG ไม่เกิน 10MB</p>
                                    </div>
                                </div>

                                <div id="image-preview-container" class="hidden flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <div class="relative flex-shrink-0">
                                            <img id="image-preview" src="#" alt="Preview" class="w-20 h-20 max-h-48 object-cover rounded-lg shadow-sm border border-slate-200">
                                            <button type="button" onclick="removeImage(event)" class="absolute -top-2 -right-2 bg-rose-500 text-white rounded-full p-1 hover:bg-rose-600 transition shadow-md focus:outline-none" title="ลบรูปภาพ">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                        <div class="truncate">
                                            <p id="file-name-display" class="text-xs font-semibold text-slate-700 truncate"></p>
                                            <span class="text-[11px] text-blue-600 font-medium">คลิกหากต้องการเปลี่ยนรูปใหม่</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">เบอร์โทรศัพท์ติดต่อ <span class="text-rose-500">*</span></label>
                            <input type="tel" name="contact_phone" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 outline-none focus:border-slate-800 focus:bg-white transition" placeholder="เช่น 0812345678" required>
                        </div>
                    </div>
                </div>

                <!-- Action Footer -->
                <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                    <a href="<?php echo $base_url; ?>/pages/admin/admin_itemslost.php" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition">
                        ยกเลิก
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-md">
                        บันทึกรับแจ้งของหาย
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<!-- Load jQuery and Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Custom Select2 Minimal Styling */
    .select2-container {
        width: 100% !important;
        display: block !important;
    }
    .select2-container--default .select2-selection--single {
        background-color: #f8fafc !important; /* bg-slate-50 */
        border: 1px solid #e2e8f0 !important; /* border-slate-200 */
        border-radius: 0.75rem !important; /* rounded-xl */
        height: 2.625rem !important; /* py-2.5 matching input */
        padding: 0.25rem 0.5rem 0.25rem 0.75rem !important;
        display: flex !important;
        align-items: center !important;
        transition: all 0.2s ease-in-out;
    }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        background-color: #ffffff !important;
        border-color: #3b82f6 !important; /* focus:border-blue-500 */
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important; /* focus:ring-2 focus:ring-blue-500 */
        outline: none !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #334155 !important; /* text-slate-700 */
        font-size: 0.75rem !important; /* text-xs */
        font-weight: 500 !important;
        line-height: 1.25rem !important;
        padding-left: 0 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #94a3b8 !important; /* text-slate-400 */
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
        top: 0 !important;
        right: 0.75rem !important;
        display: flex !important;
        align-items: center !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #64748b transparent transparent transparent !important;
        border-width: 5px 4px 0 4px !important;
    }
    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: transparent transparent #64748b transparent !important;
        border-width: 0 4px 5px 4px !important;
    }
    .select2-dropdown {
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.75rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        overflow: hidden;
        z-index: 9999 !important;
        background-color: #ffffff !important;
    }
    .select2-container--open .select2-dropdown {
        top: 100% !important;
        margin-top: 4px !important;
    }
    .select2-results__options {
        max-height: 220px !important;
        padding: 4px !important;
    }
    .select2-container--default .select2-results__option {
        font-size: 0.75rem !important;
        padding: 0.5rem 0.75rem !important;
        border-radius: 0.5rem !important;
        color: #334155 !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #3b82f6 !important;
        color: #ffffff !important;
    }
    .select2-search--dropdown {
        padding: 0.5rem !important;
    }
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.5rem !important;
        padding: 0.375rem 0.75rem !important;
        font-size: 0.75rem !important;
        outline: none !important;
    }
    .select2-search--dropdown .select2-search__field:focus {
        border-color: #3b82f6 !important;
    }
</style>

<script>
    const amphures = <?php echo file_get_contents('../../config/amphure.json'); ?>;

    $(document).ready(function() {
        $('#province').select2({
            placeholder: 'เลือกจังหวัด',
            allowClear: true,
            dropdownParent: $('#province').parent()
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
        });

        $('#district').select2({
            placeholder: 'เลือกอำเภอ / เขต',
            allowClear: true,
            dropdownParent: $('#district').parent()
        });

        $('#category').select2({
            placeholder: 'เลือกหมวดหมู่',
            minimumResultsForSearch: -1,
            dropdownParent: $('#category').parent()
        });
    });

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
        }
    }

    function removeImage(event) {
        event.stopPropagation();
        const input = document.getElementById('item_image');
        input.value = '';
        const previewContainer = document.getElementById('image-preview-container');
        const previewImage = document.getElementById('image-preview');
        const placeholder = document.getElementById('upload-placeholder');
        const fileNameDisplay = document.getElementById('file-name-display');

        previewImage.src = '#';
        previewContainer.classList.add('hidden');
        placeholder.classList.remove('hidden');
        fileNameDisplay.textContent = '';
    }
</script>

<?php require_once '../../includes/footer.php'; ?>
