<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_new_password = $_POST['confirm_new_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
            $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
            header("Location: ../pages/profile.php");
            exit;
        }

        if ($new_password !== $confirm_new_password) {
            $_SESSION['error'] = "รหัสผ่านใหม่ไม่ตรงกัน";
            header("Location: ../pages/profile.php");
            exit;
        }

        try {
            // Check current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_password, $user['password_hash'])) {
                // Update password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $update_stmt->execute([$new_hash, $user_id]);

                $_SESSION['success'] = "เปลี่ยนรหัสผ่านเรียบร้อยแล้ว";
            } else {
                $_SESSION['error'] = "รหัสผ่านเดิมไม่ถูกต้อง";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }

    header("Location: ../pages/profile.php");
    exit;

} else {
    header("Location: ../pages/profile.php");
    exit;
}
