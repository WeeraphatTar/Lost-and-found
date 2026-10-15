<?php
require_once __DIR__ . '/database.php';

echo "====================================================\n";
echo "Starting Migration: Email Notification System\n";
echo "====================================================\n";

try {
    // 1. Table: user_notification_settings
    $sql_settings = "CREATE TABLE IF NOT EXISTS `user_notification_settings` (
      `user_id` INT(11) NOT NULL,
      `email_notify_matches` TINYINT(1) NOT NULL DEFAULT 1,
      `email_notify_claims` TINYINT(1) NOT NULL DEFAULT 1,
      `email_notify_messages` TINYINT(1) NOT NULL DEFAULT 1,
      `email_notify_system` TINYINT(1) NOT NULL DEFAULT 1,
      `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`user_id`),
      CONSTRAINT `fk_user_notify_settings` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_settings);
    echo "[OK] Table 'user_notification_settings' created/verified.\n";

    // 2. Table: email_queue
    $sql_queue = "CREATE TABLE IF NOT EXISTS `email_queue` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `recipient_user_id` INT(11) NOT NULL,
      `recipient_email` VARCHAR(255) NOT NULL,
      `subject` VARCHAR(255) NOT NULL,
      `body_html` MEDIUMTEXT NOT NULL,
      `event_type` VARCHAR(50) NOT NULL,
      `dedup_key` VARCHAR(191) NOT NULL,
      `status` ENUM('pending', 'sending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
      `attempts` INT(11) NOT NULL DEFAULT 0,
      `last_error` TEXT DEFAULT NULL,
      `scheduled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `sent_at` DATETIME DEFAULT NULL,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `idx_dedup_key` (`dedup_key`),
      KEY `idx_queue_status` (`status`, `scheduled_at`),
      KEY `idx_recipient_user` (`recipient_user_id`),
      CONSTRAINT `fk_email_queue_user` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_queue);
    echo "[OK] Table 'email_queue' created/verified.\n";

    // 3. Table: email_logs
    $sql_logs = "CREATE TABLE IF NOT EXISTS `email_logs` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `queue_id` INT(11) DEFAULT NULL,
      `recipient_user_id` INT(11) NOT NULL,
      `recipient_email` VARCHAR(255) NOT NULL,
      `subject` VARCHAR(255) NOT NULL,
      `event_type` VARCHAR(50) NOT NULL,
      `status` ENUM('success', 'failed') NOT NULL,
      `error_details` TEXT DEFAULT NULL,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_log_user` (`recipient_user_id`),
      KEY `idx_log_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_logs);
    echo "[OK] Table 'email_logs' created/verified.\n";

    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
