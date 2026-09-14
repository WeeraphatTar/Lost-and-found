<?php
session_start();
require_once '../config/database.php';
require_once '../helpers/notification_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'read') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $link = $_GET['link'] ?? '';

    if ($id > 0) {
        mark_notification_read($pdo, $user_id, $id);
    }

    if (!empty($link)) {
        header("Location: ../" . $link);
    } else {
        header("Location: ../pages/notifications.php");
    }
    exit;
}

if ($action === 'mark_read') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0) {
        mark_notification_read($pdo, $user_id, $id);
    }
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../pages/notifications.php'));
    exit;
}

if ($action === 'delete') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0) {
        delete_notification($pdo, $user_id, $id);
    }
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../pages/notifications.php'));
    exit;
}

if ($action === 'mark_all_read') {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        // Log error if needed
    }
    
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../pages/notifications.php'));
    exit;
}

if ($action === 'delete_all_read') {
    delete_all_read($pdo, $user_id);
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../pages/notifications.php'));
    exit;
}

header("Location: ../index.php");
exit;
?>
