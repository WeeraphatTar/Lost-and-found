<?php
// Migration script for Social Login (Google & Facebook)
require_once __DIR__ . '/database.php';

try {
    echo "--- Starting Social Auth Database Migration ---\n";

    // 1. Modify users.password_hash to allow NULL for Social-only users
    echo "1. Updating 'users' table column 'password_hash' to allow NULL...\n";
    $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `password_hash` VARCHAR(255) NULL DEFAULT NULL");
    echo "   -> 'password_hash' column updated successfully.\n";

    // 2. Create user_social_accounts table
    echo "2. Creating 'user_social_accounts' table...\n";
    $sql_create = "CREATE TABLE IF NOT EXISTS `user_social_accounts` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` INT(11) NOT NULL,
      `provider` ENUM('google', 'facebook') NOT NULL,
      `provider_user_id` VARCHAR(255) NOT NULL,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_provider_user` (`provider`, `provider_user_id`),
      UNIQUE KEY `uk_user_provider` (`user_id`, `provider`),
      CONSTRAINT `fk_social_accounts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql_create);
    echo "   -> 'user_social_accounts' table created successfully.\n";

    echo "--- Migration Completed Successfully! ---\n";

} catch (PDOException $e) {
    echo "ERROR during migration: " . $e->getMessage() . "\n";
    exit(1);
}
