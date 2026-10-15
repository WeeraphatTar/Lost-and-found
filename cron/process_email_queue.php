<?php
/**
 * Email Queue Worker CLI / Scheduled Task
 * Run via Command Line or Windows Task Scheduler:
 * php cron/process_email_queue.php
 */

// Allow execution from CLI or Admin session
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Only logged-in admin can run via web browser
    if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        die("Access denied: CLI or Admin only.");
    }
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/email_notification_helper.php';

$start_time = microtime(true);
$timestamp = date('Y-m-d H:i:s');

echo "[{$timestamp}] Starting Email Queue Worker...\n";

// Batch size: process up to 20 emails per run
$batch_limit = 20;
$result = process_email_queue($pdo, $batch_limit);

$elapsed = round(microtime(true) - $start_time, 2);

echo "[{$timestamp}] Finished in {$elapsed}s. Processed: {$result['processed']}, Sent: {$result['sent']}, Failed/Retried: {$result['failed']}\n";

// Display remaining pending count
try {
    $pending_count = $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending' AND scheduled_at <= NOW() AND attempts < 3")->fetchColumn();
    echo "[{$timestamp}] Remaining eligible queue items: {$pending_count}\n";
} catch (Exception $e) {
    // Ignore
}
