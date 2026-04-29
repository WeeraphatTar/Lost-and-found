<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบ";
    header("Location: ../pages/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

    if ($item_id <= 0) {
        $_SESSION['error'] = "รหัสประกาศไม่ถูกต้อง";
        header("Location: ../pages/dashboard.php");
        exit;
    }

    try {
        // ตรวจสอบว่าประกาศเป็นของผู้ใช้นี้จริงๆ (Security Check)
        $check_sql = "SELECT id, image_path FROM items WHERE id = ? AND user_id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$item_id, $user_id]);
        $item = $check_stmt->fetch();

        if (!$item) {
            $_SESSION['error'] = "คุณไม่มีสิทธิ์จัดการประกาศนี้ หรือประกาศไม่มีอยู่จริง";
            header("Location: ../pages/dashboard.php");
            exit;
        }

        if ($action === 'close') {
            // ปิดประกาศ (เปลี่ยนสถานะเป็น resolved)
            $update_sql = "UPDATE items SET status = 'resolved' WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$item_id]);
            
            $_SESSION['success'] = "ปิดประกาศเรียบร้อยแล้ว";

        } elseif ($action === 'delete') {
            // ลบประกาศถาวร
            // ลบรูปภาพจากเซิร์ฟเวอร์ก่อน (ถ้ามี)
            if (!empty($item['image_path'])) {
                $file_path = '../' . $item['image_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // ลบข้อมูลจากฐานข้อมูล
            $delete_sql = "DELETE FROM items WHERE id = ?";
            $delete_stmt = $pdo->prepare($delete_sql);
            $delete_stmt->execute([$item_id]);

            $_SESSION['success'] = "ลบประกาศเรียบร้อยแล้ว";
            
        } else {
            $_SESSION['error'] = "คำสั่งไม่ถูกต้อง";
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดของฐานข้อมูล: " . $e->getMessage();
    }
    
    header("Location: ../pages/dashboard.php");
    exit;

} else {
    header("Location: ../pages/dashboard.php");
    exit;
}
?>
