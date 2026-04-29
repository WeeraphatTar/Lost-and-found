<?php
session_start();
require_once '../config/database.php';

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
        try {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
        } catch (PDOException $e) {
            // Log error if needed
        }
    }

    if (!empty($link)) {
        header("Location: ../" . $link);
    } else {
        header("Location: ../index.php");
    }
    exit;
}

if ($action === 'mark_all_read') {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        // Log error if needed
    }
    
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
    exit;
}

header("Location: ../index.php");
exit;
?>
