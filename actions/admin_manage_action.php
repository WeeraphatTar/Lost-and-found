<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/notification_helper.php';
require_once __DIR__ . '/../helpers/admin_log_helper.php';

$base_url = 'http://localhost/lost-and-found';

// Strict Admin role guard
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถดำเนินการได้";
    header("Location: " . $base_url . "/index.php");
    exit;
}

$admin_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = $_POST['redirect_url'] ?? $_SERVER['HTTP_REFERER'] ?? ($base_url . "/pages/admin/admin_dashboard.php");

switch ($action) {
    // ----------------------------------------------------
    // 1. Ban / Unban User
    // ----------------------------------------------------
    case 'ban_user':
        $target_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $ban_reason = trim($_POST['ban_reason'] ?? 'ละเมิดเงื่อนไขการใช้งานระบบ');

        if ($target_user_id <= 0 || $target_user_id === $admin_id) {
            $_SESSION['error'] = "ไม่สามารถระงับสิทธิ์บัญชีนี้ได้";
            header("Location: " . $redirect_url);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE users SET is_banned = 1, ban_reason = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$ban_reason, $target_user_id]);

        add_notification($pdo, $target_user_id, "บัญชีของคุณถูกระงับการใช้งานเนื่องจาก: " . $ban_reason, "pages/profile.php");
        log_admin_action($pdo, $admin_id, 'ban_user', "ระงับสิทธิ์การใช้งานผู้ใช้ ID #{$target_user_id} เนื่องจาก: {$ban_reason}");
        $_SESSION['success'] = "ระงับสิทธิ์การใช้งานบัญชีผู้ใช้สำเร็จเรียบร้อยแล้ว";
        header("Location: " . $redirect_url);
        exit;

    case 'unban_user':
        $target_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

        if ($target_user_id <= 0) {
            $_SESSION['error'] = "ไม่พบผู้ใช้ที่ระบุ";
            header("Location: " . $redirect_url);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE users SET is_banned = 0, ban_reason = NULL, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$target_user_id]);

        add_notification($pdo, $target_user_id, "บัญชีของคุณได้รับการปลดล็อกการระงับสิทธิ์เรียบร้อยแล้ว", "pages/profile.php");
        log_admin_action($pdo, $admin_id, 'unban_user', "ปลดล็อกการระงับสิทธิ์ผู้ใช้ ID #{$target_user_id}");
        $_SESSION['success'] = "ปลดล็อกการระงับสิทธิ์ผู้ใช้สำเร็จเรียบร้อยแล้ว";
        header("Location: " . $redirect_url);
        exit;

    // ----------------------------------------------------
    // 2. Hide / Restore / Delete Item (Moderation)
    // ----------------------------------------------------
    case 'toggle_hide_item':
    case 'hide_item':
    case 'restore_item':
        $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $hide_reason = trim($_POST['hide_reason'] ?? '');

        if ($item_id <= 0) {
            $_SESSION['error'] = "ไม่พบประกาศที่ระบุ";
            header("Location: " . $redirect_url);
            exit;
        }

        // Fetch current item details
        $stmt_item = $pdo->prepare("SELECT id, title, user_id, status FROM items WHERE id = ?");
        $stmt_item->execute([$item_id]);
        $item = $stmt_item->fetch();

        if ($item) {
            if ($action === 'restore_item' || ($action === 'toggle_hide_item' && $item['status'] === 'hidden')) {
                // Restore item status to 'open'
                $stmt = $pdo->prepare("UPDATE items SET status = 'open', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$item_id]);

                // Reset associated reports status back to 'pending' for re-review
                $pdo->prepare("UPDATE item_reports SET status = 'pending' WHERE item_id = ? AND status IN ('action_taken', 'dismissed')")->execute([$item_id]);

                add_notification($pdo, $item['user_id'], "ประกาศ \"{$item['title']}\" ของคุณได้รับการเปิดใช้งานตามเดิมเรียบร้อยแล้ว", "pages/item_detail.php?id={$item_id}");
                log_admin_action($pdo, $admin_id, 'restore_item', "คืนสถานะเปิดใช้งานประกาศ ID #{$item_id} ({$item['title']}) และส่งรายงานกลับไปรอการตรวจสอบ");
                $_SESSION['success'] = "ยกเลิกการซ่อนประกาศ และย้ายรายงานกลับไปยังแท็บรอการตรวจสอบเรียบร้อยแล้ว";
            } else {
                // Hide item status to 'hidden'
                $stmt = $pdo->prepare("UPDATE items SET status = 'hidden', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$item_id]);

                // Update related pending reports to action_taken
                $pdo->prepare("UPDATE item_reports SET status = 'action_taken' WHERE item_id = ? AND status = 'pending'")->execute([$item_id]);

                $reason_text = !empty($hide_reason) ? "เนื่องจาก: " . $hide_reason : "เนื่องจากตรวจสอบพบเนื้อหาที่ขัดต่อเงื่อนไขการใช้งาน";
                add_notification($pdo, $item['user_id'], "ประกาศ \"{$item['title']}\" ของคุณถูกซ่อนโดยผู้ดูแลระบบ {$reason_text}", "pages/profile.php");
                
                log_admin_action($pdo, $admin_id, 'hide_item', "ซ่อนประกาศ ID #{$item_id} ({$item['title']}) {$reason_text}");
                $_SESSION['success'] = "ซ่อนประกาศออกจากระบบเรียบร้อยแล้ว";
            }
        } else {
            $_SESSION['error'] = "ไม่พบข้อมูลประกาศ";
        }

        header("Location: " . $redirect_url);
        exit;

    case 'delete_item':
        $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        if ($item_id <= 0) {
            $_SESSION['error'] = "ไม่พบประกาศที่ต้องการลบ";
            header("Location: " . $redirect_url);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        $stmt->execute([$item_id]);

        log_admin_action($pdo, $admin_id, 'delete_item', "ลบประกาศสิ่งของ ID #{$item_id} ออกจากระบบถาวร");
        $_SESSION['success'] = "ลบประกาศออกจากระบบถาวรเรียบร้อยแล้ว";
        header("Location: " . $redirect_url);
        exit;

    // ----------------------------------------------------
    // 3. Report Actions (Dismiss / Action Taken / Warn User)
    // ----------------------------------------------------
    case 'warn_user_report':
        $report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
        $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $warning_message = trim($_POST['warning_message'] ?? '');

        if (empty($warning_message)) {
            $_SESSION['error'] = "กรุณาระบุข้อความตักเตือนผู้โพสต์";
            header("Location: " . $redirect_url);
            exit;
        }

        // Fetch item details & owner
        $stmt_item = $pdo->prepare("SELECT title, user_id FROM items WHERE id = ?");
        $stmt_item->execute([$item_id]);
        $item = $stmt_item->fetch();

        if (!$item) {
            $_SESSION['error'] = "ไม่พบข้อมูลประกาศที่เกี่ยวข้อง";
            header("Location: " . $redirect_url);
            exit;
        }

        $owner_id = (int)$item['user_id'];

        // Send official warning notification
        add_notification(
            $pdo, 
            $owner_id, 
            "⚠️ ประกาศแจ้งเตือนจากผู้ดูแลระบบเกี่ยวกับ \"{$item['title']}\": {$warning_message}", 
            "pages/item_detail.php?id={$item_id}"
        );

        // Update report status if coming from report
        if ($report_id > 0) {
            $stmt_rep = $pdo->prepare("UPDATE item_reports SET status = 'action_taken' WHERE id = ?");
            $stmt_rep->execute([$report_id]);
        }

        log_admin_action($pdo, $admin_id, 'warn_user', "ส่งคำเตือนไปยังผู้ใช้ ID #{$owner_id} เกี่ยวกับประกาศ ID #{$item_id} ({$item['title']}): {$warning_message}");
        $_SESSION['success'] = "ส่งข้อความตักเตือนผู้โพสต์เรียบร้อยแล้ว";
        header("Location: " . $redirect_url);
        exit;

    case 'dismiss_report':
        $report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
        if ($report_id <= 0) {
            $_SESSION['error'] = "ไม่พบรายการรายงานที่ระบุ";
            header("Location: " . $redirect_url);
            exit;
        }

        // Get report details for log
        $stmt_get = $pdo->prepare("SELECT r.item_id, i.title FROM item_reports r LEFT JOIN items i ON r.item_id = i.id WHERE r.id = ?");
        $stmt_get->execute([$report_id]);
        $rep = $stmt_get->fetch();
        $title_str = $rep ? "ประกาศ: " . $rep['title'] : "รายงาน ID #{$report_id}";

        $stmt = $pdo->prepare("UPDATE item_reports SET status = 'dismissed' WHERE id = ?");
        $stmt->execute([$report_id]);

        log_admin_action($pdo, $admin_id, 'dismiss_report', "ปฏิเสธรายงานสำหรับ {$title_str} (ไม่พบความผิด)");
        $_SESSION['success'] = "บันทึกสถานะไม่พบความผิด และปิดรายงานเรียบร้อยแล้ว";
        header("Location: " . $redirect_url);
        exit;

    default:
        $_SESSION['error'] = "คำสั่งไม่ถูกต้อง";
        header("Location: " . $redirect_url);
        exit;
}
