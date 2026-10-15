<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_config.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/mail_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/login.php");
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
    header("Location: ../pages/verify_otp.php");
    exit;
}

$email = $_SESSION['pending_verification_email'] ?? '';

if (empty($email)) {
    header("Location: ../pages/login.php");
    exit;
}

// Cooldown check (60 seconds)
$last_resend = $_SESSION['last_otp_resend_time'] ?? 0;
$time_since_last = time() - $last_resend;

if ($time_since_last < OTP_RESEND_COOLDOWN_SECONDS) {
    $wait = OTP_RESEND_COOLDOWN_SECONDS - $time_since_last;
    $_SESSION['error'] = "กรุณารอ $wait วินาทีก่อนกดขอรหัส OTP ใหม่";
    header("Location: ../pages/verify_otp.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, first_name, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: ../pages/login.php");
        exit;
    }

    if ($user['is_verified'] == 1) {
        unset($_SESSION['pending_verification_email']);
        $_SESSION['success'] = "บัญชีนี้ได้รับการยืนยันแล้ว กรุณาเข้าสู่ระบบ";
        header("Location: ../pages/login.php");
        exit;
    }

    // ลบ OTP เก่าออก
    $del_stmt = $pdo->prepare("DELETE FROM email_otps WHERE email = ?");
    $del_stmt->execute([$email]);

    // สุ่ม OTP 6 หลักใหม่
    $otp_code = sprintf("%06d", random_int(0, 999999));
    $otp_hash = hash('sha256', $otp_code);
    $expires_at = date('Y-m-d H:i:s', time() + (OTP_EXPIRY_MINUTES * 60));

    $ins_stmt = $pdo->prepare("INSERT INTO email_otps (user_id, email, otp_hash, attempts, expires_at) VALUES (?, ?, ?, 0, ?)");
    $ins_stmt->execute([$user['id'], $email, $otp_hash, $expires_at]);

    // ส่ง Email
    send_otp_email($email, $user['first_name'], $otp_code);

    $_SESSION['last_otp_resend_time'] = time();
    $_SESSION['success'] = "ระบบได้ส่งรหัส OTP ใหม่ไปยังอีเมลของคุณเรียบร้อยแล้ว";
    header("Location: ../pages/verify_otp.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการส่ง OTP: " . $e->getMessage();
    header("Location: ../pages/verify_otp.php");
    exit;
}
?>
