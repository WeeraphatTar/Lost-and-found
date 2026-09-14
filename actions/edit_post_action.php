<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนทำการแก้ไขประกาศ";
    header("Location: ../pages/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    
    // Ownership check
    $check_sql = "SELECT id, image_path, image_labels, type FROM items WHERE id = ? AND user_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $existing_item = $check_stmt->execute([$item_id, $user_id]) ? $check_stmt->fetch() : null;

    if (!$existing_item) {
        $_SESSION['error'] = "คุณไม่มีสิทธิ์แก้ไขประกาศนี้ หรือไม่พบข้อมูล";
        header("Location: ../pages/dashboard.php");
        exit;
    }

    // Check if there are active claims on this item
    $claim_check = $pdo->prepare("SELECT id FROM claims WHERE item_id = ? AND status IN ('pending', 'approved', 'under_admin_review', 'meeting_scheduled') LIMIT 1");
    $claim_check->execute([$item_id]);
    if ($claim_check->fetch()) {
        $_SESSION['error'] = "ไม่สามารถแก้ไขประกาศนี้ได้ เนื่องจากอยู่ระหว่างกระบวนการยื่นคำร้อง Claim";
        header("Location: ../pages/item_detail.php?id=$item_id");
        exit;
    }

    $type = $_POST['type'] ?? $existing_item['type'];
    $title = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $brand = trim($_POST['brand'] ?? null);
    $model = trim($_POST['model'] ?? null);
    $color = trim($_POST['color'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $secret_description = trim($_POST['secret_description'] ?? null);
    $serial_number = trim($_POST['serial_number'] ?? null);
    $province = trim($_POST['province'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $location_detail = trim($_POST['location_detail'] ?? '');
    $storage_location = trim($_POST['storage_location'] ?? null);
    $event_date = $_POST['event_date'] ?? '';
    $contact_phone = trim($_POST['contact_phone'] ?? '');

    // Fallbacks to null
    if ($brand === '') $brand = null;
    if ($model === '') $model = null;
    if ($secret_description === '') $secret_description = null;
    if ($serial_number === '') $serial_number = null;
    if ($storage_location === '') $storage_location = null;

    if (empty($title) || empty($category) || empty($color) || empty($description) || empty($province) || empty($district) || empty($location_detail) || empty($event_date) || empty($contact_phone)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (*)";
        header("Location: ../pages/edit_item.php?id=$item_id");
        exit;
    }

    if ($type === 'found' && empty($storage_location)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลสถานที่เก็บรักษาของปัจจุบัน (*)";
        header("Location: ../pages/edit_item.php?id=$item_id");
        exit;
    }

    // Construct location for backward compatibility
    $location = trim($location_detail . ' อ.' . $district . ' จ.' . $province);

    // Backend length validations (Test Case 12)
    if (mb_strlen($title, 'UTF-8') > 100) {
        $_SESSION['error'] = "ชื่อเรียกทรัพย์สินต้องไม่เกิน 100 ตัวอักษร";
        header("Location: ../pages/edit_item.php?id=$item_id");
        exit;
    }
    if ($brand !== null && mb_strlen($brand, 'UTF-8') > 100) {
        $_SESSION['error'] = "แบรนด์/ยี่ห้อต้องไม่เกิน 100 ตัวอักษร";
        header("Location: ../pages/edit_item.php?id=$item_id");
        exit;
    }
    if ($model !== null && mb_strlen($model, 'UTF-8') > 100) {
        $_SESSION['error'] = "รุ่นต้องไม่เกิน 100 ตัวอักษร";
        header("Location: ../pages/edit_item.php?id=$item_id");
        exit;
    }
    if (mb_strlen($color, 'UTF-8') > 100) {
        $_SESSION['error'] = "สีของสิ่งของต้องไม่เกิน 100 ตัวอักษร";
        header("Location: ../pages/edit_item.php?id=$item_id");
        exit;
    }
    if (mb_strlen($description, 'UTF-8') > 500) {
        $_SESSION['error'] = "ลักษณะเฉพาะ/ที่พบเห็นต้องไม่เกิน 500 ตัวอักษร";
        header("Location: ../pages/edit_item.php?id=$item_id");
        exit;
    }
    if ($secret_description !== null && mb_strlen($secret_description, 'UTF-8') > 500) {
        $_SESSION['error'] = "รายละเอียดลับต้องไม่เกิน 500 ตัวอักษร";
        header("Location: ../pages/edit_item.php?id=$item_id");
        exit;
    }
    if ($serial_number !== null && mb_strlen($serial_number, 'UTF-8') > 50) {
        $_SESSION['error'] = "เลขซีเรียล/ข้อมูลระบุตัวตนต้องไม่เกิน 50 ตัวอักษร";
        header("Location: ../pages/edit_item.php?id=$item_id");
        exit;
    }
    if (mb_strlen($contact_phone, 'UTF-8') > 20) {
        $_SESSION['error'] = "เบอร์โทรศัพท์ติดต่อต้องไม่เกิน 20 ตัวอักษร";
        header("Location: ../pages/edit_item.php?id=$item_id");
        exit;
    }

    // Handle Image Upload
    $image_path = $existing_item['image_path']; // Keep existing by default
    $image_labels = $existing_item['image_labels'];
    
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $file_type = $_FILES['item_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/items/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('item_') . '_' . time() . '.' . $file_extension;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['item_image']['tmp_name'], $destination)) {
                // Delete old image if exists
                if (!empty($existing_item['image_path']) && file_exists('../' . $existing_item['image_path'])) {
                    unlink('../' . $existing_item['image_path']);
                }
                $image_path = 'uploads/items/' . $new_filename;

                // Detect labels for new image using Google Cloud Vision API
                require_once '../includes/vision_helper.php';
                $labels = detect_labels($destination);
                if (!empty($labels)) {
                    // Translate labels to 2 languages
                    require_once '../includes/translation_helper.php';
                    $translated_labels = translate_labels($pdo, $labels);
                    $image_labels = json_encode($translated_labels, JSON_UNESCAPED_UNICODE);
                } else {
                    $image_labels = null;
                }
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพใหม่";
                header("Location: ../pages/edit_item.php?id=$item_id");
                exit;
            }
        } else {
            $_SESSION['error'] = "อัปโหลดได้เฉพาะไฟล์รูปภาพ (JPG, PNG, WEBP) เท่านั้น";
            header("Location: ../pages/edit_item.php?id=$item_id");
            exit;
        }
    }

    try {
        $sql = "UPDATE items SET 
                title = ?, 
                category = ?, 
                brand = ?,
                model = ?,
                color = ?,
                description = ?, 
                secret_description = ?, 
                serial_number = ?, 
                location = ?, 
                province = ?,
                district = ?,
                location_detail = ?,
                storage_location = ?, 
                event_date = ?, 
                contact_phone = ?, 
                image_path = ?,
                image_labels = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $title, $category, $brand, $model, $color, $description, $secret_description, 
            $serial_number, $location, $province, $district, $location_detail, $storage_location, $event_date, 
            $contact_phone, $image_path, $image_labels, $item_id, $user_id
        ]);

        $_SESSION['success'] = "อัปเดตประกาศของคุณเรียบร้อยแล้ว!";
        header("Location: ../pages/item_detail.php?id=$item_id");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดของฐานข้อมูล: " . $e->getMessage();
        header("Location: ../pages/edit_item.php?id=$item_id");
        exit;
    }
} else {
    header("Location: ../index.php");
    exit;
}
?>
