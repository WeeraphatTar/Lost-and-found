<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/notification_helper.php';

$base_url = 'http://localhost/lost-and-found';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนทำการรายงานประกาศ";
    header("Location: " . $base_url . "/pages/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reporter_id = (int)$_SESSION['user_id'];
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    $reason_type = trim($_POST['reason_type'] ?? '');
    $details = trim($_POST['details'] ?? '');

    if ($item_id <= 0 || empty($reason_type)) {
        $_SESSION['error'] = "กรุณาระบุเหตุผลการรายงาน";
        header("Location: " . $base_url . "/pages/item_detail.php?id=" . $item_id);
        exit;
    }

    try {
        // Check if item exists
        $stmt = $pdo->prepare("SELECT title, user_id FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();

        if (!$item) {
            $_SESSION['error'] = "ไม่พบประกาศที่อ้างถึง";
            header("Location: " . $base_url . "/pages/browse.php");
            exit;
        }

        if ($item['user_id'] == $reporter_id) {
            $_SESSION['error'] = "คุณไม่สามารถรายงานประกาศของตนเองได้";
            header("Location: " . $base_url . "/pages/item_detail.php?id=" . $item_id);
            exit;
        }

        // Prevent duplicate report by same user for same item
        $check_stmt = $pdo->prepare("SELECT id FROM item_reports WHERE item_id = ? AND reporter_id = ? AND status = 'pending'");
        $check_stmt->execute([$item_id, $reporter_id]);
        if ($check_stmt->fetch()) {
            $_SESSION['error'] = "คุณได้ส่งรายงานสำหรับประกาศนี้ไปแล้ว ระบบกำลังอยู่ระหว่างการตรวจสอบ";
            header("Location: " . $base_url . "/pages/item_detail.php?id=" . $item_id);
            exit;
        }

        // Insert report
        $insert_stmt = $pdo->prepare("INSERT INTO item_reports (item_id, reporter_id, reason_type, details, status) VALUES (?, ?, ?, ?, 'pending')");
        $insert_stmt->execute([$item_id, $reporter_id, $reason_type, $details]);

        // Notify admins
        $admin_stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
        while ($admin = $admin_stmt->fetch()) {
            add_notification($pdo, $admin['id'], "มีผู้แจ้งรายงานประกาศ {$item['title']} โปรดเข้าตรวจสอบ", "pages/admin/admin_reports.php");
        }

        $_SESSION['success'] = "ขอบคุณสำหรับการแจ้งรายงาน เจ้าหน้าที่จะทำการตรวจสอบโดยเร็วที่สุด";
        header("Location: " . $base_url . "/pages/item_detail.php?id=" . $item_id);
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกรายงาน: " . $e->getMessage();
        header("Location: " . $base_url . "/pages/item_detail.php?id=" . $item_id);
        exit;
    }
} else {
    header("Location: " . $base_url . "/index.php");
    exit;
}
