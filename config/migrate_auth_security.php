<?php
require_once __DIR__ . '/database.php';

echo "Starting Database Migration for Auth Security...\n";

try {
    // 1. Check if session_version exists in users table
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'session_version'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN session_version INT(11) NOT NULL DEFAULT 1 AFTER remember_token");
        echo "[OK] Added session_version column to users table.\n";
    } else {
        echo "[SKIP] session_version column already exists.\n";
    }

    // 2. Create email_otps table
    $sql_email_otps = "CREATE TABLE IF NOT EXISTS email_otps (
      id INT(11) NOT NULL AUTO_INCREMENT,
      user_id INT(11) NOT NULL,
      email VARCHAR(255) NOT NULL,
      otp_hash VARCHAR(255) NOT NULL,
      attempts INT(11) NOT NULL DEFAULT 0,
      expires_at DATETIME NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY user_id (user_id),
      KEY email (email),
      CONSTRAINT email_otps_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_email_otps);
    echo "[OK] Table email_otps created/verified.\n";

    // 3. Create user_remember_tokens table
    $sql_remember_tokens = "CREATE TABLE IF NOT EXISTS user_remember_tokens (
      id INT(11) NOT NULL AUTO_INCREMENT,
      user_id INT(11) NOT NULL,
      selector VARCHAR(64) NOT NULL,
      token_hash VARCHAR(255) NOT NULL,
      expires_at DATETIME NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY selector (selector),
      KEY user_id (user_id),
      CONSTRAINT user_remember_tokens_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_remember_tokens);
    echo "[OK] Table user_remember_tokens created/verified.\n";

    // 4. Create password_resets table
    $sql_password_resets = "CREATE TABLE IF NOT EXISTS password_resets (
      id INT(11) NOT NULL AUTO_INCREMENT,
      user_id INT(11) NOT NULL,
      email VARCHAR(255) NOT NULL,
      token_hash VARCHAR(255) NOT NULL,
      used TINYINT(1) NOT NULL DEFAULT 0,
      expires_at DATETIME NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY user_id (user_id),
      KEY email (email),
      CONSTRAINT password_resets_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_password_resets);
    echo "[OK] Table password_resets created/verified.\n";

    // 5. Create auth_rate_limits table
    $sql_rate_limits = "CREATE TABLE IF NOT EXISTS auth_rate_limits (
      id INT(11) NOT NULL AUTO_INCREMENT,
      identifier VARCHAR(255) NOT NULL,
      action_type VARCHAR(50) NOT NULL,
      attempts INT(11) NOT NULL DEFAULT 1,
      last_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      blocked_until DATETIME DEFAULT NULL,
      PRIMARY KEY (id),
      KEY idx_lookup (identifier, action_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_rate_limits);
    echo "[OK] Table auth_rate_limits created/verified.\n";

    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
