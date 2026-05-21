<?php
/**
 * Helper functions for Internal Messaging System
 */

/**
 * Get total unread messages for a user
 */
function get_unread_message_count($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get list of conversations for a user
 * Returns conversations grouped by item_id and the other participant
 */
function get_conversations($pdo, $user_id) {
    try {
        // SQL to get unique conversations (item_id + other_user)
        // We look for messages where user is either sender or receiver
        $sql = "SELECT m.*, 
                       i.title as item_title, i.image_path as item_image,
                       u.first_name as other_first_name, u.last_name as other_last_name,
                       (SELECT COUNT(*) FROM messages WHERE item_id = m.item_id AND receiver_id = ? AND sender_id = IF(m.sender_id = ?, m.receiver_id, m.sender_id) AND is_read = 0) as unread_count
                FROM messages m
                JOIN items i ON m.item_id = i.id
                JOIN users u ON u.id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
                WHERE (m.sender_id = ? OR m.receiver_id = ?)
                AND m.id IN (
                    SELECT MAX(id) FROM messages 
                    WHERE (sender_id = ? OR receiver_id = ?)
                    GROUP BY item_id, IF(sender_id = ?, receiver_id, sender_id)
                )
                ORDER BY m.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get message history between two users for a specific item
 */
function get_messages($pdo, $item_id, $user1_id, $user2_id) {
    try {
        $sql = "SELECT m.*, u.first_name as sender_name 
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.item_id = ? 
                AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
                ORDER BY m.created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$item_id, $user1_id, $user2_id, $user2_id, $user1_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Mark messages as read
 */
function mark_messages_as_read($pdo, $item_id, $receiver_id, $sender_id) {
    try {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE item_id = ? AND receiver_id = ? AND sender_id = ? AND is_read = 0");
        $stmt->execute([$item_id, $receiver_id, $sender_id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
