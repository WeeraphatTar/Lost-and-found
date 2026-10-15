<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';
require_once '../../helpers/messaging_helper.php';
require_once '../../helpers/notification_helper.php';

// Auth Check: Admin only
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเข้าถึงหน้านี้ได้";
    header("Location: " . $base_url . "/pages/login.php");
    exit;
}

$admin_id = (int)$_SESSION['user_id'];
$target_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
$claim_id = isset($_GET['claim_id']) ? (int)$_GET['claim_id'] : 0;
$ref = isset($_GET['ref']) ? trim($_GET['ref']) : '';

// Build parameter string for redirects & form actions
$redirect_params = "user_id={$target_user_id}&item_id={$item_id}";
if ($claim_id > 0) {
    $redirect_params .= "&claim_id={$claim_id}";
}
if (!empty($ref)) {
    $redirect_params .= "&ref=" . urlencode($ref);
}

// Back button configuration based on reference parameters
$back_url = 'admin_messages.php';
$back_text = 'กลับหน้ารายการข้อความ';

if ($claim_id > 0 || $ref === 'claim_detail') {
    $back_text = 'กลับหน้ารายละเอียดคำร้อง';
    $back_url = ($claim_id > 0) ? "admin_claim_detail.php?id=" . $claim_id : "admin_claims.php";
} elseif ($ref === 'admin_claims') {
    $back_text = 'กลับหน้าตรวจสอบและอนุมัติคำร้อง';
    $back_url = "admin_claims.php";
}

// Handle Admin Message Send Action (POST) BEFORE including HTML header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_admin_msg']) && $target_user_id > 0) {
    $content = trim($_POST['content'] ?? '');
    if (!empty($content)) {
        $ins_stmt = $pdo->prepare("INSERT INTO messages (item_id, sender_id, receiver_id, content, created_at, is_read) VALUES (?, ?, ?, ?, NOW(), 0)");
        $ins_stmt->execute([$item_id, $admin_id, $target_user_id, $content]);

        // Fetch item title for notification if available
        $item_name = 'ระบบสิ่งของสูญหาย';
        if ($item_id > 0) {
            $i_stmt = $pdo->prepare("SELECT title FROM items WHERE id = ?");
            $i_stmt->execute([$item_id]);
            $item_row = $i_stmt->fetch();
            if ($item_row && !empty($item_row['title'])) {
                $item_name = $item_row['title'];
            }
        }

        // Send notification to the user
        add_notification(
            $pdo, 
            $target_user_id, 
            "เจ้าหน้าที่แอดมินได้ส่งข้อความสอบถามคุณเกี่ยวกับรายการ [{$item_name}]", 
            "pages/chat.php?item_id={$item_id}&receiver_id={$admin_id}"
        );

        // Email Notification: Notify user about message from Admin
        if (file_exists(__DIR__ . '/../../helpers/email_notification_helper.php')) {
            require_once __DIR__ . '/../../helpers/email_notification_helper.php';
            dispatch_chat_message_email($pdo, $item_id, $admin_id, $target_user_id);
        }

        $_SESSION['success'] = "ส่งข้อความถึงผู้ใช้งานเรียบร้อยแล้ว";
        header("Location: admin_messages.php?" . $redirect_params);
        exit;
    }
}

// Include HTML Header
require_once '../../includes/header.php';

// Mode Selection: Mode B (Direct Chat View) if user_id is provided, otherwise Mode A (Conversations List View)
$is_direct_chat = ($target_user_id > 0);
$target_user = null;
$item_info = null;
$messages = [];

if ($is_direct_chat) {
    // 1. Fetch Target User Profile
    $u_stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, role FROM users WHERE id = ?");
    $u_stmt->execute([$target_user_id]);
    $target_user = $u_stmt->fetch();

    if (!$target_user) {
        $_SESSION['error'] = "ไม่พบข้อมูลผู้ใช้งานที่อ้างถึง";
        header("Location: admin_messages.php");
        exit;
    }

    // 2. Fetch Item Info (if item_id is provided)
    if ($item_id > 0) {
        $i_stmt = $pdo->prepare("SELECT id, title, image_path, category, status FROM items WHERE id = ?");
        $i_stmt->execute([$item_id]);
        $item_info = $i_stmt->fetch();
    }

    // 3. Mark unread messages from this user to admin as read
    mark_messages_as_read($pdo, $item_id, $admin_id, $target_user_id);

    // 4. Fetch message history
    $messages = get_messages($pdo, $item_id, $admin_id, $target_user_id);
} else {
    // Mode A: Conversations list view
    $conversations = get_conversations($pdo, $admin_id);
}
?>

<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'messages';
    require_once '../../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto flex flex-col">
        <div class="w-full flex-grow flex flex-col">

            <!-- Flash Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0"></path></svg>
                    <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($is_direct_chat): ?>
                <!-- ========================================== -->
                <!-- MODE B: DIRECT ADMIN-USER CHAT ROOM VIEW   -->
                <!-- ========================================== -->

                <!-- Header & Navigation -->
                <div class="flex items-center justify-between gap-4 mb-4 pb-3 border-b border-slate-200">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                            <a href="admin_dashboard.php" class="hover:text-slate-800">ศูนย์ควบคุมผู้ดูแลระบบ</a>
                            <span>•</span>
                            <a href="admin_messages.php" class="hover:text-slate-800">รายการข้อความ</a>
                            <span>•</span>
                            <span>แชทสนทนากับผู้ใช้งาน</span>
                        </div>
                        <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                            <span>การสนทนากับคุณ <?php echo htmlspecialchars(trim($target_user['first_name'] . ' ' . $target_user['last_name'])); ?></span>
                        </h1>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="<?php echo htmlspecialchars($back_url); ?>" onclick="if(document.referrer && document.referrer.includes('admin_claim')) { history.back(); return false; }" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition shadow-sm">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            <span><?php echo htmlspecialchars($back_text); ?></span>
                        </a>
                    </div>
                </div>

                <!-- Main Chat Container -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col flex-grow max-h-[calc(100vh-14rem)]">
                    
                    <!-- Chat Header Banner -->
                    <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 border border-indigo-200 text-indigo-700 font-bold rounded-xl flex items-center justify-center text-sm">
                                <?php echo mb_substr($target_user['first_name'], 0, 1, 'UTF-8'); ?>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">
                                    <span><?php echo htmlspecialchars(trim($target_user['first_name'] . ' ' . $target_user['last_name'])); ?></span>
                                </h3>
                                <p class="text-xs text-slate-500">
                                    อีเมล: <span class="font-medium text-slate-700"><?php echo htmlspecialchars($target_user['email']); ?></span> 
                                    <?php if (!empty($target_user['phone'])): ?>
                                        • เบอร์โทร: <span class="font-medium text-slate-700"><?php echo htmlspecialchars($target_user['phone']); ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <?php if ($item_info): ?>
                            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-200 text-xs shadow-2xs">
                                <?php if (!empty($item_info['image_path'])): ?>
                                    <img src="<?php echo $base_url . '/' . htmlspecialchars($item_info['image_path']); ?>" class="w-7 h-7 object-cover rounded-lg border border-slate-200">
                                <?php endif; ?>
                                <div>
                                    <span class="text-[10px] text-slate-400 block font-semibold uppercase">อ้างอิงประกาศ</span>
                                    <span class="font-bold text-slate-800 max-w-[150px] truncate block"><?php echo htmlspecialchars($item_info['title']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Chat Messages List -->
                    <div id="chat-messages-box" class="flex-grow p-4 md:p-6 overflow-y-auto space-y-4 bg-slate-50/50">
                        <?php if (empty($messages)): ?>
                            <div class="my-auto text-center py-12">
                                <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center mx-auto mb-3 text-indigo-500 shadow-2xs border border-slate-200">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                </div>
                                <p class="text-slate-600 font-semibold text-sm">ยังไม่มีประวัติการสนทนาระหว่าง Admin กับผู้ใช้นี้</p>
                                <p class="text-xs text-slate-400 mt-1">พิมพ์ข้อความด้านล่างเพื่อเริ่มการติดต่อสอบถามข้อมูล</p>
                            </div>
                        <?php else: ?>
                            <?php 
                            $last_date = null;
                            foreach ($messages as $msg): 
                                $msg_time = date('H:i', strtotime($msg['created_at']));
                                $msg_date = date('Y-m-d', strtotime($msg['created_at']));
                                $is_admin_sender = ($msg['sender_id'] == $admin_id);
                            ?>
                                <?php if ($msg_date !== $last_date): $last_date = $msg_date; ?>
                                    <div class="flex justify-center my-2">
                                        <span class="text-[11px] text-slate-500 bg-slate-200/80 font-semibold rounded-full px-3 py-0.5">
                                            <?php echo date('d M Y', strtotime($msg_date)); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex <?php echo $is_admin_sender ? 'justify-end' : 'justify-start'; ?>">
                                    <div class="max-w-[80%] sm:max-w-[70%]">
                                        <div class="flex items-center gap-1.5 mb-1 <?php echo $is_admin_sender ? 'justify-end' : 'justify-start'; ?>">
                                            <span class="text-[10px] font-bold text-slate-500">
                                                <?php echo $is_admin_sender ? 'แอดมิน (คุณ)' : htmlspecialchars($msg['sender_name']); ?>
                                            </span>
                                        </div>
                                        <div class="px-4 py-2.5 rounded-2xl shadow-2xs text-xs leading-relaxed <?php echo $is_admin_sender ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-white text-slate-800 border border-slate-200 rounded-tl-none'; ?>">
                                            <?php echo nl2br(htmlspecialchars($msg['content'])); ?>
                                        </div>
                                        <div class="mt-1 px-1 flex <?php echo $is_admin_sender ? 'justify-end' : 'justify-start'; ?>">
                                            <span class="text-[10px] text-slate-400 font-mono"><?php echo $msg_time; ?> น.</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Chat Input Form -->
                    <div class="p-4 bg-white border-t border-slate-200">
                        <form action="admin_messages.php?<?php echo htmlspecialchars($redirect_params); ?>" method="POST" id="admin-chat-form" class="flex items-center gap-3">
                            <input type="hidden" name="send_admin_msg" value="1">
                            <input 
                                type="text"
                                name="content" 
                                id="admin-chat-input"
                                class="flex-grow h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-none focus:border-indigo-600 focus:bg-white transition text-slate-800"
                                placeholder="พิมพ์ข้อความที่นี่"
                                required
                                autocomplete="off"
                            >
                            <button type="submit" class="w-11 h-11 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition shadow-2xs flex items-center justify-center shrink-0 cursor-pointer" title="ส่งข้อความ">
                                <svg class="w-5 h-5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            </button>
                        </form>
                    </div>

                </div>

                <script>
                    const chatBox = document.getElementById('chat-messages-box');
                    if (chatBox) {
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                    const chatInput = document.getElementById('admin-chat-input');
                    const chatForm = document.getElementById('admin-chat-form');
                    if (chatInput && chatForm) {
                        chatInput.addEventListener('keydown', function(e) {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                chatForm.submit();
                            }
                        });
                    }
                </script>

            <?php else: ?>
                <!-- ========================================== -->
                <!-- MODE A: DEFAULT CONVERSATIONS LIST VIEW    -->
                <!-- ========================================== -->

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
                                $other_user_id = ($conv['sender_id'] == $admin_id) ? $conv['receiver_id'] : $conv['sender_id'];
                                $is_unread = ($conv['unread_count'] > 0);
                                $last_msg_date = date('d M Y H:i', strtotime($conv['created_at']));
                                $item_img = !empty($conv['item_image']) ? $base_url . '/' . htmlspecialchars($conv['item_image']) : '';
                            ?>
                            <a href="admin_messages.php?user_id=<?php echo $other_user_id; ?>&item_id=<?php echo $conv['item_id']; ?>" class="block p-4 sm:p-6 hover:bg-slate-50 transition relative <?php echo $is_unread ? 'bg-blue-50/30' : ''; ?>">
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
                                            <?php echo ($conv['sender_id'] == $admin_id) ? '<span class="text-slate-400 italic">คุณ: </span>' : ''; ?>
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

            <?php endif; ?>

        </div>
    </main>
</div>

<?php 
require_once '../../includes/footer.php'; 
ob_end_flush();
?>
