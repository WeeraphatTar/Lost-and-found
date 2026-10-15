<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_config.php';
require_once __DIR__ . '/../helpers/rate_limit_helper.php';
require_once __DIR__ . '/../helpers/mail_helper.php';

$provider = $_GET['provider'] ?? $_POST['provider'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!in_array($provider, ['google', 'facebook'])) {
    $_SESSION['error'] = "ช่องทางเข้าสู่ระบบไม่ถูกต้อง";
    header("Location: ../pages/login.php");
    exit;
}

// Helper: cURL GET request
function http_get_curl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Helper: cURL POST request
function http_post_curl($url, $fields) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// =========================================================================
// 1. INITIATE OAUTH REDIRECT
// =========================================================================
if ($action === 'init' || (empty($_GET['code']) && empty($_GET['error']))) {
    // Generate one-time state and nonce
    $state = bin2hex(random_bytes(16));
    $nonce = bin2hex(random_bytes(16));

    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_nonce'] = $nonce;
    $_SESSION['oauth_provider'] = $provider;

    if ($provider === 'google') {
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
            'prompt' => 'select_account'
        ];
        $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    } else { // facebook
        $params = [
            'client_id' => FACEBOOK_APP_ID,
            'redirect_uri' => FACEBOOK_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'email,public_profile',
            'state' => $state
        ];
        $auth_url = 'https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query($params);
    }

    header("Location: " . $auth_url);
    exit;
}

// =========================================================================
// 2. OAUTH CALLBACK PROCESSING
// =========================================================================
// Check for user cancellation or error from provider
if (isset($_GET['error']) || isset($_GET['error_code'])) {
    unset($_SESSION['oauth_state'], $_SESSION['oauth_nonce'], $_SESSION['oauth_provider']);
    $_SESSION['error'] = "การเข้าสู่ระบบผ่าน " . ucfirst($provider) . " ถูกยกเลิก หรือเกิดข้อผิดพลาด";
    header("Location: ../pages/login.php");
    exit;
}

// Validate CSRF State Token (One-Time Use)
$session_state = $_SESSION['oauth_state'] ?? '';
$session_nonce = $_SESSION['oauth_nonce'] ?? '';
$param_state = $_GET['state'] ?? '';

unset($_SESSION['oauth_state'], $_SESSION['oauth_nonce'], $_SESSION['oauth_provider']);

if (empty($session_state) || empty($param_state) || !hash_equals($session_state, $param_state)) {
    $_SESSION['error'] = "คำขอไม่ถูกต้อง หรือ CSRF State Token หมดอายุ";
    header("Location: ../pages/login.php");
    exit;
}

$code = $_GET['code'] ?? '';
if (empty($code)) {
    $_SESSION['error'] = "ไม่ได้รับ Authorization Code จาก " . ucfirst($provider);
    header("Location: ../pages/login.php");
    exit;
}

$provider_user_id = '';
$email = '';
$first_name = '';
$last_name = '';

// -------------------------------------------------------------------------
// 2.1 GOOGLE TOKEN EXCHANGE & SERVER-SIDE OIDC VERIFICATION
// -------------------------------------------------------------------------
if ($provider === 'google') {
    $token_data = http_post_curl('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ]);

    if (empty($token_data['access_token']) && empty($token_data['id_token'])) {
        $_SESSION['error'] = "ไม่สามารถแลกเปลี่ยน Token กับ Google ได้";
        header("Location: ../pages/login.php");
        exit;
    }

    $user_info = null;

    // Verify ID Token via Google tokeninfo Endpoint if id_token present
    if (!empty($token_data['id_token'])) {
        $token_info = http_get_curl('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($token_data['id_token']));
        
        if ($token_info && !isset($token_info['error'])) {
            // Verify Claims: aud, iss, exp, nonce, email_verified
            $aud = $token_info['aud'] ?? '';
            $iss = $token_info['iss'] ?? '';
            $exp = intval($token_info['exp'] ?? 0);
            $token_nonce = $token_info['nonce'] ?? '';
            $email_verified = filter_var($token_info['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($aud !== GOOGLE_CLIENT_ID) {
                $_SESSION['error'] = "Google ID Token ออกให้สำหรับ Application อื่น";
                header("Location: ../pages/login.php");
                exit;
            }

            if (!in_array($iss, ['https://accounts.google.com', 'accounts.google.com'])) {
                $_SESSION['error'] = "Google ID Token Issuer ไม่ถูกต้อง";
                header("Location: ../pages/login.php");
                exit;
            }

            if ($exp < time()) {
                $_SESSION['error'] = "Google ID Token หมดอายุแล้ว";
                header("Location: ../pages/login.php");
                exit;
            }

            if (!empty($session_nonce) && !empty($token_nonce) && !hash_equals($session_nonce, $token_nonce)) {
                $_SESSION['error'] = "Google ID Token Nonce ไม่ตรงกับ Session";
                header("Location: ../pages/login.php");
                exit;
            }

            if (!$email_verified) {
                $_SESSION['error'] = "อีเมลของคุณใน Google ยังไม่ได้ผ่านการยืนยันตัวตน";
                header("Location: ../pages/login.php");
                exit;
            }

            $user_info = $token_info;
        }
    }

    // Fallback to UserInfo Endpoint if needed
    if (!$user_info && !empty($token_data['access_token'])) {
        $user_info = http_get_curl('https://openidconnect.googleapis.com/v1/userinfo?access_token=' . urlencode($token_data['access_token']));
    }

    if (!$user_info || empty($user_info['sub'])) {
        $_SESSION['error'] = "ไม่สามารถดึงข้อมูลโปรไฟล์จาก Google ได้";
        header("Location: ../pages/login.php");
        exit;
    }

    $provider_user_id = $user_info['sub'];
    $email = trim($user_info['email'] ?? '');
    $first_name = trim($user_info['given_name'] ?? $user_info['name'] ?? 'GoogleUser');
    $last_name = trim($user_info['family_name'] ?? '');
}

// -------------------------------------------------------------------------
// 2.2 FACEBOOK TOKEN EXCHANGE & GRAPH API DEBUGGING
// -------------------------------------------------------------------------
if ($provider === 'facebook') {
    $token_url = 'https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query([
        'client_id' => FACEBOOK_APP_ID,
        'client_secret' => FACEBOOK_APP_SECRET,
        'redirect_uri' => FACEBOOK_REDIRECT_URI,
        'code' => $code
    ]);

    $token_data = http_get_curl($token_url);
    if (empty($token_data['access_token'])) {
        $_SESSION['error'] = "ไม่สามารถแลกเปลี่ยน Token กับ Facebook ได้";
        header("Location: ../pages/login.php");
        exit;
    }

    $access_token = $token_data['access_token'];

    // Debug Access Token on Server-Side
    $debug_url = 'https://graph.facebook.com/v19.0/debug_token?' . http_build_query([
        'input_token' => $access_token,
        'access_token' => FACEBOOK_APP_ID . '|' . FACEBOOK_APP_SECRET
    ]);
    $debug_data = http_get_curl($debug_url);

    if (empty($debug_data['data']) || empty($debug_data['data']['is_valid']) || $debug_data['data']['app_id'] != FACEBOOK_APP_ID) {
        $_SESSION['error'] = "Facebook Access Token ไม่ถูกต้อง หรือออกให้สำหรับ Application อื่น";
        header("Location: ../pages/login.php");
        exit;
    }

    // Fetch User Profile from Graph API v19.0
    $profile_url = 'https://graph.facebook.com/v19.0/me?' . http_build_query([
        'fields' => 'id,first_name,last_name,name,email',
        'access_token' => $access_token
    ]);
    $user_info = http_get_curl($profile_url);

    if (empty($user_info['id'])) {
        $_SESSION['error'] = "ไม่สามารถดึงข้อมูลโปรไฟล์จาก Facebook ได้";
        header("Location: ../pages/login.php");
        exit;
    }

    $provider_user_id = $user_info['id'];
    $email = trim($user_info['email'] ?? '');
    $first_name = trim($user_info['first_name'] ?? $user_info['name'] ?? 'FacebookUser');
    $last_name = trim($user_info['last_name'] ?? '');
}

// =========================================================================
// 3. ACCOUNT LOOKUP & ROUTING LOGIC
// =========================================================================
try {
    // 3.1 Check if provider account is already linked
    $stmt = $pdo->prepare("SELECT sa.user_id, u.first_name, u.role, u.is_banned, u.ban_reason, u.session_version FROM user_social_accounts sa JOIN users u ON sa.user_id = u.id WHERE sa.provider = ? AND sa.provider_user_id = ?");
    $stmt->execute([$provider, $provider_user_id]);
    $linked_user = $stmt->fetch();

    if ($linked_user) {
        // Linked user found: Log in
        if (!empty($linked_user['is_banned'])) {
            $reason = !empty($linked_user['ban_reason']) ? " เนื่องจาก: " . $linked_user['ban_reason'] : "";
            $_SESSION['error'] = "บัญชีของคุณถูกระงับการใช้งาน" . $reason;
            header("Location: ../pages/login.php");
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $linked_user['user_id'];
        $_SESSION['user_name'] = $linked_user['first_name'];
        $_SESSION['user_role'] = $linked_user['role'];
        $_SESSION['session_version'] = $linked_user['session_version'];

        if ($linked_user['role'] === 'admin') {
            header("Location: ../pages/admin/admin_dashboard.php");
        } else {
            header("Location: ../index.php");
        }
        exit;
    }

    // 3.2 If not linked, check if user is already logged in (Linking from Profile Settings)
    if (isset($_SESSION['user_id'])) {
        $current_user_id = $_SESSION['user_id'];

        // Check if provider_user_id is already bound to another user
        $check_bound = $pdo->prepare("SELECT id FROM user_social_accounts WHERE provider = ? AND provider_user_id = ?");
        $check_bound->execute([$provider, $provider_user_id]);
        if ($check_bound->rowCount() > 0) {
            $_SESSION['error'] = "บัญชี " . ucfirst($provider) . " นี้ถูกผูกไว้กับผู้ใช้อื่นในระบบแล้ว";
            header("Location: ../pages/profile.php");
            exit;
        }

        // PDO Transaction: Bind account to current user
        $pdo->beginTransaction();
        $ins_social = $pdo->prepare("INSERT INTO user_social_accounts (user_id, provider, provider_user_id) VALUES (?, ?, ?)");
        $ins_social->execute([$current_user_id, $provider, $provider_user_id]);
        $pdo->commit();

        $_SESSION['success'] = "✓ เชื่อมต่อบัญชี " . ucfirst($provider) . " สำเร็จเรียบร้อยแล้ว";
        header("Location: ../pages/profile.php");
        exit;
    }

    // 3.3 Check if Email matches an existing account in `users`
    if (!empty($email)) {
        $check_email = $pdo->prepare("SELECT id, email, password_hash, first_name FROM users WHERE email = ?");
        $check_email->execute([$email]);
        $existing_user = $check_email->fetch();

        if ($existing_user) {
            // Account Linking Safety Flow: Require confirmation!
            $_SESSION['pending_social_link'] = [
                'user_id' => $existing_user['id'],
                'email' => $existing_user['email'],
                'first_name' => $existing_user['first_name'],
                'has_password' => !empty($existing_user['password_hash']),
                'provider' => $provider,
                'provider_user_id' => $provider_user_id
            ];

            header("Location: ../pages/confirm_social_link.php");
            exit;
        }
    }

    // 3.4 New Social User Registration Flow
    $_SESSION['pending_social_user'] = [
        'provider' => $provider,
        'provider_user_id' => $provider_user_id,
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name
    ];

    header("Location: ../pages/complete_social_profile.php");
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "เกิดข้อผิดพลาดฐานข้อมูล: " . $e->getMessage();
    header("Location: ../pages/login.php");
    exit;
}
