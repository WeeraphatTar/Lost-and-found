<?php
session_start();
require_once '../config/database.php';
require_once '../includes/notification_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $sender_id = $_SESSION['user_id'];

    if ($item_id > 0 && $receiver_id > 0 && !empty($content)) {
        try {
            // Save message
            $stmt = $pdo->prepare("INSERT INTO messages (item_id, sender_id, receiver_id, content) VALUES (?, ?, ?, ?)");
            $stmt->execute([$item_id, $sender_id, $receiver_id, $content]);

            // Redirect back to chat
            header("Location: ../pages/chat.php?item_id=$item_id&receiver_id=$receiver_id");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "ไม่สามารถส่งข้อความได้: " . $e->getMessage();
            header("Location: ../pages/chat.php?item_id=$item_id&receiver_id=$receiver_id");
            exit;
        }
    } else {
        $_SESSION['error'] = "กรุณากรอกข้อความ";
        header("Location: ../pages/chat.php?item_id=$item_id&receiver_id=$receiver_id");
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}
