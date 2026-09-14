<?php
require_once __DIR__ . '/../config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `claims` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `item_id` int(11) NOT NULL,
      `claimant_id` int(11) NOT NULL,
      `finder_id` int(11) NOT NULL,
      `proof_description` text NOT NULL,
      `proof_image` varchar(255) DEFAULT NULL,
      `provided_serial_number` varchar(100) DEFAULT NULL,
      `contact_phone` varchar(20) NOT NULL,
      `status` enum('pending','approved','under_admin_review','rejected','meeting_scheduled','completed','cancelled_mismatch') NOT NULL DEFAULT 'pending',
      `disputed_by` enum('none','finder','claimant') DEFAULT 'none',
      `dispute_reason` text DEFAULT NULL,
      `admin_id` int(11) DEFAULT NULL,
      `admin_notes` text DEFAULT NULL,
      `admin_action_at` datetime DEFAULT NULL,
      `cancel_reason` text DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT current_timestamp(),
      `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `item_id` (`item_id`),
      KEY `claimant_id` (`claimant_id`),
      KEY `finder_id` (`finder_id`),
      KEY `admin_id` (`admin_id`),
      CONSTRAINT `claims_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
      CONSTRAINT `claims_ibfk_2` FOREIGN KEY (`claimant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      CONSTRAINT `claims_ibfk_3` FOREIGN KEY (`finder_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      CONSTRAINT `claims_ibfk_4` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql);
    echo "SUCCESS: claims table created or already exists.";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
