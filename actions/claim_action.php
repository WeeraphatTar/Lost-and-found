<?php
session_start();
$base_url = 'http://localhost/lost-and-found';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/notification_helper.php';
require_once __DIR__ . '/../helpers/admin_log_helper.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนดำเนินการ";
    header("Location: " . $base_url . "/pages/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    // ----------------------------------------------------
    // 1. Submit Claim (ยื่นคำร้องขอ Claim)
    // ----------------------------------------------------
    case 'submit_claim':
        $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $proof_description = trim($_POST['proof_description'] ?? '');
        $provided_serial_number = trim($_POST['provided_serial_number'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');

        if ($item_id <= 0 || empty($proof_description) || empty($contact_phone)) {
            $_SESSION['error'] = "กรุณากรอกข้อมูลหลักฐานและเบอร์ติดต่อให้ครบถ้วน";
            header("Location: " . $base_url . "/pages/submit_claim.php?item_id=" . $item_id);
            exit;
        }

        // ตรวจสอบ Item
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();

        if (!$item || $item['type'] !== 'found') {
            $_SESSION['error'] = "ไม่พบรายการพบสิ่งของที่ต้องการ Claim";
            header("Location: " . $base_url . "/pages/browse.php");
            exit;
        }

        if ($item['user_id'] == $user_id) {
            $_SESSION['error'] = "คุณไม่สามารถยื่น Claim ประกาศสิ่งของที่คุณเป็นคนลงเองได้";
            header("Location: " . $base_url . "/pages/item_detail.php?id=" . $item_id);
            exit;
        }

        if ($item['status'] !== 'open') {
            $_SESSION['error'] = "รายการนี้อยู่ระหว่างกระบวนการ Claim หรือถูกปิดประกาศไปแล้ว";
            header("Location: " . $base_url . "/pages/item_detail.php?id=" . $item_id);
            exit;
        }

        // ตรวจสอบ Claim ที่ค้างอยู่
        $stmt = $pdo->prepare("SELECT id FROM claims WHERE item_id = ? AND claimant_id = ? AND status IN ('pending', 'approved', 'under_admin_review', 'meeting_scheduled')");
        $stmt->execute([$item_id, $user_id]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "คุณได้ยื่นคำร้องขอ Claim สำหรับรายการนี้ไปแล้ว";
            header("Location: " . $base_url . "/pages/item_detail.php?id=" . $item_id);
            exit;
        }

        // อัปโหลดรูปภาพหลักฐาน (ถ้ามี)
        $proof_image_path = NULL;
        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/claims/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename = 'claim_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $upload_dir . $filename)) {
                    $proof_image_path = 'uploads/claims/' . $filename;
                }
            }
        }

        // Insert Claim
        $stmt = $pdo->prepare("INSERT INTO claims (item_id, claimant_id, finder_id, proof_description, proof_image, provided_serial_number, contact_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$item_id, $user_id, $item['user_id'], $proof_description, $proof_image_path, $provided_serial_number, $contact_phone]);
        $claim_id = $pdo->lastInsertId();

        // ส่ง Notification ถึงผู้พบของ (Finder)
        $notify_msg = "มีคำร้องขอ Claim สิ่งของ '[{$item['title']}]' ใหม่ยื่นเข้ามาในระบบ คำร้องอยู่ระหว่างการตรวจสอบโดย Admin";
        $notify_link = "pages/claim_detail.php?id=" . $claim_id;
        add_notification($pdo, $item['user_id'], $notify_msg, $notify_link);

        // ส่ง Notification ถึง Admin ทุกคนให้เข้าตรวจสอบหลักฐาน
        $admin_stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
        while ($admin = $admin_stmt->fetch()) {
            add_notification($pdo, $admin['id'], "มีคำร้องขอ Claim สิ่งของ '[{$item['title']}]' ใหม่ยื่นเข้ามา รอ Admin ตรวจสอบหลักฐาน", "pages/admin/admin_claims.php");
        }

        $_SESSION['success'] = "ยื่นคำร้องขอ Claim สำเร็จเรียบร้อยแล้ว! คำร้องของคุณส่งเข้าสู่ศูนย์ตรวจสอบของ Admin เรียบร้อยแล้ว";
        header("Location: " . $base_url . "/pages/claim_detail.php?id=" . $claim_id);
        exit;

    // ----------------------------------------------------
    // 2. Approve Claim (ผู้พบอนุมัติการ Claim)
    // ----------------------------------------------------
    case 'approve_claim':
        $claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;
        
        $stmt = $pdo->prepare("SELECT c.*, i.title FROM claims c JOIN items i ON c.item_id = i.id WHERE c.id = ?");
        $stmt->execute([$claim_id]);
        $claim = $stmt->fetch();

        if (!$claim || $claim['finder_id'] != $user_id) {
            $_SESSION['error'] = "ไม่พบคำร้องขอ Claim นี้ หรือคุณไม่มีสิทธิ์ดำเนินการ";
            header("Location: " . $base_url . "/pages/claims.php");
            exit;
        }

        // อัปเดตสถานะ Claim -> approved, Item -> pending
        $pdo->prepare("UPDATE claims SET status = 'approved', updated_at = NOW() WHERE id = ?")->execute([$claim_id]);
        $pdo->prepare("UPDATE items SET status = 'pending', updated_at = NOW() WHERE id = ?")->execute([$claim['item_id']]);

        // เคลียร์คำร้องซ้อนอื่นๆ บนสิ่งของชิ้นเดียวกันให้อัตโนมัติ
        $other_claims_stmt = $pdo->prepare("SELECT id, claimant_id FROM claims WHERE item_id = ? AND id != ? AND status IN ('pending', 'under_admin_review')");
        $other_claims_stmt->execute([$claim['item_id'], $claim_id]);
        $other_claims = $other_claims_stmt->fetchAll();

        if (!empty($other_claims)) {
            $auto_reject_note = "สิ่งของชิ้นนี้ได้รับการยืนยันและอนุมัติส่งคืนให้แก่ผู้ยื่นคำร้องท่านอื่นเรียบร้อยแล้ว";
            $pdo->prepare("UPDATE claims SET status = 'rejected', admin_notes = ?, updated_at = NOW() WHERE item_id = ? AND id != ? AND status IN ('pending', 'under_admin_review')")
                ->execute([$auto_reject_note, $claim['item_id'], $claim_id]);

            foreach ($other_claims as $other) {
                add_notification($pdo, $other['claimant_id'], "คำร้องขอ Claim สิ่งของ '[{$claim['title']}]' ของคุณถูกยกเลิก เนื่องจากสิ่งของได้รับการอนุมัติส่งคืนให้แก่เจ้าของที่แท้จริงเรียบร้อยแล้ว", "pages/claim_detail.php?id=" . $other['id']);
            }
        }

        // แจ้งเตือนผู้ขอ Claim
        $notify_msg = "คำร้องขอ Claim สิ่งของ '[{$claim['title']}]' ของคุณได้รับการอนุมัติแล้ว คุณสามารถนัดหมายส่งมอบได้ทันที";
        $notify_link = "pages/claim_detail.php?id=" . $claim_id;
        add_notification($pdo, $claim['claimant_id'], $notify_msg, $notify_link);

        $_SESSION['success'] = "อนุมัติคำร้องขอ Claim เรียบร้อยแล้ว สามารถนัดหมายสถานที่และเวลาเพื่อส่งมอบสิ่งของได้";
        header("Location: " . $base_url . "/pages/claim_detail.php?id=" . $claim_id);
        exit;

    // ----------------------------------------------------
    // 3. Dispute / Request Admin Review (คัดค้าน/ส่งให้ Admin ตรวจสอบ)
    // ----------------------------------------------------
    case 'dispute_claim':
        $claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;
        $dispute_reason = trim($_POST['dispute_reason'] ?? '');

        if (empty($dispute_reason)) {
            $_SESSION['error'] = "กรุณาระบุเหตุผลการคัดค้านหรือส่ง Admin ตรวจสอบ";
            header("Location: " . $base_url . "/pages/claim_detail.php?id=" . $claim_id);
            exit;
        }

        $stmt = $pdo->prepare("SELECT c.*, i.title FROM claims c JOIN items i ON c.item_id = i.id WHERE c.id = ?");
        $stmt->execute([$claim_id]);
        $claim = $stmt->fetch();

        if (!$claim || ($claim['finder_id'] != $user_id && $claim['claimant_id'] != $user_id)) {
            $_SESSION['error'] = "คุณไม่มีสิทธิ์ยื่นคัดค้านคำร้องนี้";
            header("Location: " . $base_url . "/pages/claims.php");
            exit;
        }

        $disputed_by = ($claim['finder_id'] == $user_id) ? 'finder' : 'claimant';

        $pdo->prepare("UPDATE claims SET status = 'under_admin_review', disputed_by = ?, dispute_reason = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$disputed_by, $dispute_reason, $claim_id]);

        // แจ้งเตือนฝ่ายตรงข้าม
        $other_party_id = ($disputed_by === 'finder') ? $claim['claimant_id'] : $claim['finder_id'];
        $notify_msg = "คำร้องขอ Claim สิ่งของ '[{$claim['title']}]' ถูกส่งให้ Admin ร่วมตรวจสอบข้อพิพาท";
        $notify_link = "pages/claim_detail.php?id=" . $claim_id;
        add_notification($pdo, $other_party_id, $notify_msg, $notify_link);

        // แจ้งเตือน Admins ทุกคน
        $admin_stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
        while ($admin = $admin_stmt->fetch()) {
            add_notification($pdo, $admin['id'], "มีข้อพิพาทคำร้อง Claim ใหม่ '[{$claim['title']}]' รอการตรวจสอบ", "pages/admin/admin_claims.php");
        }

        $_SESSION['success'] = "ส่งคำร้องเข้าสู่ระบบตรวจสอบของ Admin เรียบร้อยแล้ว ทีมงานจะพิจารณาหลักฐานและดำเนินการในลำดับต่อไป";
        header("Location: " . $base_url . "/pages/claim_detail.php?id=" . $claim_id);
        exit;

    // ----------------------------------------------------
    // 4. Admin Decision (Admin อนุมัติหรือปฏิเสธคำร้อง)
    // ----------------------------------------------------
    case 'admin_decision':
        if ($user_role !== 'admin') {
            $_SESSION['error'] = "เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถดำเนินการได้";
            header("Location: " . $base_url . "/pages/claims.php");
            exit;
        }

        $claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;
        $decision = $_POST['decision'] ?? ''; // 'approve' or 'reject'
        $admin_notes = trim($_POST['admin_notes'] ?? '');

        $stmt = $pdo->prepare("SELECT c.*, i.title FROM claims c JOIN items i ON c.item_id = i.id WHERE c.id = ?");
        $stmt->execute([$claim_id]);
        $claim = $stmt->fetch();

        if (!$claim) {
            $_SESSION['error'] = "ไม่พบคำร้องขอ Claim ที่อ้างถึง";
            header("Location: " . $base_url . "/pages/admin/admin_claims.php");
            exit;
        }

        if (empty($admin_notes)) {
            $_SESSION['error'] = "กรุณากรอกบันทึกเหตุผลก่อนดำเนินการอนุมัติหรือปฏิเสธ";
            $redirect_target = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'claim_detail.php') !== false)
                ? $base_url . "/pages/claim_detail.php?id=" . $claim_id
                : $base_url . "/pages/admin/admin_claims.php";
            header("Location: " . $redirect_target);
            exit;
        }

        if ($decision === 'approve') {
            $pdo->prepare("UPDATE claims SET status = 'approved', admin_id = ?, admin_notes = ?, admin_action_at = NOW(), updated_at = NOW() WHERE id = ?")
                ->execute([$user_id, $admin_notes, $claim_id]);
            $pdo->prepare("UPDATE items SET status = 'pending', updated_at = NOW() WHERE id = ?")
                ->execute([$claim['item_id']]);

            // เคลียร์คำร้องซ้อนอื่นๆ บนสิ่งของชิ้นเดียวกันให้อัตโนมัติ
            $other_claims_stmt = $pdo->prepare("SELECT id, claimant_id FROM claims WHERE item_id = ? AND id != ? AND status IN ('pending', 'under_admin_review')");
            $other_claims_stmt->execute([$claim['item_id'], $claim_id]);
            $other_claims = $other_claims_stmt->fetchAll();

            if (!empty($other_claims)) {
                $auto_reject_note = "สิ่งของชิ้นนี้ได้รับการยืนยันและอนุมัติส่งคืนให้แก่ผู้ยื่นคำร้องท่านอื่นเรียบร้อยแล้ว";
                $pdo->prepare("UPDATE claims SET status = 'rejected', admin_notes = ?, updated_at = NOW() WHERE item_id = ? AND id != ? AND status IN ('pending', 'under_admin_review')")
                    ->execute([$auto_reject_note, $claim['item_id'], $claim_id]);

                foreach ($other_claims as $other) {
                    add_notification($pdo, $other['claimant_id'], "คำร้องขอ Claim สิ่งของ '[{$claim['title']}]' ของคุณถูกยกเลิก เนื่องจากสิ่งของได้รับการอนุมัติส่งคืนให้แก่เจ้าของที่แท้จริงเรียบร้อยแล้ว", "pages/claim_detail.php?id=" . $other['id']);
                }
            }

            add_notification($pdo, $claim['claimant_id'], "Admin ได้อนุมัติคำร้องขอ Claim '[{$claim['title']}]' เรียบร้อยแล้ว สามารถนัดรับสิ่งของได้", "pages/claim_detail.php?id=" . $claim_id);
            add_notification($pdo, $claim['finder_id'], "Admin ได้ตรวจสอบและอนุมัติคำร้องขอ Claim '[{$claim['title']}]' แล้ว โปรดดำเนินนัดส่งมอบ", "pages/claim_detail.php?id=" . $claim_id);
            
            log_admin_action($pdo, $user_id, 'approve_claim', "อนุมัติคำร้อง Claim ID #{$claim_id} สำหรับประกาศ '{$claim['title']}'");
            $_SESSION['success'] = "อนุมัติคำร้องขอ Claim สำเร็จแล้ว";

        } elseif ($decision === 'reject') {
            $pdo->prepare("UPDATE claims SET status = 'rejected', admin_id = ?, admin_notes = ?, admin_action_at = NOW(), updated_at = NOW() WHERE id = ?")
                ->execute([$user_id, $admin_notes, $claim_id]);
            // คืนสถานะ Item เป็น open
            $pdo->prepare("UPDATE items SET status = 'open', updated_at = NOW() WHERE id = ?")
                ->execute([$claim['item_id']]);

            $reason_text = !empty($admin_notes) ? "เนื่องจาก: " . $admin_notes : "เนื่องจากหลักฐานไม่เพียงพอ";
            add_notification($pdo, $claim['claimant_id'], "Admin ได้ปฏิเสธคำร้องขอ Claim '[{$claim['title']}]' {$reason_text}", "pages/claim_detail.php?id=" . $claim_id);
            add_notification($pdo, $claim['finder_id'], "Admin ได้ปฏิเสธคำร้องขอ Claim '[{$claim['title']}]' แล้ว ประกาศถูกเปิดให้ค้นหาตามปกติ", "pages/claim_detail.php?id=" . $claim_id);

            log_admin_action($pdo, $user_id, 'reject_claim', "ปฏิเสธคำร้อง Claim ID #{$claim_id} สำหรับประกาศ '{$claim['title']}'");

            $_SESSION['success'] = "ปฏิเสธคำร้องขอ Claim และเปิดประกาศกลับเป็น Open สำเร็จแล้ว";
        }

        $redirect_target = (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'claim_detail.php') !== false)
            ? $base_url . "/pages/claim_detail.php?id=" . $claim_id
            : $base_url . "/pages/admin/admin_claims.php";

        header("Location: " . $redirect_target);
        exit;

    // ----------------------------------------------------
    // 5. Cancel Mismatch (ยกเลิกเนื่องจากนัดเจอแล้วไม่ตรงกัน)
    // ----------------------------------------------------
    case 'cancel_mismatch':
        $claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;
        $cancel_reason = trim($_POST['cancel_reason'] ?? 'ตรวจสอบสิ่งของจริงแล้วไม่ใช่ของผู้ขอ Claim');

        $stmt = $pdo->prepare("SELECT c.*, i.title FROM claims c JOIN items i ON c.item_id = i.id WHERE c.id = ?");
        $stmt->execute([$claim_id]);
        $claim = $stmt->fetch();

        if (!$claim || ($claim['finder_id'] != $user_id && $claim['claimant_id'] != $user_id && $user_role !== 'admin')) {
            $_SESSION['error'] = "คุณไม่มีสิทธิ์ดำเนินการนี้";
            header("Location: " . $base_url . "/pages/claims.php");
            exit;
        }

        // ปรับสถานะ Claim -> cancelled_mismatch, Item -> open
        $pdo->prepare("UPDATE claims SET status = 'cancelled_mismatch', cancel_reason = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$cancel_reason, $claim_id]);
        $pdo->prepare("UPDATE items SET status = 'open', updated_at = NOW() WHERE id = ?")
            ->execute([$claim['item_id']]);

        // แจ้งเตือนทั้ง 2 ฝ่าย
        $msg = "การยื่น Claim สำหรับ '[{$claim['title']}]' ถูกยกเลิกเนื่องจากตรวจสอบแล้วสิ่งของไม่ตรงกัน ประกาศเปิดให้ค้นหาตามปกติแล้ว";
        add_notification($pdo, $claim['claimant_id'], $msg, "pages/claim_detail.php?id=" . $claim_id);
        add_notification($pdo, $claim['finder_id'], $msg, "pages/claim_detail.php?id=" . $claim_id);

        $_SESSION['success'] = "ยกเลิกคำร้องเนื่องจากสิ่งของไม่ตรงกันเรียบร้อย ประกาศถูกเปิดให้ค้นหาและจับคู่ใหม่อีกครั้ง";
        header("Location: " . $base_url . "/pages/claim_detail.php?id=" . $claim_id);
        exit;

    // ----------------------------------------------------
    // 6. Complete Claim (ยืนยันรับของสำเร็จ)
    // ----------------------------------------------------
    case 'complete_claim':
        $claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;

        $stmt = $pdo->prepare("SELECT c.*, i.title FROM claims c JOIN items i ON c.item_id = i.id WHERE c.id = ?");
        $stmt->execute([$claim_id]);
        $claim = $stmt->fetch();

        if (!$claim || ($claim['finder_id'] != $user_id && $claim['claimant_id'] != $user_id)) {
            $_SESSION['error'] = "คุณไม่มีสิทธิ์ดำเนินการนี้";
            header("Location: " . $base_url . "/pages/claims.php");
            exit;
        }

        // ปรับสถานะ Claim -> completed, Item -> resolved
        $pdo->prepare("UPDATE claims SET status = 'completed', updated_at = NOW() WHERE id = ?")->execute([$claim_id]);
        $pdo->prepare("UPDATE items SET status = 'resolved', updated_at = NOW() WHERE id = ?")->execute([$claim['item_id']]);

        $msg = "การส่งมอบสิ่งของ '[{$claim['title']}]' เสร็จสมบูรณ์แล้ว ขอบคุณที่ใช้งานระบบ Lost & Found!";
        add_notification($pdo, $claim['claimant_id'], $msg, "pages/claim_detail.php?id=" . $claim_id);
        add_notification($pdo, $claim['finder_id'], $msg, "pages/claim_detail.php?id=" . $claim_id);

        $_SESSION['success'] = "ยืนยันการรับส่งมอบสิ่งของสำเร็จเรียบร้อย ประกาศปรับสถานะเป็นส่งคืนสำเร็จแล้ว";
        header("Location: " . $base_url . "/pages/claim_detail.php?id=" . $claim_id);
        exit;

    default:
        $_SESSION['error'] = "คำสั่งไม่ถูกต้อง";
        header("Location: " . $base_url . "/pages/claims.php");
        exit;
}
