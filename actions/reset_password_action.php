<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_config.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/login.php");
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
    header("Location: ../pages/forgot_password.php");
    exit;
}

$raw_token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($raw_token)) {
    $_SESSION['error'] = "ไม่พบข้อมูล Token สำหรับรีเซ็ตรหัสผ่าน";
    header("Location: ../pages/forgot_password.php");
    exit;
}

if (empty($password) || empty($confirm_password)) {
    $_SESSION['error'] = "กรุณากรอกรหัสผ่านใหม่ให้ครบถ้วน";
    header("Location: ../pages/reset_password.php?token=" . urlencode($raw_token));
    exit;
}

if ($password !== $confirm_password) {
    $_SESSION['error'] = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน";
    header("Location: ../pages/reset_password.php?token=" . urlencode($raw_token));
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['error'] = "รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร";
    header("Location: ../pages/reset_password.php?token=" . urlencode($raw_token));
    exit;
}

$common_passwords = ['12345678', 'password', '123456789', 'qwertyuiop'];
if (in_array(strtolower($password), $common_passwords)) {
    $_SESSION['error'] = "รหัสผ่านนี้มีความเสี่ยงสูง กรุณาตั้งรหัสผ่านที่คาดเดาได้ยากขึ้น";
    header("Location: ../pages/reset_password.php?token=" . urlencode($raw_token));
    exit;
}

try {
    $token_hash = hash('sha256', $raw_token);
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT id, user_id, email, used, expires_at FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > ?");
    $stmt->execute([$token_hash, $now]);
    $reset_record = $stmt->fetch();

    if (!$reset_record) {
        $_SESSION['error'] = "ลิงก์รีเซ็ตรหัสผ่านนี้หมดอายุ หรือถูกใช้งานไปแล้ว กรุณาส่งคำขอใหม่อีกครั้ง";
        header("Location: ../pages/forgot_password.php");
        exit;
    }

    $user_id = $reset_record['user_id'];
    $new_hash = password_hash($password, PASSWORD_DEFAULT);

    // 1. อัปเดตรหัสผ่านใหม่ + เพิ่ม session_version + 1 เพื่อทำลาย Session อุปกรณ์อื่นทั้งหมด
    $update_user = $pdo->prepare("UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?");
    $update_user->execute([$new_hash, $user_id]);

    // 2. ล้าง Token Remember Me ทั้งหมดใน DB ของผู้ใช้นี้
    $del_rem = $pdo->prepare("DELETE FROM user_remember_tokens WHERE user_id = ?");
    $del_rem->execute([$user_id]);

    // 3. ปักป้ายว่า Reset Token นี้ถูกใช้แล้ว
    $mark_used = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
    $mark_used->execute([$reset_record['id']]);

    // 4. หาก Session ปัจจุบันเป็นของผู้ใช้ท่านนี้ ให้ล้าง Session ออกเพื่อให้ Login ใหม่
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }

    $_SESSION['success'] = "✓ เปลี่ยนรหัสผ่านสำเร็จ! กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่";
    header("Location: ../pages/login.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดของฐานข้อมูล: " . $e->getMessage();
    header("Location: ../pages/reset_password.php?token=" . urlencode($raw_token));
    exit;
}
?>
