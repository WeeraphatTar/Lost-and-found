<?php
/**
 * Email Notification Helper Service
 * Lost & Found System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_config.php';
require_once __DIR__ . '/mail_helper.php';

if (!defined('EMAIL_MAX_ATTEMPTS')) {
    define('EMAIL_MAX_ATTEMPTS', 3);
}

/**
 * Get user's email notification preferences
 */
function get_user_notification_settings($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_notification_settings WHERE user_id = ?");
        $stmt->execute([(int)$user_id]);
        $settings = $stmt->fetch();

        if ($settings) {
            return [
                'email_notify_matches' => (int)$settings['email_notify_matches'],
                'email_notify_claims' => (int)$settings['email_notify_claims'],
                'email_notify_messages' => (int)$settings['email_notify_messages'],
                'email_notify_system' => (int)$settings['email_notify_system']
            ];
        }
    } catch (PDOException $e) {
        error_log("Error fetching user notification settings: " . $e->getMessage());
    }

    // Default settings: all enabled
    return [
        'email_notify_matches' => 1,
        'email_notify_claims' => 1,
        'email_notify_messages' => 1,
        'email_notify_system' => 1
    ];
}

/**
 * Check if a specific email category is enabled for a user
 */
function is_email_category_enabled($pdo, $user_id, $category) {
    $settings = get_user_notification_settings($pdo, $user_id);

    switch ($category) {
        case 'matches':
            return !empty($settings['email_notify_matches']);
        case 'claims':
            return !empty($settings['email_notify_claims']);
        case 'messages':
            return !empty($settings['email_notify_messages']);
        case 'system':
            return !empty($settings['email_notify_system']);
        default:
            return true;
    }
}

/**
 * Render standard responsive HTML email template
 */
function render_email_template($title, $first_name, $content_html, $action_text = null, $action_url = null, $extra_notice = null) {
    $app_url = defined('APP_URL') ? rtrim(APP_URL, '/') : 'http://localhost/lost-and-found';
    $safe_title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safe_name = htmlspecialchars($first_name ?: 'ผู้ใช้งาน', ENT_QUOTES, 'UTF-8');

    $button_html = '';
    if (!empty($action_text) && !empty($action_url)) {
        $safe_action_text = htmlspecialchars($action_text, ENT_QUOTES, 'UTF-8');
        $safe_action_url = htmlspecialchars($action_url, ENT_QUOTES, 'UTF-8');
        $button_html = '
        <div style="text-align: center; margin: 28px 0;">
            <a href="' . $safe_action_url . '" class="btn-action" style="background-color: #312e81; color: #ffffff; padding: 13px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px; display: inline-block;">' . $safe_action_text . '</a>
        </div>
        <p style="color: #94a3b8; font-size: 12px; text-align: center; margin-top: -10px;">หรือเข้าใช้งานผ่านลิงก์: <a href="' . $safe_action_url . '" style="color: #312e81; word-break: break-all;">' . $safe_action_url . '</a></p>';
    }

    $extra_notice_html = '';
    if (!empty($extra_notice)) {
        $extra_notice_html = '<div style="margin-top: 20px; padding: 12px; background-color: #f8fafc; border-left: 3px solid #cbd5e1; font-size: 12px; color: #64748b;">' . $extra_notice . '</div>';
    }

    return '
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $safe_title . '</title>
        <style>
            .btn-action:hover {
                background-color: #1e1b4b !important;
            }
        </style>
    </head>
    <body style="font-family: \'Prompt\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 24px 12px; color: #1e293b; line-height: 1.6;">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
            <!-- Header -->
            <tr>
                <td style="background-color: #1e1b4b; padding: 28px 20px; text-align: center;">
                    <div style="font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: 0.3px;">Lost and Found</div>
                    <div style="font-size: 13px; font-weight: 400; color: #c7d2fe; margin-top: 5px;">ระบบแจ้งของหายและติดตามทรัพย์สินคืน</div>
                </td>
            </tr>
            <!-- Content -->
            <tr>
                <td style="padding: 32px 28px;">
                    <h2 style="color: #0f172a; margin-top: 0; margin-bottom: 16px; font-size: 20px; font-weight: bold;">' . $safe_title . '</h2>
                    <p style="font-size: 15px; color: #334155; margin-bottom: 20px;">สวัสดีคุณ <strong>' . $safe_name . '</strong>,</p>
                    
                    <div style="font-size: 14px; color: #334155; line-height: 1.7;">
                        ' . $content_html . '
                    </div>

                    ' . $button_html . '
                    ' . $extra_notice_html . '
                </td>
            </tr>
            <!-- Footer -->
            <tr>
                <td style="background-color: #f8fafc; padding: 20px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b;">
                    <p style="margin: 0 0 8px 0;">อีเมลนี้เป็นการแจ้งเตือนอัตโนมัติจากระบบ Lost &amp; Found</p>
                    <p style="margin: 0 0 8px 0;">คุณสามารถจัดการการตั้งค่าอีเมลแจ้งเตือนได้ที่หน้า <a href="' . $app_url . '/pages/profile.php" style="color: #1e293b; text-decoration: underline;">ตั้งค่าโปรไฟล์ส่วนตัว</a></p>
                    <p style="margin: 0; color: #94a3b8;">&copy; ' . date('Y') . ' Lost &amp; Found System. All rights reserved.</p>
                </td>
            </tr>
        </table>
    </body>
    </html>';
}

/**
 * Queue a system email safely with deduplication and user preference checking
 * Returns array: ['status' => 'queued'|'suppressed_by_preference'|'duplicate_skipped'|'error', 'queue_id' => int|null]
 */
function queue_system_email($pdo, $recipient_user_id, $event_type, $subject, $body_html, $dedup_key, $category = null) {
    $recipient_user_id = (int)$recipient_user_id;

    if ($recipient_user_id <= 0) {
        return ['status' => 'error', 'message' => 'Invalid recipient ID', 'queue_id' => null];
    }

    // 1. Check user notification preferences (if category is provided)
    if ($category !== null && !is_email_category_enabled($pdo, $recipient_user_id, $category)) {
        return ['status' => 'suppressed_by_preference', 'message' => 'User disabled this category', 'queue_id' => null];
    }

    try {
        // 2. Fetch recipient details from DB
        $stmt_user = $pdo->prepare("SELECT email, first_name, is_banned FROM users WHERE id = ?");
        $stmt_user->execute([$recipient_user_id]);
        $user = $stmt_user->fetch();

        if (!$user || empty($user['email'])) {
            return ['status' => 'error', 'message' => 'Recipient user or email not found', 'queue_id' => null];
        }

        // If user is banned and this is not a ban notice, suppress
        if (!empty($user['is_banned']) && $event_type !== 'ban_user') {
            return ['status' => 'suppressed_by_ban', 'message' => 'User account is banned', 'queue_id' => null];
        }

        $recipient_email = trim($user['email']);

        // 3. Deduplication check via dedup_key
        $stmt_check = $pdo->prepare("SELECT id, status, attempts FROM email_queue WHERE dedup_key = ?");
        $stmt_check->execute([$dedup_key]);
        $existing = $stmt_check->fetch();

        if ($existing) {
            // If already sent, pending, or sending, skip
            if (in_array($existing['status'], ['sent', 'pending', 'sending'])) {
                return ['status' => 'duplicate_skipped', 'queue_id' => (int)$existing['id']];
            }
            // If permanently failed (attempts >= 3), skip re-queueing identical failure
            if ($existing['status'] === 'failed' && (int)$existing['attempts'] >= EMAIL_MAX_ATTEMPTS) {
                return ['status' => 'duplicate_skipped', 'queue_id' => (int)$existing['id']];
            }
        }

        // 4. Insert into email_queue
        $stmt_ins = $pdo->prepare("
            INSERT INTO email_queue (recipient_user_id, recipient_email, subject, body_html, event_type, dedup_key, status, attempts, scheduled_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', 0, NOW())
        ");
        $stmt_ins->execute([
            $recipient_user_id,
            $recipient_email,
            $subject,
            $body_html,
            $event_type,
            $dedup_key
        ]);

        $queue_id = (int)$pdo->lastInsertId();

        // 5. Attempt immediate delivery so the user receives the email right away
        try {
            dispatch_single_queued_email($pdo, $queue_id);
        } catch (Throwable $dispatch_err) {
            error_log("Immediate email dispatch notice: " . $dispatch_err->getMessage());
        }

        // 6. Also trigger background worker for any older pending/retry items
        trigger_background_worker($queue_id);

        return ['status' => 'queued', 'queue_id' => $queue_id];

    } catch (PDOException $e) {
        // Unique constraint violation on dedup_key
        if ($e->getCode() == '23000' || strpos($e->getMessage(), 'Duplicate entry') !== false) {
            return ['status' => 'duplicate_skipped', 'queue_id' => null];
        }
        error_log("Error queueing email: " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage(), 'queue_id' => null];
    }
}

/**
 * Dispatch a single queue record with atomic locking and retry management
 */
function dispatch_single_queued_email($pdo, $queue_id) {
    $queue_id = (int)$queue_id;
    if ($queue_id <= 0) return false;

    try {
        // 1. Atomic Lock: Transition from 'pending' or 'failed' (if retry scheduled) to 'sending'
        $stmt_lock = $pdo->prepare("
            UPDATE email_queue 
            SET status = 'sending', attempts = attempts + 1 
            WHERE id = ? AND status IN ('pending', 'failed') AND scheduled_at <= NOW() AND attempts < ?
        ");
        $stmt_lock->execute([$queue_id, EMAIL_MAX_ATTEMPTS]);

        if ($stmt_lock->rowCount() === 0) {
            // Already taken by another worker or already sent
            return false;
        }

        // 2. Fetch the locked email item
        $stmt_item = $pdo->prepare("SELECT * FROM email_queue WHERE id = ?");
        $stmt_item->execute([$queue_id]);
        $item = $stmt_item->fetch();

        if (!$item) return false;

        $recipient_email = $item['recipient_email'];
        $subject = $item['subject'];
        $body_html = $item['body_html'];
        $attempts = (int)$item['attempts'];

        // 3. Send email via native socket SMTP
        $send_ok = false;
        $error_detail = null;

        try {
            $send_ok = send_system_email($recipient_email, $subject, $body_html);
        } catch (Throwable $smtp_ex) {
            $send_ok = false;
            $error_detail = $smtp_ex->getMessage();
        }

        if ($send_ok) {
            // 4A. SUCCESS: Update queue to 'sent' and write log
            $pdo->prepare("
                UPDATE email_queue 
                SET status = 'sent', sent_at = NOW(), last_error = NULL 
                WHERE id = ?
            ")->execute([$queue_id]);

            $pdo->prepare("
                INSERT INTO email_logs (queue_id, recipient_user_id, recipient_email, subject, event_type, status, error_details, created_at)
                VALUES (?, ?, ?, ?, ?, 'success', NULL, NOW())
            ")->execute([
                $queue_id,
                $item['recipient_user_id'],
                $recipient_email,
                $subject,
                $item['event_type']
            ]);

            return true;

        } else {
            // 4B. FAILURE: Calculate retry backoff
            $error_msg = $error_detail ?: "SMTP send failed or server rejected message";
            
            // Retry intervals: 1st retry -> 2 min, 2nd retry -> 10 min, 3rd -> 30 min
            $backoff_minutes = ($attempts === 1) ? 2 : (($attempts === 2) ? 10 : 30);
            $new_status = ($attempts >= EMAIL_MAX_ATTEMPTS) ? 'failed' : 'pending';

            $stmt_fail = $pdo->prepare("
                UPDATE email_queue 
                SET status = ?, 
                    scheduled_at = DATE_ADD(NOW(), INTERVAL ? MINUTE), 
                    last_error = ? 
                WHERE id = ?
            ");
            $stmt_fail->execute([$new_status, $backoff_minutes, $error_msg, $queue_id]);

            $pdo->prepare("
                INSERT INTO email_logs (queue_id, recipient_user_id, recipient_email, subject, event_type, status, error_details, created_at)
                VALUES (?, ?, ?, ?, ?, 'failed', ?, NOW())
            ")->execute([
                $queue_id,
                $item['recipient_user_id'],
                $recipient_email,
                $subject,
                $item['event_type'],
                "Attempt {$attempts}/" . EMAIL_MAX_ATTEMPTS . ": " . $error_msg
            ]);

            return false;
        }

    } catch (PDOException $e) {
        error_log("Error in dispatch_single_queued_email: " . $e->getMessage());
        return false;
    }
}

/**
 * Process pending queue items in batches (Used by CLI cron & background runners)
 */
function process_email_queue($pdo, $limit = 10) {
    $processed = 0;
    $sent = 0;
    $failed = 0;

    try {
        // Query pending or eligible retry items
        $stmt = $pdo->prepare("
            SELECT id FROM email_queue 
            WHERE status IN ('pending', 'failed') 
              AND scheduled_at <= NOW() 
              AND attempts < ? 
            ORDER BY id ASC 
            LIMIT ?
        ");
        $stmt->bindValue(1, EMAIL_MAX_ATTEMPTS, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($ids as $qid) {
            $processed++;
            if (dispatch_single_queued_email($pdo, $qid)) {
                $sent++;
            } else {
                $failed++;
            }
        }

    } catch (PDOException $e) {
        error_log("Error processing email queue: " . $e->getMessage());
    }

    return [
        'processed' => $processed,
        'sent' => $sent,
        'failed' => $failed
    ];
}

/**
 * Helper to locate real PHP CLI executable on Windows / Linux
 */
function get_real_php_cli_binary() {
    $candidates = [
        'D:\\xampp\\php\\php.exe',
        'C:\\xampp\\php\\php.exe',
    ];
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            return $candidate;
        }
    }
    // Only use PHP_BINARY if running in CLI mode (in Apache it points to httpd.exe)
    if (php_sapi_name() === 'cli' && defined('PHP_BINARY') && file_exists(PHP_BINARY)) {
        return PHP_BINARY;
    }
    return 'php';
}

/**
 * Trigger background worker process asynchronously on Windows / CLI
 */
function trigger_background_worker($queue_id = null) {
    // If running in CLI already, skip spawning background process
    if (php_sapi_name() === 'cli') {
        return;
    }

    $worker_path = realpath(__DIR__ . '/../cron/process_email_queue.php');
    if (!$worker_path || !file_exists($worker_path)) {
        return;
    }

    $php_binary = get_real_php_cli_binary();

    // On Windows, use 'cmd.exe /c start /B ""' to launch detached background process
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd = 'cmd.exe /c start /B "" ' . escapeshellarg($php_binary) . ' ' . escapeshellarg($worker_path) . ' > NUL 2>&1';
        @pclose(@popen($cmd, "r"));
    } else {
        $cmd = escapeshellarg($php_binary) . ' ' . escapeshellarg($worker_path) . ' > /dev/null 2>&1 &';
        @exec($cmd);
    }
}

// =========================================================================
// EVENT-SPECIFIC NOTIFICATION DISPATCHERS
// =========================================================================

/**
 * 1. Smart Matching Notification
 */
function dispatch_smart_match_notifications($pdo, $creator_user_id, $new_item, $top_match, $all_matches = []) {
    $creator_user_id = (int)$creator_user_id;
    $new_item_id = (int)($new_item['id'] ?? 0);
    $app_url = defined('APP_URL') ? rtrim(APP_URL, '/') : 'http://localhost/lost-and-found';

    if ($creator_user_id <= 0 || empty($top_match)) {
        return;
    }

    $new_type_th = ($new_item['type'] === 'lost') ? 'ของหาย' : 'พบของ';
    $match_type_th = ($top_match['type'] === 'lost') ? 'ของหาย' : 'พบของ';
    $new_item_version = !empty($new_item['updated_at']) ? strtotime($new_item['updated_at']) : time();

    // A. Notify creator about top match
    $creator_subject = "ระบบพบรายการที่อาจตรงกับประกาศของคุณ (Smart Match: " . round($top_match['match_score']) . "%)";
    $creator_body = '
        <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
            <p style="margin: 0 0 8px 0; font-weight: bold; color: #1e40af;">ประกาศของคุณ: ' . htmlspecialchars($new_item['title'], ENT_QUOTES, 'UTF-8') . ' (' . $new_type_th . ')</p>
            <p style="margin: 0; color: #3b82f6; font-size: 13px;">ระบบ Smart Matching ตรวจพบรายการที่ใกล้เคียงกัน:</p>
        </div>
        <table width="100%" cellpadding="6" cellspacing="0" style="background-color: #f8fafc; border-radius: 6px; font-size: 14px; margin-bottom: 20px;">
            <tr><td width="35%" style="color: #64748b;">รายการที่ตรงกัน:</td><td style="font-weight: bold; color: #0f172a;">' . htmlspecialchars($top_match['title'], ENT_QUOTES, 'UTF-8') . '</td></tr>
            <tr><td style="color: #64748b;">หมวดหมู่:</td><td>' . htmlspecialchars($top_match['category'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td></tr>
            <tr><td style="color: #64748b;">สถานที่:</td><td>' . htmlspecialchars($top_match['location'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td></tr>
            <tr><td style="color: #64748b;">คะแนนความตรงกัน:</td><td><span style="color: #16a34a; font-weight: bold;">' . round($top_match['match_score']) . '%</span> (' . htmlspecialchars($top_match['confidence'] ?? 'ระดับปานกลาง', ENT_QUOTES, 'UTF-8') . ')</td></tr>
        </table>
        <p>คุณสามารถคลิกปุ่มด้านล่างเพื่อตรวจสอบรายละเอียดของสิ่งของที่ตรงกัน และดำเนินการยื่นคำร้องหรือติดต่อได้ทันที</p>';

    $creator_html = render_email_template(
        $creator_subject,
        '',
        $creator_body,
        'ดูรายละเอียดรายการที่ตรงกัน',
        $app_url . '/pages/item_detail.php?id=' . (int)$top_match['id']
    );

    $creator_dedup = "match_creator_{$creator_user_id}_{$top_match['id']}_{$new_item_id}_v{$new_item_version}";
    queue_system_email($pdo, $creator_user_id, 'smart_match', $creator_subject, $creator_html, $creator_dedup, 'matches');

    // B. Notify owners of matching items
    $target_matches = !empty($all_matches) ? $all_matches : [$top_match];
    foreach ($target_matches as $m) {
        $target_user_id = (int)$m['user_id'];
        if ($target_user_id <= 0 || $target_user_id === $creator_user_id) {
            continue; // Skip self
        }

        $match_version = !empty($m['updated_at']) ? strtotime($m['updated_at']) : time();
        $target_dedup = "match_partner_{$target_user_id}_{$new_item_id}_{$m['id']}_v{$match_version}";

        $partner_subject = "มีผู้ลงประกาศใหม่ที่อาจตรงกับรายการของคุณ (Smart Match: " . round($m['match_score']) . "%)";
        $partner_body = '
            <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                <p style="margin: 0 0 8px 0; font-weight: bold; color: #1e40af;">รายการของคุณ: ' . htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin: 0; color: #3b82f6; font-size: 13px;">มีผู้ลงประกาศใหม่ในระบบที่มีลักษณะตรงกับสิ่งของของคุณ:</p>
            </div>
            <table width="100%" cellpadding="6" cellspacing="0" style="background-color: #f8fafc; border-radius: 6px; font-size: 14px; margin-bottom: 20px;">
                <tr><td width="35%" style="color: #64748b;">ประกาศใหม่:</td><td style="font-weight: bold; color: #0f172a;">' . htmlspecialchars($new_item['title'], ENT_QUOTES, 'UTF-8') . '</td></tr>
                <tr><td style="color: #64748b;">หมวดหมู่:</td><td>' . htmlspecialchars($new_item['category'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td></tr>
                <tr><td style="color: #64748b;">สถานที่:</td><td>' . htmlspecialchars($new_item['location'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td></tr>
                <tr><td style="color: #64748b;">คะแนนความตรงกัน:</td><td><span style="color: #16a34a; font-weight: bold;">' . round($m['match_score']) . '%</span></td></tr>
            </table>
            <p>โปรดเข้าตรวจสอบรายละเอียดของประกาศดังกล่าวเพื่อยืนยันความเป็นเจ้าของ</p>';

        $partner_html = render_email_template(
            $partner_subject,
            '',
            $partner_body,
            'ดูรายละเอียดประกาศใหม่นี้',
            $app_url . '/pages/item_detail.php?id=' . $new_item_id
        );

        queue_system_email($pdo, $target_user_id, 'smart_match', $partner_subject, $partner_html, $target_dedup, 'matches');
    }
}

/**
 * 2. New Claim Submitted Notification
 */
function dispatch_claim_submitted_email($pdo, $claim_id) {
    $claim_id = (int)$claim_id;
    $app_url = defined('APP_URL') ? rtrim(APP_URL, '/') : 'http://localhost/lost-and-found';

    try {
        $stmt = $pdo->prepare("
            SELECT c.id as claim_id, c.user_id as claimant_id, i.title as item_title, i.user_id as finder_id,
                   u_claimant.first_name as claimant_first_name, u_claimant.last_name as claimant_last_name
            FROM claims c
            JOIN items i ON c.item_id = i.id
            JOIN users u_claimant ON c.user_id = u_claimant.id
            WHERE c.id = ?
        ");
        $stmt->execute([$claim_id]);
        $data = $stmt->fetch();

        if (!$data || empty($data['finder_id'])) {
            return;
        }

        $finder_id = (int)$data['finder_id'];
        $item_title = $data['item_title'];
        $claimant_name = trim($data['claimant_first_name'] . ' ' . $data['claimant_last_name']);

        $subject = "มีคำร้องขอรับคืนสิ่งของ '{$item_title}' ยื่นเข้ามาในระบบ - Lost & Found";
        $content = '
            <p>มีผู้ใช้งาน (คุณ <strong>' . htmlspecialchars($claimant_name, ENT_QUOTES, 'UTF-8') . '</strong>) ได้ยื่นคำร้องขอรับคืนสิ่งของสำหรับประกาศพบของของคุณ:</p>
            <div style="background-color: #f8fafc; border-left: 4px solid #3b82f6; padding: 16px; margin: 20px 0; border-radius: 4px;">
                <p style="margin: 0 0 6px 0; font-size: 15px; font-weight: bold; color: #0f172a;">' . htmlspecialchars($item_title, ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin: 0; font-size: 13px; color: #64748b;">รหัสคำร้อง: #CLM-' . str_pad($claim_id, 3, '0', STR_PAD_LEFT) . '</p>
            </div>
            <p>คำร้องดังกล่าวจะได้รับการตรวจสอบหลักฐานโดยผู้ดูแลระบบ และคุณสามารถเข้าดูรายละเอียดและสถานะของคำร้องได้ตลอดเวลา</p>';

        $html = render_email_template(
            $subject,
            '',
            $content,
            'ดูรายละเอียดคำร้อง',
            $app_url . '/pages/claim_detail.php?id=' . $claim_id
        );

        $dedup_key = "claim_submitted_{$claim_id}_{$finder_id}";
        queue_system_email($pdo, $finder_id, 'claim_submitted', $subject, $html, $dedup_key, 'claims');

    } catch (PDOException $e) {
        error_log("Error in dispatch_claim_submitted_email: " . $e->getMessage());
    }
}

/**
 * 3. Claim Status Changed Notification (Approved, Rejected, Disputed, Cancelled, Completed)
 */
function dispatch_claim_status_email($pdo, $claim_id, $status, $extra_notes = '') {
    $claim_id = (int)$claim_id;
    $app_url = defined('APP_URL') ? rtrim(APP_URL, '/') : 'http://localhost/lost-and-found';

    try {
        $stmt = $pdo->prepare("
            SELECT c.id as claim_id, c.user_id as claimant_id, i.title as item_title, i.user_id as finder_id,
                   c.status as claim_status
            FROM claims c
            JOIN items i ON c.item_id = i.id
            WHERE c.id = ?
        ");
        $stmt->execute([$claim_id]);
        $data = $stmt->fetch();

        if (!$data) return;

        $claimant_id = (int)$data['claimant_id'];
        $finder_id = (int)$data['finder_id'];
        $item_title = $data['item_title'];
        $clm_code = '#CLM-' . str_pad($claim_id, 3, '0', STR_PAD_LEFT);

        switch ($status) {
            case 'approved':
                // Notify Claimant
                $subject = "✓ คำร้องขอรับคืนสิ่งของ '{$item_title}' ได้รับการอนุมัติแล้ว!";
                $content = '
                    <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                        <p style="margin: 0; color: #166534; font-weight: bold; font-size: 15px;">✓ คำร้องของคุณได้รับการอนุมัติเรียบร้อยแล้ว</p>
                        <p style="margin: 6px 0 0 0; color: #15803d; font-size: 13px;">รายการ: ' . htmlspecialchars($item_title, ENT_QUOTES, 'UTF-8') . ' (' . $clm_code . ')</p>
                    </div>
                    <p>ระบบได้เปิดสิทธิ์การส่งข้อความแชทตรงถึงผู้พบสิ่งของ คุณสามารถคลิกปุ่มด้านล่างเพื่อเปิดห้องแชทและนัดหมายวัน เวลา สถานที่รับส่งมอบสิ่งของได้ทันที</p>';

                $html = render_email_template(
                    $subject,
                    '',
                    $content,
                    'ดูคำร้องและเริ่มนัดหมาย',
                    $app_url . '/pages/claim_detail.php?id=' . $claim_id
                );

                queue_system_email($pdo, $claimant_id, 'claim_approved', $subject, $html, "claim_approved_{$claim_id}_{$claimant_id}", 'claims');

                // Also notify Finder that claim is approved and they can chat
                $finder_subject = "คำร้องขอรับคืน '{$item_title}' ได้รับการอนุมัติแล้ว - พร้อมเริ่มนัดหมาย";
                $finder_content = '
                    <p>คำร้องขอรับคืนสิ่งของสำหรับประกาศพบของของคุณ (' . htmlspecialchars($item_title, ENT_QUOTES, 'UTF-8') . ') ได้รับการอนุมัติแล้ว</p>
                    <p>ระบบเปิดให้ทั้งสองฝ่ายสามารถสนทนาผ่าน Direct Chat เพื่อยืนยันเวลานัดส่งมอบสิ่งของได้อย่างปลอดภัย</p>';
                $finder_html = render_email_template(
                    $finder_subject,
                    '',
                    $finder_content,
                    'เข้าสู่หน้าคำร้องและนัดส่งมอบ',
                    $app_url . '/pages/claim_detail.php?id=' . $claim_id
                );
                queue_system_email($pdo, $finder_id, 'claim_approved', $finder_subject, $finder_html, "claim_approved_{$claim_id}_{$finder_id}", 'claims');
                break;

            case 'rejected':
                $subject = "ผลการพิจารณาคำร้องขอรับคืนสิ่งของ '{$item_title}' ({$clm_code})";
                $reason_html = !empty($extra_notes) ? '<p style="margin: 8px 0 0 0; font-size: 13px; color: #7f1d1d;"><strong>เหตุผล:</strong> ' . htmlspecialchars($extra_notes, ENT_QUOTES, 'UTF-8') . '</p>' : '';
                $content = '
                    <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                        <p style="margin: 0; color: #991b1b; font-weight: bold;">คำร้องขอรับคืนสิ่งของของคุณไม่ผ่านการอนุมัติ</p>
                        ' . $reason_html . '
                    </div>
                    <p>หากคุณมีหลักฐานเพิ่มเติม หรือมีข้อสงสัยเกี่ยวกับผลการพิจารณา คุณสามารถตรวจสอบรายละเอียดและติดต่อเจ้าหน้าที่ได้ผ่านระบบ</p>';

                $html = render_email_template(
                    $subject,
                    '',
                    $content,
                    'ตรวจสอบรายละเอียดคำร้อง',
                    $app_url . '/pages/claim_detail.php?id=' . $claim_id
                );

                queue_system_email($pdo, $claimant_id, 'claim_rejected', $subject, $html, "claim_rejected_{$claim_id}_{$claimant_id}", 'claims');
                break;

            case 'disputed':
                // Notify the opposite party
                $subject = "คำร้องขอรับคืน '{$item_title}' ถูกส่งให้ Admin ร่วมตรวจสอบข้อพิพาท";
                $reason_text = !empty($extra_notes) ? htmlspecialchars($extra_notes, ENT_QUOTES, 'UTF-8') : 'มีข้อสงสัยหรือข้อขัดแย้งในหลักฐาน';
                $content = '
                    <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                        <p style="margin: 0; color: #92400e; font-weight: bold;">คำร้องถูกส่งเข้าสู่กระบวนการตรวจสอบโดยผู้ดูแลระบบ (Admin Review)</p>
                        <p style="margin: 6px 0 0 0; color: #b45309; font-size: 13px;">เหตุผล: ' . $reason_text . '</p>
                    </div>
                    <p>เจ้าหน้าที่ผู้ดูแลระบบจะเข้ามาร่วมตรวจสอบหลักฐานของทั้งสองฝ่ายและประสานงานให้ความเป็นธรรมในลำดับต่อไป</p>';

                $html = render_email_template(
                    $subject,
                    '',
                    $content,
                    'ติดตามสถานะคำร้อง',
                    $app_url . '/pages/claim_detail.php?id=' . $claim_id
                );

                // Send to both parties with distinct dedup keys
                queue_system_email($pdo, $claimant_id, 'claim_disputed', $subject, $html, "claim_disputed_{$claim_id}_{$claimant_id}", 'claims');
                queue_system_email($pdo, $finder_id, 'claim_disputed', $subject, $html, "claim_disputed_{$claim_id}_{$finder_id}", 'claims');
                break;

            case 'cancelled_mismatch':
                $subject = "คำร้องขอรับคืน '{$item_title}' ถูกยกเลิกเนื่องจากสิ่งของไม่ตรงกัน";
                $content = '
                    <div style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                        <p style="margin: 0; color: #475569; font-weight: bold;">การส่งมอบถูกยกเลิกเนื่องจากตรวจสอบสิ่งของจริงแล้วไม่ตรงกัน</p>
                    </div>
                    <p>ประกาศสิ่งของชิ้นนี้ได้ถูกเปิดกลับเข้าสู่ระบบการค้นหาและจับคู่ (Open) เพื่อตามหาเจ้าของที่แท้จริงต่อไป</p>';

                $html = render_email_template(
                    $subject,
                    '',
                    $content,
                    'ดูรายละเอียด',
                    $app_url . '/pages/claim_detail.php?id=' . $claim_id
                );

                queue_system_email($pdo, $claimant_id, 'claim_cancelled', $subject, $html, "claim_cancel_{$claim_id}_{$claimant_id}", 'claims');
                queue_system_email($pdo, $finder_id, 'claim_cancelled', $subject, $html, "claim_cancel_{$claim_id}_{$finder_id}", 'claims');
                break;

            case 'completed':
                $subject = "✓ การส่งมอบสิ่งของ '{$item_title}' เสร็จสมบูรณ์แล้ว - Lost & Found";
                $content = '
                    <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                        <p style="margin: 0; color: #166534; font-weight: bold; font-size: 16px;">🎉 ส่งมอบสิ่งของคืนสำเร็จเรียบร้อยแล้ว</p>
                        <p style="margin: 6px 0 0 0; color: #15803d; font-size: 13px;">รายการ: ' . htmlspecialchars($item_title, ENT_QUOTES, 'UTF-8') . '</p>
                    </div>
                    <p>ขอบคุณที่ร่วมเป็นส่วนหนึ่งในการส่งต่อความช่วยเหลือผ่านระบบ Lost &amp; Found ชุมชนของเรายินดีเป็นอย่างยิ่งที่สิ่งของได้กลับคืนสู่เจ้าของอย่างปลอดภัย</p>';

                $html = render_email_template(
                    $subject,
                    '',
                    $content,
                    'ดูประวัติการส่งมอบ',
                    $app_url . '/pages/claim_detail.php?id=' . $claim_id
                );

                queue_system_email($pdo, $claimant_id, 'claim_completed', $subject, $html, "claim_completed_{$claim_id}_{$claimant_id}", 'claims');
                queue_system_email($pdo, $finder_id, 'claim_completed', $subject, $html, "claim_completed_{$claim_id}_{$finder_id}", 'claims');
                break;
        }

    } catch (PDOException $e) {
        error_log("Error in dispatch_claim_status_email: " . $e->getMessage());
    }
}

/**
 * 4. New Chat Message Notification (Smart Cooldown & Unread Check)
 */
function dispatch_chat_message_email($pdo, $item_id, $sender_id, $receiver_id) {
    $item_id = (int)$item_id;
    $sender_id = (int)$sender_id;
    $receiver_id = (int)$receiver_id;
    $app_url = defined('APP_URL') ? rtrim(APP_URL, '/') : 'http://localhost/lost-and-found';

    if ($item_id <= 0 || $sender_id <= 0 || $receiver_id <= 0 || $sender_id === $receiver_id) {
        return;
    }

    try {
        // 1. Check if receiver has any UNREAD messages in this item
        $stmt_unread = $pdo->prepare("
            SELECT COUNT(*) FROM messages 
            WHERE item_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0
        ");
        $stmt_unread->execute([$item_id, $sender_id, $receiver_id]);
        $unread_count = (int)$stmt_unread->fetchColumn();

        if ($unread_count <= 0) {
            // Receiver has already read the message(s)
            return;
        }

        // 2. Cooldown check: 15-minute bucket window
        // 15 mins = 900 seconds
        $time_bucket = floor(time() / 900);
        $dedup_key = "chat_{$item_id}_{$sender_id}_{$receiver_id}_{$time_bucket}";

        // 3. Fetch item title and sender name
        $stmt_info = $pdo->prepare("
            SELECT i.title as item_title, u.first_name, u.last_name, u.role 
            FROM items i, users u 
            WHERE i.id = ? AND u.id = ?
        ");
        $stmt_info->execute([$item_id, $sender_id]);
        $info = $stmt_info->fetch();

        if (!$info) return;

        $item_title = $info['item_title'];
        $sender_display = ($info['role'] === 'admin') ? 'เจ้าหน้าที่ผู้ดูแลระบบ (Admin)' : trim($info['first_name'] . ' ' . $info['last_name']);

        $subject = "คุณมีข้อความใหม่เกี่ยวกับ '{$item_title}' - Lost & Found";
        $content = '
            <p>คุณมีข้อความใหม่ที่ยังไม่ได้อ่าน <strong>' . $unread_count . ' ข้อความ</strong> จาก <strong>' . htmlspecialchars($sender_display, ENT_QUOTES, 'UTF-8') . '</strong> เกี่ยวกับรายการสิ่งของ:</p>
            <div style="background-color: #f8fafc; border-left: 4px solid #2563eb; padding: 14px; margin: 16px 0; border-radius: 4px;">
                <p style="margin: 0; font-weight: bold; color: #0f172a;">' . htmlspecialchars($item_title, ENT_QUOTES, 'UTF-8') . '</p>
            </div>
            <p>กรุณาคลิกปุ่มด้านล่างเพื่อเปิดอ่านข้อความและตอบกลับในระบบแชท</p>';

        $chat_url = $app_url . '/pages/chat.php?item_id=' . $item_id . '&receiver_id=' . $sender_id;
        $html = render_email_template($subject, '', $content, 'เปิดห้องแชทเพื่อตอบกลับ', $chat_url);

        queue_system_email($pdo, $receiver_id, 'new_chat_message', $subject, $html, $dedup_key, 'messages');

    } catch (PDOException $e) {
        error_log("Error in dispatch_chat_message_email: " . $e->getMessage());
    }
}

/**
 * 5. Admin Moderation Notification (Hide Item, Ban User)
 */
function dispatch_moderation_email($pdo, $action, $target_user_id, $item_id = 0, $reason = '') {
    $target_user_id = (int)$target_user_id;
    $app_url = defined('APP_URL') ? rtrim(APP_URL, '/') : 'http://localhost/lost-and-found';

    if ($target_user_id <= 0) return;

    try {
        if ($action === 'ban_user') {
            $subject = "แจ้งเตือนการระงับสิทธิ์การใช้งานบัญชีของคุณ - Lost & Found";
            $reason_text = !empty($reason) ? htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') : 'ละเมิดเงื่อนไขและข้อกำหนดการใช้งานระบบ';
            $content = '
                <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #991b1b; font-weight: bold; font-size: 15px;">บัญชีผู้ใช้ของคุณถูกระงับสิทธิ์การใช้งาน (Suspended)</p>
                    <p style="margin: 8px 0 0 0; color: #7f1d1d; font-size: 13px;"><strong>สาเหตุ:</strong> ' . $reason_text . '</p>
                </div>
                <p>เนื่องจากบัญชีของคุณถูกระงับสิทธิ์ คุณจะไม่สามารถเข้าสู่ระบบหรือทำรายการใดๆ ได้ชั่วคราว หากคุณเห็นว่ามีความผิดพลาด กรุณาติดต่อทีมงานผู้ดูแลระบบผ่านหน้าติดต่อเรา</p>';

            $html = render_email_template($subject, '', $content, 'ติดต่อฝ่ายสนับสนุน', $app_url . '/pages/contact.php');
            $dedup_key = "ban_user_{$target_user_id}_" . date('Ymd');

            queue_system_email($pdo, $target_user_id, 'ban_user', $subject, $html, $dedup_key, 'system');

        } elseif ($action === 'hide_item') {
            $item_id = (int)$item_id;
            $stmt = $pdo->prepare("SELECT title FROM items WHERE id = ?");
            $stmt->execute([$item_id]);
            $item_title = $stmt->fetchColumn() ?: 'ประกาศของคุณ';

            $subject = "ประกาศ '{$item_title}' ถูกระงับการแสดงผลโดยผู้ดูแลระบบ";
            $reason_text = !empty($reason) ? htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') : 'ตรวจสอบพบข้อมูลหรือรูปภาพที่ขัดต่อเงื่อนไขการใช้งาน';
            $content = '
                <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #92400e; font-weight: bold;">ประกาศถูกซ่อนจากการค้นหาชั่วคราว</p>
                    <p style="margin: 6px 0 0 0; color: #b45309; font-size: 13px;"><strong>เหตุผล:</strong> ' . $reason_text . '</p>
                </div>
                <p>รายการ: <strong>' . htmlspecialchars($item_title, ENT_QUOTES, 'UTF-8') . '</strong></p>
                <p>หากคุณได้แก้ไขรายละเอียดให้ถูกต้องตามเงื่อนไขแล้ว สามารถติดต่อผู้ดูแลระบบเพื่อขอเปิดแสดงผลประกาศใหม่อีกครั้งได้</p>';

            $html = render_email_template($subject, '', $content, 'ดูประกาศของคุณ', $app_url . '/pages/profile.php');
            $dedup_key = "hide_item_{$target_user_id}_{$item_id}_" . date('Ymd');

            queue_system_email($pdo, $target_user_id, 'hide_item', $subject, $html, $dedup_key, 'system');
        }

    } catch (PDOException $e) {
        error_log("Error in dispatch_moderation_email: " . $e->getMessage());
    }
}
