<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_config.php';

function get_client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Check if the identifier is currently rate limited / blocked.
 * Returns array: ['allowed' => bool, 'message' => string, 'retry_after' => int]
 */
function check_rate_limit($pdo, $identifier, $action_type, $max_attempts, $lockout_minutes) {
    $stmt = $pdo->prepare("SELECT attempts, blocked_until, last_attempt_at FROM auth_rate_limits WHERE identifier = ? AND action_type = ?");
    $stmt->execute([$identifier, $action_type]);
    $record = $stmt->fetch();

    if (!$record) {
        return ['allowed' => true];
    }

    $now = new DateTime();
    
    if ($record['blocked_until']) {
        $blocked_until = new DateTime($record['blocked_until']);
        if ($now < $blocked_until) {
            $seconds_left = $blocked_until->getTimestamp() - $now->getTimestamp();
            $minutes_left = ceil($seconds_left / 60);
            return [
                'allowed' => false,
                'message' => "คุณทำรายการผิดเกินจำนวนครั้งที่กำหนด กรุณาลองใหม่อีกครั้งในอีก $minutes_left นาที",
                'retry_after' => $seconds_left
            ];
        } else {
            // Block expired, reset attempts
            clear_rate_limit($pdo, $identifier, $action_type);
            return ['allowed' => true];
        }
    }

    return ['allowed' => true];
}

/**
 * Record a failed attempt. If attempts exceed max_attempts, set blocked_until.
 */
function record_rate_limit_attempt($pdo, $identifier, $action_type, $max_attempts, $lockout_minutes) {
    $stmt = $pdo->prepare("SELECT id, attempts, last_attempt_at FROM auth_rate_limits WHERE identifier = ? AND action_type = ?");
    $stmt->execute([$identifier, $action_type]);
    $record = $stmt->fetch();

    $now_str = date('Y-m-d H:i:s');

    if ($record) {
        $new_attempts = $record['attempts'] + 1;
        $blocked_until = null;

        if ($new_attempts >= $max_attempts) {
            $blocked_until = date('Y-m-d H:i:s', time() + ($lockout_minutes * 60));
        }

        $update_stmt = $pdo->prepare("UPDATE auth_rate_limits SET attempts = ?, last_attempt_at = ?, blocked_until = ? WHERE id = ?");
        $update_stmt->execute([$new_attempts, $now_str, $blocked_until, $record['id']]);
    } else {
        $insert_stmt = $pdo->prepare("INSERT INTO auth_rate_limits (identifier, action_type, attempts, last_attempt_at) VALUES (?, ?, 1, ?)");
        $insert_stmt->execute([$identifier, $action_type, $now_str]);
    }
}

/**
 * Clear rate limit records upon successful authentication/action.
 */
function clear_rate_limit($pdo, $identifier, $action_type) {
    $stmt = $pdo->prepare("DELETE FROM auth_rate_limits WHERE identifier = ? AND action_type = ?");
    $stmt->execute([$identifier, $action_type]);
}
?>
