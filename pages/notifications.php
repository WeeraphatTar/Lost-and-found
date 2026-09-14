<?php
$base_url = 'http://localhost/lost-and-found';
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../helpers/notification_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบเพื่อดูการแจ้งเตือน";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$notifications = get_all_notifications($pdo, $user_id, 100);
$unread_only_count = get_unread_count($pdo, $user_id);
?>

<div class="py-12 bg-background flex-grow">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-primary">การแจ้งเตือนทั้งหมด</h1>
                <p class="text-gray-500 mt-1">คุณมีการแจ้งเตือนที่ยังไม่ได้อ่าน <?php echo $unread_only_count; ?> รายการ</p>
            </div>
            
            <div class="flex items-center gap-3">
                <?php if ($unread_only_count > 0): ?>
                    <a href="<?php echo $base_url; ?>/actions/notification_action.php?action=mark_all_read" class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition text-sm font-medium flex items-center shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        อ่านทั้งหมด
                    </a>
                <?php endif; ?>
                
                <a href="<?php echo $base_url; ?>/actions/notification_action.php?action=delete_all_read" onclick="return confirm('คุณต้องการลบการแจ้งเตือนที่อ่านแล้วทั้งหมดใช่หรือไม่?');" class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition text-sm font-medium flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    ลบที่อ่านแล้ว
                </a>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <?php if (empty($notifications)): ?>
                <div class="py-20 text-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">ไม่มีการแจ้งเตือน</h3>
                    <p class="text-gray-500">คุณยังไม่มีประวัติการแจ้งเตือนในขณะนี้</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="p-5 sm:p-6 transition hover:bg-gray-50 flex items-start gap-4 <?php echo $notif['is_read'] ? 'opacity-75' : 'bg-blue-50/30'; ?>">
                            <!-- Status Indicator -->
                            <div class="mt-1 flex-shrink-0">
                                <?php if (!$notif['is_read']): ?>
                                    <div class="w-3 h-3 bg-accent rounded-full shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
                                <?php else: ?>
                                    <div class="w-3 h-3 bg-gray-200 rounded-full"></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-grow">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-xs font-medium text-gray-400"><?php echo date('d M Y • H:i', strtotime($notif['created_at'])); ?></span>
                                    <div class="flex items-center gap-2">
                                        <?php if (!$notif['is_read']): ?>
                                            <a href="<?php echo $base_url; ?>/actions/notification_action.php?action=mark_read&id=<?php echo $notif['id']; ?>" class="text-[10px] font-bold text-accent uppercase tracking-wider hover:underline">ทำเครื่องหมายว่าอ่านแล้ว</a>
                                            <span class="text-gray-300">•</span>
                                        <?php endif; ?>
                                        <a href="<?php echo $base_url; ?>/actions/notification_action.php?action=delete&id=<?php echo $notif['id']; ?>" onclick="return confirm('ต้องการลบการแจ้งเตือนนี้ใช่หรือไม่?');" class="text-[10px] font-bold text-red-400 uppercase tracking-wider hover:text-red-600 transition">ลบ</a>
                                    </div>
                                </div>
                                
                                <p class="text-gray-800 <?php echo $notif['is_read'] ? 'font-normal' : 'font-semibold'; ?> leading-relaxed mb-3">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </p>
                                
                                <?php if (!empty($notif['link'])): ?>
                                    <a href="<?php echo $base_url; ?>/actions/notification_action.php?action=read&id=<?php echo $notif['id']; ?>&link=<?php echo urlencode($notif['link']); ?>" class="inline-flex items-center text-sm font-bold text-primary hover:text-accent transition group">
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
        
        <!-- Footer Info -->
        <div class="mt-8 text-center">
            <p class="text-xs text-gray-400">ระบบจะแสดงเฉพาะการแจ้งเตือน 100 รายการล่าสุด</p>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
