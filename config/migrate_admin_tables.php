<?php
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Create table item_reports
    $sql1 = "CREATE TABLE IF NOT EXISTS item_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        reporter_id INT NOT NULL,
        reason_type VARCHAR(50) NOT NULL,
        details TEXT NULL,
        status ENUM('pending', 'reviewed', 'dismissed', 'action_taken') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
        FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql1);

    // 2. Add is_banned & ban_reason columns to users table if not exist
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('is_banned', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_banned TINYINT(1) DEFAULT 0 AFTER role");
    }
    if (!in_array('ban_reason', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN ban_reason VARCHAR(255) NULL AFTER is_banned");
    }

    // Ensure status column in items table supports 'hidden'
    try {
        $pdo->exec("ALTER TABLE items MODIFY COLUMN status ENUM('open','pending','resolved','closed','hidden') NOT NULL DEFAULT 'open'");
    } catch (PDOException $ex) {
        // Ignore if already altered
    }

    // 3. Create table categories
    $sql_categories = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code_name VARCHAR(50) NOT NULL UNIQUE,
        name_th VARCHAR(100) NOT NULL,
        icon VARCHAR(50) DEFAULT 'box',
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_categories);

    // Seed default categories if table is empty
    $cat_count = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($cat_count === 0) {
        $default_cats = [
            ['code_name' => 'electronics', 'name_th' => 'อุปกรณ์อิเล็กทรอนิกส์', 'icon' => 'cpu', 'sort_order' => 1],
            ['code_name' => 'walletandcash', 'name_th' => 'กระเป๋าสตางค์และเงินสด', 'icon' => 'wallet', 'sort_order' => 2],
            ['code_name' => 'cardanddocument', 'name_th' => 'บัตรและเอกสารสำคัญ', 'icon' => 'id-card', 'sort_order' => 3],
            ['code_name' => 'keyandkeycard', 'name_th' => 'กุญแจและคีย์การ์ด', 'icon' => 'key', 'sort_order' => 4],
            ['code_name' => 'bagandluggage', 'name_th' => 'กระเป๋าและสัมภาระ', 'icon' => 'shopping-bag', 'sort_order' => 5],
            ['code_name' => 'clothingandjewelry', 'name_th' => 'เครื่องแต่งกายและเครื่องประดับ', 'icon' => 'shirt', 'sort_order' => 6],
            ['code_name' => 'studymaterialandstationery', 'name_th' => 'อุปกรณ์การเรียนและเครื่องเขียน', 'icon' => 'book-open', 'sort_order' => 7],
            ['code_name' => 'personalbelonging', 'name_th' => 'ของใช้ส่วนตัว', 'icon' => 'user', 'sort_order' => 8],
            ['code_name' => 'vehicleandaccessory', 'name_th' => 'ยานพาหนะและอุปกรณ์เสริม', 'icon' => 'truck', 'sort_order' => 9],
            ['code_name' => 'others', 'name_th' => 'อื่นๆ', 'icon' => 'grid', 'sort_order' => 99]
        ];
        $stmt_ins = $pdo->prepare("INSERT INTO categories (code_name, name_th, icon, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($default_cats as $c) {
            $stmt_ins->execute([$c['code_name'], $c['name_th'], $c['icon'], $c['sort_order']]);
        }
    }

    // 4. Create table admin_logs
    $sql_logs = "CREATE TABLE IF NOT EXISTS admin_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        ip_address VARCHAR(45) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_logs);

    // 5. Backfill historical admin decisions from claims table if logs table is empty
    $log_count = (int)$pdo->query("SELECT COUNT(*) FROM admin_logs")->fetchColumn();
    if ($log_count === 0) {
        $backfill_sql = "INSERT INTO admin_logs (admin_id, action_type, description, created_at)
                         SELECT c.admin_id, 
                                IF(c.status = 'approved', 'approve_claim', 'reject_claim') as action_type,
                                CONCAT('ดำเนินการพิจารณาคำร้อง Claim ID #', c.id, ' สำหรับประกาศ: ', i.title) as description,
                                IFNULL(c.admin_action_at, c.updated_at) as created_at
                         FROM claims c
                         JOIN items i ON c.item_id = i.id
                         WHERE c.admin_id IS NOT NULL";
        $pdo->exec($backfill_sql);
    }
} catch (PDOException $e) {
    // Silent error in migration
}
