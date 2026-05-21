<?php
/**
 * Smart Matching Helper Functions
 * Signal-based matching logic for Lost and Found items.
 */

if (!function_exists('normalize_text')) {
    /**
     * Normalize text for comparison: lowercasing and cleaning
     */
    function normalize_text($text) {
        if (empty($text)) return "";
        
        // Convert to lowercase (handling UTF-8/Thai)
        $text = mb_strtolower(trim($text), 'UTF-8');
        
        // Remove common Thai stop words/prefixes/suffixes that might hinder matching
        $stop_words = ["ของ", "หาย", "เจอ", "พบ", "สี", "มี", "เป็น", "อยู่", "ตึก", "ห้อง", "ชั้น", "บริเวณ", "ตรง"];
        foreach ($stop_words as $word) {
            $text = str_replace($word, "", $text);
        }
        
        // Clean special characters but keep spaces
        $text = preg_replace('/[^\w\s\x{0E00}-\x{0E7F}]/u', '', $text);
        
        return trim($text);
    }
}

if (!function_exists('get_synonym_map')) {
    /**
     * Centralized Synonym Map for both translation and concept extraction
     */
    function get_synonym_map() {
        return [
            // --- Product Types ---
            'phone' => ['มือถือ', 'โทรศัพท์', 'phone', 'mobile', 'smartphone', 'telephone'],
            'laptop' => ['โน้ตบุ๊ก', 'โน้ตบุ๊ค', 'notebook', 'laptop', 'คอมพิวเตอร์พกพา', 'personal computer'],
            'wallet' => ['กระเป๋าเงิน', 'กระเป๋าสตางค์', 'กระเป๋าตังค์', 'wallet', 'coin purse'],
            'bag' => ['กระเป๋า', 'bag', 'backpack', 'handbag', 'purse', 'tote bag', 'luggage'],
            'watch' => ['นาฬิกา', 'watch', 'wristwatch'],
            'key' => ['กุญแจ', 'key', 'keys'],
            'card' => ['บัตร', 'card'],
            'headphones' => ['หูฟัง', 'headphones', 'earpods', 'airpods', 'headset', 'audio equipment'],
            'keychain' => ['พวงกุญแจ', 'keychain', 'keyring'],
            'doll' => ['ตุ๊กตา', 'doll', 'toy', 'โมเดล'],
            'glasses' => ['แว่น', 'แว่นตา', 'แว่นกันแดด', 'glasses', 'eyewear', 'sunglasses', 'spectacles'],
            'bottle' => ['ขวด', 'ขวดน้ำ', 'bottle', 'water bottle', 'drinkware'],
            'book' => ['หนังสือ', 'สมุด', 'book', 'notebook', 'novel', 'textbook'],
            
            // --- Brands & Models ---
            'apple' => ['ไอโฟน', 'iphone', 'apple', 'ipad', 'macbook'],
            'samsung' => ['ซัมซุง', 'samsung', 'galaxy'],
            'dell' => ['เดลล์', 'dell'],
            'popmart' => ['pop mart', 'popmart', 'ป๊อปมาร์ท', 'pop-mart'],
            'labubu' => ['labubu', 'ลาบูบู้', 'labub'],
            
            // --- Attributes & Colors ---
            'black' => ['ดำ', 'สีดำ', 'black'],
            'white' => ['ขาว', 'สีขาว', 'white'],
            'blue' => ['น้ำเงิน', 'ฟ้า', 'สีน้ำเงิน', 'สีฟ้า', 'blue'],
            'brown' => ['น้ำตาล', 'สีน้ำตาล', 'brown'],
            'thai_dress' => ['ชุดไทย', 'ชุดสไตล์ไทย', 'thai dress'],
            'rabbit' => ['กระต่าย', 'rabbit'],
            'gold' => ['ทอง', 'สีทอง', 'gold'],
            
            // --- Locations ---
            'canteen' => ['โรงอาหาร', 'canteen', 'โรงอาหารใหญ่'],
            'parking' => ['โรงรถ', 'โรงจอดรถ', 'ที่จอดรถ', 'ลานจอดรถ'],
            'money' => ['เงิน', 'เงินสด', 'cash', 'money'],
            'student_card' => ['บัตรนิสิต', 'บัตรนักศึกษา', 'student card'],
            'sticker' => ['สติกเกอร์', 'สติ๊กเกอร์', 'sticker'],
            'cat' => ['แมว', 'cat'],
            'dog' => ['หมา', 'สุนัข', 'dog'],
            'grey' => ['เทา', 'สีเทา', 'grey', 'gray'],
        ];
    }
}

if (!function_exists('apply_synonyms')) {
    /**
     * Map variations of words to a standard form
     */
    function apply_synonyms($text) {
        $synonym_map = get_synonym_map();
        $normalized = $text;
        foreach ($synonym_map as $standard => $variations) {
            // Sort variations by length descending to match longer strings first
            usort($variations, function($a, $b) {
                return mb_strlen($b) <=> mb_strlen($a);
            });
            
            foreach ($variations as $var) {
                $normalized = str_replace($var, $standard, $normalized);
            }
        }
        
        return $normalized;
    }
}

if (!function_exists('extract_keywords')) {
    /**
     * Extract keywords from text, supporting both spaceless concept matching and space-separated tokens.
     */
    function extract_keywords($text) {
        if (empty($text)) return [];
        
        $normalized = normalize_text($text);
        
        $keywords = [];
        
        // 1. Concept/Concept-based Substring Matching (Essential for Thai text without spaces)
        $synonym_map = get_synonym_map();
        foreach ($synonym_map as $standard => $variations) {
            foreach ($variations as $var) {
                $norm_var = normalize_text($var);
                if (!empty($norm_var) && mb_strpos($normalized, $norm_var) !== false) {
                    $keywords[] = $standard;
                    break; // Found the standard concept, move to next standard group
                }
            }
        }
        
        // 2. Fallback space-separated token matching (great for English/Thai combination)
        $split_text = preg_replace('/([0-9]+)/', ' $1 ', $normalized);
        $standardized = apply_synonyms($split_text);
        
        // Split by whitespace
        $tokens = preg_split('/\s+/', $standardized, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($tokens as $token) {
            $keywords[] = $token;
        }
        
        return array_unique($keywords);
    }
}

if (!function_exists('calculate_keyword_score')) {
    /**
     * Calculate a symmetric keyword match score between two texts
     */
    function calculate_keyword_score($text1, $text2, $max_score = 30) {
        if (empty($text1) || empty($text2)) return 0;
        
        // Perfect Match when spaces are removed (100% confidence for this field)
        $no_space1 = str_replace(' ', '', normalize_text($text1));
        $no_space2 = str_replace(' ', '', normalize_text($text2));
        if (empty($no_space1) || empty($no_space2)) {
            return 0;
        }
        if ($no_space1 === $no_space2) {
            return $max_score;
        }
        
        $kw1 = extract_keywords($text1);
        $kw2 = extract_keywords($text2);
        
        if (empty($kw1) || empty($kw2)) return 0;

        // Internal function to count matches in one direction
        $count_matches = function($list1, $list2) {
            $count = 0;
            foreach ($list1 as $w1) {
                foreach ($list2 as $w2) {
                    if ($w1 === $w2 || mb_stripos($w1, $w2) !== false || mb_stripos($w2, $w1) !== false) {
                        $count++;
                        break; 
                    }
                }
            }
            return $count;
        };

        // Take the maximum match count from both directions to ensure symmetry
        $match_count = max($count_matches($kw1, $kw2), $count_matches($kw2, $kw1));

        if ($match_count > 0) {
            // Faster progression: 1 match = 20, 2 matches = 25, 3+ matches = max
            // This ensures we get high scores faster for high-quality semantic signals
            return min($max_score, 20 + ($match_count - 1) * 10);
        }
        
        return 0;
    }
}

if (!function_exists('calculate_image_label_score')) {
    /**
     * Calculate similarity score using Google Cloud Vision image labels
     */
    function calculate_image_label_score($item1, $item2, $max_score = 30) {
        $labels1 = !empty($item1['image_labels']) ? json_decode($item1['image_labels'], true) : [];
        $labels2 = !empty($item2['image_labels']) ? json_decode($item2['image_labels'], true) : [];
        
        if (!is_array($labels1)) $labels1 = [];
        if (!is_array($labels2)) $labels2 = [];

        // ลบคำป้ายกำกับทั่วไปที่กว้างเกินไป (Generic Label Blacklist)
        $blacklist = [
            'peripheral', 'gadget', 'electronic device', 'technology', 'plastic', 
            'font', 'logo', 'brand', 'material', 'design', 'product', 'metal', 
            'computer hardware', 'input device', 'output device', 'multimedia',
            'rectangle', 'circle', 'line', 'pattern', 'text', 'screenshot', 
            'software', 'accessory', 'personal protective equipment', 'office supplies'
        ];
        
        $labels1 = array_diff($labels1, $blacklist);
        $labels2 = array_diff($labels2, $blacklist);

        if (empty($labels1) && empty($labels2)) {
            return 0;
        }

        $score = 0;

        // 1. Image-to-Image Match (both have labels)
        if (!empty($labels1) && !empty($labels2)) {
            $common_labels = array_intersect($labels1, $labels2);
            $match_count = count($common_labels);
            if ($match_count > 0) {
                // คำนวณคะแนนตามสัดส่วนการซ้อนทับจริง (Overlap Ratio): (จำนวนคำที่ตรงกัน / จำนวนคำทั้งหมดของภาพที่มีคำน้อยกว่า) * 30
                $min_total_count = min(count($labels1), count($labels2));
                if ($min_total_count > 0) {
                    $score = min($max_score, round(($match_count / $min_total_count) * $max_score));
                }
            }
        }

        // 2. Image-to-Text Match (cross matching labels to text)
        $get_label_concepts = function($labels) {
            $concepts = [];
            foreach ($labels as $label) {
                $concepts = array_merge($concepts, extract_keywords($label));
            }
            return array_unique($concepts);
        };

        $concepts1 = $get_label_concepts($labels1);
        $concepts2 = $get_label_concepts($labels2);

        $get_text_concepts = function($item) {
            return array_unique(array_merge(
                extract_keywords($item['title'] ?? ''),
                extract_keywords(($item['description'] ?? '') . ' ' . ($item['secret_description'] ?? '')),
                extract_keywords($item['category'] ?? '')
            ));
        };

        $text_concepts1 = $get_text_concepts($item1);
        $text_concepts2 = $get_text_concepts($item2);

        // Match Item 1 image labels to Item 2 text
        if (!empty($concepts1) && !empty($text_concepts2)) {
            $matches1 = array_intersect($concepts1, $text_concepts2);
            if (!empty($matches1)) {
                $score = max($score, min($max_score, 15 + count($matches1) * 5));
            }
        }

        // Match Item 2 image labels to Item 1 text
        if (!empty($concepts2) && !empty($text_concepts1)) {
            $matches2 = array_intersect($concepts2, $text_concepts1);
            if (!empty($matches2)) {
                $score = max($score, min($max_score, 15 + count($matches2) * 5));
            }
        }

        return $score;
    }
}

if (!function_exists('calculate_match_score')) {
    /**
     * Calculate match score between two items (Symmetric)
     */
    function calculate_match_score($item1, $item2) {
        $score = 0;
        
        // 1. Serial Number (Highest Signal) - 100 points
        $clean_sn = function($sn) {
            $sn = mb_strtolower(trim($sn));
            $sn = str_replace(['sn:', 'sn', 's/n:', 's/n'], '', $sn);
            return preg_replace('/[^a-z0-9]/', '', $sn);
        };

        $get_sn = function($item) use ($clean_sn) {
            if (!empty($item['serial_number'])) return $clean_sn($item['serial_number']);
            $text = mb_strtolower(($item['description'] ?? '') . ' ' . ($item['secret_description'] ?? ''));
            if (preg_match('/(?:s\/?n|serial|no|id):?\s*([a-z0-9\-\/\.]+)/i', $text, $matches)) {
                return $clean_sn($matches[1]);
            }
            return null;
        };

        $s1 = $get_sn($item1);
        $s2 = $get_sn($item2);
        if ($s1 && $s2 && $s1 === $s2) return 100;

        // 2. Title Keywords (Max 20)
        $score += calculate_keyword_score($item1['title'], $item2['title'], 20);
        
        // 3. Description Keywords (Max 20) - Include Secret Description!
        $desc1 = ($item1['description'] ?? '') . ' ' . ($item1['secret_description'] ?? '');
        $desc2 = ($item2['description'] ?? '') . ' ' . ($item2['secret_description'] ?? '');
        $score += calculate_keyword_score($desc1, $desc2, 20);
        
        // 4. Location Similarity (Max 20)
        $score += calculate_keyword_score($item1['location'], $item2['location'], 20);
        
        // 5. Category Match (Reduced Weight) - 10 points
        if (!empty($item1['category']) && !empty($item2['category']) && $item1['category'] === $item2['category']) {
            $score += 10;
        }

        // 6. Image Label Match (Max 30)
        $score += calculate_image_label_score($item1, $item2, 30);
        
        return $score;
    }
}

if (!function_exists('get_confidence_level')) {
    /**
     * Categorize score into confidence levels
     */
    function get_confidence_level($score) {
        if ($score >= 80) return "High";
        if ($score >= 50) return "Medium";
        if ($score >= 40) return "Low";
        return "None";
    }
}

if (!function_exists('get_confidence_label')) {
    /**
     * Get Thai display label for confidence level
     */
    function get_confidence_label($level, $score) {
        switch ($level) {
            case 'High':
                return "พบรายการที่มีความใกล้เคียงสูง (" . min(100, $score) . "%)";
            case 'Medium':
                return "พบรายการที่อาจเกี่ยวข้องกับของคุณ (" . $score . "%)";
            case 'Low':
                return "มีรายการที่อาจใกล้เคียง ลองตรวจสอบเพิ่มเติม";
            default:
                return "พบรายการที่อาจเกี่ยวข้อง";
        }
    }
}

if (!function_exists('get_clean_sn_for_sql')) {
    /**
     * Generate a alphanumeric-only string for better SQL LIKE matching
     */
    function get_clean_sn_for_sql($sn) {
        if (empty($sn)) return null;
        $sn = mb_strtolower(trim($sn));
        $sn = str_replace(['sn:', 'sn', 's/n:', 's/n'], '', $sn);
        $clean = preg_replace('/[^a-z0-9]/', '', $sn);
        return !empty($clean) ? $clean : null;
    }
}
