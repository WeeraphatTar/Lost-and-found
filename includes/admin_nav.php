<?php
// Shared Admin Sub-Navigation Component
// Parameters expected: $active_tab (e.g. 'dashboard', 'claims', 'reports', 'items', 'users')
// Also calculates pending report count for badge indicator

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
}

$pending_reports_badge = (int)($pdo->query("SELECT COUNT(*) FROM item_reports WHERE status = 'pending'")->fetchColumn() ?? 0);
$active_tab = $active_tab ?? 'dashboard';
?>
<div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200/80 text-xs font-medium flex-wrap shadow-2xs">
    
    <!-- 1. แดชบอร์ด -->
    <a href="<?php echo $base_url; ?>/pages/admin/admin_dashboard.php" 
       class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 <?php echo $active_tab === 'dashboard' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/80 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'; ?>">
        <span>แดชบอร์ด</span>
    </a>

    <!-- 2. คำร้อง Claim -->
    <a href="<?php echo $base_url; ?>/pages/admin/admin_claims.php" 
       class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 <?php echo $active_tab === 'claims' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/80 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'; ?>">
        <span>คำร้อง Claim</span>
    </a>

    <!-- 3. การรายงาน (พร้อม Badge แจ้งเตือนสีแดง) -->
    <a href="<?php echo $base_url; ?>/pages/admin/admin_reports.php" 
       class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 relative <?php echo $active_tab === 'reports' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/80 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'; ?>">
        <span>การรายงาน</span>
        <?php if ($pending_reports_badge > 0): ?>
            <span class="px-1.5 py-0.5 text-[10px] font-bold bg-red-500 text-white rounded-full leading-none shadow-2xs">
                <?php echo $pending_reports_badge; ?>
            </span>
        <?php endif; ?>
    </a>

    <!-- 4. จัดการประกาศ -->
    <a href="<?php echo $base_url; ?>/pages/admin/admin_items.php" 
       class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 <?php echo $active_tab === 'items' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/80 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'; ?>">
        <span>จัดการประกาศ</span>
    </a>

    <!-- 5. ผู้ใช้งาน -->
    <a href="<?php echo $base_url; ?>/pages/admin/admin_users.php" 
       class="px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 <?php echo $active_tab === 'users' ? 'bg-white text-slate-900 shadow-sm border border-slate-200/80 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'; ?>">
        <span>ผู้ใช้งาน</span>
    </a>

</div>

