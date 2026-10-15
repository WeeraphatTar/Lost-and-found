<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_config.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/mail_helper.php';

$pending = $_SESSION['pending_social_user'] ?? null;
if (!$pending) {
    header("Location: login.php");
    exit;
}

$provider = $pending['provider'] ?? '';
$provider_user_id = $pending['provider_user_id'] ?? '';
$email = trim($_POST['email'] ?? $pending['email'] ?? '');
$first_name = trim($_POST['first_name'] ?? $pending['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? $pending['last_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');

$email_needs_otp = empty($pending['email']); // True if email was missing from provider (e.g. Facebook)

// 1. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['submit_otp'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "คำขอไม่ถูกต้อง หรือ CSRF Token หมดอายุ";
    } elseif (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วนทุกช่อง";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "รูปแบบอีเมลไม่ถูกต้อง";
    } elseif (!preg_match('/^0[0-9]{8,9}$/', $phone)) {
        $_SESSION['error'] = "รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง (ต้องเป็นตัวเลข 9-10 หลัก ขึ้นต้นด้วย 0)";
    } elseif (!isset($_POST['accept_terms']) || !isset($_POST['accept_privacy'])) {
        $_SESSION['error'] = "กรุณากดยอมรับข้อตกลงการใช้งานและนโยบายความเป็นส่วนตัว";
    } else {
        try {
            // Check if email already exists
            $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check_email->execute([$email]);
            if ($check_email->rowCount() > 0) {
                $_SESSION['error'] = "อีเมลนี้มีผู้ใช้งานแล้วในระบบ โปรดใช้อีเมลอื่น หรือทำขั้นตอนเชื่อมต่อบัญชี";
            } else {
                if ($email_needs_otp) {
                    // Need Email OTP Verification first!
                    $otp_code = sprintf("%06d", random_int(0, 999999));
                    $otp_hash = hash('sha256', $otp_code);
                    $expires_at = date('Y-m-d H:i:s', time() + (OTP_EXPIRY_MINUTES * 60));

                    // Store pending data + OTP details in session
                    $_SESSION['pending_social_user']['email'] = $email;
                    $_SESSION['pending_social_user']['first_name'] = $first_name;
                    $_SESSION['pending_social_user']['last_name'] = $last_name;
                    $_SESSION['pending_social_user']['phone'] = $phone;
                    $_SESSION['pending_social_user']['otp_hash'] = $otp_hash;
                    $_SESSION['pending_social_user']['otp_expires_at'] = $expires_at;

                    // Send Email OTP
                    send_otp_email($email, $first_name, $otp_code);

                    $_SESSION['success'] = "โปรดยืนยันรหัส OTP ที่ส่งไปยังอีเมล " . htmlspecialchars($email) . " เพื่อเปิดใช้งานบัญชี";
                    header("Location: complete_social_profile.php?verify_otp=1");
                    exit;
                } else {
                    // Direct Registration via PDO Transaction
                    $pdo->beginTransaction();

                    $ins_user = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password_hash, is_verified, session_version) VALUES (?, ?, ?, ?, NULL, 1, 1)");
                    $ins_user->execute([$first_name, $last_name, $email, $phone]);
                    $new_user_id = $pdo->lastInsertId();

                    $ins_social = $pdo->prepare("INSERT INTO user_social_accounts (user_id, provider, provider_user_id) VALUES (?, ?, ?)");
                    $ins_social->execute([$new_user_id, $provider, $provider_user_id]);

                    $pdo->commit();

                    unset($_SESSION['pending_social_user']);
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['user_name'] = $first_name;
                    $_SESSION['user_role'] = 'user';
                    $_SESSION['session_version'] = 1;

                    $_SESSION['success'] = "✓ สมัครสมาชิกผ่าน " . ucfirst($provider) . " สำเร็จ!";
                    header("Location: ../index.php");
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = "เกิดข้อผิดพลาดฐานข้อมูล: " . $e->getMessage();
        }
    }
}

// 2. Handle OTP Verification if required
if (isset($_GET['verify_otp']) && isset($_POST['submit_otp'])) {
    $otp_input = trim($_POST['otp_code'] ?? '');
    $stored_hash = $pending['otp_hash'] ?? '';
    $expires_at = $pending['otp_expires_at'] ?? '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "CSRF Token ไม่ถูกต้อง";
    } elseif (empty($otp_input) || strlen($otp_input) !== 6) {
        $_SESSION['error'] = "กรุณากรอกรหัส OTP ตัวเลข 6 หลักให้ถูกต้อง";
    } elseif ($expires_at < date('Y-m-d H:i:s')) {
        $_SESSION['error'] = "รหัส OTP หมดอายุแล้ว กรุณาลองใหม่อีกครั้ง";
    } elseif (!hash_equals($stored_hash, hash('sha256', $otp_input))) {
        $_SESSION['error'] = "รหัส OTP ไม่ถูกต้อง";
    } else {
        try {
            $pdo->beginTransaction();

            $ins_user = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password_hash, is_verified, session_version) VALUES (?, ?, ?, ?, NULL, 1, 1)");
            $ins_user->execute([$pending['first_name'], $pending['last_name'], $pending['email'], $pending['phone']]);
            $new_user_id = $pdo->lastInsertId();

            $ins_social = $pdo->prepare("INSERT INTO user_social_accounts (user_id, provider, provider_user_id) VALUES (?, ?, ?)");
            $ins_social->execute([$new_user_id, $provider, $provider_user_id]);

            $pdo->commit();

            unset($_SESSION['pending_social_user']);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['user_name'] = $pending['first_name'];
            $_SESSION['user_role'] = 'user';
            $_SESSION['session_version'] = 1;

            $_SESSION['success'] = "✓ ยืนยันอีเมลและสมัครสมาชิกผ่าน " . ucfirst($provider) . " สำเร็จ!";
            header("Location: ../index.php");
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
        }
    }
}

// Render HTML View
$base_url = 'http://localhost/lost-and-found';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-16 bg-background flex-grow flex items-center justify-center px-4">
    <div class="w-full max-w-lg bg-white p-8 md:p-10 rounded-lg shadow-sm border border-gray-200">
        <h2 class="text-2xl font-bold text-center text-primary mb-2">กรอกข้อมูลเพิ่มเติม</h2>
        <p class="text-center text-gray-500 mb-8">อีกเพียงขั้นตอนเดียวเพื่อสร้างบัญชีผ่าน <?php echo ucfirst($provider); ?></p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="p-4 mb-6 text-sm text-red-700 bg-red-100 border border-red-300 rounded-md">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="p-4 mb-6 text-sm text-indigo-900 bg-indigo-50 border border-indigo-200 rounded-md">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['verify_otp'])): ?>
            <!-- OTP Form -->
            <form action="complete_social_profile.php?verify_otp=1" method="POST" class="space-y-5">
                <?php render_csrf_input(); ?>
                <input type="hidden" name="submit_otp" value="1">
                <div>
                    <label class="block font-medium text-primary mb-1">รหัส OTP 6 หลัก</label>
                    <input type="text" name="otp_code" maxlength="6" class="w-full text-center tracking-widest font-bold text-2xl px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:border-accent" placeholder="000000" required>
                </div>
                <button type="submit" class="w-full py-3 px-4 bg-primary text-white font-medium text-lg rounded-md hover:bg-secondary transition shadow-sm">ยืนยันรหัส OTP</button>
            </form>
        <?php else: ?>
            <!-- Main Profile Form -->
            <form action="complete_social_profile.php" method="POST" class="space-y-5">
                <?php render_csrf_input(); ?>

                <div class="flex flex-col sm:flex-row gap-5">
                    <div class="flex-1">
                        <label class="block font-medium text-primary mb-1">ชื่อ <span class="asterisk text-red-500">*</span></label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent" required>
                    </div>
                    <div class="flex-1">
                        <label class="block font-medium text-primary mb-1">นามสกุล <span class="asterisk text-red-500">*</span></label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent" required>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-primary mb-1">อีเมล <span class="asterisk text-red-500">*</span></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent <?php echo !empty($pending['email']) ? 'bg-gray-100 cursor-not-allowed' : ''; ?>" <?php echo !empty($pending['email']) ? 'readonly' : 'required'; ?>>
                </div>

                <div>
                    <label class="block font-medium text-primary mb-1">เบอร์โทรศัพท์ <span class="asterisk text-red-500">*</span></label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone); ?>" pattern="^0[0-9]{8,9}$" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent" placeholder="08xxxxxxxx หรือ 02xxxxxxx" required>
                    <p class="text-xs text-gray-400 mt-1">กรอกเบอร์โทรศัพท์ 9-10 หลัก ขึ้นต้นด้วย 0</p>
                </div>

                <div class="text-sm text-gray-600 pt-2 space-y-2">
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" name="accept_terms" value="1" required class="mt-1 mr-2 rounded text-primary"> 
                        <span>ฉันยอมรับ <a href="<?php echo $base_url; ?>/pages/help.php" target="_blank" class="text-accent hover:underline">ข้อตกลงการใช้งาน</a></span>
                    </label>
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" name="accept_privacy" value="1" required class="mt-1 mr-2 rounded text-primary"> 
                        <span>ฉันรับทราบ <a href="<?php echo $base_url; ?>/pages/privacy.php" target="_blank" class="text-accent hover:underline">นโยบายความเป็นส่วนตัว</a></span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-primary text-white font-medium text-lg rounded-md hover:bg-secondary transition shadow-sm mt-2">เสร็จสิ้นการลงทะเบียน</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
