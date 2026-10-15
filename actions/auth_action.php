<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_config.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/rate_limit_helper.php';
require_once __DIR__ . '/../helpers/mail_helper.php';

// รับค่าจาก Form (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ตรวจสอบ CSRF Token ในทุก POST Action
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "คำขอไม่ถูกต้อง หรือ CSRF Token หมดอายุ กรุณาลองใหม่อีกครั้ง";
        header("Location: ../pages/" . ($action === 'register' ? 'register.php' : 'login.php'));
        exit;
    }

    // ==========================================
    // 1. กรณีสมัครสมาชิก (Register)
    // ==========================================
    if ($action === 'register') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $accept_terms = isset($_POST['accept_terms']);
        $accept_privacy = isset($_POST['accept_privacy']);

        // ตรวจสอบข้อมูลไม่ให้ว่าง
        if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password)) {
            $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
            header("Location: ../pages/register.php");
            exit;
        }

        // ตรวจสอบการยอมรับเงื่อนไข
        if (!$accept_terms || !$accept_privacy) {
            $_SESSION['error'] = "กรุณากดยอมรับข้อตกลงการใช้งานและนโยบายความเป็นส่วนตัว";
            header("Location: ../pages/register.php");
            exit;
        }

        // ตรวจสอบรูปแบบ Email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "รูปแบบอีเมลไม่ถูกต้อง กรุณาระบุอีเมลที่ใช้งานได้จริง";
            header("Location: ../pages/register.php");
            exit;
        }

        // ตรวจสอบรูปแบบเบอร์โทรศัพท์ (9-10 หลัก ขึ้นต้นด้วย 0)
        if (!preg_match('/^0[0-9]{8,9}$/', $phone)) {
            $_SESSION['error'] = "รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง (ต้องเป็นตัวเลข 9-10 หลัก ขึ้นต้นด้วย 0)";
            header("Location: ../pages/register.php");
            exit;
        }

        // ตรวจสอบรหัสผ่านตรงกัน
        if ($password !== $confirm_password) {
            $_SESSION['error'] = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน";
            header("Location: ../pages/register.php");
            exit;
        }

        // ตรวจสอบความยาวรหัสผ่าน (อย่างน้อย 8 ตัวอักษร)
        if (strlen($password) < 8) {
            $_SESSION['error'] = "รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร";
            header("Location: ../pages/register.php");
            exit;
        }

        // ตรวจสอบการเดาง่ายของรหัสผ่าน
        $common_passwords = ['12345678', 'password', '123456789', 'qwertyuiop', strtolower($first_name), strtolower($email)];
        if (in_array(strtolower($password), $common_passwords)) {
            $_SESSION['error'] = "รหัสผ่านนี้มีความเสี่ยงสูง กรุณาตั้งรหัสผ่านที่คาดเดาได้ยากขึ้น";
            header("Location: ../pages/register.php");
            exit;
        }

        try {
            // ตรวจสอบว่าอีเมลซ้ำหรือไม่
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = "อีเมลนี้มีผู้ใช้งานแล้ว โปรดใช้อีเมลอื่น หรือเข้าสู่ระบบ";
                header("Location: ../pages/register.php");
                exit;
            }

            // เข้ารหัสผ่าน
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // บันทึกลงฐานข้อมูล โดยตั้งค่า is_verified = 0 และ session_version = 1
            $insert_stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password_hash, is_verified, session_version) VALUES (?, ?, ?, ?, ?, 0, 1)");
            
            if ($insert_stmt->execute([$first_name, $last_name, $email, $phone, $password_hash])) {
                $user_id = $pdo->lastInsertId();

                // สุ่ม OTP 6 หลัก
                $otp_code = sprintf("%06d", random_int(0, 999999));
                $otp_hash = hash('sha256', $otp_code);
                $expires_at = date('Y-m-d H:i:s', time() + (OTP_EXPIRY_MINUTES * 60));

                // บันทึก OTP Hash ลง email_otps
                $otp_stmt = $pdo->prepare("INSERT INTO email_otps (user_id, email, otp_hash, attempts, expires_at) VALUES (?, ?, ?, 0, ?)");
                $otp_stmt->execute([$user_id, $email, $otp_hash, $expires_at]);

                // ส่ง Email OTP
                send_otp_email($email, $first_name, $otp_code);

                $_SESSION['pending_verification_email'] = $email;
                $_SESSION['last_otp_resend_time'] = time();
                $_SESSION['success'] = "สมัครสมาชิกสำเร็จ! ระบบได้ส่งรหัส OTP ไปยังอีเมลของคุณแล้ว กรุณายืนยันตัวตน";
                header("Location: ../pages/verify_otp.php");
                exit;
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง";
                header("Location: ../pages/register.php");
                exit;
            }

        } catch (PDOException $e) {
            $_SESSION['error'] = "Database Error: " . $e->getMessage();
            header("Location: ../pages/register.php");
            exit;
        }
    }

    // ==========================================
    // 2. กรณีเข้าสู่ระบบ (Login)
    // ==========================================
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = "กรุณากรอกอีเมลและรหัสผ่าน";
            header("Location: ../pages/login.php");
            exit;
        }

        // ตรวจสอบ Rate Limiting (จำกัด Login ผิด 5 ครั้ง / 15 นาที)
        $client_ip = get_client_ip();
        $rate_check = check_rate_limit($pdo, $email, 'login', LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_MINUTES);
        if (!$rate_check['allowed']) {
            $_SESSION['error'] = $rate_check['message'];
            header("Location: ../pages/login.php");
            exit;
        }

        try {
            // ดึงข้อมูลผู้ใช้จากฐานข้อมูล
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, password_hash, role, is_verified, is_banned, ban_reason, session_version FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // ตรวจสอบว่าเป็นบัญชี Social-only (ไม่มี password_hash) หรือไม่
            if ($user && empty($user['password_hash'])) {
                $_SESSION['error'] = "บัญชีนี้สมัครใช้งานผ่าน Social Login กรุณาเข้าสู่ระบบด้วย Google หรือ Facebook";
                header("Location: ../pages/login.php");
                exit;
            }

            // ตรวจสอบว่าพบผู้ใช้ และรหัสผ่านถูกต้องหรือไม่
            if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                
                // ตรวจสอบสถานะการยืนยันอีเมล
                if ($user['is_verified'] == 0) {
                    $_SESSION['pending_verification_email'] = $email;
                    $_SESSION['error'] = "บัญชีของคุณยังไม่ได้ยืนยันอีเมล กรุณากรอกรหัส OTP ยืนยันตัวตนก่อนใช้งาน";
                    header("Location: ../pages/verify_otp.php");
                    exit;
                }

                // ตรวจสอบสถานะการถูกระงับสิทธิ์
                if (!empty($user['is_banned'])) {
                    $reason_text = !empty($user['ban_reason']) ? " เนื่องจาก: " . $user['ban_reason'] : "";
                    $_SESSION['error'] = "บัญชีนี้ถูกระงับการใช้งาน" . $reason_text;
                    header("Location: ../pages/login.php");
                    exit;
                }

                // ล้างประวัติ Rate Limit
                clear_rate_limit($pdo, $email, 'login');

                // ป้องกัน Session Fixation
                session_regenerate_id(true);

                // สร้าง Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['session_version'] = $user['session_version'];

                // ระบบจดจำฉันไว้ (Remember Me) แบบ Selector / Validator Hash
                if (isset($_POST['remember'])) {
                    $selector = bin2hex(random_bytes(16));
                    $validator = bin2hex(random_bytes(32));
                    $token_hash = hash('sha256', $validator);
                    $expires_at = date('Y-m-d H:i:s', time() + (86400 * REMEMBER_ME_DAYS));

                    // บันทึก selector & token_hash ลงตาราง user_remember_tokens
                    $rem_stmt = $pdo->prepare("INSERT INTO user_remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)");
                    $rem_stmt->execute([$user['id'], $selector, $token_hash, $expires_at]);

                    // สร้าง Cookie แบบ Secure & HttpOnly & SameSite
                    $cookie_value = $selector . ':' . $validator;
                    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                    setcookie('remember_me', $cookie_value, time() + (86400 * REMEMBER_ME_DAYS), "/", "", $secure, true);
                }
                
                if ($user['role'] === 'admin') {
                    header("Location: ../pages/admin/admin_dashboard.php");
                } else {
                    header("Location: ../index.php");
                }
                exit;

            } else {
                // บันทึกความพยายาม Login ล้มเหลว
                record_rate_limit_attempt($pdo, $email, 'login', LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_MINUTES);
                $_SESSION['error'] = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
                header("Location: ../pages/login.php");
                exit;
            }

        } catch (PDOException $e) {
            $_SESSION['error'] = "Database Error: " . $e->getMessage();
            header("Location: ../pages/login.php");
            exit;
        }
    }

    // ==========================================
    // 3. กรณีออกจากระบบ (Logout - POST Method)
    // ==========================================
    if ($action === 'logout') {
        if (isset($_COOKIE['remember_me'])) {
            $parts = explode(':', $_COOKIE['remember_me']);
            if (count($parts) === 2) {
                $selector = $parts[0];
                $del_stmt = $pdo->prepare("DELETE FROM user_remember_tokens WHERE selector = ?");
                $del_stmt->execute([$selector]);
            }
            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            setcookie('remember_me', '', time() - 3600, "/", "", $secure, true);
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();

        header("Location: ../index.php");
        exit;
    }
} else {
    header("Location: ../index.php");
    exit;
}
?>
