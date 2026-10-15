<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/notification_helper.php';

// Helper to send response (supports both AJAX JSON and HTML Redirect)
function send_chat_response($success, $message, $redirect_url) {
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'redirect' => $redirect_url
        ]);
        exit;
    } else {
        if (!$success) {
            $_SESSION['error'] = $message;
        }
        header("Location: $redirect_url");
        exit;
    }
}

if (!isset($_SESSION['user_id'])) {
    send_chat_response(false, 'กรุณาเข้าสู่ระบบก่อน', '../pages/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support both Form POST and JSON Input payload
    $raw_input = file_get_contents('php://input');
    $json_input = json_decode($raw_input, true);

    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : (isset($json_input['item_id']) ? (int)$json_input['item_id'] : 0);
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : (isset($json_input['receiver_id']) ? (int)$json_input['receiver_id'] : 0);
    $ref = isset($_POST['ref']) ? trim($_POST['ref']) : (isset($json_input['ref']) ? trim($json_input['ref']) : '');
    $claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : (isset($json_input['claim_id']) ? (int)$json_input['claim_id'] : 0);
    $tab = isset($_POST['tab']) ? trim($_POST['tab']) : (isset($json_input['tab']) ? trim($json_input['tab']) : '');
    $content = isset($_POST['content']) ? trim($_POST['content']) : (isset($json_input['content']) ? trim($json_input['content']) : '');
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
            // ตรวจสอบข้อมูลสิ่งของ
            $item_stmt = $pdo->prepare("SELECT user_id, type, status FROM items WHERE id = ?");
            $item_stmt->execute([$item_id]);
            $item_info = $item_stmt->fetch();
            $is_admin_user = (($_SESSION['user_role'] ?? '') === 'admin');

            // ตรวจสอบบทบาทคู่สนทนาปลายทาง
            $rec_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $rec_stmt->execute([$receiver_id]);
            $rec_info = $rec_stmt->fetch();
            $is_receiver_admin = (($rec_info['role'] ?? '') === 'admin');
            $is_admin_conversation = ($is_admin_user || $is_receiver_admin);

            if ($is_admin_user && $item_info && $sender_id != $item_info['user_id'] && $sender_id != $receiver_id && !$is_receiver_admin) {
                send_chat_response(false, "ผู้ดูแลระบบอยู่ในโหมดอ่านอย่างเดียว (Read-Only) ไม่สามารถส่งข้อความได้", $redirect_url);
            }

            // ตรวจสอบสถานะคำร้อง Claim ก่อนอนุญาตให้ส่งข้อความ
            $claim_check_stmt = $pdo->prepare("
                SELECT c.status 
                FROM claims c 
                JOIN items i ON c.item_id = i.id 
                WHERE c.item_id = ? 
                  AND ((c.user_id = ? AND i.user_id = ?) OR (c.user_id = ? AND i.user_id = ?)) 
                ORDER BY c.id DESC LIMIT 1
            ");
            $claim_check_stmt->execute([$item_id, $sender_id, $receiver_id, $receiver_id, $sender_id]);
            $active_claim = $claim_check_stmt->fetch();

            if (!$is_admin_conversation) {
                $is_item_closed = in_array($item_info['status'] ?? '', ['resolved', 'closed', 'completed']);
                $is_claim_closed_status = ($active_claim && in_array($active_claim['status'], ['completed', 'rejected', 'cancelled', 'cancelled_mismatch']));

                if ($is_item_closed || $is_claim_closed_status) {
                    if (($active_claim && $active_claim['status'] === 'completed') || $is_item_closed) {
                        send_chat_response(false, "การส่งมอบสิ่งของเสร็จสมบูรณ์แล้ว ไม่สามารถส่งข้อความได้อีก", $redirect_url);
                    } else {
                        send_chat_response(false, "การสนทนานี้ถูกปิดลงแล้ว ไม่สามารถส่งข้อความได้", $redirect_url);
                    }
                }

                if ($item_info && $item_info['type'] === 'found' || $active_claim) {
                    if (!$active_claim || !in_array($active_claim['status'], ['approved', 'meeting_scheduled'])) {
                        if ($active_claim && in_array($active_claim['status'], ['pending', 'under_admin_review'])) {
                            send_chat_response(false, "ต้องได้รับการอนุมัติคำร้องขอรับคืนจากผู้ดูแลระบบ (Admin) ก่อน จึงจะสามารถส่งข้อความได้", $redirect_url);
                        } else {
                            send_chat_response(false, "ไม่สามารถส่งข้อความได้ เนื่องจากคำร้องยังไม่ได้รับการอนุมัติ หรือถูกยกเลิกแล้ว", $redirect_url);
                        }
                    }
                }
            }

            // บันทึกข้อความลงตาราง messages (columns: item_id, sender_id, receiver_id, content)
            $stmt = $pdo->prepare("INSERT INTO messages (item_id, sender_id, receiver_id, content) VALUES (?, ?, ?, ?)");
            $stmt->execute([$item_id, $sender_id, $receiver_id, $content]);

            // Email Notification: Notify receiver about new chat message (Smart Cooldown 15m)
            if (file_exists(__DIR__ . '/../helpers/email_notification_helper.php')) {
                require_once __DIR__ . '/../helpers/email_notification_helper.php';
                dispatch_chat_message_email($pdo, $item_id, $sender_id, $receiver_id);
            }

            send_chat_response(true, "ส่งข้อความสำเร็จ", $redirect_url);
        } catch (PDOException $e) {
            send_chat_response(false, "ไม่สามารถส่งข้อความได้: " . $e->getMessage(), $redirect_url);
        }
    } else {
        send_chat_response(false, "กรุณากรอกข้อความ", $redirect_url);
    }
} else {
    header('Location: ../index.php');
    exit;
}
