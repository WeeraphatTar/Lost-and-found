<?php
// เรียกใช้งาน Header
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="bg-white py-16 text-center border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-primary mb-4">ระบบแจ้งของหายและติดตามทรัพย์สินคืน</h1>
        <p class="text-gray-500 text-lg md:text-xl max-w-2xl mx-auto mb-8">พื้นที่ส่วนกลางที่ปลอดภัยและเชื่อถือได้ สำหรับการค้นหาของที่หายไป และส่งคืนของที่พบเจอให้กับเจ้าของตัวจริง</p>
        
        <!-- Search Bar -->
        <form action="<?php echo $base_url; ?>/pages/browse.php" method="GET" class="max-w-3xl mx-auto flex flex-col md:flex-row gap-3 mb-8">
            <input type="text" name="q" class="flex-1 w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="ค้นหาทรัพย์สิน เช่น กระเป๋าสตางค์, โทรศัพท์...">
            <select name="category" class="w-full md:w-1/3 px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent bg-white">
                <option value="">ทุกหมวดหมู่</option>
                <option value="electronics">อุปกรณ์อิเล็กทรอนิกส์</option>
                <option value="documents">เอกสารสำคัญ</option>
                <option value="keys">กุญแจ</option>
                <option value="wallet">กระเป๋าสตางค์</option>
                <option value="accessories">กระเป๋าและเครื่องแต่งกาย</option>
                <option value="vehicles">ยานพาหนะ</option>
                <option value="others">อื่นๆ</option>
            </select>
            <button type="submit" class="w-full md:w-auto px-6 py-3 bg-primary text-white font-medium rounded-md hover:bg-secondary transition shadow-sm">ค้นหา</button>
        </form>

        <!-- Call to Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?php echo $base_url; ?>/pages/report_lost.php" class="px-6 py-3 border border-primary text-primary font-medium rounded-md hover:bg-primary hover:text-white transition bg-white">แจ้งของหาย</a>
            <a href="<?php echo $base_url; ?>/pages/report_found.php" class="px-6 py-3 bg-primary text-white font-medium rounded-md hover:bg-secondary transition shadow-sm">แจ้งพบของ</a>
        </div>
    </div>
</section>

<!-- Statistics / Recent Items (Placeholder) -->
<section class="py-16 bg-background">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-center text-primary mb-10">ประกาศล่าสุด</h2>
        
<?php
        require_once 'config/database.php';
        try {
            $stmt = $pdo->query("SELECT * FROM items WHERE status = 'open' ORDER BY created_at DESC LIMIT 6");
            $items = $stmt->fetchAll();

            if (count($items) > 0) {
                echo '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">';
                foreach ($items as $item) {
                    $type_badge = ($item['type'] === 'lost') 
                        ? '<span class="self-start inline-block px-2.5 py-1 rounded bg-red-100 text-red-700 text-xs font-semibold mb-2">ตามหาของหาย</span>'
                        : '<span class="self-start inline-block px-2.5 py-1 rounded bg-green-100 text-green-700 text-xs font-semibold mb-2">ประกาศพบของ</span>';
                    
                    $image_html = '';
                    if (!empty($item['image_path'])) {
                        $image_html = '<img src="' . $base_url . '/' . htmlspecialchars($item['image_path']) . '" alt="item image" class="w-full h-full object-contain">';
                    } else {
                        // SVG Placeholder
                        $image_html = '<svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
                    }

                    $date_formatted = date('d M Y', strtotime($item['event_date']));

                    echo '
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-1 flex flex-col">
                        <div class="w-full aspect-video bg-slate-100 flex items-center justify-center border-b border-gray-200 text-gray-400 overflow-hidden relative">
                            ' . $image_html . '
                        </div>
                        <div class="p-6 flex-1 flex flex-col">
                            ' . $type_badge . '
                            <h3 class="text-lg font-bold text-primary mt-1 truncate">' . htmlspecialchars($item['title']) . '</h3>
                            <p class="text-gray-500 text-sm mt-2 truncate"><strong>สถานที่:</strong> ' . htmlspecialchars($item['location']) . '</p>
                            <p class="text-gray-500 text-sm mt-1 mb-4 flex-1"><strong>วันที่:</strong> ' . $date_formatted . '</p>
                            <a href="' . $base_url . '/pages/item_detail.php?id=' . $item['id'] . '" class="block w-full text-center px-4 py-2 border border-primary text-primary text-sm font-medium rounded hover:bg-primary hover:text-white transition">ดูรายละเอียด</a>
                        </div>
                    </div>';
                }
                echo '</div>';

                // ปุ่มดูประกาศทั้งหมด
                echo '
                <div class="mt-12 text-center">
                    <a href="' . $base_url . '/pages/browse.php" class="inline-flex items-center justify-center px-8 py-3 bg-white border border-primary text-primary font-bold rounded-md hover:bg-primary hover:text-white transition shadow-sm group">
                        ดูประกาศทั้งหมด
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                </div>';
            } else {
                echo '<div class="text-center py-12 text-gray-500 bg-white rounded-lg border border-gray-200">ยังไม่มีประกาศในขณะนี้</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="text-center text-red-500">เกิดข้อผิดพลาดในการโหลดข้อมูล: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</section>

<?php
// เรียกใช้งาน Footer
require_once 'includes/footer.php';
?>
