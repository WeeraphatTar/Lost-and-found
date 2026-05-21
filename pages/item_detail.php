<?php
$base_url = 'http://localhost/Lost_found';
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../includes/matching_helper.php';

// รับค่า ID จาก URL
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($item_id <= 0) {
    $_SESSION['error'] = "ไม่พบรายการประกาศที่คุณต้องการ";
    echo "<script>window.location.href = '".$base_url."/pages/browse.php';</script>";
    exit;
}

try {
    // ดึงข้อมูล Item พร้อมข้อมูลผู้ใช้ที่โพสต์
    $sql = "SELECT i.*, u.first_name, u.last_name 
            FROM items i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();

    if (!$item) {
        $_SESSION['error'] = "ไม่พบรายการประกาศนี้ในระบบ หรืออาจถูกลบไปแล้ว";
        echo "<script>window.location.href = '".$base_url."/pages/browse.php';</script>";
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Database Error: " . $e->getMessage();
    echo "<script>window.location.href = '".$base_url."/pages/browse.php';</script>";
    exit;
}

// Map ประเภทและหมวดหมู่เป็นภาษาไทย
$type_label = ($item['type'] === 'lost') ? 'ตามหาของหาย' : 'ประกาศพบของ';
$type_color = ($item['type'] === 'lost') ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';

$categories = [
    'electronics' => 'อุปกรณ์อิเล็กทรอนิกส์',
    'walletandcash' => 'กระเป๋าสตางค์และเงินสด', 
    'cardanddocument' => 'บัตรและเอกสารสำคัญ',
    'keyandkeycard' => 'กุญแจและคีย์การ์ด',
    'bagandluggage' => 'กระเป๋าและสัมภาระ',
    'clothingandjewelry' => 'เครื่องแต่งกายและเครื่องประดับ',
    'studymaterialandstationery' => 'อุปกรณ์การเรียนและเครื่องเขียน',
    'personalbelonging' => 'ของใช้ส่วนตัว',
    'vehicleandaccessory' => 'ยานพาหนะและอุปกรณ์เสริม',
    'others' => 'อื่นๆ'
];
$category_label = isset($categories[$item['category']]) ? $categories[$item['category']] : 'อื่นๆ';

$date_formatted = date('d M Y', strtotime($item['event_date']));
$date_label = ($item['type'] === 'lost') ? 'วันที่คาดว่าหาย' : 'วันที่พบเจอ';
$posted_date = date('d M Y H:i', strtotime($item['created_at']));
?>

<div class="py-12 bg-background flex-grow">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- ปุ่มกลับ -->
        <a href="<?php echo $base_url; ?>/pages/browse.php" class="inline-flex items-center text-primary hover:text-accent font-medium mb-6 transition">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            กลับหน้ารายการประกาศ
        </a>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col md:flex-row">
            
            <!-- ฝั่งซ้าย: รูปภาพ -->
            <div class="md:w-5/12 bg-slate-100 flex items-center justify-center border-b md:border-b-0 md:border-r border-gray-200 aspect-[4/3] md:aspect-auto">
                <?php if (!empty($item['image_path'])): ?>
                    <img src="<?php echo $base_url . '/' . htmlspecialchars($item['image_path']); ?>" alt="รูปภาพสิ่งของ" class="w-full h-full object-contain">
                <?php else: ?>
                    <div class="py-32 flex flex-col items-center justify-center text-gray-400">
                        <svg class="w-24 h-24 mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                        <p class="text-sm">ไม่มีรูปภาพประกอบ</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ฝั่งขวา: ข้อมูลรายละเอียด -->
            <div class="md:w-7/12 p-6 sm:p-10 flex flex-col">
                <div class="flex justify-between items-start mb-4">
                    <span class="inline-block px-3 py-1 rounded <?php echo $type_color; ?> text-sm font-semibold">
                        <?php echo $type_label; ?>
                    </span>
                    <span class="text-xs text-gray-600">โพสต์เมื่อ: <?php echo $posted_date; ?></span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-bold text-primary mb-6"><?php echo htmlspecialchars($item['title']); ?></h1>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                    <div class="bg-gray-50 p-4 rounded-md border border-gray-100">
                        <div class="text-xs text-gray-500 mb-1">หมวดหมู่</div>
                        <div class="font-medium text-gray-800 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                            <?php echo $category_label; ?>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-md border border-gray-100">
                        <div class="text-xs text-gray-500 mb-1"><?php echo $date_label; ?></div>
                        <div class="font-medium text-gray-800 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <?php echo $date_formatted; ?>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-md border border-gray-100 sm:col-span-2">
                        <div class="text-xs text-gray-500 mb-1">สถานที่</div>
                        <div class="font-medium text-gray-800 flex items-start">
                            <svg class="w-5 h-5 mr-2 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <?php echo htmlspecialchars($item['location']); ?>
                        </div>
                    </div>
                    <?php 
                    $is_owner = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $item['user_id']);
                    $is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
                    if ($item['type'] === 'found' && !empty($item['storage_location']) && ($is_owner || $is_admin)): 
                    ?>
                        <div class="bg-green-50 p-4 rounded-md border border-green-100 sm:col-span-2">
                            <div class="text-xs text-green-600 mb-1">สถานที่เก็บรักษาของในปัจจุบัน (เฉพาะผู้พบของ)</div>
                            <div class="font-medium text-green-800">
                                <?php echo htmlspecialchars($item['storage_location']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-8">
                    <h3 class="text-lg font-bold text-primary mb-3">รายละเอียดเพิ่มเติม</h3>
                    <div class="text-gray-700 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($item['description']); ?></div>
                </div>

                <?php if (!empty($item['serial_number']) && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $item['user_id']): ?>
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-primary mb-3">เลขซีเรียล / ข้อมูลระบุตัวตน</h3>
                    <div class="text-gray-700 bg-gray-100 px-4 py-2 rounded inline-block font-mono"><?php echo htmlspecialchars($item['serial_number']); ?></div>
                </div>
                <?php endif; ?>

                <div class="mt-auto border-t border-gray-200 pt-6">
                    <h3 class="text-sm font-bold text-gray-500 mb-4 uppercase tracking-wider">ผู้ลงประกาศ</h3>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center font-bold text-xl flex-shrink-0">
                            <?php echo mb_substr($item['first_name'], 0, 1, "UTF-8"); ?>
                        </div>
                        <div class="flex-grow">
                            <div class="font-bold text-gray-800 text-lg leading-tight"><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></div>
                            <!-- <div class="text-gray-500 text-sm mt-0.5">ผู้ลงประกาศ</div> -->
                        </div>
                        
                        <!-- ปุ่มแก้ไขสำหรับเจ้าของโพสต์ -->
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $item['user_id']): ?>
                            <a href="<?php echo $base_url; ?>/pages/edit_item.php?id=<?php echo $item['id']; ?>" class="ml-auto px-4 py-2 bg-yellow-500 text-white font-medium rounded hover:bg-yellow-600 transition shadow-sm flex items-center whitespace-nowrap">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                แก้ไขประกาศ
                            </a>
                        <?php endif; ?>

                        <!-- ปุ่มส่งข้อความ (Chat) -->
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $item['user_id']): ?>
                            <a href="<?php echo $base_url; ?>/pages/chat.php?item_id=<?php echo $item['id']; ?>&receiver_id=<?php echo $item['user_id']; ?>" class="ml-auto px-4 py-2 bg-accent text-white font-medium rounded hover:bg-blue-600 transition shadow-sm flex items-center whitespace-nowrap">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                ส่งข้อความ
                            </a>
                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                            <a href="<?php echo $base_url; ?>/pages/login.php" class="ml-auto px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded hover:bg-gray-300 transition shadow-sm flex items-center whitespace-nowrap">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                เข้าสู่ระบบเพื่อส่งข้อความ
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $item['user_id']): ?>
            <?php
            $target_type = ($item['type'] === 'lost') ? 'found' : 'lost';
            
            // 1. Pre-extract SN from current item (even if not in serial_number field)
            $raw_sn = $item['serial_number'];
            if (empty($raw_sn)) {
                $text_for_sn = $item['title'] . ' ' . $item['description'] . ' ' . $item['secret_description'];
                if (preg_match('/(?:s\/?n|serial|no|id):?\s*([a-z0-9\-\/\.]+)/i', $text_for_sn, $matches)) {
                    $raw_sn = $matches[1];
                }
            }
            $searchable_sn = get_clean_sn_for_sql($raw_sn);

            // 2. Extract multiple tags for broader SQL search
            $normalized_title = normalize_text($item['title']);
            $standardized_title = apply_synonyms($normalized_title);
            $title_kws = preg_split('/\s+/', $standardized_title, -1, PREG_SPLIT_NO_EMPTY);
            
            $search_tag1 = !empty($title_kws[0]) ? "%" . mb_substr($title_kws[0], 0, 4) . "%" : "%NON_EXISTENT%";
            $search_tag2 = !empty($title_kws[1]) ? "%" . mb_substr($title_kws[1], 0, 4) . "%" : $search_tag1;
            // Also keep original first word as a fallback
            $orig_kws = preg_split('/\s+/', $normalized_title, -1, PREG_SPLIT_NO_EMPTY);
            $search_tag3 = !empty($orig_kws[0]) ? "%" . mb_substr($orig_kws[0], 0, 4) . "%" : $search_tag1;

            // ใช้ Logic ที่กว้างขึ้นในการดึงข้อมูลมาคำนวณ Smart Match
            $match_sql = "SELECT id, user_id, title, category, location, serial_number, description, secret_description, event_date, image_path, image_labels 
                          FROM items 
                          WHERE type = ? AND status = 'open' AND user_id != ? 
                          AND (category = ? 
                               OR (serial_number IS NOT NULL AND (serial_number LIKE ? OR serial_number = ?)) 
                               OR title LIKE ? OR title LIKE ? OR title LIKE ?
                               OR description LIKE ? OR description LIKE ?
                               OR secret_description LIKE ? OR secret_description LIKE ?
                               OR location LIKE ?)
                          ORDER BY created_at DESC LIMIT 50";
            
            $search_loc = "%" . mb_substr(normalize_text($item['location']), 0, 4) . "%";
            $search_sn_like = "%" . ($searchable_sn ?? 'NON_EXISTENT_SN') . "%";
            
            $match_stmt = $pdo->prepare($match_sql);
            $match_stmt->execute([
                $target_type, $_SESSION['user_id'], $item['category'], 
                $search_sn_like, $item['serial_number'], 
                $search_tag1, $search_tag2, $search_tag3,
                $search_tag1, $search_tag2, 
                $search_tag1, $search_sn_like,
                $search_loc
            ]);
            $potential_matches = $match_stmt->fetchAll();

            $smart_matches = [];
            foreach ($potential_matches as $m) {
                $score = calculate_match_score($item, $m);
                if ($score >= 40) {
                    $m['match_score'] = $score;
                    $m['confidence_level'] = get_confidence_level($score);
                    $smart_matches[] = $m;
                }
            }

            // เรียงลำดับตามคะแนน
            usort($smart_matches, function($a, $b) {
                return $b['match_score'] <=> $a['match_score'];
            });
            
            $smart_matches = array_slice($smart_matches, 0, 3);
            
            if (count($smart_matches) > 0):
            ?>
            <div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 md:p-8 border border-blue-100 shadow-sm relative overflow-hidden">
                <!-- Decorative icon -->
                <svg class="absolute top-0 right-0 w-32 h-32 text-blue-500 opacity-5 transform translate-x-8 -translate-y-8" fill="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                
                <div class="flex items-center gap-3 mb-6 relative z-10">
                    <div class="bg-blue-100 text-blue-600 p-2 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-primary">ระบบช่วยจับคู่อัจฉริยะ (Smart Matches)</h2>
                        <p class="text-sm text-gray-500">เราพบ <?php echo count($smart_matches); ?> รายการที่อาจจะเป็นสิ่งของที่คุณกำลัง<?php echo ($item['type'] === 'lost') ? 'ตามหา' : 'ตามหาเจ้าของ'; ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 relative z-10">
                    <?php foreach ($smart_matches as $match): ?>
                        <?php
                        $conf = $match['confidence_level'];
                        $score = $match['match_score'];
                        
                        $conf_text = 'ต่ำ';
                        $conf_color = 'bg-gray-100 text-gray-700 border-gray-200';
                        
                        if ($conf === 'High') {
                            $conf_text = 'สูงมาก';
                            $conf_color = 'bg-green-100 text-green-700 border-green-200';
                        } elseif ($conf === 'Medium') {
                            $conf_text = 'ปานกลาง';
                            $conf_color = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                        }
                        
                        $match_img = !empty($match['image_path']) ? $base_url . '/' . htmlspecialchars($match['image_path']) : '';
                        $match_date = date('d M Y', strtotime($match['event_date']));
                        ?>
                        <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $match['id']; ?>" class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm hover:shadow-md hover:border-blue-300 transition group flex flex-col h-full relative">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-xs font-semibold px-2 py-1 rounded border <?php echo $conf_color; ?>">
                                    ความแม่นยำ: <?php echo $conf_text; ?> (<?php echo $score; ?>%)
                                </span>
                                <span class="text-xs text-gray-400"><?php echo $match_date; ?></span>
                            </div>
                            
                            <div class="flex gap-3 flex-1">
                                <?php if ($match_img): ?>
                                    <img src="<?php echo $match_img; ?>" class="w-16 h-16 rounded object-cover flex-shrink-0 bg-gray-100">
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded bg-gray-100 text-gray-400 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle></svg>
                                    </div>
                                <?php endif; ?>
                                <div class="overflow-hidden">
                                    <h4 class="font-bold text-gray-800 text-sm truncate group-hover:text-primary transition"><?php echo htmlspecialchars($match['title']); ?></h4>
                                    <p class="text-xs text-gray-500 mt-1 truncate">📍 <?php echo htmlspecialchars($match['location']); ?></p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
