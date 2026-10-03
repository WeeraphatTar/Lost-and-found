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
    $active_tab = 'export';
    require_once '../../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-500 uppercase tracking-wider mb-1 font-semibold">
                    <span>ศูนย์ควบคุมผู้ดูแลระบบ</span>
                    <span>•</span>
                    <span>ส่งออกรายงานและสถิติ</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                    ส่งออกรายงานและสถิติระบบ
                </h1>
            </div>
        </div>

        <!-- Main Card Form -->
        <div class="w-full bg-white rounded-2xl border border-slate-200 shadow-xs p-6 md:p-8">

            <div class="flex items-center gap-3.5 pb-6 border-b border-slate-100 mb-6">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shadow-xs flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-slate-900">ศูนย์ส่งออกข้อมูล</h2>
                    <p class="text-sm text-slate-500">กำหนดรูปแบบรายงาน ช่วงเวลา และฟอร์แมตไฟล์ที่ต้องการดาวน์โหลด</p>
                </div>
            </div>

            <form id="standaloneExportForm" action="<?php echo $base_url; ?>/actions/admin_export_action.php" method="POST" class="space-y-8">
                <input type="hidden" name="export_format" id="page_export_format_input" value="csv">

                <!-- 1. Select Report Type -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">1. เลือกประเภทรายงานที่ต้องการส่งออก</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        
                        <label class="relative flex flex-col p-5 bg-slate-50/70 hover:bg-slate-100/80 rounded-xl border border-slate-200 cursor-pointer transition text-left has-[:checked]:border-blue-600 has-[:checked]:bg-white has-[:checked]:ring-2 has-[:checked]:ring-blue-500/10 has-[:checked]:shadow-sm">
                            <input type="radio" name="report_type" value="items" checked class="sr-only">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 border border-blue-100 flex items-center justify-center mb-3 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-900">ประกาศสิ่งของ</span>
                            <span class="text-[11px] text-slate-500 mt-1">รายการแจ้งของหาย และแจ้งพบของทั้งหมด</span>
                        </label>

                        <label class="relative flex flex-col p-5 bg-slate-50/70 hover:bg-slate-100/80 rounded-xl border border-slate-200 cursor-pointer transition text-left has-[:checked]:border-blue-600 has-[:checked]:bg-white has-[:checked]:ring-2 has-[:checked]:ring-blue-500/10 has-[:checked]:shadow-sm">
                            <input type="radio" name="report_type" value="claims" class="sr-only">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-100 flex items-center justify-center mb-3 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-900">คำร้องส่งคืนสิ่งของ</span>
                            <span class="text-[11px] text-slate-500 mt-1">สถานะ Claim และประวัติการจับคู่สิ่งของ</span>
                        </label>

                        <label class="relative flex flex-col p-5 bg-slate-50/70 hover:bg-slate-100/80 rounded-xl border border-slate-200 cursor-pointer transition text-left has-[:checked]:border-blue-600 has-[:checked]:bg-white has-[:checked]:ring-2 has-[:checked]:ring-blue-500/10 has-[:checked]:shadow-sm">
                            <input type="radio" name="report_type" value="logs" class="sr-only">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 border border-purple-100 flex items-center justify-center mb-3 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <span class="text-xs font-bold text-slate-900">ประวัติการใช้งานระบบ</span>
                            <span class="text-[11px] text-slate-500 mt-1">System Logs และกิจกรรมของผู้ใช้งาน</span>
                        </label>

                    </div>
                </div>

                <!-- 2. Date Range Picker -->
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">2. เลือกช่วงเวลาของข้อมูล</label>
                        <div class="flex flex-wrap items-center gap-1.5 text-xs" id="presetButtonsGroup">
                            <button type="button" data-preset="all" onclick="setPageExportPreset('all', this)" class="preset-btn px-3 py-1 bg-slate-900 text-white shadow-xs rounded-lg transition font-medium cursor-pointer">ทั้งหมด</button>
                            <button type="button" data-preset="month" onclick="setPageExportPreset('month', this)" class="preset-btn px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition font-medium cursor-pointer">เดือนนี้</button>
                            <button type="button" data-preset="30" onclick="setPageExportPreset(30, this)" class="preset-btn px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition font-medium cursor-pointer">30 วันล่าสุด</button>
                            <button type="button" data-preset="7" onclick="setPageExportPreset(7, this)" class="preset-btn px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition font-medium cursor-pointer">7 วันล่าสุด</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs font-semibold text-slate-600 mb-1.5">วันที่เริ่มต้น</span>
                            <input type="date" name="start_date" id="page_export_start_date" value="2026-01-01" required class="w-full text-xs p-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-slate-800 focus:border-transparent outline-none transition font-medium">
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-600 mb-1.5">วันที่สิ้นสุด</span>
                            <input type="date" name="end_date" id="page_export_end_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full text-xs p-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-slate-800 focus:border-transparent outline-none transition font-medium">
                        </div>
                    </div>
                </div>

                <!-- 3. Export Buttons (Multiple Format Options) -->
                <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-xs text-slate-500">เลือกรูปแบบไฟล์ที่ต้องการดาวน์โหลดลงเครื่อง</p>
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        
                        <!-- CSV Download Button -->
                        <button type="button" onclick="submitPageExport('csv')" class="flex-1 sm:flex-none px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold transition shadow-xs flex items-center justify-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>ส่งออกไฟล์ Excel</span>
                        </button>

                        <!-- Printable PDF / HTML View Button -->
                        <button type="button" onclick="submitPageExport('pdf')" class="flex-1 sm:flex-none px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition shadow-xs flex items-center justify-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            <span>บันทึก PDF</span>
                        </button>

                    </div>
                </div>

            </form>
        </div>

    </main>
</div>

<script>
// Format Date to YYYY-MM-DD using Local Timezone
function formatLocalDate(dateObj) {
    const year = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day = String(dateObj.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function clearPresetActiveState() {
    const buttons = document.querySelectorAll('.preset-btn');
    buttons.forEach(btn => {
        btn.classList.remove('bg-slate-900', 'text-white', 'shadow-xs');
        btn.classList.add('bg-slate-100', 'text-slate-700', 'hover:bg-slate-200');
    });
}

function setActivePresetButton(btnElement) {
    clearPresetActiveState();
    if (btnElement) {
        btnElement.classList.remove('bg-slate-100', 'text-slate-700', 'hover:bg-slate-200');
        btnElement.classList.add('bg-slate-900', 'text-white', 'shadow-xs');
    }
}

function setPageExportPreset(preset, btnElement) {
    const today = new Date();
    const endDateStr = formatLocalDate(today);
    let startDateStr = '';

    if (preset === 'all') {
        startDateStr = '2026-01-01';
    } else if (preset === 'month') {
        const startDate = new Date(today.getFullYear(), today.getMonth(), 1);
        startDateStr = formatLocalDate(startDate);
    } else {
        const startDate = new Date();
        startDate.setDate(today.getDate() - parseInt(preset, 10));
        startDateStr = formatLocalDate(startDate);
    }

    document.getElementById('page_export_start_date').value = startDateStr;
    document.getElementById('page_export_end_date').value = endDateStr;

    setActivePresetButton(btnElement);
}

function submitPageExport(format) {
    const form = document.getElementById('standaloneExportForm');
    document.getElementById('page_export_format_input').value = format;
    
    if (format === 'csv') {
        form.target = '_self'; // Download directly
    } else {
        form.target = '_blank'; // Open printable document tab
    }
    
    form.submit();
}

// Clear active state when user changes dates manually
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('page_export_start_date');
    const endDateInput = document.getElementById('page_export_end_date');

    if (startDateInput) startDateInput.addEventListener('change', clearPresetActiveState);
    if (endDateInput) endDateInput.addEventListener('change', clearPresetActiveState);
});
</script>

<?php require_once '../../includes/footer.php'; ?>
