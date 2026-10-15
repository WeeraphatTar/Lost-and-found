<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF Check
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
        header("Location: ../pages/profile.php");
        exit;
    }

    if ($action === 'update_profile') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');

        if (empty($first_name) || empty($last_name)) {
            $_SESSION['error'] = "กรุณากรอกชื่อและนามสกุล";
            header("Location: ../pages/profile.php");
            exit;
        }

        try {
            $sql = "UPDATE users SET first_name = ?, last_name = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$first_name, $last_name, $user_id]);

            // Update session name
            $_SESSION['user_name'] = $first_name;

            $_SESSION['success'] = "อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว";
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดต: " . $e->getMessage();
        }

    } elseif ($action === 'disconnect_social') {
        $provider = $_POST['provider'] ?? '';
        if (!in_array($provider, ['google', 'facebook'])) {
            $_SESSION['error'] = "ช่องทางไม่ถูกต้อง";
            header("Location: ../pages/profile.php");
            exit;
        }

        try {
            // Check if user has password_hash and count of social accounts
            $u_stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $u_stmt->execute([$user_id]);
            $user = $u_stmt->fetch();

            $s_stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM user_social_accounts WHERE user_id = ?");
            $s_stmt->execute([$user_id]);
            $social_count = $s_stmt->fetch()['cnt'];

            // Disconnect Safety Rule: Cannot disconnect if password_hash is NULL and social_count <= 1
            if (empty($user['password_hash']) && $social_count <= 1) {
                $_SESSION['error'] = "ไม่สามารถยกเลิกการเชื่อมต่อได้ เนื่องจากเป็นช่องทางเข้าสู่ระบบเพียงช่องทางเดียวของคุณ กรุณากำหนดรหัสผ่าน หรือเชื่อมต่อ Social Account อื่นก่อน";
                header("Location: ../pages/profile.php");
                exit;
            }

            // Execute Delete
            $del_stmt = $pdo->prepare("DELETE FROM user_social_accounts WHERE user_id = ? AND provider = ?");
            $del_stmt->execute([$user_id, $provider]);

            $_SESSION['success'] = "ยกเลิกการเชื่อมต่อกับ " . ucfirst($provider) . " เรียบร้อยแล้ว";
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการยกเลิกการเชื่อมต่อ: " . $e->getMessage();
        }

    } elseif ($action === 'set_initial_password' || $action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_new_password = $_POST['confirm_new_password'] ?? '';

        if ($action === 'change_password' && empty($current_password)) {
            $_SESSION['error'] = "กรุณากรอกรหัสผ่านเดิม";
            header("Location: ../pages/profile.php");
            exit;
        }

        if (empty($new_password) || empty($confirm_new_password)) {
            $_SESSION['error'] = "กรุณากรอกรหัสผ่านใหม่ให้ครบถ้วน";
            header("Location: ../pages/profile.php");
            exit;
        }

        if ($new_password !== $confirm_new_password) {
            $_SESSION['error'] = "รหัสผ่านใหม่ไม่ตรงกัน";
            header("Location: ../pages/profile.php");
            exit;
        }

        if (strlen($new_password) < 8) {
            $_SESSION['error'] = "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร";
            header("Location: ../pages/profile.php");
            exit;
        }

        $common_passwords = ['12345678', 'password', '123456789', 'qwertyuiop'];
        if (in_array(strtolower($new_password), $common_passwords)) {
            $_SESSION['error'] = "รหัสผ่านใหม่มีความเสี่ยงสูง กรุณาตั้งรหัสผ่านที่คาดเดาได้ยากขึ้น";
            header("Location: ../pages/profile.php");
            exit;
        }

        try {
            // Check current user details
            $stmt = $pdo->prepare("SELECT password_hash, session_version FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            $can_update = false;

            if ($action === 'set_initial_password') {
                // If user currently has no password, allow setting initial password without current_password
                if (empty($user['password_hash'])) {
                    $can_update = true;
                } else {
                    $_SESSION['error'] = "คุณมีรหัสผ่านเดิมอยู่แล้ว กรุณาใช้การเปลี่ยนรหัสผ่านปกติ";
                }
            } else { // change_password
                if (!empty($user['password_hash']) && password_verify($current_password, $user['password_hash'])) {
                    $can_update = true;
                } else {
                    $_SESSION['error'] = "รหัสผ่านเดิมไม่ถูกต้อง";
                }
            }

            if ($can_update) {
                // Update password & increment session_version
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $new_session_ver = ($user['session_version'] ?? 1) + 1;

                $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ?, session_version = ? WHERE id = ?");
                $update_stmt->execute([$new_hash, $new_session_ver, $user_id]);

                // Clear all remember me tokens for this user in DB
                $del_rem = $pdo->prepare("DELETE FROM user_remember_tokens WHERE user_id = ?");
                $del_rem->execute([$user_id]);

                // Update current session's version so this current user isn't logged out
                $_SESSION['session_version'] = $new_session_ver;

                $_SESSION['success'] = "กำหนดรหัสผ่านเรียบร้อยแล้ว (ยกเลิกการเข้าสู่ระบบในอุปกรณ์อื่นเรียบร้อยแล้ว)";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    } elseif ($action === 'update_notification_preferences') {
        $email_notify_matches = isset($_POST['email_notify_matches']) ? 1 : 0;
        $email_notify_claims = isset($_POST['email_notify_claims']) ? 1 : 0;
        $email_notify_messages = isset($_POST['email_notify_messages']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("
                INSERT INTO user_notification_settings (user_id, email_notify_matches, email_notify_claims, email_notify_messages, email_notify_system)
                VALUES (?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                    email_notify_matches = VALUES(email_notify_matches),
                    email_notify_claims = VALUES(email_notify_claims),
                    email_notify_messages = VALUES(email_notify_messages),
                    email_notify_system = 1,
                    updated_at = NOW()
            ");
            $stmt->execute([$user_id, $email_notify_matches, $email_notify_claims, $email_notify_messages]);

            $_SESSION['success'] = "บันทึกการตั้งค่าการแจ้งเตือนทางอีเมลเรียบร้อยแล้ว";
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกการตั้งค่า: " . $e->getMessage();
        }
    }

    header("Location: ../pages/profile.php");
    exit;

} else {
    header("Location: ../pages/profile.php");
    exit;
}
