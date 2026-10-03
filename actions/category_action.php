<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/admin_log_helper.php';

// Verify Admin Role
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    $_SESSION['error'] = "เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถดำเนินการนี้ได้";
    header("Location: " . $base_url . "/pages/login.php");
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create_category') {
    $name_th = trim($_POST['name_th'] ?? '');
    $code_name = strtolower(trim($_POST['code_name'] ?? ''));
    $icon = trim($_POST['icon'] ?? 'box');
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    // Auto-generate code_name if empty
    if (empty($code_name) && !empty($name_th)) {
        $code_name = 'cat_' . time();
    }

    if (empty($name_th)) {
        $_SESSION['error'] = "กรุณากรอกชื่อหมวดหมู่ภาษาไทย";
        header("Location: " . $base_url . "/pages/admin/admin_categories.php");
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO categories (code_name, name_th, icon, sort_order, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$code_name, $name_th, $icon, $sort_order]);

        log_admin_action($pdo, $_SESSION['user_id'], 'create_category', "เพิ่มหมวดหมู่ใหม่: {$name_th} ({$code_name})");
        $_SESSION['success'] = "เพิ่มหมวดหมู่ '{$name_th}' เรียบร้อยแล้ว";
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด หรือรหัสหมวดหมู่นี้มีอยู่ในระบบแล้ว";
    }

    header("Location: " . $base_url . "/pages/admin/admin_categories.php");
    exit;
}

if ($action === 'toggle_status') {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $current_status = (int)($_POST['current_status'] ?? 1);
    $new_status = ($current_status === 1) ? 0 : 1;

    try {
        $stmt = $pdo->prepare("UPDATE categories SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $category_id]);

        $status_text = ($new_status === 1) ? "เปิดใช้งาน" : "ปิดใช้งาน";
        log_admin_action($pdo, $_SESSION['user_id'], 'toggle_category', "เปลี่ยนสถานะหมวดหมู่ ID #{$category_id} เป็น{$status_text}");
        $_SESSION['success'] = "อัปเดตสถานะหมวดหมู่เรียบร้อยแล้ว";
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตสถานะ";
    }

    header("Location: " . $base_url . "/pages/admin/admin_categories.php");
    exit;
}

if ($action === 'edit_category') {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name_th = trim($_POST['name_th'] ?? '');
    $icon = trim($_POST['icon'] ?? 'box');
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    if (empty($name_th) || $category_id <= 0) {
        $_SESSION['error'] = "ข้อมูลไม่ถูกต้อง";
        header("Location: " . $base_url . "/pages/admin/admin_categories.php");
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE categories SET name_th = ?, icon = ?, sort_order = ? WHERE id = ?");
        $stmt->execute([$name_th, $icon, $sort_order, $category_id]);

        log_admin_action($pdo, $_SESSION['user_id'], 'edit_category', "แก้ไขหมวดหมู่ ID #{$category_id}: {$name_th}");
        $_SESSION['success'] = "แก้ไขข้อมูลหมวดหมู่เรียบร้อยแล้ว";
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการแก้ไขหมวดหมู่";
    }

    header("Location: " . $base_url . "/pages/admin/admin_categories.php");
    exit;
}

header("Location: " . $base_url . "/pages/admin/admin_categories.php");
exit;
