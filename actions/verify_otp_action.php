<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_config.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/rate_limit_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/login.php");
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "คำขอไม่ถูกต้อง หรือ CSRF Token หมดอายุ กรุณาลองใหม่อีกครั้ง";
    header("Location: ../pages/verify_otp.php");
    exit;
}

$email = $_SESSION['pending_verification_email'] ?? '';
$otp_code = trim($_POST['otp_code'] ?? '');

if (empty($email)) {
    header("Location: ../pages/login.php");
    exit;
}

if (empty($otp_code) || strlen($otp_code) !== 6) {
    $_SESSION['error'] = "กรุณากรอกรหัส OTP ตัวเลข 6 หลักให้ถูกต้อง";
    header("Location: ../pages/verify_otp.php");
    exit;
}

$client_ip = get_client_ip();
$rate_check = check_rate_limit($pdo, $email, 'verify_otp', OTP_MAX_ATTEMPTS, LOGIN_LOCKOUT_MINUTES);
if (!$rate_check['allowed']) {
    $_SESSION['error'] = $rate_check['message'];
    header("Location: ../pages/verify_otp.php");
    exit;
}

try {
    // ดึง OTP ล่าสุดที่ยังไม่หมดอายุ
    $stmt = $pdo->prepare("SELECT id, user_id, otp_hash, attempts, expires_at FROM email_otps WHERE email = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email]);
    $otp_record = $stmt->fetch();

    $now = date('Y-m-d H:i:s');

    if (!$otp_record || $otp_record['expires_at'] < $now) {
        $_SESSION['error'] = "รหัส OTP หมดอายุแล้ว กรุณากดส่งรหัสอีกครั้ง";
        header("Location: ../pages/verify_otp.php");
        exit;
    }

    if ($otp_record['attempts'] >= OTP_MAX_ATTEMPTS) {
        $_SESSION['error'] = "คุณกรอกรหัส OTP ผิดเกินจำนวนครั้งที่กำหนด กรุณากดขอรหัส OTP ใหม่";
        header("Location: ../pages/verify_otp.php");
        exit;
    }

    // ตรวจสอบ OTP Hash
    if (hash_equals($otp_record['otp_hash'], hash('sha256', $otp_code))) {
        // ยืนยันสำเร็จ
        clear_rate_limit($pdo, $email, 'verify_otp');
        clear_rate_limit($pdo, $email, 'resend_otp');

        // อัปเดตสถานะผู้ใช้เป็น Verified
        $update_stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
        $update_stmt->execute([$email]);

        // ลบ OTP ที่ใช้แล้ว
        $del_stmt = $pdo->prepare("DELETE FROM email_otps WHERE email = ?");
        $del_stmt->execute([$email]);

        unset($_SESSION['pending_verification_email']);
        $_SESSION['success'] = "✓ ยืนยันบัญชีสำเร็จ! บัญชีของคุณพร้อมใช้งานแล้ว กรุณาเข้าสู่ระบบ";
        header("Location: ../pages/login.php");
        exit;
    } else {
        // OTP ผิด
        $new_attempts = $otp_record['attempts'] + 1;
        $upd_attempts = $pdo->prepare("UPDATE email_otps SET attempts = ? WHERE id = ?");
        $upd_attempts->execute([$new_attempts, $otp_record['id']]);

        record_rate_limit_attempt($pdo, $email, 'verify_otp', OTP_MAX_ATTEMPTS, LOGIN_LOCKOUT_MINUTES);

        $remaining = OTP_MAX_ATTEMPTS - $new_attempts;
        if ($remaining > 0) {
            $_SESSION['error'] = "รหัส OTP ไม่ถูกต้อง (เหลือโอกาสลองอีก $remaining ครั้ง)";
        } else {
            $_SESSION['error'] = "คุณกรอกรหัส OTP ผิดเกินจำนวนครั้งที่กำหนด กรุณากดขอรหัส OTP ใหม่";
        }
        header("Location: ../pages/verify_otp.php");
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดของฐานข้อมูล: " . $e->getMessage();
    header("Location: ../pages/verify_otp.php");
    exit;
}
?>
