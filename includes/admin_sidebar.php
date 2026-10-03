<?php
// Shared Admin Left Sidebar Component
// Expected variable: $active_tab (e.g. 'dashboard', 'items_lost', 'items_found', 'claims', 'reports', 'users', 'export', 'settings')

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
}

// Fetch pending reports badge count
$pending_reports_badge = (int)($pdo->query("SELECT COUNT(*) FROM item_reports WHERE status = 'pending'")->fetchColumn() ?? 0);
$active_tab = $active_tab ?? 'dashboard';
?>

<aside class="w-full md:w-64 bg-slate-900 text-slate-300 flex-shrink-0 flex flex-col justify-between md:sticky md:top-20 md:h-[calc(100vh-5rem)] border-r border-slate-800 font-sans print:hidden select-none">
    
    <!-- Top Navigation Menu Area -->
    <div class="p-4 flex-1 overflow-y-auto space-y-1">
        <div class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">เมนูหลัก</div>

        <!-- 1. ภาพรวมระบบ -->
        <a href="<?php echo $base_url; ?>/pages/admin/admin_dashboard.php" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?php echo $active_tab === 'dashboard' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            <span>ภาพรวมระบบ</span>
        </a>

        <!-- 2. รายการของหาย -->
        <a href="<?php echo $base_url; ?>/pages/admin/admin_itemslost.php" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?php echo ($active_tab === 'items_lost' || ($active_tab === 'items' && ($_GET['type'] ?? '') === 'lost')) ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>รายการของหาย</span>
        </a>

        <!-- 3. รายการของที่เก็บได้ -->
        <a href="<?php echo $base_url; ?>/pages/admin/admin_itemsfound.php" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?php echo ($active_tab === 'items_found' || ($active_tab === 'items' && ($_GET['type'] ?? '') === 'found')) ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            <span>รายการของที่เก็บได้</span>
        </a>

        <!-- 4. คำร้องขอรับคืน -->
        <a href="<?php echo $base_url; ?>/pages/admin/admin_claims.php" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?php echo $active_tab === 'claims' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>คำร้องขอรับคืน</span>
        </a>

        <!-- 5. การรายงาน (พร้อม Badge) -->
        <a href="<?php echo $base_url; ?>/pages/admin/admin_reports.php" 
           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?php echo $active_tab === 'reports' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>การรายงาน</span>
            </div>
            <?php if ($pending_reports_badge > 0): ?>
                <span class="px-2 py-0.5 text-[10px] font-bold bg-rose-500 text-white rounded-full leading-none shadow-sm animate-pulse">
                    <?php echo $pending_reports_badge; ?>
                </span>
            <?php endif; ?>
        </a>

        <!-- 6. ผู้ใช้งาน -->
        <a href="<?php echo $base_url; ?>/pages/admin/admin_users.php" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?php echo $active_tab === 'users' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span>ผู้ใช้งาน</span>
        </a>

        <div class="pt-4 pb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400 border-t border-slate-800/80 mt-4">เครื่องมือระบบ</div>

        <!-- 7. ส่งออกรายงาน -->
        <a href="<?php echo $base_url; ?>/pages/admin/admin_export.php" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?php echo $active_tab === 'export' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
            <svg class="w-5 h-5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 01-2-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span>ส่งออกรายงาน</span>
        </a>

        <!-- 8. ตั้งค่าระบบ -->
        <a href="<?php echo $base_url; ?>/pages/admin/admin_settings.php" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?php echo $active_tab === 'settings' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
            <svg class="w-5 h-5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span>ตั้งค่าระบบ</span>
        </a>
    </div>

    <!-- Sidebar Footer / Brand (Pinned to Bottom) -->
    <div class="mt-auto p-4 border-t border-slate-800/60 flex items-center gap-3 flex-shrink-0 bg-slate-900/90 backdrop-blur-xs">
        <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center text-white font-bold shadow-md shadow-blue-500/20 flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
            </svg>
        </div>
        <div>
            <h2 class="font-bold text-white text-sm tracking-wide leading-tight">Admin Portal</h2>
            <p class="text-xs text-slate-400">ระบบจัดการผู้ดูแลระบบ</p>
        </div>
    </div>

</aside>
