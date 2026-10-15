<?php
require_once __DIR__ . '/../config/auth_config.php';

/**
 * Send email using Native Socket SMTP or PHP mail() fallback
 */
function send_system_email($to_email, $subject, $html_body) {
    if (defined('MAIL_DRIVER') && MAIL_DRIVER === 'smtp' && defined('SMTP_HOST') && !empty(SMTP_HOST)) {
        return send_native_smtp_email($to_email, $subject, $html_body);
    }

    // Fallback to PHP mail()
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $headers[] = 'From: =?UTF-8?B?' . base64_encode(MAIL_FROM_NAME) . '?= <' . MAIL_FROM . '>';
    $headers[] = 'Reply-To: ' . MAIL_FROM;
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    $encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    return @mail($to_email, $encoded_subject, $html_body, implode("\r\n", $headers));
}

/**
 * Native Socket-based SMTP Implementation (Supports TLS / STARTTLS & Port 587/465)
 */
function send_native_smtp_email($to_email, $subject, $html_body) {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USER;
    $pass = str_replace(' ', '', SMTP_PASS); // Remove spaces from App Password if any
    $from = MAIL_FROM;
    $from_name = MAIL_FROM_NAME;

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket_prefix = ($port == 465 || (defined('SMTP_SECURE') && SMTP_SECURE === 'ssl')) ? 'ssl://' : 'tcp://';
    $socket = @stream_socket_client("{$socket_prefix}{$host}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);

    if (!$socket) {
        error_log("SMTP Error: Connection failed - $errstr ($errno)");
        return false;
    }

    $read_response = function($expected_code) use ($socket) {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        $code = substr($response, 0, 3);
        if ($code != $expected_code) {
            error_log("SMTP Response Error: Expected $expected_code, Got: $response");
            return false;
        }
        return $response;
    };

    $send_cmd = function($cmd) use ($socket) {
        fputs($socket, $cmd . "\r\n");
    };

    if (!$read_response('220')) { fclose($socket); return false; }

    $send_cmd("EHLO localhost");
    if (!$read_response('250')) { fclose($socket); return false; }

    if ((defined('SMTP_SECURE') && SMTP_SECURE === 'tls') || $port == 587) {
        $send_cmd("STARTTLS");
        if (!$read_response('220')) { fclose($socket); return false; }

        $crypto_ok = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
        if (!$crypto_ok) {
            $crypto_ok = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }
        if (!$crypto_ok) {
            error_log("SMTP TLS Enable Failed");
            fclose($socket);
            return false;
        }

        $send_cmd("EHLO localhost");
        if (!$read_response('250')) { fclose($socket); return false; }
    }

    $send_cmd("AUTH LOGIN");
    if (!$read_response('334')) { fclose($socket); return false; }

    $send_cmd(base64_encode($user));
    if (!$read_response('334')) { fclose($socket); return false; }

    $send_cmd(base64_encode($pass));
    if (!$read_response('235')) { fclose($socket); return false; }

    $send_cmd("MAIL FROM: <{$from}>");
    if (!$read_response('250')) { fclose($socket); return false; }

    $send_cmd("RCPT TO: <{$to_email}>");
    if (!$read_response('250')) { fclose($socket); return false; }

    $send_cmd("DATA");
    if (!$read_response('354')) { fclose($socket); return false; }

    $encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encoded_from = '=?UTF-8?B?' . base64_encode($from_name) . '?= <' . $from . '>';

    $headers = [
        "From: {$encoded_from}",
        "To: <{$to_email}>",
        "Subject: {$encoded_subject}",
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "Content-Transfer-Encoding: 8bit",
        "Date: " . date('r'),
        "Message-ID: <" . time() . "." . uniqid() . "@gmail.com>"
    ];

    $email_payload = implode("\r\n", $headers) . "\r\n\r\n" . $html_body . "\r\n.";
    $send_cmd($email_payload);
    if (!$read_response('250')) { fclose($socket); return false; }

    $send_cmd("QUIT");
    fclose($socket);

    return true;
}

/**
 * Send OTP Code Email
 */
function send_otp_email($to_email, $first_name, $otp_code) {
    $subject = "รหัส OTP ยืนยันบัญชีผู้ใช้ - Lost & Found";
    $body = '
    <div style="font-family: \'Prompt\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Arial, sans-serif; max-width: 540px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <!-- Header -->
        <div style="background-color: #1e1b4b; padding: 26px 20px; text-align: center;">
            <div style="font-size: 19px; font-weight: 700; color: #ffffff; letter-spacing: 0.3px;">Lost and Found</div>
            <div style="font-size: 13px; font-weight: 400; color: #c7d2fe; margin-top: 4px;">ระบบแจ้งของหายและติดตามทรัพย์สินคืน</div>
        </div>
        <!-- Content -->
        <div style="padding: 28px 24px; color: #1e293b; line-height: 1.6;">
            <h2 style="color: #0f172a; margin-top: 0; margin-bottom: 16px; font-size: 18px; font-weight: bold;">ยืนยันอีเมลของคุณ</h2>
            <p style="font-size: 14px; color: #334155; margin-bottom: 12px;">สวัสดีคุณ <strong>' . htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
            <p style="font-size: 14px; color: #334155; margin-bottom: 20px;">ขอบคุณที่สมัครใช้งานระบบ Lost &amp; Found กรุณาใช้รหัส OTP ด้านล่างเพื่อยืนยันบัญชีของคุณ (รหัสมีอายุ ' . OTP_EXPIRY_MINUTES . ' นาที):</p>
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; text-align: center; border-radius: 6px; margin: 20px 0;">
                <span style="font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #1e1b4b;">' . htmlspecialchars($otp_code, ENT_QUOTES, 'UTF-8') . '</span>
            </div>
            <p style="color: #64748b; font-size: 13px; margin: 0;">หากคุณไม่ได้สมัครสมาชิกระบบ Lost &amp; Found โปรดข้ามอีเมลฉบับนี้</p>
        </div>
        <!-- Footer -->
        <div style="background-color: #f8fafc; padding: 16px 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;">
            &copy; ' . date('Y') . ' Lost &amp; Found System. All rights reserved.
        </div>
    </div>';
    
    return send_system_email($to_email, $subject, $body);
}

/**
 * Send Password Reset Email
 */
function send_password_reset_email($to_email, $first_name, $reset_url) {
    $subject = "คำขอตั้งรหัสผ่านใหม่ - Lost & Found";
    $body = '
    <div style="font-family: \'Prompt\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Arial, sans-serif; max-width: 540px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <style>
            .btn-action:hover {
                background-color: #1e1b4b !important;
            }
        </style>
        <!-- Header -->
        <div style="background-color: #1e1b4b; padding: 26px 20px; text-align: center;">
            <div style="font-size: 19px; font-weight: 700; color: #ffffff; letter-spacing: 0.3px;">Lost and Found</div>
            <div style="font-size: 13px; font-weight: 400; color: #c7d2fe; margin-top: 4px;">ระบบแจ้งของหายและติดตามทรัพย์สินคืน</div>
        </div>
        <!-- Content -->
        <div style="padding: 28px 24px; color: #1e293b; line-height: 1.6;">
            <h2 style="color: #0f172a; margin-top: 0; margin-bottom: 16px; font-size: 18px; font-weight: bold;">รีเซ็ตรหัสผ่าน</h2>
            <p style="font-size: 14px; color: #334155; margin-bottom: 12px;">สวัสดีคุณ <strong>' . htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
            <p style="font-size: 14px; color: #334155; margin-bottom: 20px;">คุณได้ทำการร้องขอเปลี่ยนรหัสผ่านสำหรับระบบ Lost &amp; Found กรุณาคลิกปุ่มด้านล่างเพื่อตั้งรหัสผ่านใหม่ (ลิงก์มีอายุ ' . RESET_TOKEN_EXPIRY_MINUTES . ' นาที):</p>
            <div style="text-align: center; margin: 24px 0;">
                <a href="' . htmlspecialchars($reset_url, ENT_QUOTES, 'UTF-8') . '" class="btn-action" style="background-color: #312e81; color: #ffffff; padding: 12px 26px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block;">ตั้งรหัสผ่านใหม่</a>
            </div>
            <p style="color: #64748b; font-size: 13px; margin-bottom: 4px;">หากปุ่มกดไม่ได้ สามารถคัดลอก URL นี้ไปวางในเบราว์เซอร์:</p>
            <p style="font-size: 12px; word-break: break-all; margin-top: 0;"><a href="' . htmlspecialchars($reset_url, ENT_QUOTES, 'UTF-8') . '" style="color: #312e81;">' . htmlspecialchars($reset_url, ENT_QUOTES, 'UTF-8') . '</a></p>
            <p style="color: #64748b; font-size: 13px; margin: 0;">หากคุณไม่ได้ส่งคำขอนี้ โปรดละเว้นอีเมลนี้ รหัสผ่านของคุณจะไม่มีการเปลี่ยนแปลง</p>
        </div>
        <!-- Footer -->
        <div style="background-color: #f8fafc; padding: 16px 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;">
            &copy; ' . date('Y') . ' Lost &amp; Found System. All rights reserved.
        </div>
    </div>';

    return send_system_email($to_email, $subject, $body);
}
?>
