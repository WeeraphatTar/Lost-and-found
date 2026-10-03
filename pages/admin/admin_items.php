<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';
require_once '../../helpers/category_helper.php';

if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$type_filter = isset($type_filter) ? $type_filter : (isset($_GET['type']) ? trim($_GET['type']) : 'all');

$categories_list = get_active_categories($pdo);

$query = "SELECT i.*, u.first_name, u.last_name, u.email 
          FROM items i 
          JOIN users u ON i.user_id = u.id 
          WHERE 1=1";

$params = [];
if (!empty($search)) {
    $query .= " AND (i.title LIKE :search OR i.description LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($status_filter !== 'all') {
    $query .= " AND i.status = :status";
    $params[':status'] = $status_filter;
}
if ($category_filter !== 'all') {
    $query .= " AND i.category = :category";
    $params[':category'] = $category_filter;
}
if ($type_filter !== 'all') {
    $query .= " AND i.type = :type";
    $params[':type'] = $type_filter;
}

$query .= " ORDER BY i.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Type-aware Counts logic
$count_type_clause = ($type_filter !== 'all') ? " WHERE type = '{$type_filter}'" : "";
$count_type_and = ($type_filter !== 'all') ? " AND type = '{$type_filter}'" : "";

$total_count = $pdo->query("SELECT COUNT(*) FROM items{$count_type_clause}")->fetchColumn();
$open_count = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'open'{$count_type_and}")->fetchColumn();
$pending_count = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'pending'{$count_type_and}")->fetchColumn();
$resolved_count = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'resolved'{$count_type_and}")->fetchColumn();
$hidden_count = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'hidden'{$count_type_and}")->fetchColumn();
?>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    if ($type_filter === 'lost') {
        $active_tab = 'items_lost';
    } elseif ($type_filter === 'found') {
        $active_tab = 'items_found';
    } else {
        $active_tab = 'items';
    }
    require_once '../../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        
        <!-- Header -->
        <?php 
            if ($type_filter === 'lost') {
                $page_title = 'จัดการประกาศของหาย';
                $breadcrumb_title = 'รายการของหาย';
            } elseif ($type_filter === 'found') {
                $page_title = 'จัดการประกาศของที่เก็บได้';
                $breadcrumb_title = 'รายการของที่เก็บได้';
            } else {
                $page_title = 'จัดการประกาศสิ่งของทั้งหมด';
                $breadcrumb_title = 'จัดการประกาศสิ่งของ';
            }
        ?>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                    <span>ศูนย์ควบคุมผู้ดูแลระบบ</span>
                    <span>•</span>
                    <span><?php echo $breadcrumb_title; ?></span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    <?php echo $page_title; ?>
                </h1>
            </div>

            <?php if ($type_filter === 'lost'): ?>
                <a href="<?php echo $base_url; ?>/pages/admin/admin_report_lost.php" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-medium transition shadow-xs self-start md:self-auto flex items-center gap-1.5">
                    แจ้งของหาย
                </a>
            <?php elseif ($type_filter === 'found'): ?>
                <a href="<?php echo $base_url; ?>/pages/admin/admin_report_found.php" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-medium transition shadow-xs self-start md:self-auto flex items-center gap-1.5">
                    แจ้งพบของ
                </a>
            <?php endif; ?>
        </div>

        <!-- Alert Notification -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-medium flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0"></path></svg>
                    <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filter & Search Bar -->
        <?php $current_script = basename($_SERVER['PHP_SELF']); ?>
        <form action="<?php echo $current_script; ?>" method="GET" class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs mb-6 flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="w-full md:w-1/3 relative">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ค้นชื่อประกาศ หรือผู้ลงประกาศ..." class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:border-slate-800 transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto text-sm font-medium justify-end">
                <select name="category" onchange="this.form.submit()" class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:border-slate-800 transition">
                    <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>หมวดหมู่ทั้งหมด</option>
                    <?php foreach ($categories_list as $cat_code => $cat_name): ?>
                        <option value="<?php echo htmlspecialchars($cat_code); ?>" <?php echo $category_filter === $cat_code ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="status" onchange="this.form.submit()" class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:border-slate-800 transition">
                    <?php 
                        $open_label = ($type_filter === 'lost') ? 'กำลังตามหา' : (($type_filter === 'found') ? 'ยังไม่มีผู้รับ' : 'เปิดค้นหา');
                        $pending_label = ($type_filter === 'lost') ? 'รอรับของ' : (($type_filter === 'found') ? 'รอส่งมอบ' : 'รอรับ/ส่งมอบ');
                    ?>
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>สถานะทั้งหมด (<?php echo $total_count; ?>)</option>
                    <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>><?php echo $open_label; ?> (<?php echo $open_count; ?>)</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>><?php echo $pending_label; ?> (<?php echo $pending_count; ?>)</option>
                    <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>ส่งคืนสำเร็จ (<?php echo $resolved_count; ?>)</option>
                    <option value="hidden" <?php echo $status_filter === 'hidden' ? 'selected' : ''; ?>>ซ่อนประกาศ (<?php echo $hidden_count; ?>)</option>
                </select>
            </div>
        </form>

        <!-- Items Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-700 font-semibold uppercase tracking-wider text-sm">
                        <tr>
                            <th class="py-3.5 px-4">สิ่งของ</th>
                            <th class="py-3.5 px-4">ประเภท</th>
                            <th class="py-3.5 px-4">ผู้ลงประกาศ</th>
                            <th class="py-3.5 px-4">สถานะ</th>
                            <th class="py-3.5 px-4">วันที่ลง</th>
                            <th class="py-3.5 px-4 text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-500 font-medium">ไม่พบรายการประกาศที่ตรงกับเงื่อนไข</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <?php 
                                    $img_src = !empty($item['image_path']) ? $base_url . '/' . htmlspecialchars($item['image_path']) : '';
                                    $item_open_badge = ($item['type'] === 'lost') ? 'กำลังตามหา' : 'ยังไม่มีผู้รับ';
                                    $item_pending_badge = ($item['type'] === 'lost') ? 'รอรับของ' : 'รอส่งมอบ';
                                ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-slate-100 rounded-lg overflow-hidden flex-shrink-0 border border-slate-200">
                                                <?php if ($img_src): ?>
                                                    <img src="<?php echo $img_src; ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <div class="w-full h-full flex items-center justify-center text-slate-400">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="font-semibold text-slate-900 text-sm truncate max-w-[200px]">
                                                <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $item['id']; ?>" class="hover:underline">
                                                    <?php echo htmlspecialchars($item['title']); ?>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="p-4">
                                        <?php if ($item['type'] === 'lost'): ?>
                                            <span class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-md font-medium text-xs">ตามหาของหาย</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-md font-medium text-xs">แจ้งพบของ</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4">
                                        <div class="font-medium text-slate-800 text-sm"><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></div>
                                        <div class="text-xs text-slate-500"><?php echo htmlspecialchars($item['email']); ?></div>
                                    </td>

                                    <td class="p-4">
                                        <?php if ($item['status'] === 'open'): ?>
                                            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full font-medium text-xs"><?php echo $item_open_badge; ?></span>
                                        <?php elseif ($item['status'] === 'pending'): ?>
                                            <span class="px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-full font-medium text-xs"><?php echo $item_pending_badge; ?></span>
                                        <?php elseif ($item['status'] === 'resolved'): ?>
                                            <span class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-full font-medium text-xs">ส่งคืนสำเร็จ</span>
                                        <?php elseif ($item['status'] === 'hidden'): ?>
                                            <span class="px-2.5 py-1 bg-slate-100 text-slate-700 border border-slate-200 rounded-full font-medium text-xs">ซ่อนประกาศ</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 bg-slate-100 text-slate-700 border border-slate-200 rounded-full font-medium text-xs"><?php echo !empty($item['status']) ? htmlspecialchars($item['status']) : 'ไม่ระบุ'; ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4 text-slate-600 text-sm whitespace-nowrap">
                                        <?php echo date('d M Y', strtotime($item['created_at'])); ?>
                                    </td>

                                    <td class="p-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $item['id']; ?>" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium text-xs md:text-sm transition border border-slate-200">
                                                ดูประกาศ
                                            </a>

                                            <?php if ($item['status'] === 'hidden'): ?>
                                                <form action="<?php echo $base_url; ?>/actions/admin_manage_action.php" method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_hide_item">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium text-xs md:text-sm transition shadow-xs">
                                                        แสดง (ยกเลิกซ่อน)
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="<?php echo $base_url; ?>/actions/admin_manage_action.php" method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_hide_item">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                    <button type="submit" onclick="return confirm('ต้องการซ่อนประกาศนี้ใช่หรือไม่?')" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-medium text-xs md:text-sm transition shadow-xs">
                                                        ซ่อน
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form action="<?php echo $base_url; ?>/actions/admin_manage_action.php" method="POST" class="inline">
                                                <input type="hidden" name="action" value="delete_item">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                <button type="submit" onclick="return confirm('ยืนยันลบประกาศนี้ถาวรใช่หรือไม่?')" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-medium text-xs md:text-sm transition shadow-xs">
                                                    ลบ
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
    </main>
</div>

<?php require_once '../../includes/footer.php'; ?>
