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
    $brand = trim($_POST['brand'] ?? null);
    $model = trim($_POST['model'] ?? null);
    $color = trim($_POST['color'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $secret_description = trim($_POST['secret_description'] ?? null);
    $serial_number = trim($_POST['serial_number'] ?? null);
    $province = trim($_POST['province'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $location_detail = trim($_POST['location_detail'] ?? '');
    $storage_location = trim($_POST['storage_location'] ?? null);
    $event_date = ($type === 'lost') ? ($_POST['lost_date'] ?? '') : ($_POST['found_date'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');

    // Secret description / brand / model fallback to null if empty
    if ($brand === '') $brand = null;
    if ($model === '') $model = null;
    if ($secret_description === '') $secret_description = null;
    if ($serial_number === '') $serial_number = null;
    if ($storage_location === '') $storage_location = null;

    if (empty($title) || empty($category) || empty($color) || empty($description) || empty($province) || empty($district) || empty($location_detail) || empty($event_date) || empty($contact_phone)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (*)";
        header("Location: ../pages/report_{$type}.php");
        exit;
    }

    if ($type === 'found' && empty($storage_location)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลสถานที่เก็บรักษาของปัจจุบัน (*)";
        header("Location: ../pages/report_found.php");
        exit;
    }

    // Construct location for backward compatibility
    $location = trim($location_detail . ' อ.' . $district . ' จ.' . $province);

    // Backend length validations (Test Case 12)
    if (mb_strlen($title, 'UTF-8') > 100) {
        $_SESSION['error'] = "ชื่อเรียกทรัพย์สินต้องไม่เกิน 100 ตัวอักษร";
        header("Location: ../pages/report_{$type}.php");
        exit;
    }
    if ($brand !== null && mb_strlen($brand, 'UTF-8') > 100) {
        $_SESSION['error'] = "แบรนด์/ยี่ห้อต้องไม่เกิน 100 ตัวอักษร";
        header("Location: ../pages/report_{$type}.php");
        exit;
    }
    if ($model !== null && mb_strlen($model, 'UTF-8') > 100) {
        $_SESSION['error'] = "รุ่นต้องไม่เกิน 100 ตัวอักษร";
        header("Location: ../pages/report_{$type}.php");
        exit;
    }
    if (mb_strlen($color, 'UTF-8') > 100) {
        $_SESSION['error'] = "สีของสิ่งของต้องไม่เกิน 100 ตัวอักษร";
        header("Location: ../pages/report_{$type}.php");
        exit;
    }
    if (mb_strlen($description, 'UTF-8') > 500) {
        $_SESSION['error'] = "ลักษณะเฉพาะ/ที่พบเห็นต้องไม่เกิน 500 ตัวอักษร";
        header("Location: ../pages/report_{$type}.php");
        exit;
    }
    if ($secret_description !== null && mb_strlen($secret_description, 'UTF-8') > 500) {
        $_SESSION['error'] = "รายละเอียดลับต้องไม่เกิน 500 ตัวอักษร";
        header("Location: ../pages/report_{$type}.php");
        exit;
    }
    if ($serial_number !== null && mb_strlen($serial_number, 'UTF-8') > 50) {
        $_SESSION['error'] = "เลขซีเรียล/ข้อมูลระบุตัวตนต้องไม่เกิน 50 ตัวอักษร";
        header("Location: ../pages/report_{$type}.php");
        exit;
    }
    if (mb_strlen($contact_phone, 'UTF-8') > 20) {
        $_SESSION['error'] = "เบอร์โทรศัพท์ติดต่อต้องไม่เกิน 20 ตัวอักษร";
        header("Location: ../pages/report_{$type}.php");
        exit;
    }

    // Handle Image Upload
    $image_path = null;
    $image_labels = null;
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
                
                // Detect labels using Google Cloud Vision API
                if (file_exists('../helpers/vision_helper.php')) {
                    require_once '../helpers/vision_helper.php';
                    $labels = detect_labels($destination);
                    if (!empty($labels) && file_exists('../helpers/translation_helper.php')) {
                        require_once '../helpers/translation_helper.php';
                        $translated_labels = translate_labels($pdo, $labels);
                        $image_labels = json_encode($translated_labels, JSON_UNESCAPED_UNICODE);
                    }
                }
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
        $sql = "INSERT INTO items (user_id, type, title, category, brand, model, color, description, secret_description, serial_number, location, province, district, location_detail, storage_location, event_date, contact_phone, image_path, image_labels, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id, $type, $title, $category, $brand, $model, $color, $description, $secret_description, 
            $serial_number, $location, $province, $district, $location_detail, $storage_location, $event_date, $contact_phone, $image_path, $image_labels
        ]);

        $item_id = $pdo->lastInsertId();

        // --- Improved Matching Logic & Notifications ---
        require_once '../helpers/notification_helper.php';
        require_once '../helpers/matching_helper.php';
        
        $target_type = ($type === 'lost') ? 'found' : 'lost';
        
        // Prepare data for matching
        $new_item = [
            'title' => $title,
            'category' => $category,
            'location' => $location,
            'serial_number' => $serial_number,
            'description' => $description,
            'secret_description' => $secret_description,
            'image_labels' => $image_labels,
            'province' => $province,
            'district' => $district,
            'location_detail' => $location_detail,
            'color' => $color,
            'brand' => $brand,
            'model' => $model
        ];

        // 1. Pre-extract SN from current item (even if not in serial_number field)
        $raw_sn = $serial_number;
        if (empty($raw_sn)) {
            $text_for_sn = $title . ' ' . $description . ' ' . $secret_description;
            if (preg_match('/(?:s\/?n|serial|no|id|imei):?\s*([a-z0-9\-\/\.]+)/i', $text_for_sn, $matches)) {
                $raw_sn = $matches[1];
            }
        }
        $searchable_sn = get_clean_sn_for_sql($raw_sn);

        // 2. Extract multiple tags for broader SQL search
        $normalized_title = normalize_text($title);
        $standardized_title = apply_synonyms($normalized_title);
        $title_kws = preg_split('/\s+/', $standardized_title, -1, PREG_SPLIT_NO_EMPTY);
        
        $search_tag1 = !empty($title_kws[0]) ? "%" . mb_substr($title_kws[0], 0, 4) . "%" : "%NON_EXISTENT%";
        $search_tag2 = !empty($title_kws[1]) ? "%" . mb_substr($title_kws[1], 0, 4) . "%" : $search_tag1;
        // Also keep original first word as a fallback
        $orig_kws = preg_split('/\s+/', $normalized_title, -1, PREG_SPLIT_NO_EMPTY);
        $search_tag3 = !empty($orig_kws[0]) ? "%" . mb_substr($orig_kws[0], 0, 4) . "%" : $search_tag1;

        // Broad fetch for potential matches - Expanded to search multiple tags across all text fields (including image_labels)
        $match_sql = "SELECT id, user_id, title, category, location, serial_number, description, secret_description, image_labels, province, district, location_detail, color, brand, model 
                      FROM items 
                      WHERE type = ? AND status = 'open' AND user_id != ? 
                      AND (category = ? 
                           OR (serial_number IS NOT NULL AND (serial_number LIKE ? OR serial_number = ?)) 
                           OR title LIKE ? OR title LIKE ? OR title LIKE ?
                           OR description LIKE ? OR description LIKE ?
                           OR secret_description LIKE ? OR secret_description LIKE ?
                           OR location LIKE ?)
                      ORDER BY created_at DESC LIMIT 50";
        
        $search_loc = "%" . mb_substr(normalize_text($location), 0, 4) . "%";
        $search_sn_like = "%" . ($searchable_sn ?? 'NON_EXISTENT_SN') . "%";
        
        $match_stmt = $pdo->prepare($match_sql);
        $match_stmt->execute([
            $target_type, $user_id, $category, 
            $search_sn_like, $serial_number, 
            $search_tag1, $search_tag2, $search_tag3,
            $search_tag1, $search_tag2, 
            $search_tag1, $search_sn_like,
            $search_loc
        ]);
        $potential_matches = $match_stmt->fetchAll();

        $valid_matches = [];
        foreach ($potential_matches as $m) {
            $score = calculate_match_score($new_item, $m);
            
            if ($score >= 40) {
                $m['match_score'] = $score;
                $m['confidence'] = get_confidence_level($score);
                $valid_matches[] = $m;
            }
        }

        // Sort by score descending, then by brand/model match indicator descending
        usort($valid_matches, function($a, $b) use ($new_item) {
            if ($b['match_score'] !== $a['match_score']) {
                return $b['match_score'] <=> $a['match_score'];
            }
            $brand_model_b = calculate_brand_model_match($new_item, $b);
            $brand_model_a = calculate_brand_model_match($new_item, $a);
            return $brand_model_b <=> $brand_model_a;
        });

        // Limit to top 5 matches
        $final_matches = array_slice($valid_matches, 0, 5);

        if (count($final_matches) > 0) {
            // 1. Notify current user about the best match found
            $top_match = $final_matches[0];
            $msg_to_current = get_confidence_label($top_match['confidence'], $top_match['match_score']);
            $msg_to_current .= " (รายการ: " . mb_substr($top_match['title'], 0, 20) . "...)";
            
            add_notification($pdo, $user_id, $msg_to_current, "pages/item_detail.php?id=" . $top_match['id']);

            // 2. Notify owners of matching items
            foreach ($final_matches as $match) {
                $msg = get_confidence_label($match['confidence'], $match['match_score']);
                $msg .= " (รายการ: " . mb_substr($title, 0, 20) . "...)";
                
                add_notification($pdo, $match['user_id'], $msg, "pages/item_detail.php?id=$item_id");
            }
        }
        // ---------------------------------------

        // Dynamic redirect URL after successful creation
        $redirect_target = $_POST['redirect_url'] ?? '';
        if (empty($redirect_target)) {
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                $redirect_target = ($type === 'lost') ? '../pages/admin/admin_itemslost.php' : '../pages/admin/admin_itemsfound.php';
            } else {
                $redirect_target = '../pages/browse.php';
            }
        }

        $_SESSION['success'] = "บันทึกประกาศเรียบร้อยแล้ว!";
        header("Location: " . $redirect_target);
        exit;

    } catch (PDOException $e) {
        $error_redirect = $_POST['redirect_url'] ?? "../pages/report_{$type}.php";
        $_SESSION['error'] = "เกิดข้อผิดพลาดของฐานข้อมูล: " . $e->getMessage();
        header("Location: " . $error_redirect);
        exit;
    }
} else {
    header("Location: ../index.php");
    exit;
}
?>
