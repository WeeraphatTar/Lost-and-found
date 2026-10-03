<?php
/**
 * Category Helper Functions
 * Fetch active categories dynamically from database or fallback
 */

if (!function_exists('get_active_categories')) {
    function get_active_categories($pdo) {
        try {
            $stmt = $pdo->query("SELECT code_name, name_th, icon FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name_th ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $categories = [];
                foreach ($rows as $r) {
                    $categories[$r['code_name']] = $r['name_th'];
                }
                return $categories;
            }
        } catch (PDOException $e) {
            // Fallback if table doesn't exist yet
        }

        return [
            'electronics' => 'อุปกรณ์อิเล็กทรอนิกส์',
            'walletandcash' => 'กระเป๋าสตางค์และเงินสด',
            'cardanddocument' => 'บัตรและเอกสารสำคัญ',
            'keyandkeycard' => 'กุญแจและคีย์การ์ด',
            'bagandluggage' => 'กระเป๋าและสัมภาระ',
            'clothingandjewelry' => 'เครื่องแต่งกายและเครื่องประดับ',
            'studymaterialandstationery' => 'อุปกรณ์การเรียนและเครื่องเขียน',
            'personalbelonging' => 'ของใช้ส่วนตัว',
            'vehicleandaccessory' => 'ยานพาหนะและอุปกรณ์เสริม',
            'others' => 'อื่นๆ'
        ];
    }
}
