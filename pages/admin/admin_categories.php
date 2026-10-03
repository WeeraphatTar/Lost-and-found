<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';
require_once '../../config/migrate_admin_tables.php'; // Ensure DB structure exists

if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

// Fetch categories from DB
$categories_stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM items i WHERE i.category = c.code_name) as item_count FROM categories c ORDER BY c.sort_order ASC, c.id ASC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

$total_cats = count($categories);
$active_cats = count(array_filter($categories, fn($c) => $c['is_active'] == 1));
?>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'categories';
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
                    <span>จัดการหมวดหมู่สิ่งของ</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    จัดการหมวดหมู่สิ่งของ
                </h1>
            </div>
        </div>

        <!-- Alert Notification -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-medium flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0"></path></svg>
                    <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Category Summary Bar -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 font-medium">หมวดหมู่ทั้งหมด</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1 font-mono"><?php echo number_format($total_cats); ?></p>
                </div>
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 font-medium">หมวดหมู่เปิดใช้งานอยู่</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1 font-mono"><?php echo number_format($active_cats); ?></p>
                </div>
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"></path></svg>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 font-medium mb-1">เพิ่มหมวดหมู่ใหม่</p>
                    <button onclick="openAddModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition shadow-xs flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>เพิ่มหมวดหมู่</span>
                    </button>
                </div>
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            </div>
        </div>

        <!-- Category Data Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">รายการหมวดหมู่สิ่งของทั้งหมด</h3>
                <span class="text-xs text-slate-500">สามารถเปิด/ปิด หรือแก้ไขหมวดหมู่ได้ทันที</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/80 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-100 font-bold">
                        <tr>
                            <th class="px-6 py-3.5">ลำดับแสดง</th>
                            <th class="px-6 py-3.5">ชื่อหมวดหมู่</th>
                            <th class="px-6 py-3.5">รหัสอ้างอิง</th>
                            <th class="px-6 py-3.5 text-center">จำนวนประกาศ</th>
                            <th class="px-6 py-3.5 text-center">สถานะ</th>
                            <th class="px-6 py-3.5 text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-xs">
                        <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold">
                                        #<?php echo $cat['sort_order']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-900 font-semibold">
                                    <?php echo htmlspecialchars($cat['name_th']); ?>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                    <?php echo htmlspecialchars($cat['code_name']); ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-semibold border border-slate-200">
                                        <?php echo number_format($cat['item_count']); ?> รายการ
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($cat['is_active'] == 1): ?>
                                        <span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-full text-xs font-semibold inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> เปิดใช้งาน
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 bg-slate-100 text-slate-500 border border-slate-200 rounded-full text-xs font-semibold inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> ปิดใช้งาน
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <form action="<?php echo $base_url; ?>/actions/category_action.php" method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $cat['is_active']; ?>">
                                            <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition border border-slate-200">
                                                <?php echo ($cat['is_active'] == 1) ? 'ปิดใช้งาน' : 'เปิดใช้งาน'; ?>
                                            </button>
                                        </form>

                                        <button onclick='openEditModal(<?php echo json_encode($cat); ?>)' class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold transition shadow-xs">
                                            แก้ไข
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- Modal เพิ่มหมวดหมู่ -->

<div id="addModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 transform transition-all">
        <h3 class="text-base font-bold text-slate-900 mb-4">เพิ่มหมวดหมู่สิ่งของใหม่</h3>
        <form action="<?php echo $base_url; ?>/actions/category_action.php" method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="action" value="create_category">
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">ชื่อหมวดหมู่ *</label>
                <input type="text" name="name_th" required placeholder="เช่น อุปกรณ์แคมป์ปิ้ง" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs outline-none focus:border-slate-800 transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">รหัสอ้างอิงภาษาอังกฤษ</label>
                <input type="text" name="code_name" placeholder="เช่น camping_gear" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs outline-none focus:border-slate-800 font-mono transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">ลำดับการแสดงผล</label>
                <input type="number" name="sort_order" value="10" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs outline-none focus:border-slate-800 transition">
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl font-semibold transition">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-semibold shadow-xs transition">บันทึกหมวดหมู่</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal แก้ไขหมวดหมู่ -->
<div id="editModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 transform transition-all">
        <h3 class="text-base font-bold text-slate-900 mb-4">แก้ไขหมวดหมู่สิ่งของ</h3>
        <form action="<?php echo $base_url; ?>/actions/category_action.php" method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="action" value="edit_category">
            <input type="hidden" id="edit_category_id" name="category_id">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">ชื่อหมวดหมู่ *</label>
                <input type="text" id="edit_name_th" name="name_th" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs outline-none focus:border-slate-800 transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">ลำดับการแสดงผล</label>
                <input type="number" id="edit_sort_order" name="sort_order" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-xs outline-none focus:border-slate-800 transition">
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl font-semibold transition">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-semibold shadow-xs transition">บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>
</div>

<!-- Modals JavaScript -->
<script>
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}
function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}
function openEditModal(cat) {
    document.getElementById('edit_category_id').value = cat.id;
    document.getElementById('edit_name_th').value = cat.name_th;
    document.getElementById('edit_sort_order').value = cat.sort_order;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php require_once '../../includes/footer.php'; ?>


