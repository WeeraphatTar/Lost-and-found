<?php
/**
 * Admin Log Helper Functions
 * Centralized audit logging for admin actions
 */

if (!function_exists('log_admin_action')) {
    function log_admin_action($pdo, $admin_id, $action_type, $description) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action_type, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([(int)$admin_id, $action_type, $description, $ip]);
        } catch (PDOException $e) {
            // Log error silently
        }
    }
}
