<?php
$base_url = 'http://localhost/lost-and-found';
require_once '../includes/header.php';
require_once '../config/database.php';

// Get query parameters
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$status_filter = isset($_GET['status']) ? (array)$_GET['status'] : ['lost', 'found'];
$category_filter = isset($_GET['category']) ? (array)$_GET['category'] : [];
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 15; // 5 rows * 3 items per row

// Helper function to build page URLs preserving filters
function getPageUrl($page_num) {
    $params = $_GET;
    $params['page'] = $page_num;
    return 'browse.php?' . http_build_query($params);
}

// Build Query
$sql = "SELECT * FROM items WHERE status = 'open'";
$params = [];

if ($q !== '') {
    $sql .= " AND (title LIKE ? OR description LIKE ? OR location LIKE ?)";
    $q_param = "%{$q}%";
    $params[] = $q_param;
    $params[] = $q_param;
    $params[] = $q_param;
}

if (!empty($status_filter)) {
    $placeholders = implode(',', array_fill(0, count($status_filter), '?'));
    $sql .= " AND type IN ($placeholders)";
    foreach ($status_filter as $s) {
        $params[] = $s;
    }
}

if (!empty($category_filter)) {
    $placeholders = implode(',', array_fill(0, count($category_filter), '?'));
    $sql .= " AND category IN ($placeholders)";
    foreach ($category_filter as $c) {
        $params[] = $c;
    }
}

if ($sort === 'oldest') {
    $sql .= " ORDER BY created_at ASC";
} else {
    $sql .= " ORDER BY created_at DESC";
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $all_items = $stmt->fetchAll();
    $total_count = count($all_items);
    
    // Pagination calculation
    $total_pages = ceil($total_count / $limit);
    if ($total_pages < 1) $total_pages = 1;
    if ($page > $total_pages) $page = $total_pages;
    
    $offset = ($page - 1) * $limit;
    $items = array_slice($all_items, $offset, $limit);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database Error: " . $e->getMessage();
    $items = [];
    $total_count = 0;
    $total_pages = 1;
}
?>

<style>
    /* บังคับไม่ให้ทั้งหน้าเว็บขยับได้ ให้ขยับได้เฉพาะส่วนที่กำหนด */
    body { overflow: hidden !important; }
</style>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-6 flex-grow flex flex-col h-[calc(100vh-64px)] overflow-hidden">
    
    <!-- ส่วนหัวอยู่นิ่ง -->
    <div class="bg-background z-40 pb-4 border-b border-gray-200 mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 flex-shrink-0">
        <h1 class="text-2xl font-bold text-primary m-0">รายการประกาศทั้งหมด</h1>
        <div class="text-sm text-gray-500 flex items-center gap-4 w-full sm:w-auto">
            <span>พบทั้งหมด <?php echo $total_count; ?> รายการ</span>
            <select form="filter-form" name="sort" onchange="this.form.submit()" class="px-3 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:border-accent bg-white">
                <option value="latest" <?php echo ($sort === 'latest') ? 'selected' : ''; ?>>ล่าสุด</option>
                <option value="oldest" <?php echo ($sort === 'oldest') ? 'selected' : ''; ?>>เก่าสุด</option>
            </select>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 border border-green-300 rounded-md flex-shrink-0">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 border border-red-300 rounded-md flex-shrink-0">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- ส่วนเนื้อหา: แบ่งซ้ายขวา -->
    <div class="flex flex-col md:flex-row gap-6 items-start flex-grow overflow-hidden pb-6">
        
        <!-- Sidebar Filter อยู่นิ่ง -->
        <aside class="w-full md:w-72 flex-shrink-0 bg-white p-5 rounded-lg border border-gray-200 shadow-sm overflow-y-auto h-fit max-h-full">
            <h2 class="text-lg font-bold text-primary mb-4 pb-2 border-b border-gray-100">ตัวกรองค้นหา</h2>
            
            <form id="filter-form" action="" method="GET" class="space-y-6">
                <!-- Search Keyword -->
                <div>
                    <h3 class="font-semibold text-primary mb-2">คำค้นหา</h3>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="พิมพ์คำค้นหา..." class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-accent text-sm">
                </div>

                <!-- Status Filter -->
                <div>
                    <h3 class="font-semibold text-primary mb-3">สถานะ</h3>
                    <div class="space-y-2">
                        <label class="flex items-center text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="status[]" value="lost" onchange="this.form.submit()" class="mr-2 rounded text-primary focus:ring-primary" <?php echo in_array('lost', $status_filter) ? 'checked' : ''; ?>> ตามหาของหาย
                        </label>
                        <label class="flex items-center text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="status[]" value="found" onchange="this.form.submit()" class="mr-2 rounded text-primary focus:ring-primary" <?php echo in_array('found', $status_filter) ? 'checked' : ''; ?>> ประกาศพบของ
                        </label>
                    </div>
                </div>

                <!-- Category Filter -->
                <div>
                    <h3 class="font-semibold text-primary mb-3">หมวดหมู่</h3>
                    <div class="space-y-2">
                        <?php 
                        $categories = [
                            'electronics' => 'อุปกรณ์อิเล็กทรอนิกส์',
                            'walletandcash' => 'กระเป๋าสตางค์และเงินสด', 
                            'cardanddocument' => 'บัตรและเอกสารสำคัญ',
                            'keyandkeycard' => 'กุญแจและคีย์การ์ด',
                            'bagandluggage' => 'กระเป๋าและสัมภาระ',
                            'clothingandjewelry' => 'เครื่องแต่งกายและเครื่องประดับ',
                            'studymaterialandstationery' => 'อุปกรณ์การเรียนและเครื่องเขียน',
                            'personalbelonging' => 'ของใช้ส่วนตัว',
                            'vehicleandaccessory' => 'ยานพาหนะและอุปกรณ์เสริม',
                            'others' => 'อื่นๆ'
                        ];
                        foreach ($categories as $val => $label) {
                            $checked = in_array($val, $category_filter) ? 'checked' : '';
                            echo '<label class="flex items-center text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" name="category[]" value="'.$val.'" onchange="this.form.submit()" class="mr-2 rounded text-primary focus:ring-primary" '.$checked.'> '.$label.'
                                  </label>';
                        }
                        ?>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="browse.php" class="block w-full py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded hover:bg-gray-200 transition text-center border border-gray-200">ล้างค่าตัวกรองทั้งหมด</a>
                </div>
            </form>
        </aside>

        <!-- Main Content (เลื่อนได้) -->
        <main class="flex-1 w-full overflow-y-auto h-full pr-2 pb-16 custom-scrollbar">
            <?php if ($total_count > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($items as $item): ?>
                        <?php
                            $type_badge = ($item['type'] === 'lost') 
                                ? '<span class="self-start inline-block px-2.5 py-1 rounded bg-red-100 text-red-700 text-xs font-semibold mb-2">ตามหาของหาย</span>'
                                : '<span class="self-start inline-block px-2.5 py-1 rounded bg-green-100 text-green-700 text-xs font-semibold mb-2">ประกาศพบของ</span>';
                            
                            $image_html = '';
                            if (!empty($item['image_path'])) {
                                $image_html = '<img src="' . $base_url . '/' . htmlspecialchars($item['image_path']) . '" alt="item image" class="w-full h-full object-contain">';
                            } else {
                                $image_html = '<svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
                            }

                            $date_formatted = date('d M Y', strtotime($item['event_date']));
                        ?>
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-1 flex flex-col">
                            <div class="w-full aspect-video bg-slate-100 flex items-center justify-center border-b border-gray-200 text-gray-400 overflow-hidden relative">
                                <?php echo $image_html; ?>
                            </div>
                            <div class="p-5 flex-1 flex flex-col">
                                <?php echo $type_badge; ?>
                                <h3 class="text-lg font-bold text-primary mt-1 truncate"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p class="text-gray-500 text-sm mt-2 truncate"><strong>สถานที่:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                                <p class="text-gray-500 text-sm mt-1 mb-4 flex-1"><strong>วันที่:</strong> <?php echo $date_formatted; ?></p>
                                <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $item['id']; ?>" class="block w-full text-center px-4 py-2 border border-primary text-primary text-sm font-medium rounded hover:bg-primary hover:text-white transition">ดูรายละเอียด</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination Control -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-10 mb-6 flex justify-center items-center gap-2">
                        <!-- Previous Page -->
                        <?php if ($page > 1): ?>
                            <a href="<?php echo getPageUrl($page - 1); ?>" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 hover:text-accent hover:border-accent transition flex items-center gap-1 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
                                ย้อนกลับ
                            </a>
                        <?php else: ?>
                            <span class="px-4 py-2 bg-gray-50 border border-gray-100 text-gray-400 text-sm font-semibold rounded-xl cursor-not-allowed flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
                                ย้อนกลับ
                            </span>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <div class="flex items-center gap-1.5">
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<a href="' . getPageUrl(1) . '" class="px-3.5 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 hover:text-accent hover:border-accent transition shadow-sm">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="text-gray-400 px-1">...</span>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                if ($i === $page) {
                                    echo '<span class="px-3.5 py-2 bg-primary text-white text-sm font-bold rounded-xl shadow-md">' . $i . '</span>';
                                } else {
                                    echo '<a href="' . getPageUrl($i) . '" class="px-3.5 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 hover:text-accent hover:border-accent transition shadow-sm">' . $i . '</a>';
                                }
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="text-gray-400 px-1">...</span>';
                                }
                                echo '<a href="' . getPageUrl($total_pages) . '" class="px-3.5 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 hover:text-accent hover:border-accent transition shadow-sm">' . $total_pages . '</a>';
                            }
                            ?>
                        </div>

                        <!-- Next Page -->
                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo getPageUrl($page + 1); ?>" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 hover:text-accent hover:border-accent transition flex items-center gap-1 shadow-sm">
                                ถัดไป
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        <?php else: ?>
                            <span class="px-4 py-2 bg-gray-50 border border-gray-100 text-gray-400 text-sm font-semibold rounded-xl cursor-not-allowed flex items-center gap-1">
                                ถัดไป
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="bg-white p-10 text-center rounded-lg border border-gray-200">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">ไม่พบรายการประกาศ</h3>
                    <p class="text-gray-500">ลองเปลี่ยนตัวกรองค้นหา หรือล้างค่าตัวกรองเพื่อดูรายการทั้งหมด</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
