<?php
$base_url = 'http://localhost/lost-and-found';
require_once '../includes/header.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนเข้าใช้งาน Dashboard";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // ดึงข้อมูลประกาศของตัวเองเรียงตามล่าสุด
    $sql = "SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Database Error: " . $e->getMessage();
    $items = [];
}
?>

<div class="py-12 bg-background flex-grow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-primary">รายการประกาศของฉัน</h1>
            <div class="space-x-2">
                <a href="<?php echo $base_url; ?>/pages/report_lost.php" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded hover:bg-secondary transition shadow-sm hidden sm:inline-block">แจ้งของหาย</a>
                <a href="<?php echo $base_url; ?>/pages/report_found.php" class="px-4 py-2 border border-primary text-primary text-sm font-medium rounded hover:bg-primary hover:text-white transition bg-white hidden sm:inline-block">แจ้งพบของ</a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="p-4 mb-6 text-sm text-green-700 bg-green-100 border border-green-300 rounded-md">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="p-4 mb-6 text-sm text-red-700 bg-red-100 border border-red-300 rounded-md">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-sm text-gray-500 uppercase tracking-wider">
                            <th class="p-4 font-semibold">รูปภาพ</th>
                            <th class="p-4 font-semibold">ชื่อประกาศ</th>
                            <th class="p-4 font-semibold">ประเภท</th>
                            <th class="p-4 font-semibold">สถานะ</th>
                            <th class="p-4 font-semibold">วันที่โพสต์</th>
                            <th class="p-4 font-semibold text-right">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                        <?php if (count($items) > 0): ?>
                            <?php foreach ($items as $item): ?>
                                <?php
                                    $type_label = ($item['type'] === 'lost') ? 'ตามหาของหาย' : 'ประกาศพบของ';
                                    $type_class = ($item['type'] === 'lost') ? 'text-red-600 bg-red-50 border border-red-100' : 'text-green-600 bg-green-50 border border-green-100';
                                    
                                    $status_label = '';
                                    $status_class = '';
                                    if ($item['status'] === 'open') {
                                        $status_label = 'กำลังดำเนินการ';
                                        $status_class = 'text-blue-600 bg-blue-50';
                                    } elseif ($item['status'] === 'resolved' || $item['status'] === 'closed') {
                                        $status_label = 'ปิดประกาศแล้ว';
                                        $status_class = 'text-gray-500 bg-gray-100 line-through decoration-gray-400';
                                    }

                                    $date_formatted = date('d M Y H:i', strtotime($item['created_at']));
                                ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-4 align-middle">
                                        <?php if (!empty($item['image_path'])): ?>
                                            <img src="<?php echo $base_url . '/' . htmlspecialchars($item['image_path']); ?>" alt="รูปภาพ" class="w-16 h-16 object-cover rounded border border-gray-200">
                                        <?php else: ?>
                                            <div class="w-16 h-16 bg-gray-100 text-gray-400 flex items-center justify-center rounded border border-gray-200">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 align-middle font-medium text-gray-900 max-w-[200px] truncate">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                        <div class="text-xs text-gray-400 font-normal mt-1"><?php echo htmlspecialchars($item['location']); ?></div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span class="inline-block px-2 py-1 rounded text-xs <?php echo $type_class; ?>"><?php echo $type_label; ?></span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span class="inline-block px-2 py-1 rounded text-xs font-medium <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                                    </td>
                                    <td class="p-4 align-middle text-gray-500 text-xs">
                                        <?php echo $date_formatted; ?>
                                    </td>
                                    <td class="p-4 align-middle text-right space-y-2 sm:space-y-0 sm:space-x-2">
                                        <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $item['id']; ?>" class="inline-block px-3 py-1.5 bg-white border border-blue-600 text-blue-600 hover:bg-blue-100 rounded shadow-sm transition text-xs">ดูรายละเอียด</a>
                                        
                                        <?php if ($item['status'] === 'open'): ?>
                                            <a href="<?php echo $base_url; ?>/pages/edit_item.php?id=<?php echo $item['id']; ?>" class="inline-block px-3 py-1.5 bg-white border border-yellow-600 text-yellow-600 hover:bg-yellow-100 rounded shadow-sm transition text-xs">แก้ไข</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($item['status'] === 'open'): ?>
                                            <form action="<?php echo $base_url; ?>/actions/dashboard_action.php" method="POST" class="inline-block" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการปิดประกาศนี้? (เมื่อปิดแล้วจะไม่สามารถกลับมาเปิดใหม่ได้)');">
                                                <input type="hidden" name="action" value="close">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="inline-block px-3 py-1.5 bg-white border border-slate-500 text-slate-600 hover:bg-slate-100 rounded shadow-sm transition text-xs">ปิดประกาศ</button>
                                            </form>
                                        <?php endif; ?>

                                        <form action="<?php echo $base_url; ?>/actions/dashboard_action.php" method="POST" class="inline-block" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบประกาศนี้? ข้อมูลและรูปภาพทั้งหมดจะถูกลบทิ้งอย่างถาวร');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="px-3 py-1.5 bg-red-600 border border-transparent text-white hover:bg-red-700 rounded shadow-sm transition text-xs">ลบ</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                        <p>คุณยังไม่ได้โพสต์ประกาศใดๆ</p>
                                        <div class="mt-4 flex gap-3">
                                            <a href="<?php echo $base_url; ?>/pages/report_lost.php" class="text-primary hover:text-accent font-medium text-sm">แจ้งของหาย</a>
                                            <span class="text-gray-300">|</span>
                                            <a href="<?php echo $base_url; ?>/pages/report_found.php" class="text-primary hover:text-accent font-medium text-sm">แจ้งพบของ</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
