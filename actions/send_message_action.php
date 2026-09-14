<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/notification_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $ref = isset($_POST['ref']) ? trim($_POST['ref']) : '';
    $claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;
    $tab = isset($_POST['tab']) ? trim($_POST['tab']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $sender_id = $_SESSION['user_id'];

    $redirect_url = "../pages/chat.php?item_id=$item_id&receiver_id=$receiver_id";
    if (!empty($ref)) {
        $redirect_url .= "&ref=" . urlencode($ref);
    }
    if ($claim_id > 0) {
        $redirect_url .= "&claim_id=" . $claim_id;
    }
    if (!empty($tab)) {
        $redirect_url .= "&tab=" . urlencode($tab);
    }

    if ($item_id > 0 && $receiver_id > 0 && !empty($content)) {
        try {
            // ตรวจสอบว่าแอดมินอยู่ในโหมดผู้สังเกตการณ์หรือไม่
            $item_stmt = $pdo->prepare("SELECT user_id FROM items WHERE id = ?");
            $item_stmt->execute([$item_id]);
            $item_info = $item_stmt->fetch();
            $is_admin_user = (($_SESSION['user_role'] ?? '') === 'admin');
            if ($is_admin_user && $item_info && $sender_id != $item_info['user_id'] && $sender_id != $receiver_id) {
                $_SESSION['error'] = "ผู้ดูแลระบบอยู่ในโหมดอ่านอย่างเดียว (Read-Only) ไม่สามารถส่งข้อความได้";
                header("Location: $redirect_url");
                exit;
            }

            // ตรวจสอบว่าคำร้องปิดการสนทนาไปแล้วหรือไม่ (completed, rejected, cancelled)
            $claim_check_stmt = $pdo->prepare("SELECT status FROM claims WHERE item_id = ? AND ((claimant_id = ? AND finder_id = ?) OR (claimant_id = ? AND finder_id = ?)) ORDER BY id DESC LIMIT 1");
            $claim_check_stmt->execute([$item_id, $sender_id, $receiver_id, $receiver_id, $sender_id]);
            $closed_claim = $claim_check_stmt->fetch();
            if ($closed_claim && in_array($closed_claim['status'], ['completed', 'rejected', 'cancelled', 'cancelled_mismatch'])) {
                $_SESSION['error'] = "การสนทนานี้ถูกปิดโดยอัตโนมัติเนื่องจากคำร้องเสร็จสมบูรณ์หรือถูกยกเลิกแล้ว";
                header("Location: $redirect_url");
                exit;
            }

            // Save message
            $stmt = $pdo->prepare("INSERT INTO messages (item_id, sender_id, receiver_id, content) VALUES (?, ?, ?, ?)");
            $stmt->execute([$item_id, $sender_id, $receiver_id, $content]);

            // Redirect back to chat
            header("Location: $redirect_url");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "ไม่สามารถส่งข้อความได้: " . $e->getMessage();
            header("Location: $redirect_url");
            exit;
        }
    } else {
        $_SESSION['error'] = "กรุณากรอกข้อความ";
        header("Location: $redirect_url");
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}
