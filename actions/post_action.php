<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนทำการโพสต์ประกาศ";
    header("Location: ../pages/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $type = $_POST['type'] ?? '';
    
    // Check if type is valid
    if (!in_array($type, ['lost', 'found'])) {
        $_SESSION['error'] = "ประเภทประกาศไม่ถูกต้อง";
        header("Location: ../pages/browse.php");
        exit;
    }

    $title = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $secret_description = trim($_POST['secret_description'] ?? null);
    $serial_number = trim($_POST['serial_number'] ?? null);
    $location = trim($_POST['location'] ?? '');
    $storage_location = trim($_POST['storage_location'] ?? null);
    $event_date = ($type === 'lost') ? ($_POST['lost_date'] ?? '') : ($_POST['found_date'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');

    // Secret description fallback to null if empty
    if ($secret_description === '') $secret_description = null;
    if ($serial_number === '') $serial_number = null;
    if ($storage_location === '') $storage_location = null;

    if (empty($title) || empty($category) || empty($description) || empty($location) || empty($event_date) || empty($contact_phone)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (*)";
        header("Location: ../pages/report_{$type}.php");
        exit;
    }

    // Handle Image Upload
    $image_path = null;
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $file_type = $_FILES['item_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/items/';
            
            // Create dir if not exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('item_') . '_' . time() . '.' . $file_extension;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['item_image']['tmp_name'], $destination)) {
                $image_path = 'uploads/items/' . $new_filename; // relative to base url
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
                header("Location: ../pages/report_{$type}.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "อัปโหลดได้เฉพาะไฟล์รูปภาพ (JPG, PNG, WEBP) เท่านั้น";
            header("Location: ../pages/report_{$type}.php");
            exit;
        }
    }

    try {
        $sql = "INSERT INTO items (user_id, type, title, category, description, secret_description, serial_number, location, storage_location, event_date, contact_phone, image_path, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id, $type, $title, $category, $description, $secret_description, 
            $serial_number, $location, $storage_location, $event_date, $contact_phone, $image_path
        ]);

        $item_id = $pdo->lastInsertId();

        // --- Improved Matching Logic & Notifications ---
        require_once '../includes/notification_helper.php';
        
        $target_type = ($type === 'lost') ? 'found' : 'lost';
        
        // Broad fetch for potential matches (same category OR serial number OR title/location keyword)
        $match_sql = "SELECT id, user_id, title, category, location, serial_number 
                      FROM items 
                      WHERE type = ? AND status = 'open' AND user_id != ? 
                      AND (category = ? OR (serial_number IS NOT NULL AND serial_number = ?) OR title LIKE ? OR location LIKE ?)
                      ORDER BY created_at DESC LIMIT 20";
        
        // Use part of title and location for broad search
        $search_title = "%" . mb_substr($title, 0, 5) . "%"; 
        $search_loc = "%" . mb_substr($location, 0, 5) . "%";
        
        $match_stmt = $pdo->prepare($match_sql);
        $match_stmt->execute([$target_type, $user_id, $category, $serial_number, $search_title, $search_loc]);
        $potential_matches = $match_stmt->fetchAll();

        $valid_matches = [];
        foreach ($potential_matches as $m) {
            $score = 0;
            
            // 1. Serial number match (Highest priority)
            if (!empty($serial_number) && !empty($m['serial_number']) && strtolower($serial_number) === strtolower($m['serial_number'])) {
                $score += 100;
            }
            
            // 2. Category match
            if ($category === $m['category']) {
                $score += 40;
            }
            
            // 3. Title similarity (Simple keyword check)
            if (mb_stripos($m['title'], $title) !== false || mb_stripos($title, $m['title']) !== false) {
                $score += 30;
            }
            
            // 4. Location similarity
            if (mb_stripos($m['location'], $location) !== false || mb_stripos($location, $m['location']) !== false) {
                $score += 30;
            }
            
            // If score is high enough (e.g., matching category + title OR just serial number), add to valid matches
            if ($score >= 40) {
                $m['match_score'] = $score;
                $valid_matches[] = $m;
            }
        }

        // Sort by score descending
        usort($valid_matches, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });

        // Limit to top 5 matches
        $final_matches = array_slice($valid_matches, 0, 5);

        if (count($final_matches) > 0) {
            // 1. Notify current user
            $match_count = count($final_matches);
            $top_score = $final_matches[0]['match_score'];
            $msg_to_current = ($top_score >= 100) 
                ? "พบรายการที่ตรงกับ Serial Number ของคุณเป๊ะ! ($match_count รายการใหม่)"
                : "เราพบ $match_count รายการที่อาจตรงกับที่คุณเพิ่งโพสต์!";
            
            add_notification($pdo, $user_id, $msg_to_current, "pages/item_detail.php?id=$item_id");

            // 2. Notify owners of matching items
            foreach ($final_matches as $match) {
                $msg = ($type === 'lost') 
                    ? "มีผู้แจ้งของหายที่มีโอกาสตรงกับรายการที่คุณพบสูง: " . $title 
                    : "มีผู้แจ้งพบของที่อาจเป็นของคุณ (ความแม่นยำสูง): " . $title;
                
                add_notification($pdo, $match['user_id'], $msg, "pages/item_detail.php?id=$item_id");
            }
        }
        // ---------------------------------------

        $_SESSION['success'] = "บันทึกประกาศของคุณเรียบร้อยแล้ว!";
        header("Location: ../pages/browse.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดของฐานข้อมูล: " . $e->getMessage();
        header("Location: ../pages/report_{$type}.php");
        exit;
    }
} else {
    header("Location: ../index.php");
    exit;
}
?>
