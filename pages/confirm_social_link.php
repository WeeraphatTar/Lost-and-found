<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';

$pending = $_SESSION['pending_social_link'] ?? null;
if (!$pending) {
    header("Location: login.php");
    exit;
}

$user_id = $pending['user_id'];
$email = $pending['email'];
$first_name = $pending['first_name'];
$has_password = $pending['has_password'];
$provider = $pending['provider'];
$provider_user_id = $pending['provider_user_id'];

// Find existing connected social providers for this user (e.g. Facebook, Google)
$existing_providers = [];
try {
    $stmt_existing = $pdo->prepare("SELECT provider FROM user_social_accounts WHERE user_id = ?");
    $stmt_existing->execute([$user_id]);
    $existing_providers = $stmt_existing->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $existing_providers = [];
}

// Handle Cancel Action
if (isset($_GET['action']) && $_GET['action'] === 'cancel') {
    unset($_SESSION['pending_social_link']);
    $_SESSION['error'] = "ยกเลิกการเชื่อมต่อบัญชี " . ucfirst($provider) . " แล้ว";
    header("Location: login.php");
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "CSRF Token ไม่ถูกต้อง หรือหมดอายุแล้ว";
    } elseif ($has_password) {
        $password = $_POST['password'] ?? '';
        
        // Fetch current user details
        $stmt = $pdo->prepare("SELECT id, password_hash, role, is_banned, ban_reason, session_version FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
            if (!empty($user['is_banned'])) {
                $_SESSION['error'] = "บัญชีนี้ถูกระงับการใช้งาน" . (!empty($user['ban_reason']) ? " เนื่องจาก: " . $user['ban_reason'] : "");
                unset($_SESSION['pending_social_link']);
                header("Location: login.php");
                exit;
            }

            try {
                // PDO Transaction: Bind account
                $pdo->beginTransaction();
                $ins_social = $pdo->prepare("INSERT INTO user_social_accounts (user_id, provider, provider_user_id) VALUES (?, ?, ?)");
                $ins_social->execute([$user_id, $provider, $provider_user_id]);
                $pdo->commit();

                unset($_SESSION['pending_social_link']);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $first_name;
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['session_version'] = $user['session_version'];

                $_SESSION['success'] = "✓ ยืนยันรหัสผ่านและเชื่อมต่อบัญชี " . ucfirst($provider) . " สำเร็จ!";
                
                if ($user['role'] === 'admin') {
                    header("Location: admin/admin_dashboard.php");
                } else {
                    header("Location: ../index.php");
                }
                exit;

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['error'] = "เกิดข้อผิดพลาดในการผูกบัญชี: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "รหัสผ่านไม่ถูกต้อง โปรดลองใหม่อีกครั้ง";
        }
    }
}

$page_title = "ยืนยันการเชื่อมต่อบัญชี - Lost and Found";
$base_url = 'http://localhost/lost-and-found';
$hide_nav_footer = true;
require_once __DIR__ . '/../includes/header.php';

// Format existing providers string for UI
$existing_providers_labels = array_map(function($p) {
    return ucfirst($p);
}, $existing_providers);

$existing_providers_text = !empty($existing_providers_labels) ? implode(' / ', $existing_providers_labels) : 'อีเมลและรหัสผ่านเดิม';
?>

<div class="min-h-screen flex items-center justify-center bg-[#f1f5f9] py-10 px-4">
    <div class="w-full max-w-lg bg-white p-8 md:p-10 rounded-2xl shadow-lg border border-gray-200/80">
        
        <a href="<?php echo $base_url; ?>/index.php" class="block">
            <img src="<?php echo $base_url; ?>/assets/images/logo-navy%20v2%20nobg.png" alt="Lost & Found" class="h-16 md:h-20 w-auto mx-auto block mb-3 object-contain">
        </a>

        <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 mx-auto flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-center text-primary leading-tight">ยืนยันการเชื่อมต่อบัญชี</h2>
        <p class="text-center text-gray-500 text-sm mt-1 mb-6">เชื่อมโยงบัญชีโซเชียลของคุณเข้ากับระบบ Lost & Found</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="p-4 mb-6 text-sm text-red-700 bg-red-100 border border-red-300 rounded-xl">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="p-4 mb-6 text-sm text-green-700 bg-green-100 border border-green-300 rounded-xl">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Detection Notice Card -->
        <div class="mb-6 p-4 rounded-xl bg-amber-50/70 border border-amber-200 text-amber-900 text-sm space-y-2">
            <div class="flex items-start gap-2.5">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div class="flex-1">
                    <p class="font-semibold text-amber-950">ตรวจพบอีเมลซ้ำในระบบ</p>
                    <p class="text-xs text-amber-800 mt-1">
                        อีเมล <strong><?php echo htmlspecialchars($email); ?></strong> มีการเชื่อมโยงกับบัญชี <strong><?php echo htmlspecialchars($existing_providers_text); ?></strong> อยู่แล้วในระบบ
                    </p>
                </div>
            </div>
        </div>

        <?php if ($has_password): ?>
            <!-- Password Confirmation Form -->
            <p class="text-xs text-gray-500 mb-5 leading-relaxed">
                เพื่อความปลอดภัยของบัญชี กรุณากรอกรหัสผ่านเดิมเพื่อยืนยันว่าคุณเป็นเจ้าของบัญชีจริง ก่อนทำการเชื่อมโยงเข้ากับบัญชี <strong><?php echo ucfirst($provider); ?></strong>
            </p>

            <form action="confirm_social_link.php" method="POST" class="space-y-5">
                <?php render_csrf_input(); ?>

                <div>
                    <label class="block font-medium text-sm text-primary mb-1.5">รหัสผ่านเดิมของบัญชีนี้</label>
                    <div class="relative password-wrapper">
                        <input type="password" name="password" class="w-full pl-4 pr-11 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent text-sm" required placeholder="••••••••" autofocus>
                        <button type="button" class="toggle-password-btn hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1 transition">
                            <svg class="w-5 h-5 icon-eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.573 16.49 16.638 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <svg class="w-5 h-5 icon-eye hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.326 16.157 7.305 19.5 12 19.5c1.84 0 3.568-.45 5.088-1.246M9.88 9.88a3 3 0 104.24 4.24M10.73 5.08A10.43 10.43 0 0112 4.5c4.695 0 8.673 2.926 10.168 7.083a10.495 10.495 0 01-2.28 3.864M3 3l18 18"></path></svg>
                        </button>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <a href="confirm_social_link.php?action=cancel" class="sm:flex-1 py-2.5 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-xl text-center transition">
                        ยกเลิก
                    </a>
                    <button type="submit" class="sm:flex-2 py-2.5 px-5 bg-primary text-white font-medium text-sm rounded-xl hover:bg-secondary transition shadow-sm">
                        ยืนยันรหัสผ่านและเชื่อมต่อ <?php echo ucfirst($provider); ?>
                    </button>
                </div>
            </form>
        <?php else: ?>
            <!-- No Password Set: Social Only Account -->
            <div class="p-4 bg-blue-50/70 border border-blue-200 rounded-xl text-xs text-blue-900 space-y-2 mb-6">
                <p class="font-medium text-blue-950">คำแนะนำการเข้าสู่ระบบ:</p>
                <p>บัญชีอีเมลนี้ถูกสร้างผ่านระบบ Social Login (<?php echo htmlspecialchars($existing_providers_text); ?>) และยังไม่มีการตั้งรหัสผ่าน</p>
                <p>กรุณาเข้าสู่ระบบผ่านช่องทาง <strong><?php echo htmlspecialchars($existing_providers_text); ?></strong> จากนั้นสามารถเข้าไปที่หน้า "ตั้งค่าโปรไฟล์" เพื่อทำการเชื่อมต่อกับ <?php echo ucfirst($provider); ?> ได้ทันที</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="confirm_social_link.php?action=cancel" class="w-full py-2.5 px-4 bg-primary text-white font-medium text-sm rounded-xl hover:bg-secondary text-center transition shadow-sm">
                    กลับไปหน้าเข้าสู่ระบบ (Login)
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
