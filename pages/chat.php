<?php
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../helpers/messaging_helper.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบเพื่อใช้งานระบบแชท";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$current_user_id = $_SESSION['user_id'];
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
$receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;
$ref = isset($_GET['ref']) ? trim($_GET['ref']) : '';
$claim_id = isset($_GET['claim_id']) ? (int)$_GET['claim_id'] : 0;
$tab = isset($_GET['tab']) ? trim($_GET['tab']) : '';

// คำนวณหา Back URL ตามบริบทการเดินทางของผู้ใช้ (Contextual Back Navigation)
$back_url = "messages.php";
if ($ref === 'claim_detail' && $claim_id > 0) {
    $back_url = "claim_detail.php?id=" . $claim_id;
} elseif ($ref === 'claims') {
    $back_url = !empty($tab) ? "claims.php?tab=" . urlencode($tab) : "claims.php";
} elseif ($ref === 'item_detail' && $item_id > 0) {
    $back_url = "item_detail.php?id=" . $item_id;
} elseif ($ref === 'admin_claims') {
    $back_url = "admin/admin_claims.php";
} elseif ($ref === 'messages') {
    $back_url = "messages.php";
}

// ดึงข้อมูลคู่สนทนาจริงจาก claim_id หากถูกส่งมา (เช่น Admin เปิดดูประวัติแชทของคำร้อง)
$chat_user1 = 0;
$chat_user2 = 0;

if ($claim_id > 0) {
    $claim_info_stmt = $pdo->prepare("SELECT finder_id, claimant_id FROM claims WHERE id = ?");
    $claim_info_stmt->execute([$claim_id]);
    $claim_info = $claim_info_stmt->fetch();
    if ($claim_info) {
        $chat_user1 = (int)$claim_info['finder_id'];
        $chat_user2 = (int)$claim_info['claimant_id'];
        if ($receiver_id <= 0) {
            $receiver_id = ($current_user_id == $chat_user1) ? $chat_user2 : $chat_user1;
        }
    }
}

if ($item_id <= 0 || ($receiver_id <= 0 && ($chat_user1 <= 0 || $chat_user2 <= 0))) {
    header("Location: messages.php");
    exit;
}

// ตรวจสอบข้อมูลสิ่งของ
$stmt = $pdo->prepare("SELECT type, title, image_path, user_id FROM items WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    $_SESSION['error'] = "ไม่พบข้อมูลสิ่งของที่อ้างถึง";
    header("Location: messages.php");
    exit;
}

// ตรวจสอบสิทธิ์การเข้าถึงแชทสำหรับประกาศประเภท 'found':
// เฉพาะเจ้าของโพสต์ (Finder), ผู้ยื่น Claim บนสิ่งของนี้, หรือ Admin เท่านั้นที่แชทได้
$is_item_owner = ($item['user_id'] == $current_user_id);
$is_admin_user = (($_SESSION['user_role'] ?? '') === 'admin');

if ($chat_user1 <= 0 || $chat_user2 <= 0) {
    $chat_user1 = $current_user_id;
    $chat_user2 = $receiver_id;
}

$is_admin_monitor = ($is_admin_user && $current_user_id != $chat_user1 && $current_user_id != $chat_user2);

if ($item['type'] === 'found' && !$is_item_owner && !$is_admin_user) {
    // ตรวจสอบว่าผู้ใช้คนนี้ได้ยื่น Claim ไว้บนสิ่งของนี้หรือไม่
    $claim_stmt = $pdo->prepare("SELECT id FROM claims WHERE item_id = ? AND claimant_id = ? LIMIT 1");
    $claim_stmt->execute([$item_id, $current_user_id]);
    $has_claimed = $claim_stmt->fetch();

    if (!$has_claimed) {
        $_SESSION['error'] = "คุณต้องยื่นคำร้องขอ Claim สิ่งของชิ้นนี้ก่อน จึงจะสามารถเปิดแชทสนทนากับผู้พบของได้";
        echo "<script>window.location.href = '".$base_url."/pages/item_detail.php?id={$item_id}';</script>";
        exit;
    }
}

// ตรวจสอบข้อมูลคู่สนทนาสำหรับแสดงใน UI
$u1_stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$u1_stmt->execute([$chat_user1]);
$u1_info = $u1_stmt->fetch();

$u2_stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$u2_stmt->execute([$chat_user2]);
$u2_info = $u2_stmt->fetch();

if (!$u1_info || !$u2_info) {
    $_SESSION['error'] = "ไม่พบข้อมูลคู่สนทนาที่อ้างถึง";
    header("Location: messages.php");
    exit;
}

// Mark messages as read (เฉพาะผู้ใช้งานจริง)
if (!$is_admin_monitor) {
    mark_messages_as_read($pdo, $item_id, $current_user_id, $receiver_id);
}

// Get message history
$messages = get_messages($pdo, $item_id, $chat_user1, $chat_user2);
?>

<div class="py-8 bg-background flex-grow flex flex-col">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 w-full flex flex-col h-full min-h-[600px]">
        
        <!-- Chat Header -->
        <div class="bg-white rounded-t-xl shadow-sm border border-gray-200 p-4 sm:p-6 flex items-center gap-4">
            <a href="<?php echo htmlspecialchars($back_url); ?>" class="p-2 hover:bg-gray-100 rounded-full transition" title="ย้อนกลับ">
                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            
            <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 border border-gray-200">
                <?php if (!empty($item['image_path'])): ?>
                    <img src="<?php echo $base_url . '/' . htmlspecialchars($item['image_path']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="flex-grow">
                <div class="text-xs text-accent font-bold uppercase tracking-wider mb-0.5">การสนทนาเกี่ยวกับสิ่งของ</div>
                <h2 class="font-bold text-primary truncate leading-tight"><?php echo htmlspecialchars($item['title']); ?></h2>
                <div class="text-sm text-gray-500 flex items-center">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    <?php if ($is_admin_monitor): ?>
                        คู่สนทนา: <span class="font-semibold text-gray-700 ml-1"><?php echo htmlspecialchars($u1_info['first_name'] . ' ' . $u1_info['last_name']); ?></span> <span class="mx-1 text-gray-400">↔</span> <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($u2_info['first_name'] . ' ' . $u2_info['last_name']); ?></span>
                    <?php else: ?>
                        คุยกับ: <?php echo htmlspecialchars(($current_user_id == $chat_user1 ? $u2_info['first_name'] . ' ' . $u2_info['last_name'] : $u1_info['first_name'] . ' ' . $u1_info['last_name'])); ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php
            // ตรวจสอบ Claim ระหว่างคู่นี้เกี่ยวกับสิ่งของนี้
            $chat_claim_stmt = $pdo->prepare("SELECT id, status FROM claims WHERE item_id = ? AND (claimant_id = ? OR finder_id = ?) ORDER BY id DESC LIMIT 1");
            $chat_claim_stmt->execute([$item_id, $chat_user1, $chat_user2]);
            $chat_claim = $chat_claim_stmt->fetch();
            $is_claim_closed = ($chat_claim && in_array($chat_claim['status'], ['completed', 'rejected', 'cancelled', 'cancelled_mismatch']));
            ?>

            <div class="flex items-center gap-2 flex-wrap">
                <?php if ($chat_claim): ?>
                    <a href="claim_detail.php?id=<?php echo $chat_claim['id']; ?>" class="px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-100 hover:bg-emerald-200 rounded-lg transition border border-emerald-200 flex items-center gap-1">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        ดูสถานะ Claim (#CLM-<?php echo str_pad($chat_claim['id'], 5, '0', STR_PAD_LEFT); ?>)
                    </a>
                <?php endif; ?>

                <a href="item_detail.php?id=<?php echo $item_id; ?>" class="hidden sm:inline-flex px-4 py-2 text-sm font-medium text-primary bg-gray-50 border border-gray-200 rounded-md hover:bg-gray-100 transition shadow-sm">
                    ดูประกาศ
                </a>
            </div>
        </div>

        <?php if ($is_admin_monitor): ?>
            <div class="bg-gray-100/90 border-x border-b border-gray-200 px-4 sm:px-6 py-2.5 text-xs text-gray-500 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    <span class="font-normal text-gray-600">ผู้ดูแลระบบกำลังเข้าชมประวัติการสนทนานี้</span>
                </div>
                <span class="text-[10px] text-gray-400 font-mono tracking-wider uppercase">ADMIN READ-ONLY</span>
            </div>
        <?php endif; ?>

        <!-- Chat History -->
        <div id="chat-messages" class="bg-gray-50 border-x border-gray-200 flex-grow overflow-y-auto p-4 sm:p-6 flex flex-col gap-4 max-h-[500px]">
            <?php if (empty($messages)): ?>
                <div class="my-auto text-center py-12">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 text-accent shadow-sm">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    </div>
                    <p class="text-gray-500 text-sm">ยังไม่มีข้อความ เริ่มการสนทนาได้เลย!</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <?php 
                        $is_me = ($msg['sender_id'] == $current_user_id);
                        $msg_time = date('H:i', strtotime($msg['created_at']));
                    ?>
                    <div class="flex <?php echo $is_me ? 'justify-end' : 'justify-start'; ?>">
                        <div class="max-w-[80%] sm:max-w-[70%]">
                            <div class="flex items-center gap-2 mb-1 <?php echo $is_me ? 'justify-end' : 'justify-start'; ?>">
                                <span class="text-[10px] font-bold text-gray-400 uppercase"><?php echo $is_me ? 'คุณ' : htmlspecialchars($msg['sender_name']); ?></span>
                                <span class="text-[10px] text-gray-400"><?php echo $msg_time; ?></span>
                            </div>
                            <div class="px-4 py-2.5 rounded-2xl shadow-sm text-sm <?php echo $is_me ? 'bg-accent text-white rounded-tr-none' : 'bg-white text-gray-800 border border-gray-200 rounded-tl-none'; ?>">
                                <?php echo nl2br(htmlspecialchars($msg['content'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Chat Input -->
        <div class="bg-white rounded-b-xl shadow-sm border border-gray-200 p-4 border-t-0">
            <?php if ($is_admin_monitor): ?>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 text-center text-xs text-gray-500 font-normal">
                    ผู้ดูแลระบบเข้าชมประวัติการสนทนาในโหมดอ่านอย่างเดียว (Read-Only)
                </div>
            <?php elseif ($is_claim_closed): ?>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-center text-xs font-semibold text-gray-600 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    <span>
                        <?php if ($chat_claim['status'] === 'completed'): ?>
                            การส่งมอบสิ่งของเสร็จสมบูรณ์เรียบร้อยแล้ว การสนทนานี้ถูกปิดโดยอัตโนมัติ
                        <?php else: ?>
                            คำร้องขอ Claim นี้ถูกปิดหรือปฏิเสธแล้ว การสนทนานี้ถูกปิดโดยอัตโนมัติ
                        <?php endif; ?>
                    </span>
                </div>
            <?php else: ?>
                <form action="../actions/send_message_action.php" method="POST" class="flex gap-2">
                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                    <input type="hidden" name="receiver_id" value="<?php echo $receiver_id; ?>">
                    <input type="hidden" name="ref" value="<?php echo htmlspecialchars($ref); ?>">
                    <?php if ($claim_id > 0): ?>
                        <input type="hidden" name="claim_id" value="<?php echo $claim_id; ?>">
                    <?php endif; ?>
                    <?php if (!empty($tab)): ?>
                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                    <?php endif; ?>
                    
                    <textarea 
                        name="content" 
                        rows="1" 
                        placeholder="พิมพ์ข้อความที่นี่..." 
                        class="flex-grow px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent resize-none transition"
                        required
                    ></textarea>
                    
                    <button type="submit" class="p-3 bg-accent text-white rounded-xl hover:bg-blue-600 transition shadow-sm flex items-center justify-center">
                        <svg class="w-6 h-6 transform rotate-0" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path></svg>
                    </button>
                </form>
                <p class="text-[12px] text-gray-400 mt-2 text-center">ระบบจะเก็บการสนทนาไว้เป็นหลักฐานเพื่อความปลอดภัยของทั้งสองฝ่าย</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
    // Scroll chat to bottom on load
    const chatContainer = document.getElementById('chat-messages');
    chatContainer.scrollTop = chatContainer.scrollHeight;

    // Auto-expand textarea
    const tx = document.getElementsByTagName('textarea');
    for (let i = 0; i < tx.length; i++) {
        tx[i].setAttribute('style', 'height:' + (tx[i].scrollHeight) + 'px;overflow-y:hidden;');
        tx[i].addEventListener("input", OnInput, false);
    }

    function OnInput() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        if (this.scrollHeight > 150) {
            this.style.height = '150px';
            this.style.overflowY = 'scroll';
        }
    }
</script>

<?php
require_once '../includes/footer.php';
?>
