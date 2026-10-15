<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "CSRF Token ไม่ถูกต้อง";
    } elseif ($has_password) {
        $password = $_POST['password'] ?? '';
        
        // Fetch current user details
        $stmt = $pdo->prepare("SELECT id, password_hash, role, is_banned, ban_reason, session_version FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
            if (!empty($user['is_banned'])) {
                $_SESSION['error'] = "บัญชีนี้ถูกระงับการใช้งาน";
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
                header("Location: ../index.php");
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
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-16 bg-background flex-grow flex items-center justify-center px-4">
    <div class="w-full max-w-lg bg-white p-8 md:p-10 rounded-lg shadow-sm border border-gray-200">
        <h2 class="text-2xl font-bold text-center text-primary mb-2">ยืนยันการเชื่อมต่อบัญชี</h2>
        <p class="text-center text-gray-500 mb-6">พบอีเมล <strong><?php echo htmlspecialchars($email); ?></strong> ในระบบแล้ว</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="p-4 mb-6 text-sm text-red-700 bg-red-100 border border-red-300 rounded-md">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if ($has_password): ?>
            <p class="text-sm text-gray-600 mb-6 bg-blue-50 p-4 border border-blue-200 rounded-md">
                เพื่อความปลอดภัยของการผูกบัญชี กรุณากรอกรหัสผ่านเดิมของบัญชีนี้เพื่อยืนยันความเป็นเจ้าของ ก่อนทำการผูกกับ <?php echo ucfirst($provider); ?>
            </p>

            <form action="confirm_social_link.php" method="POST" class="space-y-5">
                <?php render_csrf_input(); ?>

                <div>
                    <label class="block font-medium text-primary mb-1">รหัสผ่านเดิมของคุณ</label>
                    <div class="relative password-wrapper">
                        <input type="password" name="password" class="w-full pl-4 pr-11 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-accent" required placeholder="••••••••">
                        <button type="button" class="toggle-password-btn hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1 transition">
                            <svg class="w-5 h-5 icon-eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.573 16.49 16.638 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <svg class="w-5 h-5 icon-eye hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.326 16.157 7.305 19.5 12 19.5c1.84 0 3.568-.45 5.088-1.246M9.88 9.88a3 3 0 104.24 4.24M10.73 5.08A10.43 10.43 0 0112 4.5c4.695 0 8.673 2.926 10.168 7.083a10.495 10.495 0 01-2.28 3.864M3 3l18 18"></path></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-primary text-white font-medium text-lg rounded-md hover:bg-secondary transition shadow-sm">ยืนยันรหัสผ่านและผูกบัญชี</button>
            </form>
        <?php else: ?>
            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-md text-sm text-yellow-900 space-y-3">
                <p>บัญชีอีเมลนี้ถูกสร้างผ่าน Social Login ช่องทางอื่นแล้ว และยังไม่ได้ตั้งรหัสผ่าน</p>
                <p>กรุณาเข้าสู่ระบบผ่านช่องทาง Social เดิมก่อน จากนั้นจึงทำการเชื่อมต่อกับ <?php echo ucfirst($provider); ?> ที่หน้าตั้งค่าโปรไฟล์</p>
            </div>
            <div class="mt-6 text-center">
                <a href="login.php" class="inline-block py-2.5 px-6 bg-primary text-white font-medium rounded-md hover:bg-secondary transition">กลับไปหน้าเข้าสู่ระบบ</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
