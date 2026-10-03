<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/category_helper.php';

// Strict Admin guard
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถดำเนินการได้";
    header("Location: http://localhost/lost-and-found/pages/login.php");
    exit;
}

$report_type = $_POST['report_type'] ?? 'items';
$start_date = trim($_POST['start_date'] ?? date('Y-m-01'));
$end_date = trim($_POST['end_date'] ?? date('Y-m-d'));
$export_format = $_POST['export_format'] ?? 'csv';

// Format timestamps for SQL query
$start_ts = $start_date . ' 00:00:00';
$end_ts = $end_date . ' 23:59:59';

$category_th_map = get_active_categories($pdo);

// ----------------------------------------------------
// 1. Export as CSV / Excel
// ----------------------------------------------------
if ($export_format === 'csv') {
    $filename = "report_" . $report_type . "_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    
    // Output UTF-8 BOM for Microsoft Excel Thai language compatibility
    fputs($output, "\xEF\xBB\xBF");

    if ($report_type === 'items') {
        // Headers
        fputcsv($output, ['ลำดับ (ID)', 'ประเภทประกาศ', 'ชื่อสิ่งของ', 'หมวดหมู่', 'สถานะ', 'จังหวัด', 'อำเภอ/เขต', 'สถานที่รายละเอียด', 'วันที่สร้าง', 'ผู้ประกาศ', 'อีเมลผู้ประกาศ']);
        
        $stmt = $pdo->prepare("SELECT i.*, u.first_name, u.last_name, u.email FROM items i JOIN users u ON i.user_id = u.id WHERE i.created_at BETWEEN ? AND ? ORDER BY i.created_at DESC");
        $stmt->execute([$start_ts, $end_ts]);
        
        $type_map = ['lost' => 'แจ้งของหาย', 'found' => 'แจ้งพบของ'];
        $status_map = ['open' => 'เปิดใช้งาน', 'claimed' => 'ส่งคืนสำเร็จ', 'hidden' => 'ถูกซ่อน'];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cat_name = $category_th_map[$row['category']] ?? $row['category'];
            fputcsv($output, [
                $row['id'],
                $type_map[$row['type']] ?? $row['type'],
                $row['title'],
                $cat_name,
                $status_map[$row['status']] ?? $row['status'],
                $row['province'],
                $row['district'],
                $row['location_details'],
                date('d/m/Y H:i', strtotime($row['created_at'])),
                $row['first_name'] . ' ' . $row['last_name'],
                $row['email']
            ]);
        }
    } elseif ($report_type === 'claims') {
        // Headers
        fputcsv($output, ['ลำดับคำร้อง (ID)', 'ID ประกาศ', 'ชื่อสิ่งของ', 'ผู้ขอรับคืน', 'อีเมลผู้ขอรับคืน', 'เจ้าของโพสต์', 'อีเมลเจ้าของโพสต์', 'คะแนนจับคู่', 'สถานะคำร้อง', 'วันที่ยื่นคำร้อง']);
        
        $stmt = $pdo->prepare("SELECT c.*, i.title as item_title, u_claim.first_name as claim_first, u_claim.last_name as claim_last, u_claim.email as claim_email, u_owner.first_name as owner_first, u_owner.last_name as owner_last, u_owner.email as owner_email FROM claims c JOIN items i ON c.item_id = i.id JOIN users u_claim ON c.claimant_id = u_claim.id LEFT JOIN users u_owner ON c.finder_id = u_owner.id WHERE c.created_at BETWEEN ? AND ? ORDER BY c.created_at DESC");
        $stmt->execute([$start_ts, $end_ts]);

        $claim_status_map = [
            'pending' => 'รอตรวจสอบ',
            'under_admin_review' => 'แอดมินกำลังตรวจสอบ',
            'approved' => 'อนุมัติคำร้อง',
            'meeting_scheduled' => 'นัดหมายรับของ',
            'completed' => 'ส่งคืนสำเร็จ',
            'rejected' => 'ปฏิเสธคำร้อง',
            'cancelled_mismatch' => 'ยกเลิกเนื่องจากไม่ตรงกัน'
        ];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['item_id'],
                $row['item_title'],
                $row['claim_first'] . ' ' . $row['claim_last'],
                $row['claim_email'],
                $row['owner_first'] . ' ' . $row['owner_last'],
                $row['owner_email'],
                ($row['match_score'] ?? 0) . '%',
                $claim_status_map[$row['status']] ?? $row['status'],
                date('d/m/Y H:i', strtotime($row['created_at']))
            ]);
        }
    } else { // Audit Logs
        // Headers
        fputcsv($output, ['ลำดับบันทึก (ID)', 'วัน-เวลา', 'ผู้ดำเนินการ', 'อีเมลผู้ดำเนินการ', 'ประเภทกิจกรรม', 'รายละเอียด', 'IP Address']);
        
        $stmt = $pdo->prepare("SELECT l.*, u.first_name, u.last_name, u.email FROM admin_logs l JOIN users u ON l.admin_id = u.id WHERE l.created_at BETWEEN ? AND ? ORDER BY l.created_at DESC");
        $stmt->execute([$start_ts, $end_ts]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                date('d/m/Y H:i:s', strtotime($row['created_at'])),
                $row['first_name'] . ' ' . $row['last_name'],
                $row['email'],
                $row['action_type'],
                $row['description'],
                $row['ip_address'] ?? '127.0.0.1'
            ]);
        }
    }

    fclose($output);
    exit;
}

// ----------------------------------------------------
// 2. Export as Printable HTML / PDF View
// ----------------------------------------------------
$report_titles = [
    'items' => 'รายงานสรุปประกาศสิ่งของ (Lost & Found Items Report)',
    'claims' => 'รายงานสรุปคำร้องการส่งคืนสิ่งของ (Claims & Returns Report)',
    'logs' => 'รายงานประวัติการทำงานผู้ดูแลระบบ (Admin Audit Logs)'
];

$title_text = $report_titles[$report_type] ?? 'รายงานสถิติ';

// Fetch Data
if ($report_type === 'items') {
    $stmt = $pdo->prepare("SELECT i.*, u.first_name, u.last_name, u.email FROM items i JOIN users u ON i.user_id = u.id WHERE i.created_at BETWEEN ? AND ? ORDER BY i.created_at DESC");
    $stmt->execute([$start_ts, $end_ts]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($report_type === 'claims') {
    $stmt = $pdo->prepare("SELECT c.*, i.title as item_title, u_claim.first_name as claim_first, u_claim.last_name as claim_last, u_claim.email as claim_email, u_owner.first_name as owner_first, u_owner.last_name as owner_last, u_owner.email as owner_email FROM claims c JOIN items i ON c.item_id = i.id JOIN users u_claim ON c.claimant_id = u_claim.id LEFT JOIN users u_owner ON c.finder_id = u_owner.id WHERE c.created_at BETWEEN ? AND ? ORDER BY c.created_at DESC");
    $stmt->execute([$start_ts, $end_ts]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT l.*, u.first_name, u.last_name, u.email FROM admin_logs l JOIN users u ON l.admin_id = u.id WHERE l.created_at BETWEEN ? AND ? ORDER BY l.created_at DESC");
    $stmt->execute([$start_ts, $end_ts]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title_text); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: white; }
            .print-shadow-none { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 py-8 px-4 sm:px-8">
    
    <div class="max-w-6xl mx-auto">
        <!-- Floating Print Toolbar -->
        <div class="no-print bg-white p-4 rounded-2xl shadow-md border border-gray-200 mb-6 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">โหมดมุมมองรายงานพร้อมพิมพ์</span>
                <span class="text-sm font-bold text-gray-800">กดปุ่มสั่งพิมพ์ด้านขวาเพื่อบันทึกเป็นไฟล์ PDF</span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    สั่งพิมพ์เป็น PDF
                </button>
                <button onclick="window.close()" class="px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-xl text-xs font-semibold">ปิดหน้าต่าง</button>
            </div>
        </div>

        <!-- Printable Document Box -->
        <div class="bg-white p-8 sm:p-12 rounded-3xl border border-gray-200 shadow-sm print-shadow-none space-y-6">
            
            <!-- Document Header -->
            <div class="flex items-start justify-between border-b border-gray-200 pb-6">
                <div>
                    <h1 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($title_text); ?></h1>
                    <p class="text-xs text-gray-500 mt-1">ระบบศูนย์รับแจ้งของหายและส่งคืนสิ่งของ (Lost & Found System)</p>
                </div>
                <div class="text-right text-xs text-gray-500 font-mono space-y-0.5">
                    <p>ช่วงเวลา: <span class="font-bold text-gray-800"><?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></span></p>
                    <p>พิมพ์เมื่อ: <span class="font-bold text-gray-800"><?php echo date('d/m/Y H:i'); ?></span></p>
                    <p>จำนวนทั้งหมด: <span class="font-bold text-emerald-600"><?php echo number_format(count($data)); ?> รายการ</span></p>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase">
                            <th class="py-3 px-3">#</th>
                            <?php if ($report_type === 'items'): ?>
                                <th class="py-3 px-3">ประเภท</th>
                                <th class="py-3 px-3">ชื่อสิ่งของ</th>
                                <th class="py-3 px-3">หมวดหมู่</th>
                                <th class="py-3 px-3">สถานที่</th>
                                <th class="py-3 px-3">สถานะ</th>
                                <th class="py-3 px-3">ผู้ประกาศ</th>
                                <th class="py-3 px-3 text-right">วันที่</th>
                            <?php elseif ($report_type === 'claims'): ?>
                                <th class="py-3 px-3">ชื่อสิ่งของ</th>
                                <th class="py-3 px-3">ผู้ขอรับคืน</th>
                                <th class="py-3 px-3">เจ้าของโพสต์</th>
                                <th class="py-3 px-3">คะแนนแมตช์</th>
                                <th class="py-3 px-3">สถานะคำร้อง</th>
                                <th class="py-3 px-3 text-right">วันที่ยื่น</th>
                            <?php else: ?>
                                <th class="py-3 px-3">ผู้ดำเนินการ</th>
                                <th class="py-3 px-3">กิจกรรม</th>
                                <th class="py-3 px-3">รายละเอียด</th>
                                <th class="py-3 px-3 text-right">วัน-เวลา</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <?php if (empty($data)): ?>
                            <tr>
                                <td colspan="8" class="py-8 text-center text-gray-400">ไม่พบข้อมูลตรงตามเงื่อนไขที่ระบุ</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data as $idx => $row): ?>
                                <tr class="hover:bg-gray-50/50">
                                    <td class="py-3 px-3 font-mono text-gray-400"><?php echo $idx + 1; ?></td>
                                    
                                    <?php if ($report_type === 'items'): ?>
                                        <td class="py-3 px-3 font-semibold">
                                            <span class="<?php echo $row['type'] === 'lost' ? 'text-red-600' : 'text-emerald-600'; ?>">
                                                <?php echo $row['type'] === 'lost' ? 'แจ้งของหาย' : 'แจ้งพบของ'; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 font-bold text-gray-900"><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td class="py-3 px-3"><?php echo htmlspecialchars($category_th_map[$row['category']] ?? $row['category']); ?></td>
                                        <td class="py-3 px-3 text-gray-500"><?php echo htmlspecialchars($row['province'] . ' ' . $row['district']); ?></td>
                                        <td class="py-3 px-3 font-semibold"><?php echo htmlspecialchars($row['status']); ?></td>
                                        <td class="py-3 px-3"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td class="py-3 px-3 text-right font-mono text-gray-400"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                    
                                    <?php elseif ($report_type === 'claims'): ?>
                                        <td class="py-3 px-3 font-bold text-gray-900"><?php echo htmlspecialchars($row['item_title']); ?></td>
                                        <td class="py-3 px-3"><?php echo htmlspecialchars($row['claim_first'] . ' ' . $row['claim_last']); ?></td>
                                        <td class="py-3 px-3"><?php echo htmlspecialchars($row['owner_first'] . ' ' . $row['owner_last']); ?></td>
                                        <td class="py-3 px-3 font-mono font-bold text-emerald-600"><?php echo ($row['match_score'] ?? 0); ?>%</td>
                                        <td class="py-3 px-3 font-semibold"><?php echo htmlspecialchars($row['status']); ?></td>
                                        <td class="py-3 px-3 text-right font-mono text-gray-400"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>

                                    <?php else: ?>
                                        <td class="py-3 px-3 font-bold text-gray-900"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td class="py-3 px-3 font-mono font-semibold text-blue-700"><?php echo htmlspecialchars($row['action_type']); ?></td>
                                        <td class="py-3 px-3"><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td class="py-3 px-3 text-right font-mono text-gray-400"><?php echo date('d/m/Y H:i:s', strtotime($row['created_at'])); ?></td>
                                    <?php endif; ?>

                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer Stamp -->
            <div class="pt-6 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
                <p>รายงานนี้สร้างโดยอัตโนมัติจากระบบ Lost & Found Admin Center</p>
                <p>หน้า 1 จาก 1</p>
            </div>

        </div>
    </div>

</body>
</html>
