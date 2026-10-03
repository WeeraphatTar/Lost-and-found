<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

// Executive KPI Metrics
$total_items = (int)$pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$total_claims = (int)$pdo->query("SELECT COUNT(*) FROM claims")->fetchColumn();
$completed_claims = (int)$pdo->query("SELECT COUNT(*) FROM claims WHERE status = 'completed'")->fetchColumn();
$total_users = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pending_reports = (int)$pdo->query("SELECT COUNT(*) FROM item_reports WHERE status = 'pending'")->fetchColumn();

// Calculate Success Return Rate
$success_rate = ($total_claims > 0) ? round(($completed_claims / $total_claims) * 100, 1) : 0;

// Category Translation Map (Dynamic from DB)
require_once '../../helpers/category_helper.php';
$category_th_map = get_active_categories($pdo);

// Category Stats
$cat_stmt = $pdo->query("SELECT category, COUNT(*) as count FROM items GROUP BY category ORDER BY count DESC LIMIT 5");
$cat_data = $cat_stmt->fetchAll();
$cat_labels = [];
foreach ($cat_data as $row) {
    $raw_cat = $row['category'];
    $cat_labels[] = $category_th_map[$raw_cat] ?? $raw_cat;
}
$cat_counts = array_column($cat_data, 'count');

// Monthly Lost vs Found Trend (Last 6 months)
$months = [];
$lost_monthly = [];
$found_monthly = [];

for ($i = 5; $i >= 0; $i--) {
    $m_key = date('Y-m', strtotime("-$i months"));
    $m_label = date('M Y', strtotime("-$i months"));
    $months[] = $m_label;

    $stmt_lost = $pdo->prepare("SELECT COUNT(*) FROM items WHERE type = 'lost' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt_lost->execute([$m_key]);
    $lost_monthly[] = (int)$stmt_lost->fetchColumn();

    $stmt_found = $pdo->prepare("SELECT COUNT(*) FROM items WHERE type = 'found' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt_found->execute([$m_key]);
    $found_monthly[] = (int)$stmt_found->fetchColumn();
}
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'dashboard';
    require_once '../../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                    <span>ศูนย์ควบคุมผู้ดูแลระบบ</span>
                    <span>•</span>
                    <span>ภาพรวมระบบ</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    ภาพรวมระบบจัดการของหายและส่งคืน
                </h1>
            </div>

        </div>

        <!-- Dashboard Action Bar (Analytics Toolbar) -->
        <div class="flex items-center justify-between gap-4 mb-6 print:hidden">
            <div>
                <h2 class="text-base font-bold text-slate-900">สรุปดัชนีชี้วัดประสิทธิภาพภาพรวม</h2>
                <p class="text-xs text-slate-500">ข้อมูลสถิติอัปเดตล่าสุด ณ วันที่ <?php echo date('d/m/Y'); ?></p>
            </div>
        </div>

        <!-- Metric KPI Grid (5 Data-First Cards with Semantic Accents) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <!-- 1. ประกาศทั้งหมด -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-slate-500">ประกาศทั้งหมด</span>
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 01-2-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <p class="text-2xl font-bold text-slate-900 tracking-tight font-mono"><?php echo number_format($total_items); ?></p>
            </div>

            <!-- 2. คำร้อง Claim ทั้งหมด (Blue Accent) -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-slate-500">คำร้อง Claim ทั้งหมด</span>
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <p class="text-2xl font-bold text-blue-600 tracking-tight font-mono"><?php echo number_format($total_claims); ?></p>
            </div>

            <!-- 3. ส่งคืนสำเร็จ (Emerald Accent) -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-slate-500">ส่งคืนสำเร็จ</span>
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <p class="text-2xl font-bold text-emerald-600 tracking-tight font-mono"><?php echo number_format($completed_claims); ?></p>
            </div>

            <!-- 4. สมาชิกทั้งหมด -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-slate-500">สมาชิกทั้งหมด</span>
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <p class="text-2xl font-bold text-slate-900 tracking-tight font-mono"><?php echo number_format($total_users); ?></p>
            </div>

            <!-- 5. รายงานรอตรวจสอบ (Rose Accent) -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-slate-500">รายงานรอตรวจสอบ</span>
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <p class="text-2xl font-bold text-rose-600 tracking-tight font-mono"><?php echo number_format($pending_reports); ?></p>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Line Chart: Monthly Trend -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
                <h3 class="text-sm font-bold text-slate-900 mb-4">แนวโน้มการแจ้งของหายและพบของ (สถิติ 6 เดือนล่าสุด)</h3>
                <div class="relative h-72">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Bar Chart: Category Share -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
                <h3 class="text-sm font-bold text-slate-900 mb-4">สัดส่วนหมวดหมู่สิ่งของ</h3>
                <div class="relative h-72">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- System Activity Recent Summary -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-900">กิจกรรมล่าสุดของผู้ดูแลระบบ</h3>
                <a href="<?php echo $base_url; ?>/pages/admin/admin_logs.php" class="text-xs text-slate-600 hover:text-slate-900 font-semibold transition">ดูประวัติทั้งหมด →</a>
            </div>

            <?php
            $recent_logs = $pdo->query("SELECT l.*, u.first_name, u.last_name FROM admin_logs l JOIN users u ON l.admin_id = u.id ORDER BY l.created_at DESC LIMIT 5")->fetchAll();
            ?>
            <div class="space-y-3">
                <?php if (empty($recent_logs)): ?>
                    <p class="text-xs text-slate-400">ยังไม่มีบันทึกประวัติการทำงาน</p>
                <?php else: ?>
                    <?php foreach ($recent_logs as $rl): ?>
                        <div class="flex items-center justify-between text-xs py-2 border-b border-slate-100 last:border-0">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-800"><?php echo htmlspecialchars($rl['first_name'] . ' ' . $rl['last_name']); ?></span>
                                <span class="text-slate-500">• <?php echo htmlspecialchars($rl['description']); ?></span>
                            </div>
                            <span class="text-slate-400 font-mono text-[11px]"><?php echo date('d/m H:i', strtotime($rl['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script class="print:hidden">
document.addEventListener("DOMContentLoaded", function() {
    // 1. Line Chart (High-Contrast Semantic Palette: Rose Red vs Emerald Green)
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    
    const gradientLost = ctxTrend.createLinearGradient(0, 0, 0, 300);
    gradientLost.addColorStop(0, 'rgba(244, 63, 94, 0.15)');
    gradientLost.addColorStop(1, 'rgba(244, 63, 94, 0.01)');

    const gradientFound = ctxTrend.createLinearGradient(0, 0, 0, 300);
    gradientFound.addColorStop(0, 'rgba(16, 185, 129, 0.15)');
    gradientFound.addColorStop(1, 'rgba(16, 185, 129, 0.01)');

    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: 'แจ้งของหาย',
                    data: <?php echo json_encode($lost_monthly); ?>,
                    borderColor: '#f43f5e',
                    backgroundColor: gradientLost,
                    borderWidth: 2.5,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#f43f5e',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'แจ้งพบของ',
                    data: <?php echo json_encode($found_monthly); ?>,
                    borderColor: '#10b981',
                    backgroundColor: gradientFound,
                    borderWidth: 2.5,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        font: { size: 12, family: 'sans-serif' }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: '#f1f5f9' }
                }
            }
        }
    });

    // 2. Category Chart (Horizontal Bar Chart with Harmonious Multi-Tone Palette)
    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($cat_labels); ?>,
            datasets: [{
                label: 'จำนวนรายการ',
                data: <?php echo json_encode($cat_counts); ?>,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4'],
                hoverBackgroundColor: ['#2563eb', '#059669', '#d97706', '#7c3aed', '#0891b2'],
                borderRadius: 6,
                barThickness: 16
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: '#f1f5f9' }
                },
                y: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>

<!-- Export Report Modal (Professional Redesign) -->
<div id="exportModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4 sm:p-6">
    <div class="bg-white rounded-2xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200/80 transform transition-all">
        
        <!-- Header -->
        <div class="flex items-center justify-between pb-5 border-b border-slate-100">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center shadow-xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-lg">ศูนย์ส่งออกรายงานและสถิติ</h3>
                    <p class="text-xs text-slate-500">เลือกประเภทข้อมูลและกำหนดช่วงเวลาเพื่อดาวน์โหลดไฟล์รายงาน</p>
                </div>
            </div>
            <button type="button" onclick="closeExportModal()" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 hover:text-slate-600 hover:bg-slate-200 flex items-center justify-center transition font-semibold text-sm">✕</button>
        </div>

        <form id="exportForm" action="<?php echo $base_url; ?>/actions/admin_export_action.php" method="POST" class="mt-6 space-y-6">
            <input type="hidden" name="export_format" id="export_format_input" value="csv">

            <!-- 1. Report Type Selection Card Grid -->
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-3">1. เลือกประเภทรายงานที่ต้องการ</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    
                    <label class="relative flex flex-col p-4 bg-slate-50/70 hover:bg-slate-100/80 rounded-xl border border-slate-200 cursor-pointer transition text-left has-[:checked]:border-slate-800 has-[:checked]:bg-white has-[:checked]:ring-2 has-[:checked]:ring-slate-800/10 has-[:checked]:shadow-sm">
                        <input type="radio" name="report_type" value="items" checked class="sr-only">
                        <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-700 border border-blue-100 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        <span class="text-xs font-bold text-slate-900">ประกาศสิ่งของ</span>
                        <span class="text-[11px] text-slate-500 mt-1">รายการแจ้งของหาย และแจ้งพบของ</span>
                    </label>

                    <label class="relative flex flex-col p-4 bg-slate-50/70 hover:bg-slate-100/80 rounded-xl border border-slate-200 cursor-pointer transition text-left has-[:checked]:border-slate-800 has-[:checked]:bg-white has-[:checked]:ring-2 has-[:checked]:ring-slate-800/10 has-[:checked]:shadow-sm">
                        <input type="radio" name="report_type" value="claims" class="sr-only">
                        <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="text-xs font-bold text-slate-900">คำร้องส่งคืน</span>
                        <span class="text-[11px] text-slate-500 mt-1">สถานะ Claim และประวัติการส่งคืน</span>
                    </label>

                    <label class="relative flex flex-col p-4 bg-slate-50/70 hover:bg-slate-100/80 rounded-xl border border-slate-200 cursor-pointer transition text-left has-[:checked]:border-slate-800 has-[:checked]:bg-white has-[:checked]:ring-2 has-[:checked]:ring-slate-800/10 has-[:checked]:shadow-sm">
                        <input type="radio" name="report_type" value="logs" class="sr-only">
                        <div class="w-9 h-9 rounded-lg bg-slate-200 text-slate-700 border border-slate-300 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <span class="text-xs font-bold text-slate-900">ประวัติงานแอดมิน</span>
                        <span class="text-[11px] text-slate-500 mt-1">บันทึกกิจกรรมและ Audit Logs</span>
                    </label>

                </div>
            </div>

            <!-- 2. Date Range Picker & Quick Presets -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">2. เลือกช่วงเวลาของข้อมูล</label>
                    <div class="flex items-center gap-1.5 text-[11px]">
                        <button type="button" onclick="setExportPreset(7)" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition font-medium">7 วันล่าสุด</button>
                        <button type="button" onclick="setExportPreset(30)" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition font-medium">30 วันล่าสุด</button>
                        <button type="button" onclick="setExportPreset('month')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition font-medium">เดือนนี้</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="block text-[11px] font-medium text-slate-500 mb-1">วันที่เริ่มต้น</span>
                        <input type="date" name="start_date" id="export_start_date" value="<?php echo date('Y-m-01'); ?>" required class="w-full text-xs p-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-slate-800 focus:border-transparent outline-none transition">
                    </div>
                    <div>
                        <span class="block text-[11px] font-medium text-slate-500 mb-1">วันที่สิ้นสุด</span>
                        <input type="date" name="end_date" id="export_end_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full text-xs p-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-slate-800 focus:border-transparent outline-none transition">
                    </div>
                </div>
            </div>

            <!-- 3. Actions -->
            <div class="pt-5 border-t border-slate-100 flex items-center justify-end gap-3 text-xs">
                <button type="button" onclick="closeExportModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition">
                    ยกเลิก
                </button>

                <button type="button" id="btnExportCsv" onclick="submitExport('csv')" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold transition flex items-center gap-2 shadow-xs">
                    <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    ส่งออก Excel (CSV)
                </button>

                <button type="button" id="btnExportPdf" onclick="submitExport('pdf')" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-semibold transition flex items-center gap-2 shadow-xs">
                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    มุมมองพิมพ์ / PDF
                </button>

            </div>

        </form>
    </div>
</div>

<script>
function openExportModal() {
    document.getElementById('exportModal').classList.remove('hidden');
}

function closeExportModal() {
    document.getElementById('exportModal').classList.add('hidden');
}

function setExportPreset(preset) {
    const today = new Date();
    const endDateStr = today.toISOString().split('T')[0];
    let startDate = new Date();
    if (preset === 'month') {
        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
    } else {
        startDate.setDate(today.getDate() - preset);
    }
    document.getElementById('export_start_date').value = startDate.toISOString().split('T')[0];
    document.getElementById('export_end_date').value = endDateStr;
}

function submitExport(format) {
    const form = document.getElementById('exportForm');
    document.getElementById('export_format_input').value = format;
    
    if (format === 'csv') {
        form.target = '_self'; // Download directly without opening blank new tab
    } else {
        form.target = '_blank'; // Printable PDF view opens in clean tab
    }
    
    form.submit();
    closeExportModal();
}
</script>

    </main>
</div>

<?php require_once '../../includes/footer.php'; ?>
