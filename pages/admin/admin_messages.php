<?php
require_once '../../includes/header.php';
require_once '../../config/database.php';
require_once '../../helpers/messaging_helper.php';

// Auth Check: Admin only
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$conversations = get_conversations($pdo, $user_id);
?>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'messages';
    require_once '../../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        <div class="w-full">

            <!-- Breadcrumb Header -->
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                <span>ศูนย์ควบคุมผู้ดูแลระบบ</span>
                <span>•</span>
                <span>รายการข้อความการสนทนา</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-6 flex items-center gap-2">
                ข้อความของผู้ดูแลระบบ
            </h1>

            <?php if (empty($conversations)): ?>
                <!-- Empty State for Admin -->
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-12 text-center my-4">
                    <div class="w-20 h-20 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-5 border border-slate-100">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                    </div>
                    <h2 class="text-lg font-bold text-slate-800 mb-2">ยังไม่มีข้อความติดต่อในระบบ</h2>
                    <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">ขณะนี้ยังไม่มีผู้ใช้งานส่งข้อความหรือเคสสอบถามเข้ามาในระบบ</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden divide-y divide-slate-100">
                    <?php foreach ($conversations as $conv): ?>
                        <?php 
                            $other_user_id = ($conv['sender_id'] == $user_id) ? $conv['receiver_id'] : $conv['sender_id'];
                            $is_unread = ($conv['unread_count'] > 0);
                            $last_msg_date = date('d M Y H:i', strtotime($conv['created_at']));
                            $item_img = !empty($conv['item_image']) ? $base_url . '/' . htmlspecialchars($conv['item_image']) : '';
                        ?>
                        <a href="<?php echo $base_url; ?>/pages/chat.php?item_id=<?php echo $conv['item_id']; ?>&receiver_id=<?php echo $other_user_id; ?>&ref=admin_messages" class="block p-4 sm:p-6 hover:bg-slate-50 transition relative <?php echo $is_unread ? 'bg-blue-50/30' : ''; ?>">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-lg bg-slate-100 flex-shrink-0 overflow-hidden border border-slate-200">
                                    <?php if ($item_img): ?>
                                        <img src="<?php echo $item_img; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-slate-400">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex-grow min-w-0">
                                    <div class="flex justify-between items-start mb-1">
                                        <h3 class="font-bold text-slate-900 truncate pr-4"><?php echo htmlspecialchars($conv['item_title']); ?></h3>
                                        <span class="text-xs text-slate-500 whitespace-nowrap"><?php echo $last_msg_date; ?></span>
                                    </div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($conv['other_first_name'] . ' ' . $conv['other_last_name']); ?></span>
                                    </div>
                                    <p class="text-sm text-slate-600 truncate <?php echo $is_unread ? 'font-semibold text-slate-900' : ''; ?>">
                                        <?php echo ($conv['sender_id'] == $user_id) ? '<span class="text-slate-400 italic">คุณ: </span>' : ''; ?>
                                        <?php echo htmlspecialchars($conv['content']); ?>
                                    </p>
                                </div>

                                <?php if ($is_unread): ?>
                                    <div class="ml-2 flex-shrink-0">
                                        <span class="flex h-3 w-3 rounded-full bg-blue-600"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php require_once '../../includes/footer.php'; ?>
