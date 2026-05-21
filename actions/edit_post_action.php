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
    
    // Ownership check including image_labels
    $check_sql = "SELECT id, image_path, image_labels, type FROM items WHERE id = ? AND user_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$item_id, $user_id]);
    $existing_item = $check_stmt->fetch();

    if (!$existing_item) {
        $_SESSION['error'] = "คุณไม่มีสิทธิ์แก้ไขประกาศนี้ หรือไม่พบข้อมูล";
        header("Location: ../pages/dashboard.php");
        exit;
    }

    $type = $_POST['type'] ?? $existing_item['type'];
    $title = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $secret_description = trim($_POST['secret_description'] ?? null);
    $serial_number = trim($_POST['serial_number'] ?? null);
    $location = trim($_POST['location'] ?? '');
    $storage_location = trim($_POST['storage_location'] ?? null);
    $event_date = $_POST['event_date'] ?? '';
    $contact_phone = trim($_POST['contact_phone'] ?? '');

    // Fallbacks to null
    if ($secret_description === '') $secret_description = null;
    if ($serial_number === '') $serial_number = null;
    if ($storage_location === '') $storage_location = null;

    if (empty($title) || empty($category) || empty($description) || empty($location) || empty($event_date) || empty($contact_phone)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (*)";
        header("Location: ../pages/edit_item.php?id=$item_id");
        exit;
    }

    // Handle Image Upload
    $image_path = $existing_item['image_path']; // Keep existing by default
    $image_labels = $existing_item['image_labels']; // Keep existing by default
    
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
                    $image_labels = json_encode($labels, JSON_UNESCAPED_UNICODE);
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
                description = ?, 
                secret_description = ?, 
                serial_number = ?, 
                location = ?, 
                storage_location = ?, 
                event_date = ?, 
                contact_phone = ?, 
                image_path = ?,
                image_labels = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $title, $category, $description, $secret_description, 
            $serial_number, $location, $storage_location, $event_date, 
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
