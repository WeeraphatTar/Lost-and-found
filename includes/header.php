<?php
// เริ่มต้น session หากยังไม่มีการเริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// หาชื่อไฟล์ปัจจุบันเพื่อทำ Active State
$current_page = basename($_SERVER['PHP_SELF']);

// ตรวจสอบ Cookie Remember Me
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
    require_once __DIR__ . '/../config/database.php';
    
    $user_id = $_COOKIE['remember_user'];
    $token = $_COOKIE['remember_token'];
    
    try {
        $stmt = $pdo->prepare("SELECT id, first_name, role, remember_token FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        // ตรวจสอบว่า token ตรงกันหรือไม่
        if ($user && $user['remember_token'] === $token) {
            // เข้าสู่ระบบอัตโนมัติ
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'];
            $_SESSION['user_role'] = $user['role'];
        } else {
            // หากไม่ตรง ให้ลบ Cookie ทิ้งเพื่อความปลอดภัย
            setcookie('remember_token', '', time() - 3600, "/");
            setcookie('remember_user', '', time() - 3600, "/");
        }
    } catch (PDOException $e) {
        // เงียบไว้หากเกิดข้อผิดพลาดฐานข้อมูล
    }
}

// กำหนด Base URL ของโปรเจค
$base_url = 'http://localhost/Lost_found';

$nav_items = [
    '/index.php' => 'หน้าหลัก',
    '/pages/browse.php' => 'รายการประกาศ',
    '/pages/report_lost.php' => 'แจ้งของหาย',
    '/pages/report_found.php' => 'แจ้งพบของ',
    '/pages/support.php' => 'ศูนย์ช่วยเหลือ'
];

// Notification logic
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/notification_helper.php';
require_once __DIR__ . '/messaging_helper.php';

$unread_count = 0;
$unread_notifications = [];
$unread_msg_count = 0;

if (isset($_SESSION['user_id'])) {
    $unread_count = get_unread_count($pdo, $_SESSION['user_id']);
    $unread_notifications = get_unread_notifications($pdo, $_SESSION['user_id'], 5);
    $unread_msg_count = get_unread_message_count($pdo, $_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found - ระบบแจ้งของหายและติดตามทรัพย์สินคืน</title>
    <!-- Google Fonts: Prompt -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0A192F',
                        secondary: '#172A45',
                        accent: '#3b82f6',
                        background: '#F8F9FA'
                    },
                    fontFamily: {
                        sans: ['Prompt', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Styles (for leftovers/overrides) -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
</head>
<body class="bg-background text-gray-800 font-sans min-h-screen flex flex-col overflow-y-scroll">

    <nav class="bg-primary shadow-md sticky top-0 z-50">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20 relative">
                <!-- Logo (Left) -->
                <div class="flex-shrink-0">
                    <a href="<?php echo $base_url; ?>/index.php" class="text-white text-2xl font-bold hover:text-gray-300 tracking-wider">Lost & Found</a>
                </div>
                
                <!-- Desktop Menu (Center - Absolute Position) -->
                <div class="hidden md:flex absolute left-1/2 transform -translate-x-1/2 space-x-8 whitespace-nowrap">
                    <?php
                    foreach ($nav_items as $url => $label) {
                        $item_page = basename($url);
                        $is_active = ($current_page == $item_page);
                        if ($is_active) {
                            echo '<a href="'.$base_url.$url.'" class="text-white font-bold border-b-2 border-accent pb-1 transition">'.$label.'</a>';
                        } else {
                            echo '<a href="'.$base_url.$url.'" class="text-gray-400 hover:text-white transition">'.$label.'</a>';
                        }
                    }
                    ?>
                </div>
                
                <!-- User Area (Right) -->
                <div class="hidden md:flex ml-auto items-center space-x-5">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        
                        <!-- Messaging Icon (Desktop) -->
                        <div class="relative">
                            <a href="<?php echo $base_url; ?>/pages/messages.php" class="relative inline-flex items-center p-2 text-gray-400 hover:text-white transition focus:outline-none">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                </svg>
                                <?php if ($unread_msg_count > 0): ?>
                                    <span class="absolute top-1 right-1 flex h-4 w-4">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[10px] text-white items-center justify-center font-bold"><?php echo $unread_msg_count; ?></span>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </div>

                        <!-- Notification Bell (Desktop) -->
                        <div class="relative" id="notification-wrapper">
                            <button id="notification-btn" class="relative p-2 text-gray-400 hover:text-white transition focus:outline-none">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                <?php if ($unread_count > 0): ?>
                                    <span class="absolute top-1 right-1 flex h-4 w-4">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[10px] text-white items-center justify-center font-bold"><?php echo $unread_count; ?></span>
                                    </span>
                                <?php endif; ?>
                            </button>

                            <!-- Notification Dropdown -->
                            <div id="notification-dropdown" class="hidden absolute right-0 mt-3 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-[60] overflow-hidden">
                                <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                    <h3 class="text-sm font-bold text-gray-700">การแจ้งเตือน</h3>
                                    <?php if ($unread_count > 0): ?>
                                        <a href="<?php echo $base_url; ?>/actions/notification_action.php?action=mark_all_read" class="text-xs text-accent hover:underline">อ่านทั้งหมด</a>
                                    <?php endif; ?>
                                </div>
                                <div class="max-h-96 overflow-y-auto">
                                    <?php if (empty($unread_notifications)): ?>
                                        <div class="px-4 py-8 text-center text-gray-400">
                                            <svg class="w-10 h-10 mx-auto mb-2 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                            <p class="text-sm">ไม่มีการแจ้งเตือนใหม่</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($unread_notifications as $notif): ?>
                                            <a href="<?php echo $base_url; ?>/actions/notification_action.php?action=read&id=<?php echo $notif['id']; ?>&link=<?php echo urlencode($notif['link']); ?>" class="block px-4 py-4 border-b border-gray-50 hover:bg-blue-50 transition">
                                                <p class="text-sm text-gray-800 line-clamp-2"><?php echo htmlspecialchars($notif['message']); ?></p>
                                                <span class="text-[10px] text-gray-400 mt-1 block"><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="px-4 py-2 border-t border-gray-100 bg-gray-50 text-center">
                                    <a href="<?php echo $base_url; ?>/pages/notifications.php" class="text-xs text-gray-500 hover:text-primary font-medium">ดูการแจ้งเตือนทั้งหมด</a>
                                </div>
                            </div>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="relative" id="profile-wrapper">
                            <button id="profile-btn" class="flex items-center space-x-3 p-1.5 rounded-full hover:bg-secondary transition focus:outline-none border border-gray-700/50">
                                <div class="w-8 h-8 rounded-full bg-accent flex items-center justify-center text-white font-bold text-sm shadow-inner">
                                    <?php echo mb_substr($_SESSION['user_name'], 0, 1); ?>
                                </div>
                                <span class="text-gray-200 text-sm font-medium pr-1"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <div id="profile-dropdown" class="hidden absolute right-0 mt-3 w-52 bg-white rounded-lg shadow-xl border border-gray-200 z-[60] overflow-hidden">
                                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                                    <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">บัญชีผู้ใช้</p>
                                    <p class="text-sm font-bold text-primary truncate"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                                </div>
                                <div class="py-1">
                                    <a href="<?php echo $base_url; ?>/pages/dashboard.php" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-accent transition">
                                        <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                        รายการของฉัน
                                    </a>
                                    <a href="<?php echo $base_url; ?>/pages/profile.php" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-accent transition">
                                        <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        โปรไฟล์ส่วนตัว
                                    </a>
                                </div>
                                <div class="border-t border-gray-100 py-1">
                                    <a href="<?php echo $base_url; ?>/actions/auth_action.php?action=logout" onclick="return confirm('คุณต้องการออกจากระบบใช่หรือไม่?');" class="flex items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-6 0v-1m6-10V7a3 3 0 00-6 0v1"></path></svg>
                                        ออกจากระบบ
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo $base_url; ?>/pages/login.php" class="px-6 py-3 border border-white text-white rounded hover:bg-white hover:text-primary transition text-sm font-medium whitespace-nowrap">เข้าสู่ระบบ</a>
                        <a href="<?php echo $base_url; ?>/pages/register.php" class="px-6 py-3 bg-accent text-white rounded hover:bg-blue-600 transition text-sm font-medium shadow-md whitespace-nowrap">สมัครสมาชิก</a>
                    <?php endif; ?>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center ml-auto">
                    <button id="mobile-menu-btn" class="text-gray-300 hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white p-2 rounded-md">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Panel -->
        <div id="mobile-menu" class="hidden md:hidden bg-primary border-t border-gray-700 pb-4 shadow-inner">
            <div class="px-4 pt-4 pb-3 space-y-2">
                <?php
                foreach ($nav_items as $url => $label) {
                    $item_page = basename($url);
                    $is_active = ($current_page == $item_page);
                    if ($is_active) {
                        echo '<a href="'.$base_url.$url.'" class="block px-4 py-3 rounded-md text-base font-bold text-white bg-secondary border-l-4 border-accent transition">'.$label.'</a>';
                    } else {
                        echo '<a href="'.$base_url.$url.'" class="block px-4 py-3 rounded-md text-base font-medium text-gray-400 hover:text-white hover:bg-secondary transition">'.$label.'</a>';
                    }
                }
                ?>
                
                <div class="pt-4 pb-2 border-t border-gray-700 mt-4 space-y-3">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="px-4 mb-2 flex justify-between items-center">
                            <div class="text-sm text-gray-400">เข้าสู่ระบบในชื่อ: <span class="text-white font-medium"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span></div>
                            <?php if ($unread_count > 0): ?>
                                <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo $unread_count; ?> ใหม่</span>
                            <?php endif; ?>
                        </div>
                        <?php 
                        $dash_mobile_active = ($current_page == 'dashboard.php') ? 'text-white bg-secondary border-l-4 border-accent font-bold' : 'text-gray-400 hover:text-white hover:bg-secondary';
                        ?>
                        <a href="<?php echo $base_url; ?>/pages/dashboard.php" class="block px-4 py-3 rounded-md text-base transition <?php echo $dash_mobile_active; ?>">จัดการประกาศ</a>
                        <a href="<?php echo $base_url; ?>/pages/messages.php" class="block px-4 py-3 rounded-md text-base text-gray-400 hover:text-white hover:bg-secondary transition flex justify-between items-center">
                            <span>ข้อความ</span>
                            <?php if ($unread_msg_count > 0): ?>
                                <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo $unread_msg_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?php echo $base_url; ?>/actions/auth_action.php?action=logout" onclick="return confirm('คุณต้องการออกจากระบบใช่หรือไม่?');" class="block w-full text-center px-4 py-3 border border-red-400/50 text-red-400 rounded-md hover:bg-red-500 hover:text-white transition mt-4">ออกจากระบบ</a>
                    <?php else: ?>
                        <div class="grid grid-cols-2 gap-3 px-2">
                            <a href="<?php echo $base_url; ?>/pages/login.php" class="block w-full text-center px-4 py-3 border border-white text-white rounded hover:bg-secondary transition font-medium">เข้าสู่ระบบ</a>
                            <a href="<?php echo $base_url; ?>/pages/register.php" class="block w-full text-center px-4 py-3 bg-accent text-white rounded hover:bg-blue-600 transition font-medium shadow-sm">สมัครสมาชิก</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Script for mobile menu and notifications -->
    <script>
        // Mobile Menu Toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            var menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });

        // Dropdown Toggles (Desktop)
        function setupDropdown(btnId, dropdownId) {
            const btn = document.getElementById(btnId);
            const dropdown = document.getElementById(dropdownId);
            
            if (btn && dropdown) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    // Close other dropdowns first
                    document.querySelectorAll('[id$="-dropdown"]').forEach(d => {
                        if (d.id !== dropdownId) d.classList.add('hidden');
                    });
                    dropdown.classList.toggle('hidden');
                });
            }
        }

        setupDropdown('notification-btn', 'notification-dropdown');
        setupDropdown('profile-btn', 'profile-dropdown');

        // Close all dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            document.querySelectorAll('[id$="-dropdown"]').forEach(dropdown => {
                const btnId = dropdown.id.replace('-dropdown', '-btn');
                const btn = document.getElementById(btnId);
                if (!dropdown.classList.contains('hidden')) {
                    if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
                        dropdown.classList.add('hidden');
                    }
                }
            });
        });
    </script>
