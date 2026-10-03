<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';
require_once '../../helpers/notification_helper.php';

// Auth Check: Admin only
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$notifications = get_all_notifications($pdo, $user_id, 100);
$unread_only_count = get_unread_count($pdo, $user_id);
?>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'notifications';
    require_once '../../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        <div class="w-full">

            <!-- Breadcrumb Header -->
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                <span>ศูนย์ควบคุมผู้ดูแลระบบ</span>
                <span>•</span>
                <span>รายการแจ้งเตือนระบบ</span>
            </div>
            
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-4 border-b border-slate-200 pb-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">การแจ้งเตือนของผู้ดูแลระบบ</h1>
                    <p class="text-xs text-slate-500 mt-1">คุณมีการแจ้งเตือนที่ยังไม่ได้อ่าน <?php echo $unread_only_count; ?> รายการ</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <?php if ($unread_only_count > 0): ?>
                        <a href="<?php echo $base_url; ?>/actions/notification_action.php?action=mark_all_read" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50 transition text-xs font-semibold flex items-center shadow-xs">
                            <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            อ่านทั้งหมด
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo $base_url; ?>/actions/notification_action.php?action=delete_all_read" onclick="return confirm('คุณต้องการลบการแจ้งเตือนที่อ่านแล้วทั้งหมดใช่หรือไม่?');" class="px-4 py-2 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl hover:bg-rose-100 transition text-xs font-semibold flex items-center shadow-xs">
                        <svg class="w-4 h-4 mr-1.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        ลบที่อ่านแล้ว
                    </a>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
                <?php if (empty($notifications)): ?>
                    <div class="py-20 text-center">
                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-800 mb-1">ไม่มีการแจ้งเตือน</h3>
                        <p class="text-xs text-slate-500">ระบบไม่พบการแจ้งเตือนใหม่ในขณะนี้</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($notifications as $notif): ?>
                            <div class="p-5 sm:p-6 transition hover:bg-slate-50 flex items-start gap-4 <?php echo $notif['is_read'] ? 'opacity-75' : 'bg-blue-50/20'; ?>">
                                <!-- Status Indicator -->
                                <div class="mt-1 flex-shrink-0">
                                    <?php if (!$notif['is_read']): ?>
                                        <div class="w-3 h-3 bg-blue-600 rounded-full shadow-xs"></div>
                                    <?php else: ?>
                                        <div class="w-3 h-3 bg-slate-200 rounded-full"></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Content -->
                                <div class="flex-grow">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="text-xs font-medium text-slate-400"><?php echo date('d M Y • H:i', strtotime($notif['created_at'])); ?></span>
                                        <div class="flex items-center gap-2">
                                            <?php if (!$notif['is_read']): ?>
                                                <a href="<?php echo $base_url; ?>/actions/notification_action.php?action=mark_read&id=<?php echo $notif['id']; ?>" class="text-[10px] font-bold text-blue-600 uppercase tracking-wider hover:underline">ทำเครื่องหมายว่าอ่านแล้ว</a>
                                                <span class="text-slate-300">•</span>
                                            <?php endif; ?>
                                            <a href="<?php echo $base_url; ?>/actions/notification_action.php?action=delete&id=<?php echo $notif['id']; ?>" onclick="return confirm('ต้องการลบการแจ้งเตือนนี้ใช่หรือไม่?');" class="text-[10px] font-bold text-rose-500 uppercase tracking-wider hover:text-rose-700 transition">ลบ</a>
                                        </div>
                                    </div>
                                    
                                    <p class="text-slate-800 text-sm <?php echo $notif['is_read'] ? 'font-normal' : 'font-semibold'; ?> leading-relaxed mb-3">
                                        <?php echo htmlspecialchars($notif['message']); ?>
                                    </p>
                                    
                                    <?php if (!empty($notif['link'])): ?>
                                        <a href="<?php echo $base_url; ?>/actions/notification_action.php?action=read&id=<?php echo $notif['id']; ?>&link=<?php echo urlencode($notif['link']); ?>" class="inline-flex items-center text-xs font-bold text-slate-900 hover:text-blue-600 transition group">
                                            ดูรายละเอียด
                                            <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mt-8 text-center">
                <p class="text-xs text-slate-400">ระบบจะแสดงเฉพาะการแจ้งเตือน 100 รายการล่าสุด</p>
            </div>
        </div>
    </main>
</div>

<?php require_once '../../includes/footer.php'; ?>
