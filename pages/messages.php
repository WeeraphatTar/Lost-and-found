<?php
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../helpers/messaging_helper.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบเพื่อดูข้อความ";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$conversations = get_conversations($pdo, $user_id);
?>

<div class="py-12 bg-background flex-grow">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-primary mb-8 flex items-center">
            <svg class="w-8 h-8 mr-3 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
            ข้อความของคุณ
        </h1>

        <?php if (empty($conversations)): ?>
            <!-- Empty State for General User -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-10 sm:p-12 text-center my-4">
                <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-5 border border-blue-100 shadow-2xs">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">ยังไม่มีห้องสนทนาที่เปิดใช้งาน</h2>
                <p class="text-sm text-gray-500 max-w-lg mx-auto mb-8 leading-relaxed">
                    ห้องสนทนาจะเปิดขึ้นโดยอัตโนมัติเมื่อคำร้องขอรับคืนสิ่งของ (Claim) ของคุณได้รับการตรวจสอบและอนุมัติจากผู้ดูแลระบบเรียบร้อยแล้วเท่านั้น
                </p>
                <a href="<?php echo $base_url; ?>/pages/claims.php" class="inline-flex items-center px-6 py-3 bg-primary hover:bg-slate-800 text-white text-sm font-semibold rounded-xl transition shadow-sm gap-2">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    ตรวจสอบสถานะคำร้องของฉัน
                </a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden divide-y divide-gray-100">
                <?php 
                    $thai_months = [1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                    $today_date = date('Y-m-d');
                    $yesterday_date = date('Y-m-d', strtotime('-1 day'));
                ?>
                <?php foreach ($conversations as $conv): ?>
                    <?php 
                        $other_user_id = ($conv['sender_id'] == $user_id) ? $conv['receiver_id'] : $conv['sender_id'];
                        $is_unread = ($conv['unread_count'] > 0);
                        
                        $conv_ts = strtotime($conv['created_at']);
                        $conv_date = date('Y-m-d', $conv_ts);
                        
                        if ($conv_date === $today_date) {
                            $last_msg_date = date('H:i', $conv_ts) . ' น.';
                        } elseif ($conv_date === $yesterday_date) {
                            $last_msg_date = 'เมื่อวาน ' . date('H:i', $conv_ts) . ' น.';
                        } else {
                            $day = date('j', $conv_ts);
                            $month = $thai_months[(int)date('n', $conv_ts)];
                            $year = (int)date('Y', $conv_ts) + 543;
                            $last_msg_date = "{$day} {$month} {$year}";
                        }

                        $item_img = !empty($conv['item_image']) ? $base_url . '/' . htmlspecialchars($conv['item_image']) : '';
                    ?>
                    <a href="<?php echo $base_url; ?>/pages/chat.php?item_id=<?php echo $conv['item_id']; ?>&receiver_id=<?php echo $other_user_id; ?>&ref=messages" class="block p-4 sm:p-6 hover:bg-blue-50 transition relative <?php echo $is_unread ? 'bg-blue-50/30' : ''; ?>">
                        <div class="flex items-center gap-4">
                            <!-- Item Image or Icon -->
                            <div class="w-16 h-16 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200">
                                <?php if ($item_img): ?>
                                    <img src="<?php echo $item_img; ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex-grow min-w-0">
                                <div class="flex justify-between items-start mb-1">
                                    <h3 class="font-bold text-gray-900 truncate pr-4"><?php echo htmlspecialchars($conv['item_title']); ?></h3>
                                    <span class="text-xs text-gray-500 whitespace-nowrap"><?php echo $last_msg_date; ?></span>
                                </div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-medium text-primary"><?php echo htmlspecialchars($conv['other_first_name'] . ' ' . $conv['other_last_name']); ?></span>
                                </div>
                                <p class="text-sm text-gray-600 truncate <?php echo $is_unread ? 'font-semibold text-gray-900' : ''; ?>">
                                    <?php echo ($conv['sender_id'] == $user_id) ? '<span class="text-gray-400 italic">คุณ: </span>' : ''; ?>
                                    <?php echo htmlspecialchars($conv['content']); ?>
                                </p>
                            </div>

                            <?php if ($is_unread): ?>
                                <div class="ml-2 flex-shrink-0">
                                    <span class="flex h-3 w-3 rounded-full bg-accent"></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
