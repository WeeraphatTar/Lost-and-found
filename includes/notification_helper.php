<?php
/**
 * Notification Helper Functions
 */

if (!function_exists('add_notification')) {
    /**
     * Add a new notification for a user
     */
    function add_notification($pdo, $user_id, $message, $link = null) {
        try {
            $sql = "INSERT INTO notifications (user_id, message, link, is_read, created_at) VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP)";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$user_id, $message, $link]);
        } catch (PDOException $e) {
            error_log("Error adding notification: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('get_unread_notifications')) {
    /**
     * Get unread notifications for a user
     */
    function get_unread_notifications($pdo, $user_id, $limit = 5) {
        try {
            $sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('get_unread_count')) {
    /**
     * Get count of unread notifications
     */
    function get_unread_count($pdo, $user_id) {
        try {
            $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
}
?>
