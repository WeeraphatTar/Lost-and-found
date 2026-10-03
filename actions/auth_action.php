<?php
session_start();
require_once '../config/database.php';

// รับค่าจาก Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // กรณีสมัครสมาชิก
    if ($action === 'register') {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // ตรวจสอบข้อมูลเบื้องต้น
        if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password)) {
            $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
            header("Location: ../pages/register.php");
            exit;
        }

        // ตรวจสอบรหัสผ่านตรงกัน
        if ($password !== $confirm_password) {
            $_SESSION['error'] = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน";
            header("Location: ../pages/register.php");
            exit;
        }

        // ตรวจสอบความยาวรหัสผ่าน
        if (strlen($password) < 8) {
            $_SESSION['error'] = "รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร";
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

            // บันทึกลงฐานข้อมูล
            $insert_stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
            if ($insert_stmt->execute([$first_name, $last_name, $email, $phone, $password_hash])) {
                $_SESSION['success'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
                header("Location: ../pages/login.php");
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

    // กรณีเข้าสู่ระบบ
    if ($action === 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = "กรุณากรอกอีเมลและรหัสผ่าน";
            header("Location: ../pages/login.php");
            exit;
        }

        try {
            // ดึงข้อมูลผู้ใช้จากฐานข้อมูล
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, password_hash, role, is_banned, ban_reason FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // ตรวจสอบว่าพบผู้ใช้ และรหัสผ่านถูกต้องหรือไม่
            if ($user && password_verify($password, $user['password_hash'])) {
                // ตรวจสอบสถานะการถูกระงับสิทธิ์
                if (!empty($user['is_banned'])) {
                    $reason_text = !empty($user['ban_reason']) ? " เนื่องจาก: " . $user['ban_reason'] : "";
                    $_SESSION['error'] = "บัญชีนี้ถูกระงับการใช้งาน" . $reason_text;
                    header("Location: ../pages/login.php");
                    exit;
                }

                // รหัสผ่านถูกต้อง สร้าง Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'];
                $_SESSION['user_role'] = $user['role'];

                // ระบบจดจำฉันไว้ (Remember Me)
                if (isset($_POST['remember'])) {
                    $token = bin2hex(random_bytes(32));
                    // บันทึก token ลงฐานข้อมูล
                    $update_stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $update_stmt->execute([$token, $user['id']]);
                    // สร้าง Cookie มีอายุ 30 วัน
                    setcookie('remember_token', $token, time() + (86400 * 30), "/");
                    setcookie('remember_user', $user['id'], time() + (86400 * 30), "/");
                }
                
                if ($user['role'] === 'admin') {
                    header("Location: ../pages/admin/admin_dashboard.php");
                } else {
                    header("Location: ../index.php");
                }
                exit;
            } else {
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
}

// กรณีออกจากระบบ (ใช้ GET method)
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (isset($_SESSION['user_id'])) {
        // ล้าง token ในฐานข้อมูล
        $update_stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $update_stmt->execute([$_SESSION['user_id']]);
    }
    
    // ล้าง Cookie
    setcookie('remember_token', '', time() - 3600, "/");
    setcookie('remember_user', '', time() - 3600, "/");

    session_destroy();
    header("Location: ../index.php");
    exit;
}
?>
