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

// ตรวจสอบสิทธิ์การเข้าถึงแชท:
// ป้องกันไม่ให้ Admin แอบเข้าดูประวัติแชทของผู้ใช้งาน เพื่อความเป็นส่วนตัวและความปลอดภัยของข้อมูล (Data Privacy)
$is_item_owner = ($item['user_id'] == $current_user_id);
$is_admin_user = (($_SESSION['user_role'] ?? '') === 'admin');

if ($chat_user1 <= 0 || $chat_user2 <= 0) {
    $chat_user1 = $current_user_id;
    $chat_user2 = $receiver_id;
}

// ตรวจสอบว่าผู้ใช้งานปัจจุบันเป็นคู่สนทนาตัวจริง (Claimant หรือ Finder) หรือไม่
$is_participant = ($current_user_id == $chat_user1 || $current_user_id == $chat_user2);

if (!$is_participant) {
    if ($is_admin_user) {
        $_SESSION['error'] = "ไม่อนุญาตให้ผู้ดูแลระบบ (Admin) เข้าถึงห้องแชทส่วนตัวของผู้ใช้งานเพื่อคุ้มครองความเป็นส่วนตัวของข้อมูล";
        echo "<script>window.location.href = '".$base_url."/pages/admin/admin_claims.php';</script>";
        exit;
    } else {
        $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงห้องแชทนี้";
        header("Location: messages.php");
        exit;
    }
}

$is_admin_monitor = false;

if (!$is_admin_user) {
    // Check for a claim on this item involving the two participants
    $claim_check_sql = "
        SELECT id, status 
        FROM claims 
        WHERE item_id = ? 
          AND ((claimant_id = ? AND finder_id = ?) OR (claimant_id = ? AND finder_id = ?))
        ORDER BY id DESC LIMIT 1
    ";
    $claim_check_stmt = $pdo->prepare($claim_check_sql);
    $claim_check_stmt->execute([$item_id, $chat_user1, $chat_user2, $chat_user2, $chat_user1]);
    $active_claim = $claim_check_stmt->fetch();

    if ($item['type'] === 'found' || $active_claim) {
        if (!$active_claim) {
            $_SESSION['error'] = "คุณต้องยื่นคำร้องขอรับคืนสิ่งของชิ้นนี้และรอผู้ดูแลระบบ (Admin) อนุมัติก่อน จึงจะสามารถเปิดแชทสนทนากันได้";
            echo "<script>window.location.href = '".$base_url."/pages/item_detail.php?id={$item_id}';</script>";
            exit;
        }

        if (in_array($active_claim['status'], ['pending', 'under_admin_review'])) {
            $_SESSION['error'] = "ต้องได้รับการอนุมัติคำร้องขอรับคืนจากผู้ดูแลระบบ (Admin) ก่อน จึงจะสามารถเปิดแชทสนทนากันได้";
            $back_redirect = ($ref === 'claim_detail' && $claim_id > 0) ? "claim_detail.php?id=" . $claim_id : "claims.php";
            echo "<script>window.location.href = '".$base_url."/pages/" . $back_redirect . "';</script>";
            exit;
        }

        if (in_array($active_claim['status'], ['rejected', 'cancelled_mismatch'])) {
            $_SESSION['error'] = "คำร้องขอรับคืนนี้ถูกปฏิเสธหรือยกเลิกเรียบร้อยแล้ว ไม่สามารถเปิดแชทสนทนาได้";
            $back_redirect = ($ref === 'claim_detail' && $claim_id > 0) ? "claim_detail.php?id=" . $claim_id : "claims.php";
            echo "<script>window.location.href = '".$base_url."/pages/" . $back_redirect . "';</script>";
            exit;
        }
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

$is_admin_mode = (($_SESSION['user_role'] ?? '') === 'admin');
?>

<?php if ($is_admin_mode): ?>
<div class="h-[calc(100vh-5rem)] bg-slate-50 flex flex-col md:flex-row flex-grow font-sans overflow-hidden">
    
    <!-- Left Sidebar Column -->
    <?php 
    $active_tab = 'claims';
    require_once '../includes/admin_sidebar.php'; 
    ?>

    <!-- Right Main Content Area -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        <div class="w-full flex flex-col flex-grow">



<?php else: ?>
<div class="py-8 bg-background flex-grow flex flex-col">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 w-full flex flex-col h-full min-h-[600px]">
<?php endif; ?>

        
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
                    คุยกับ: <?php echo htmlspecialchars(($current_user_id == $chat_user1 ? $u2_info['first_name'] . ' ' . $u2_info['last_name'] : $u1_info['first_name'] . ' ' . $u1_info['last_name'])); ?>
                </div>
            </div>
            
            <?php
            // ตรวจสอบ Claim ระหว่างคู่นี้เกี่ยวกับสิ่งของนี้เพื่อควบคุมการปิดแชท
            $chat_claim_stmt = $pdo->prepare("SELECT id, status FROM claims WHERE item_id = ? AND (claimant_id = ? OR finder_id = ?) ORDER BY id DESC LIMIT 1");
            $chat_claim_stmt->execute([$item_id, $chat_user1, $chat_user2]);
            $chat_claim = $chat_claim_stmt->fetch();
            $is_claim_closed = ($chat_claim && in_array($chat_claim['status'], ['completed', 'rejected', 'cancelled', 'cancelled_mismatch']));
            ?>

            <div class="flex items-center">
                <a href="item_detail.php?id=<?php echo $item_id; ?>" class="px-4 py-2 text-sm font-medium text-primary bg-gray-50 border border-gray-200 rounded-md hover:bg-gray-100 transition shadow-xs">
                    ดูประกาศ
                </a>
            </div>
        </div>

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
                <?php 
                    $thai_months = [1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                    $last_date = null;
                    $today_date = date('Y-m-d');
                    $yesterday_date = date('Y-m-d', strtotime('-1 day'));
                ?>
                <?php foreach ($messages as $msg): ?>
                    <?php 
                        $msg_timestamp = strtotime($msg['created_at']);
                        $msg_date_str = date('Y-m-d', $msg_timestamp);
                        $is_me = ($msg['sender_id'] == $current_user_id);
                        $msg_time = date('H:i', $msg_timestamp) . ' น.';
                    ?>

                    <?php if ($msg_date_str !== $last_date): ?>
                        <?php 
                            if ($msg_date_str === $today_date) {
                                $display_date = 'วันนี้';
                            } elseif ($msg_date_str === $yesterday_date) {
                                $display_date = 'เมื่อวานนี้';
                            } else {
                                $day = date('j', $msg_timestamp);
                                $month = $thai_months[(int)date('n', $msg_timestamp)];
                                $year = (int)date('Y', $msg_timestamp) + 543;
                                $display_date = "วันที่ {$day} {$month} {$year}";
                            }
                            $last_date = $msg_date_str;
                        ?>
                        <div class="flex justify-center my-3">
                            <span class="text-xs text-slate-500 bg-slate-200/70 font-medium rounded-full px-3 py-1 shadow-2xs">
                                <?php echo $display_date; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="flex <?php echo $is_me ? 'justify-end' : 'justify-start'; ?>">
                        <div class="max-w-[80%] sm:max-w-[70%]">
                            <?php if (!$is_me): ?>
                                <div class="flex items-center gap-2 mb-1 justify-start">
                                    <span class="text-[10px] font-bold text-gray-500"><?php echo htmlspecialchars($msg['sender_name']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="px-4 py-2.5 rounded-2xl shadow-xs text-sm <?php echo $is_me ? 'bg-accent text-white rounded-tr-none' : 'bg-white text-gray-800 border border-gray-200 rounded-tl-none'; ?>">
                                <?php echo nl2br(htmlspecialchars($msg['content'])); ?>
                            </div>
                            <div class="mt-1 px-1 flex <?php echo $is_me ? 'justify-end' : 'justify-start'; ?>">
                                <span class="text-[10px] text-slate-400 font-medium"><?php echo $msg_time; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Chat Input -->
        <div class="bg-white rounded-b-xl shadow-sm border border-gray-200 p-4 border-t-0">
            <?php if ($is_claim_closed): ?>
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

<?php if ($is_admin_mode): ?>
        </div>
    </main>
</div>
<?php else: ?>
    </div>
</div>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>

