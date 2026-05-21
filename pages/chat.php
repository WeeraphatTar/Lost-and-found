<?php
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/messaging_helper.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบเพื่อใช้งานระบบแชท";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$current_user_id = $_SESSION['user_id'];
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
$receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;

if ($item_id <= 0 || $receiver_id <= 0) {
    header("Location: messages.php");
    exit;
}

// ตรวจสอบข้อมูลสิ่งของ
$stmt = $pdo->prepare("SELECT title, image_path, user_id FROM items WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    $_SESSION['error'] = "ไม่พบข้อมูลสิ่งของที่อ้างถึง";
    header("Location: messages.php");
    exit;
}

// ตรวจสอบข้อมูลผู้รับ
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$receiver_id]);
$other_user = $stmt->fetch();

if (!$other_user) {
    $_SESSION['error'] = "ไม่พบผู้ใช้ที่คุณต้องการสนทนาด้วย";
    header("Location: messages.php");
    exit;
}

// Mark messages as read
mark_messages_as_read($pdo, $item_id, $current_user_id, $receiver_id);

// Get message history
$messages = get_messages($pdo, $item_id, $current_user_id, $receiver_id);
?>

<div class="py-8 bg-background flex-grow flex flex-col">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 w-full flex flex-col h-full min-h-[600px]">
        
        <!-- Chat Header -->
        <div class="bg-white rounded-t-xl shadow-sm border border-gray-200 p-4 sm:p-6 flex items-center gap-4">
            <a href="messages.php" class="p-2 hover:bg-gray-100 rounded-full transition">
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
                    คุยกับ: <?php echo htmlspecialchars($other_user['first_name'] . ' ' . $other_user['last_name']); ?>
                </div>
            </div>
            
            <a href="item_detail.php?id=<?php echo $item_id; ?>" class="hidden sm:inline-flex px-4 py-2 text-sm font-medium text-primary bg-gray-50 border border-gray-200 rounded-md hover:bg-gray-100 transition shadow-sm">
                ดูประกาศ
            </a>
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
            <form action="../actions/send_message_action.php" method="POST" class="flex gap-2">
                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                <input type="hidden" name="receiver_id" value="<?php echo $receiver_id; ?>">
                
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
