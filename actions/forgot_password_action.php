<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_config.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/rate_limit_helper.php';
require_once __DIR__ . '/../helpers/mail_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/login.php");
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
    header("Location: ../pages/forgot_password.php");
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "กรุณากรอกรูปแบบอีเมลให้ถูกต้อง";
    header("Location: ../pages/forgot_password.php");
    exit;
}

// Uniform response message to prevent email enumeration
$uniform_message = 'ระบบได้ส่งลิงก์รีเซ็ตรหัสผ่านไปยังอีเมลของคุณแล้ว<span class="block text-sm opacity-85 mt-0.5">(โปรดตรวจสอบในกล่องข้อความหรืออีเมลขยะ)</span>';

// Check rate limit (Max 3 attempts per hour)
$rate_check = check_rate_limit($pdo, $email, 'forgot_password', 3, 60);
if (!$rate_check['allowed']) {
    $_SESSION['error'] = $rate_check['message'];
    header("Location: ../pages/forgot_password.php");
    exit;
}

record_rate_limit_attempt($pdo, $email, 'forgot_password', 3, 60);

try {
    // ดึงข้อมูลผู้ใช้
    $stmt = $pdo->prepare("SELECT id, first_name, is_banned FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && empty($user['is_banned'])) {
        // สร้าง 32-byte cryptographically secure token
        $raw_token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $raw_token);
        $expires_at = date('Y-m-d H:i:s', time() + (RESET_TOKEN_EXPIRY_MINUTES * 60));

        // ยกเลิก Token เก่าทั้งหมดของอีเมลนี้
        $del_stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $del_stmt->execute([$email]);

        // บันทึก Token Hash
        $ins_stmt = $pdo->prepare("INSERT INTO password_resets (user_id, email, token_hash, used, expires_at) VALUES (?, ?, ?, 0, ?)");
        $ins_stmt->execute([$user['id'], $email, $token_hash, $expires_at]);

        // ส่ง Email ลิงก์รีเซ็ตรหัสผ่าน
        $reset_url = APP_URL . "/pages/reset_password.php?token=" . urlencode($raw_token);
        send_password_reset_email($email, $user['first_name'], $reset_url);
    }

    $_SESSION['success'] = $uniform_message;
    header("Location: ../pages/forgot_password.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการประมวลผล กรุณาลองใหม่อีกครั้ง";
    header("Location: ../pages/forgot_password.php");
    exit;
}
?>
