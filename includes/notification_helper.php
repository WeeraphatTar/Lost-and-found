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
if (!function_exists('get_all_notifications')) {
    /**
     * Get all notifications for a user (read and unread)
     */
    function get_all_notifications($pdo, $user_id, $limit = 50) {
        try {
            $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting all notifications: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('mark_notification_read')) {
    /**
     * Mark a specific notification as read
     */
    function mark_notification_read($pdo, $user_id, $id) {
        try {
            $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$id, $user_id]);
        } catch (PDOException $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('delete_notification')) {
    /**
     * Delete a specific notification
     */
    function delete_notification($pdo, $user_id, $id) {
        try {
            $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$id, $user_id]);
        } catch (PDOException $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('delete_all_read')) {
    /**
     * Delete all read notifications for a user
     */
    function delete_all_read($pdo, $user_id) {
        try {
            $sql = "DELETE FROM notifications WHERE user_id = ? AND is_read = 1";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Error deleting read notifications: " . $e->getMessage());
            return false;
        }
    }
}
?>
