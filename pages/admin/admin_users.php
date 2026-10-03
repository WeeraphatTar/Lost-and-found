<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : 'all';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';

$query = "SELECT u.*, 
                 (SELECT COUNT(*) FROM items WHERE user_id = u.id) as item_count,
                 (SELECT COUNT(*) FROM claims WHERE claimant_id = u.id) as claim_count
          FROM users u 
          WHERE 1=1";

$params = [];
if (!empty($search)) {
    $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($role_filter !== 'all') {
    $query .= " AND u.role = :role";
    $params[':role'] = $role_filter;
}
if ($status_filter === 'banned') {
    $query .= " AND u.is_banned = 1";
} elseif ($status_filter === 'active') {
    $query .= " AND (u.is_banned IS NULL OR u.is_banned = 0)";
}

$query .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Counts
$total_users = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_users = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_banned IS NULL OR is_banned = 0")->fetchColumn();
$banned_users = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_banned = 1")->fetchColumn();

$user_role_count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$admin_role_count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
?>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'users';
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
                    <span>จัดการบัญชีผู้ใช้งาน</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    จัดการสมาชิกและสิทธิ์การใช้งาน
                </h1>
            </div>
        </div>

        <!-- Search & Filter Bar -->
        <form action="admin_users.php" method="GET" class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs mb-6 flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="w-full md:w-80 relative">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ค้นหาชื่อ, อีเมล หรือเบอร์โทรศัพท์..." class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs outline-none focus:border-slate-800 transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <div class="flex items-center gap-2 w-full md:w-auto text-xs font-semibold overflow-x-auto">
                <select name="role" onchange="this.form.submit()" class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:border-slate-800 transition cursor-pointer hover:border-slate-300">
                    <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>บทบาททั้งหมด (<?php echo $total_users; ?>)</option>
                    <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>ผู้ใช้ทั่วไป / User (<?php echo $user_role_count; ?>)</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>ผู้ดูแลระบบ / Admin (<?php echo $admin_role_count; ?>)</option>
                </select>

                <select name="status" onchange="this.form.submit()" class="px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:border-slate-800 transition cursor-pointer hover:border-slate-300">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>สถานะทั้งหมด (<?php echo $total_users; ?>)</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>ปกติ (<?php echo $active_users; ?>)</option>
                    <option value="banned" <?php echo $status_filter === 'banned' ? 'selected' : ''; ?>>ถูกระงับ (<?php echo $banned_users; ?>)</option>
                </select>
            </div>
        </form>


        <!-- Users Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="p-4">สมาชิก</th>
                            <th class="p-4">เบอร์ติดต่อ</th>
                            <th class="p-4">บทบาท</th>
                            <th class="p-4">ประวัติกิจกรรม</th>
                            <th class="p-4">สถานะบัญชี</th>
                            <th class="p-4 text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-500 font-medium">ไม่พบสมาชิกที่ตรงตามเงื่อนไข</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <?php 
                                    $is_self = ($u['id'] == $_SESSION['user_id']);
                                    $is_banned = !empty($u['is_banned']);
                                ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="p-4">
                                        <a href="admin_user_detail.php?id=<?php echo $u['id']; ?>" class="font-bold text-slate-900 hover:text-blue-600 transition text-sm inline-flex items-center gap-1.5 group">
                                            <span><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                        <div class="text-[11px] text-slate-400"><?php echo htmlspecialchars($u['email']); ?></div>
                                    </td>

                                    <td class="p-4 font-mono text-slate-600">
                                        <?php echo !empty($u['phone']) ? htmlspecialchars($u['phone']) : '-'; ?>
                                    </td>

                                    <td class="p-4">
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="px-2.5 py-1 bg-purple-50 text-purple-700 border border-purple-200 rounded-md font-bold text-[11px]">Admin</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 bg-slate-100 text-slate-700 border border-slate-200 rounded-md font-medium text-[11px]">User</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4 whitespace-nowrap">
                                        <a href="admin_user_detail.php?id=<?php echo $u['id']; ?>" class="text-slate-600 hover:text-blue-600 transition">
                                            ประกาศ: <strong class="text-slate-900"><?php echo $u['item_count']; ?></strong>
                                            <span class="text-slate-300 mx-1">•</span>
                                            ยื่น Claim: <strong class="text-slate-900"><?php echo $u['claim_count']; ?></strong>
                                        </a>
                                    </td>

                                    <td class="p-4">
                                        <?php if ($is_banned): ?>
                                            <span class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200/80 rounded-full font-semibold text-[11px] inline-flex items-center gap-1" title="<?php echo htmlspecialchars($u['ban_reason'] ?? ''); ?>">
                                                <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                                                ถูกระงับ
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-full font-semibold text-[11px] inline-flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                                ปกติ
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-4 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-2 justify-end">
                                            <a href="admin_user_detail.php?id=<?php echo $u['id']; ?>" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold transition text-xs inline-flex items-center gap-1 border border-slate-200/80">
                                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                ดูโปรไฟล์
                                            </a>

                                            <?php if (!$is_self): ?>
                                                <?php if ($is_banned): ?>
                                                    <button type="button" onclick="openUnbanModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['first_name'] . ' ' . $u['last_name'])); ?>')" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold transition shadow-2xs text-xs inline-flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                                                        ปลดระงับ
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" onclick="openBanModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['first_name'] . ' ' . $u['last_name'])); ?>')" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-semibold transition shadow-2xs text-xs inline-flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                        ระงับสิทธิ์
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- Modal 1: ระงับสิทธิ์ Ban User -->
<div id="banModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden transform transition-all">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                ระงับสิทธิ์ผู้ใช้งาน
            </h3>
            <button onclick="closeBanModal()" class="text-slate-400 hover:text-slate-600 font-bold text-sm">✕</button>
        </div>
        <form action="<?php echo $base_url; ?>/actions/admin_manage_action.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="ban_user">
            <input type="hidden" name="user_id" id="ban_user_id" value="">
            
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ผู้ใช้ที่จะระงับสิทธิ์</label>
                <div id="ban_user_name" class="font-bold text-sm text-slate-900 bg-slate-50 p-2.5 rounded-lg border border-slate-200"></div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">เหตุผลการระงับสิทธิ์</label>
                <input type="text" name="ban_reason" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 outline-none focus:border-slate-800 transition" placeholder="ระบุเหตุผลการระงับสิทธิ์..." required>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 text-xs">
                <button type="button" onclick="closeBanModal()" class="px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl font-semibold transition">
                    ยกเลิก
                </button>
                <button type="submit" class="px-4 py-2 bg-rose-600 text-white hover:bg-rose-700 rounded-xl font-semibold transition shadow-xs">
                    ยืนยันระงับสิทธิ์
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: ปลดระงับสิทธิ์ Unban User -->
<div id="unbanModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden transform transition-all">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                ปลดล็อกการระงับสิทธิ์ผู้ใช้งาน
            </h3>
            <button onclick="closeUnbanModal()" class="text-slate-400 hover:text-slate-600 font-bold text-sm">✕</button>
        </div>
        <form action="<?php echo $base_url; ?>/actions/admin_manage_action.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="unban_user">
            <input type="hidden" name="user_id" id="unban_user_id" value="">
            
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ผู้ใช้งานที่ต้องการปลดระงับ</label>
                <div id="unban_user_name" class="font-bold text-sm text-slate-900 bg-slate-50 p-2.5 rounded-lg border border-slate-200"></div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                คุณยืนยันที่จะปลดระงับสิทธิ์การใช้งานบัญชีนี้ใช่หรือไม่? เมื่อปลดระงับแล้ว สมาชิกจะสามารถเข้าสู่ระบบและทำรายการต่างๆ ในระบบได้ตามปกติ
            </p>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 text-xs">
                <button type="button" onclick="closeUnbanModal()" class="px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl font-semibold transition">
                    ยกเลิก
                </button>
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white hover:bg-emerald-700 rounded-xl font-semibold transition shadow-xs">
                    ยืนยันปลดระงับ
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openBanModal(userId, userName) {
    document.getElementById('ban_user_id').value = userId;
    document.getElementById('ban_user_name').innerText = userName;
    document.getElementById('banModal').classList.remove('hidden');
}
function closeBanModal() {
    document.getElementById('banModal').classList.add('hidden');
}

function openUnbanModal(userId, userName) {
    document.getElementById('unban_user_id').value = userId;
    document.getElementById('unban_user_name').innerText = userName;
    document.getElementById('unbanModal').classList.remove('hidden');
}
function closeUnbanModal() {
    document.getElementById('unbanModal').classList.add('hidden');
}
</script>

<?php require_once '../../includes/footer.php'; ?>
