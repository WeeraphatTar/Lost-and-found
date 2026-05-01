<?php
$base_url = 'http://localhost/Lost_found';
require_once '../includes/header.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนทำการแก้ไขประกาศ";
    echo "<script>window.location.href = '".$base_url."/pages/login.php';</script>";
    exit;
}

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($item_id <= 0) {
    $_SESSION['error'] = "ไม่พบรายการที่ต้องการแก้ไข";
    echo "<script>window.location.href = '".$base_url."/pages/dashboard.php';</script>";
    exit;
}

try {
    // Fetch item and verify ownership
    $sql = "SELECT * FROM items WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$item_id, $user_id]);
    $item = $stmt->fetch();

    if (!$item) {
        $_SESSION['error'] = "คุณไม่มีสิทธิ์แก้ไขประกาศนี้ หรือไม่พบข้อมูลในระบบ";
        echo "<script>window.location.href = '".$base_url."/pages/dashboard.php';</script>";
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database Error: " . $e->getMessage();
    echo "<script>window.location.href = '".$base_url."/pages/dashboard.php';</script>";
    exit;
}

$type = $item['type'];
$type_label = ($type === 'lost') ? 'แก้ไขประกาศของหาย' : 'แก้ไขประกาศพบของ';
?>

<div class="py-12 bg-background flex-grow">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white p-8 sm:p-10 rounded-lg shadow-sm border border-gray-200">
            <h2 class="text-2xl font-bold text-center text-primary mb-2"><?php echo $type_label; ?></h2>
            <p class="text-center text-important mb-8">คุณสามารถอัปเดตข้อมูลประกาศของคุณได้ที่นี่</p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="p-4 mb-6 text-sm text-red-700 bg-red-100 border border-red-300 rounded-md">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo $base_url; ?>/actions/edit_post_action.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                <input type="hidden" name="type" value="<?php echo $type; ?>">
                
                <!-- ขั้นตอนที่ 1: ข้อมูลพื้นฐาน -->
                <fieldset class="border border-gray-200 p-6 rounded-md bg-gray-50">
                    <legend class="px-2 text-lg font-semibold text-primary bg-white border border-gray-200 rounded px-4 py-1 shadow-sm">ขั้นตอนที่ 1: ข้อมูลพื้นฐาน</legend>
                    <div class="space-y-5 mt-4">
                        <div>
                            <label class="block font-medium text-primary mb-1">ชื่อเรียกทรัพย์สิน <span class="asterisk text-red-500">*</span></label>
                            <input type="text" name="item_name" value="<?php echo htmlspecialchars($item['title']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" required>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-5">
                            <div class="flex-1">
                                <label class="block font-medium text-primary mb-1">หมวดหมู่ <span class="asterisk text-red-500">*</span></label>
                                <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" required>
                                    <?php
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
                                    foreach ($categories as $val => $label) {
                                        $selected = ($item['category'] === $val) ? 'selected' : '';
                                        echo "<option value=\"$val\" $selected>$label</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="flex-1">
                                <label class="block font-medium text-primary mb-1"><?php echo ($type === 'lost') ? 'วันที่หาย (โดยประมาณ)' : 'วันที่พบเจอ'; ?> <span class="asterisk text-red-500">*</span></label>
                                <input type="date" name="event_date" value="<?php echo $item['event_date']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" required>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- ขั้นตอนที่ 2: รายละเอียดเชิงลึก -->
                <fieldset class="border border-gray-200 p-6 rounded-md bg-gray-50">
                    <legend class="px-2 text-lg font-semibold text-primary bg-white border border-gray-200 rounded px-4 py-1 shadow-sm">ขั้นตอนที่ 2: รายละเอียด</legend>
                    <div class="space-y-5 mt-4">
                        <div>
                            <label class="block font-medium text-primary mb-1">รายละเอียด <span class="asterisk text-red-500">*</span></label>
                            <textarea name="description" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" required><?php echo htmlspecialchars($item['description']); ?></textarea>
                        </div>
                        
                        <?php if ($type === 'found'): ?>
                        <div>
                            <label class="block font-medium text-primary mb-1">รายละเอียดลับ (Secret Details)</label>
                            <textarea name="secret_description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" placeholder="สิ่งที่จะมีแต่เจ้าของเท่านั้นที่รู้"><?php echo htmlspecialchars($item['secret_description'] ?? ''); ?></textarea>
                        </div>
                        <?php endif; ?>

                        <div>
                            <label class="block font-medium text-primary mb-1">เลขซีเรียล หรือ ข้อมูลระบุตัวตน (ถ้ามี)</label>
                            <input type="text" name="serial_number" value="<?php echo htmlspecialchars($item['serial_number'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white">
                        </div>
                    </div>
                </fieldset>

                <!-- ขั้นตอนที่ 3: สถานที่ -->
                <fieldset class="border border-gray-200 p-6 rounded-md bg-gray-50">
                    <legend class="px-2 text-lg font-semibold text-primary bg-white border border-gray-200 rounded px-4 py-1 shadow-sm">ขั้นตอนที่ 3: สถานที่</legend>
                    <div class="space-y-5 mt-4">
                        <div>
                            <label class="block font-medium text-primary mb-1">สถานที่ <span class="asterisk text-red-500">*</span></label>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($item['location']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" required>
                        </div>
                        
                        <?php if ($type === 'found'): ?>
                        <div>
                            <label class="block font-medium text-primary mb-1">สถานที่เก็บรักษาของปัจจุบัน <span class="asterisk text-red-500">*</span></label>
                            <input type="text" name="storage_location" value="<?php echo htmlspecialchars($item['storage_location'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" required>
                        </div>
                        <?php endif; ?>
                    </div>
                </fieldset>

                <!-- ขั้นตอนที่ 4: การอัปโหลดรูปภาพและช่องทางติดต่อ -->
                <fieldset class="border border-gray-200 p-6 rounded-md bg-gray-50">
                    <legend class="px-2 text-lg font-semibold text-primary bg-white border border-gray-200 rounded px-4 py-1 shadow-sm">ขั้นตอนที่ 4: รูปภาพและติดต่อ</legend>
                    <div class="space-y-5 mt-4">
                        <div>
                            <label class="block font-medium text-primary mb-1">รูปภาพปัจจุบัน</label>
                            <?php if (!empty($item['image_path'])): ?>
                                <div class="mb-3">
                                    <img src="<?php echo $base_url . '/' . htmlspecialchars($item['image_path']); ?>" class="w-32 h-32 object-cover rounded border border-gray-200">
                                </div>
                            <?php else: ?>
                                <p class="text-sm text-gray-500 mb-3">ไม่มีรูปภาพ</p>
                            <?php endif; ?>
                            
                            <label class="block font-medium text-primary mb-1">เปลี่ยนรูปภาพ (อัปโหลดใหม่เพื่อเปลี่ยน)</label>
                            <input type="file" name="item_image" class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white text-sm" accept="image/*">
                        </div>
                        <div>
                            <label class="block font-medium text-primary mb-1">เบอร์โทรศัพท์ติดต่อ <span class="asterisk text-red-500">*</span></label>
                            <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($item['contact_phone']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white" required>
                        </div>
                    </div>
                </fieldset>

                <div class="pt-4 flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?php echo $base_url; ?>/pages/item_detail.php?id=<?php echo $item_id; ?>" class="w-full sm:w-auto py-3 px-8 bg-gray-200 text-gray-700 font-medium text-lg rounded-md hover:bg-gray-300 transition text-center">ยกเลิก</a>
                    <button type="submit" class="w-full sm:w-auto sm:min-w-[200px] py-3 px-8 bg-primary text-white font-medium text-lg rounded-md hover:bg-secondary transition shadow-sm">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
