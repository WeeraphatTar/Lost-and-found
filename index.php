<?php
// เรียกใช้งาน Header
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="py-16 text-center bg-[#f0f4f9] border-b border-slate-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl md:text-4xl font-bold text-slate-900 mb-3">ระบบแจ้งของหายและติดตามทรัพย์สินคืน</h1>
        <p class="text-slate-600 text-lg md:text-xl max-w-2xl mx-auto mb-8">พื้นที่ส่วนกลางที่ปลอดภัยและเชื่อถือได้ สำหรับการค้นหาของที่หายไป และส่งคืนของที่พบเจอให้กับเจ้าของตัวจริง</p>
        
        <!-- Search Container Card -->
        <div class="max-w-2xl mx-auto bg-white p-2.5 sm:p-3 rounded-2xl shadow-md mb-6 border border-slate-200/80">
            <form action="<?php echo $base_url; ?>/pages/browse.php" method="GET" class="flex flex-col md:flex-row gap-2.5">
                <input type="text" name="q" class="flex-1 w-full px-3.5 py-2.5 border border-slate-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all text-sm text-slate-800 placeholder-slate-400" placeholder="ค้นหาทรัพย์สิน เช่น กระเป๋าสตางค์, โทรศัพท์...">
                <div class="w-full md:w-1/3 relative text-left">
                    <select id="category" name="category" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white transition-all text-sm text-slate-800">
                        <option value="">ทุกหมวดหมู่</option>
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
                <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-[#0f172a] hover:bg-slate-900 text-white font-medium text-sm rounded-xl transition shadow-md hover:shadow-lg cursor-pointer whitespace-nowrap">ค้นหา</button>
            </form>
        </div>

        <!-- Call to Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center mt-5">
            <a href="<?php echo $base_url; ?>/pages/report_lost.php" class="px-6 py-3 bg-[#0f172a] hover:bg-slate-900 text-white font-semibold rounded-xl transition shadow-md">แจ้งของหาย</a>
            <a href="<?php echo $base_url; ?>/pages/report_found.php" class="px-6 py-3 bg-white border-2 border-slate-800 text-slate-800 hover:bg-slate-800 hover:text-white font-semibold rounded-xl transition shadow-sm">แจ้งพบของ</a>
        </div>
    </div>
</section>

<!-- Statistics / Recent Items (Placeholder) -->
<section class="py-16 bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl md:text-2xl font-bold text-center text-slate-800 mb-10">ประกาศล่าสุด</h2>
        
<?php
        require_once 'config/database.php';
        try {
            $stmt = $pdo->query("SELECT * FROM items WHERE status = 'open' ORDER BY created_at DESC LIMIT 6");
            $items = $stmt->fetchAll();

            if (count($items) > 0) {
                echo '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">';
                foreach ($items as $item) {
                    $type_badge = ($item['type'] === 'lost') 
                        ? '<span class="self-start inline-block px-2.5 py-1 rounded bg-red-100 text-red-700 text-xs font-semibold mb-2">ตามหาของหาย</span>'
                        : '<span class="self-start inline-block px-2.5 py-1 rounded bg-green-100 text-green-700 text-xs font-semibold mb-2">ประกาศพบของ</span>';
                    
                    $image_html = '';
                    if (!empty($item['image_path'])) {
                        $image_html = '<img src="' . $base_url . '/' . htmlspecialchars($item['image_path']) . '" alt="item image" class="w-full h-full object-contain p-2">';
                    } else {
                        // SVG Placeholder
                        $image_html = '<svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
                    }

                    $date_formatted = date('d M Y', strtotime($item['event_date']));

                    echo '
                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-md transition duration-200 flex flex-col">
                        <div class="w-full aspect-video bg-white flex items-center justify-center border-b border-slate-300 text-gray-400 overflow-hidden relative">
                            ' . $image_html . '
                        </div>
                        <div class="p-6 flex-1 flex flex-col">
                            ' . $type_badge . '
                            <h3 class="text-lg font-bold text-primary mt-1 truncate">' . htmlspecialchars($item['title']) . '</h3>
                            <p class="text-gray-500 text-sm mt-2 truncate"><strong>สถานที่:</strong> ' . htmlspecialchars($item['location']) . '</p>
                            <p class="text-gray-500 text-sm mt-1 mb-4 flex-1"><strong>วันที่:</strong> ' . $date_formatted . '</p>
                            <a href="' . $base_url . '/pages/item_detail.php?id=' . $item['id'] . '" class="block w-full text-center px-4 py-2 border border-primary text-primary text-sm font-medium rounded hover:bg-primary hover:text-white transition">ดูรายละเอียด</a>
                        </div>
                    </div>';
                }
                echo '</div>';

                // ปุ่มดูประกาศทั้งหมด
                echo '
                <div class="mt-12 text-center">
                    <a href="' . $base_url . '/pages/browse.php" class="inline-flex items-center justify-center px-8 py-3 bg-white border border-primary text-primary font-bold rounded-md hover:bg-primary hover:text-white transition shadow-sm group">
                        ดูประกาศทั้งหมด
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                </div>';
            } else {
                echo '<div class="text-center py-12 text-gray-500 bg-white rounded-lg border border-gray-200">ยังไม่มีประกาศในขณะนี้</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="text-center text-red-500">เกิดข้อผิดพลาดในการโหลดข้อมูล: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</section>

<!-- How It Works Section (Stepper) -->
<section class="py-16 bg-[#f0f4f9] border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl md:text-2xl font-bold text-center text-slate-800 mb-10">ขั้นตอนการใช้งาน</h2>
        
        <div class="relative">
            <!-- Horizontal Connecting Line for Desktop/Tablet (passing behind center of icons) -->
            <div class="hidden md:block absolute top-10 left-[16%] right-[16%] h-0.5 bg-slate-300 z-0"></div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-10 md:gap-6 relative z-10">
                <!-- Step 1 -->
                <div class="text-center flex flex-col items-center">
                    <div class="w-20 h-20 bg-[#0f172a] rounded-2xl shadow-md flex items-center justify-center mb-5 hover:scale-105 transition-transform duration-200">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-[#0f172a] mb-2">1. ลงประกาศ</h3>
                    <p class="text-slate-600 text-sm leading-relaxed max-w-xs">
                        กรอกรายละเอียดสิ่งของ สถานที่ และวันเวลาที่พบหรือหาย พร้อมแนบรูปถ่ายประกอบ
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="text-center flex flex-col items-center">
                    <div class="w-20 h-20 bg-[#0f172a] rounded-2xl shadow-md flex items-center justify-center mb-5 hover:scale-105 transition-transform duration-200">
                        <img src="<?php echo $base_url; ?>/assets/images/smart-match.png" alt="Smart Match" class="w-8 h-8 object-contain">
                    </div>
                    <h3 class="text-lg font-bold text-[#0f172a] mb-2">2. ระบบจับคู่อัจฉริยะ (Smart Match)</h3>
                    <p class="text-slate-600 text-sm leading-relaxed max-w-xs">
                        ระบบจะวิเคราะห์และเปรียบเทียบข้อมูล เพื่อจับคู่สิ่งของที่มีลักษณะคล้ายคลึงกันให้อัตโนมัติ
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="text-center flex flex-col items-center">
                    <div class="w-20 h-20 bg-[#0f172a] rounded-2xl shadow-md flex items-center justify-center mb-5 hover:scale-105 transition-transform duration-200">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-[#0f172a] mb-2">3. พิสูจน์สิทธิ์และส่งคืน</h3>
                    <p class="text-slate-600 text-sm leading-relaxed max-w-xs">
                        ยืนยันความเป็นเจ้าของผ่านระบบ และนัดหมายรับคืนสิ่งของอย่างปลอดภัย
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

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
        border: 1px solid #D1D5DB !important;
        border-radius: 0.75rem !important; /* rounded-xl */
        height: auto !important;
        padding: 0.625rem 0.875rem !important; /* py-2.5 px-3.5 */
        font-size: 0.875rem !important; /* text-sm */
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
        color: #4B5563 !important;
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
</style>

<script>
    $(document).ready(function() {
        $('#category').select2({
            placeholder: 'ทุกหมวดหมู่',
            allowClear: true,
            minimumResultsForSearch: -1,
            dropdownParent: $('#category').parent()
        });
    });
</script>

<?php
// เรียกใช้งาน Footer
require_once 'includes/footer.php';
?>
