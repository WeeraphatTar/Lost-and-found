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
        $stop_words = ["ของ", "หาย", "เจอ", "พบ", "สี", "มี", "เป็น", "อยู่", "ตึก", "ห้อง", "ชั้น", "บริเวณ", "ตรง", 
            "ตก", "หล่น", "ทำหาย", "เก็บได้", "เก็บ", "ได้", "ประกาศ", "ตามหา", "แจ้ง", "รับ", "ติดต่อ",
            "ที่", "ใน", "บน", "ใต้", "ข้าง", "หน้า", "หลัง", "ระหว่าง", "ใกล้", "แถว", "ๆ", "และ", "หรือ", "กับ", "ก็", 
            "นี้", "นั้น", "คะ", "ค่ะ", "ครับ", "นะ", "นะคร้าบ", "นะค่ะ", "ค่ะ", "ครับผม", "ใคร", "ผู้ใด", "เจ้าของ",
            "ตัว", "อัน", "เครื่อง", "ใบ", "เล่ม", "ชิ้น", "กล่อง", "สาย", "ชุด", "คู่", "แผ่น", "ด้าม",
            "คณะ", "แผนก", "สาขา", "มหาวิทยาลัย", "มหาลัย", "ยี่ห้อ", "รุ่น", "แบบ", "สาย", "เครื่อง", "ตัว", "แถว", "ฝั่ง"];
        foreach ($stop_words as $word) {
            $text = str_replace($word, " ", $text);
        }
        
        // Clean special characters but keep spaces
        $text = preg_replace('/[^\w\s\x{0E00}-\x{0E7F}]/u', '', $text);
        
        // Normalize multiple spaces into one space
        $text = preg_replace('/\s+/', ' ', $text);
        
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
            'lamp' => ['โคมไฟ', 'โคมไฟตั้งโต๊ะ', 'lamp', 'light', 'ไฟตั้งโต๊ะ'],
            'phone' => ['มือถือ', 'โทรศัพท์', 'phone', 'mobile', 'smartphone', 'telephone', 'สมาร์ทโฟน','mobile phone','โทรศัพท์มือถือ'],
            'laptop' => ['โน้ตบุ๊ก', 'โน้ตบุ๊ค', 'notebook', 'laptop', 'คอมพิวเตอร์พกพา', 'personal computer', 'คอมพิวเตอร์โน้ตบุ๊ก', 'computer', 'คอมพิวเตอร์'],
            'wallet' => ['กระเป๋าเงิน', 'กระเป๋าสตางค์', 'กระเป๋าตังค์', 'wallet', 'coin purse','กระเป๋าตัง'],
            'bag' => ['กระเป๋า', 'bag', 'backpack', 'handbag', ' Purse', 'tote bag', 'luggage','กระเป๋าเป้','กระเป๋าสะพายหลัง','กระเป๋าสะพาย'],
            'key' => ['กุญแจ', 'key', 'keys','กุญแจบ้าน','กุญแจรถ','แม่กุญแจ','กุญแจหอ','กุญแจคอนโด','กุญแจหอพัก','กุญแจห้องพัก','กุญแจห้องเช่า','กุญแจห้องเก็บของ','กุญแจดิจิตอล','ลูกกุญแจ','ลูกกุญแจห้อง','ลูกกุญแจหอ','ลูกกุญแจคอนโด','ลูกกุญแจหอพัก','ลูกกุญแจห้องพัก','ลูกกุญแจห้องเช่า','ลูกกุญแจห้องเก็บของ','ลูกกุญแจดิจิตอล','พวงกุญแจ','ลูกกุญแจ + พวงกุญแจ'],
            'card' => ['บัตร', 'card', 'บัตรประชาชน', 'บัตรปชช', 'id card', 'national id', 'บัตรนิสิต', 'บัตรนักศึกษา', 'student card', 'บัตรเอทีเอ็ม', 'บัตรเครดิต', 'บัตรเดบิต', 'atm card', 'credit card', 'debit card', 'บัตรดิจิตอล', 'บัตรธนาคาร', 'บัตรเงินสด', 'บัตรส่วนลด', 'บัตรสมาชิก', 'บัตรของขวัญ', 'บัตรคอนโด', 'บัตรหอพัก', 'บัตรห้องพัก', 'บัตรห้องเช่า', 'บัตรห้องเก็บของ', 'บัตรจอดรถ', 'บัตรผ่าน', 'บัตรประจำตัว'],
            'headphones' => ['หูฟัง', 'headphones', 'earpods', 'airpods', 'headset', 'audio equipment', 'หูฟังไร้สาย', 'หูฟังบลูทูธ','earphone','หูฟังครอบหู','หูฟังเกมมิ่ง','หูฟัง gaming','headphone gaming'],
            'keychain' => ['พวงกุญแจ', 'keychain', 'keyring','พวงกุญแจ + ลูกกุญแจ','กุญแจ + พวงกุญแจ','ลูกกุญแจ + พวงกุญแจ'],
            'doll' => ['ตุ๊กตา', 'doll', 'toy', 'โมเดล','ของเล่น','ฟิกเกอร์','ฟิกเกอร์อนิเมะ','ฟิกเกอร์วันพีช','ฟิกเกอร์ดาบพิฆาตอสูร','ฟิกเกอร์นารูโตะ','ฟิกเกอร์ฮีโร่','โมเดลอนิเมะ','โมเดลวันพีช','โมเดลดาบพิฆาตอสูร','โมเดลนารูโตะ','โมเดลฮีโร่','ของสะสม','ของสะสมอนิเมะ','ของสะสมวันพีช','ของสะสมดาบพิฆาตอสูร','ของสะสมนารูโตะ','ของสะสมฮีโร่','ของสะสมโมเดล','ของสะสมฟิกเกอร์','ตุ๊กตาอนิเมะ','ตุ๊กตาวันพีช','ตุ๊กตาดาบพิฆาตอสูร','ตุ๊กตานารูโตะ','ตุ๊กตาฮีโร่','ตุ๊กตาโมเดล','ตุ๊กตาฟิกเกอร์','ตุ๊กตามือสอง','ของเล่นมือสอง'],
            'glasses' => ['แว่น', 'แว่นตา', 'แว่นกันแดด', 'glasses', 'eyewear', 'sunglasses', 'spectacles','แว่นตาแฟชั่น','แว่นกันแดดแฟชั่น','แว่นกันแดด rayban','แว่นกันแดด oakley','แว่นกันแดด ray-ban','แว่นกันแดดมือสอง','แว่นตาแฟชั่นมือสอง'],
            'bottle' => ['ขวด', 'ขวดน้ำ', 'bottle', 'water bottle', 'drinkware','กระติกน้ำ','กระติกน้ำเก็บอุณหภูมิ','กระติกน้ำร้อน','กระติกน้ำเก็บความเย็น','กระติกน้ำ Yeti','กระติกน้ำYeti','กระติกน้ำ yeti','กระติกน้ำyeti','กระติกน้ำเยติ','กระติกน้ำเก็บอุณหภูมิ Yeti','ขวดน้ำเยติ','ขวดน้ำ Yeti','ขวดน้ำYeti','ขวดน้ำ yeti','ขวดน้ำyeti','แก้วน้ำ Yeti','แก้วน้ำเยติ', 'แก้ว Yeti', 'แก้วyeti', 'แก้วเยติ'],
            'book' => ['หนังสือ', 'สมุด', 'book', 'notebook', 'novel', 'textbook','สมุดโน๊ต','สมุดบันทึก','สมุดไดอารี่','สมุดการบ้าน','หนังสือการ์ตูน','หนังสือสอบ','หนังสือเรียน','หนังสือเรียนพิเศษ','หนังสือวิชา','หนังสือวิชาคณิตศาสตร์','หนังสือวิชาฟิสิกส์','หนังสือวิชาเคมี','หนังสือวิชาชีววิทยา','หนังสือวิชาภาษาไทย','หนังสือวิชาภาษาอังกฤษ','หนังสือวิชาสังคมศึกษา','หนังสือวิชาประวัติศาสตร์','หนังสือวิชาคอมพิวเตอร์','หนังสือวิชาศิลปะ','หนังสือวิชานาฏศิลป์','หนังสือวิชาดนตรี','หนังสือวิชาคณิตศาสตร์ประยุกต์','หนังสือวิชาฟิสิกส์ประยุกต์','หนังสือวิชาเคมีประยุกต์','หนังสือวิชาชีววิทยาประยุกต์','หนังสือวิชาภาษาไทยประยุกต์','หนังสือวิชาภาษาอังกฤษประยุกต์','หนังสือวิชาสังคมศึกษาประยุกต์','หนังสือวิชาประวัติศาสตร์ประยุกต์','หนังสือวิชาคอมพิวเตอร์ประยุกต์','หนังสือวิชาศิลปะประยุกต์','หนังสือวิชานาฏศิลป์ประยุกต์','หนังสือวิชาดนตรีประยุกต์'],
            
            // --- Clothing & Accessories ---
            'clothing' => ['เสื้อ', 'กางเกง', 'กระโปรง', 'เดรส', 'เสื้อยืด', 'เสื้อเชิ้ต', 'shirt', 'pants', 'clothes', 'ชุดนอน', 'เสื้อกันหนาว', 'เสื้อคลุม', 'แจ็กเกต', 'เสื้อแขนยาว','เสื้อกีฬา','ชุดกีฬา','ชุดวอร์ม','ชุดนักเรียน','ชุดนักศึกษา','ชุดทำงาน'],
            'jacket' => ['เสื้อกันหนาว', 'เสื้อคลุม', 'แจ็กเกต', 'เสื้อแขนยาว', 'sweater', 'jacket', 'hoodie','เสื้อกันฝน','ชุดกันฝน'],
            'footwear' => ['รองเท้า', 'รองเท้าแตะ', 'รองเท้าผ้าใบ', 'ถุงเท้า', 'shoes', 'sneakers', 'sandals','รองเท้าสุขภาพ','รองเท้าคัทชู','รองเท้าบูท','รองเท้าหุ้มส้น','รองเท้าหุ้มข้อ','รองเท้าหนัง','รองเท้าคีบ'],
            'jewelry' => ['แหวน', 'สร้อย', 'กำไล', 'ตุ้มหู', 'ต่างหู', 'จี้', 'ring', 'necklace', 'earring', 'bracelet','นาฬิกาข้อมือ','นาฬิกาข้อมือผู้ชาย','นาฬิกาข้อมือผู้หญิง','นาฬิกาข้อมือแฟชั่น','นาฬิกาข้อมือสปอร์ต','นาฬิกาข้อมืออัจฉริยะ','สร้อยคอ','จี้สร้อยคอ','สร้อยข้อมือ','จี้สร้อยข้อมือ','ต่างหู','จี้ต่างหู','สร้อยคอทอง','สร้อยคอเงิน','สร้อยคอทองคำ'],
            'hat' => ['หมวก', 'หมวกแก๊ป', 'หมวกกันน็อก', 'hat', 'cap', 'helmet','หมวกปีก','หมวกแก๊ปเด็ก','หมวกแก๊ปแฟชั่น','หมวกแก๊ปกีฬา','หมวกแก๊ปกันแดด','หมวกแก๊ปผู้ชาย','หมวกแก๊ปผู้หญิง','หมวกแก๊ปเด็ก','หมวกกันฝน'],

            // --- Cards & Documents ---
            'card_id' => ['บัตรประชาชน', 'บัตรปชช', 'id card', 'national id','บัตรประจำตัว','บัตรประจำตัวประชาชน'],
            'student_card' => ['บัตรนิสิต', 'บัตรนักศึกษา', 'student card','บัตรประจำตัวนักศึกษา','บัตรประจำตัวนิสิต','บัตรนักเรียน'],
            'driver_license' => ['ใบขับขี่', 'ใบอนุญาตขับขี่', 'driver license','ใบขับขี่รถยนต์','ใบขับขี่รถจักรยานยนต์'],
            'atm_card' => ['บัตรเอทีเอ็ม', 'บัตรเครดิต', 'บัตรเดบิต', 'atm card', 'credit card', 'debit card', 'บัตรธนาคาร','สมุดบัญชี','บัตรเงินสด'],
            'document' => ['แฟ้ม', 'เอกสาร', 'แฟ้มเอกสาร', 'ชีทเรียน', 'การบ้าน', 'paper', 'document', 'file', 'folder'],

            // --- Stationery & Personal Belongings ---
            'pencil_case' => ['กล่องดินสอ', 'กระเป๋าดินสอ', 'pencil case', 'pencil box'],
            'pen' => ['ปากกา', 'ดินสอ', 'ลิควิด', 'ยางลบ', 'pen', 'pencil', 'eraser','ปากกาเจล','ปากกาไวท์บอร์ด','ปากกาลูกลื่น','ปากกาเคมี','ปากกาสี','ปากกามาร์กเกอร์','ดินสอสี','ดินสอกด','ยางลบ','ไม้บรรทัด','กบเหลาดินสอ','เทปลบคำผิด','กาว','กรรไกร','มีดคัตเตอร์',],
            'calculator' => ['เครื่องคิดเลข', 'calculator','เครื่องคิดเลขวิทยาศาสตร์'],
            'tumbler' => ['กระติกน้ำ', 'แก้วน้ำ', 'ขวดน้ำ', 'แก้วเก็บความเย็น', 'tumbler', 'flask', 'water bottle','กระติกน้ำสแตนเลส','กระบอกน้ำ','กระติกน้ำร้อน'],
            'umbrella' => ['ร่ม', 'ร่มกันฝน', 'umbrella','ร่มพับ','ร่มสนาม','ร่มพกพา','ร่มกันแดด'],
            'cosmetics' => ['เครื่องสำอาง', 'ลิปสติก', 'แป้งพัฟ', 'หวี', 'ยาดม', 'cosmetics', 'lipstick', 'comb','มาสคาร่า','อายไลเนอร์','บลัชออน','คุชชั่น','รองพื้น','คอนซิลเลอร์','ไฮไลท์','เฉดดิ้ง','แปรงแต่งหน้า','ฟองน้ำแต่งหน้า','พัฟแต่งหน้า'],
            'perfume' => ['น้ำหอม', 'perfume', 'น้ำหอมผู้ชาย', 'น้ำหอมผู้หญิง','น้ำหอมแบ่งขาย','น้ำหอมพกพา','น้ำหอมแท้','น้ำหอมแบรนด์เนม'],
            'mirror' => ['กระจก', 'กระจกแต่งหน้า', 'กระจกพับ','กระจกพกพา','กระจกส่องหน้า','กระจกพกพาแต่งหน้า','กระจกพกพาแต่งหน้า'],

            // --- Brands & Models ---
            'apple' => ['ไอโฟน', 'iphone', 'apple', 'ipad', 'macbook','airpods','apple watch','apple pencil','airtag','apple charger','apple adapter','apple case'],
            'samsung' => ['ซัมซุง', 'samsung', 'galaxy','samsung tab','samsung watch','samsung note','samsung buds','samsung charger','samsung adapter','samsung case','samsung power bank'],
            'oppo' => ['โอปโป', 'โอปโป้', 'oppo','oppo pad','oppo watch','oppo buds','oppo charger','oppo adapter','oppo case','oppo power bank','ออฟโป้','ออฟโป้ว'],
            'vivo' => ['วีโว่', 'vivo'],
            'xiaomi' => ['เสียวหมี่', 'เสียวมี่','xiaomi', 'redmi', 'รี้ดมี'],
            'realme' => ['เรียลมี', 'realme', 'เรียวมี','realmi','Realmi','Realme'],
            'asus' => ['เอซุส', 'asus','Asus','ASUS'],
            'acer' => ['เอเซอร์', 'acer','Acer','ACER'],
            'lenovo' => ['เลอโนโว', 'lenovo','Lenovo','LENOVO'],
            'hp' => ['เอชพี', 'hp','Hp','HP'],
            'dell' => ['เดลล์', 'dell','Dell','DELL','เดล'],
            'sony' => ['โซนี่', 'sony','Sony','SONY',],
            'marshall' => ['มาร์แชล', 'มาร์แชลล์', 'marshall','Marshall','MARSHALL'],
            'jbl' => ['เจบีแอล', 'jbl','Jbl','JBL'],
            'popmart' => ['pop mart', 'popmart', 'ป๊อปมาร์ท', 'pop-mart'],
            'labubu' => ['labubu', 'ลาบูบู้', 'labub'],
            'art_toy' => ['ลาบูบู้', 'มอนสเตอร์', 'ลิซ่า', 'crybaby', 'popmart', 'labubu', 'art toy', 'พวงกุญแจตุ๊กตา'],

            // --- Attributes & Colors & Materials ---
            'red' => ['แดง', 'สีแดง', 'red', 'Red', 'RED'],
            'pink' => ['ชมพู', 'สีชมพู', 'pink', 'Pink', 'PINK'],
            'yellow' => ['เหลือง', 'สีเหลือง', 'yellow', 'Yellow', 'YELLOW'],
            'green' => ['เขียว', 'สีเขียว', 'green', 'Green', 'GREEN'],
            'orange' => ['ส้ม', 'สีส้ม', 'orange', 'Orange', 'ORANGE'],
            'purple' => ['ม่วง', 'สีม่วง', 'purple', 'Purple', 'PURPLE'],
            'white' => ['ขาว', 'สีขาว', 'white', 'White', 'WHITE'],
            'black' => ['ดำ', 'สีดำ', 'black', 'Black', 'BLACK'],
            'blue' => ['น้ำเงิน', 'ฟ้า', 'สีน้ำเงิน', 'สีฟ้า', 'blue', 'Blue', 'BLUE'],
            'grey' => ['เทา', 'สีเทา', 'grey', 'gray', 'Grey', 'Gray', 'GREY', 'GRAY'],
            'gold' => ['ทอง', 'สีทอง', 'gold', 'Gold', 'GOLD'],
            'silver' => ['เงิน', 'สีเงิน', 'silver', 'Silver', 'SILVER'],
            'leather' => ['หนัง', 'หนังแท้', 'leather', 'Leather', 'LEATHER','หนังวัว'],
            'plastic' => ['พลาสติก', 'plastic', 'Plastic', 'PLASTIC'],
            'metal' => ['โลหะ', 'เหล็ก', 'metal', 'Metal', 'METAL','โลหะผสม'],
            'canvas' => ['ผ้า', 'ผ้าใบ', 'canvas', 'Canvas', 'CANVAS','ผ้าฝ้าย','ผ้าลินิน','ผ้ากำมะหยี่','ผ้าเดนิม','ผ้าไนลอน','ผ้าโพลีเอสเตอร์','ผ้าซาติน','ผ้ามัสลิน','ผ้าคอตตอน','ผ้ายีนส์'],
            'thai_dress' => ['ชุดไทย', 'ชุดสไตล์ไทย', 'thai dress', 'Thai Dress', 'THAI DRESS','ชุดไทยประยุกต์','ชุดไทยสมัยใหม่'],
            'rabbit' => ['กระต่าย', 'rabbit', 'Rabbit', 'RABBIT'],
            'money' => ['เงิน', 'เงินสด', 'cash', 'money','แบงค์','ธนบัตร','เหรียญ','เหรียญบาท','ธนบัตรไทย','เหรียญไทย','ธนบัตรปลอม','ตังค์','ตัง','สตางค์'],
            'sticker' => ['สติกเกอร์', 'สติ๊กเกอร์', 'sticker','สติ๊กเกอ','สติกเก้อ'],
            'cat' => ['แมว', 'cat','เหมียว','เหมียวๆ','น้องเหมียว','น้องแมว'],
            'dog' => ['หมา', 'สุนัข', 'dog','ตูบ','น้องหมา','น้องสุนัข',],

            // --- Locations ---
            'canteen' => ['โรงอาหาร', 'canteen', 'โรงอาหารใหญ่', 'ร้านค้า', 'ร้านข้าว', 'ร้านกาแฟ', 'โรงอาหารกลาง','โรงอาหารคณะ','โรงอาหารตึกกลาง'],
            'library' => ['หอสมุด', 'ห้องสมุด', 'library',],
            'classroom' => ['ห้องเรียน', 'อาคารเรียน', 'ตึกเรียน', 'ห้องบรรยาย', 'classroom','ห้องเรียนรวม'],
            'lab' => ['ห้องแล็บ', 'ห้องปฏิบัติการ', 'lab', 'laboratory','แล็บรวม'],
            'sports' => ['สนามกีฬา', 'โรงยิม', 'สนามบอล', 'สนามบาส', 'gym', 'sports complex','สนาม','สนามฟุตซอล','สนามบาสเกตบอล','สนามกีฬาในร่ม','สนามฟุตบอล','สนามเทนนิส','สนามวอลเลย์บอล','สนามแบดมินตัน','สนามกรีฑา','ลาน'],
            'parking' => ['โรงรถ', 'โรงจอดรถ', 'ที่จอดรถ', 'ลานจอดรถ', 'parking','อาคารจอดรถ'],
            'bus_stop' => ['ป้ายรถเมล์','bus stop'],
            'food_court' => ['ศูนย์อาหาร','food court','ฟู้ดคอร์ด'],
            'office' => ['ห้องทำงาน','ออฟฟิศ','ห้องพักครู','ห้องพักบุคลากร','ห้องพักอาจารย์','ห้องพักนักศึกษา'],
            'stair' => ['บันได','บันไดหนีไฟ'],
            'lift' => ['ลิฟต์','ลิฟท์','elevator','ลิฟท์โดยสาร','ลิฟท์ขนของ'],
            'toilet' => ['ห้องน้ำ','ห้องสุขา','ห้องน้ำหญิง','ห้องน้ำชาย'],
            'entrance' => ['ประตูทางเข้า','ประตูใหญ่'],
            'hall' => ['โถง','โถงทางเดิน'],
            'balcony' => ['ระเบียง','ระเบียงตึก'],
            'field' => ['สนามหญ้า','สนามกลาง'],
            'gym' => ['ยิม', 'โรงยิม', 'ฟิตเนส', 'gym', 'fitness', 'สนามกีฬา'],
            'co_working' => ['co working space', 'coworking', 'โคเวิร์กกิ้ง'],
            'dormitory' => ['หอพัก', 'หอใน', 'หอนอก', 'หอ', 'dorm'],
           
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
        
        $flat_synonyms = [];
        foreach ($synonym_map as $standard => $variations) {
            foreach ($variations as $var) {
                $flat_synonyms[] = [
                    'var' => mb_strtolower(trim($var), 'UTF-8'),
                    'std' => $standard
                ];
            }
        }
        
        // Sort flat list by length descending to match longer strings first
        usort($flat_synonyms, function($a, $b) {
            return mb_strlen($b['var']) <=> mb_strlen($a['var']);
        });
        
        foreach ($flat_synonyms as $item) {
            $var = $item['var'];
            $std = $item['std'];
            if (preg_match('/^[a-zA-Z0-9\s]+$/', $var)) {
                $pattern = '/\b' . preg_quote($var, '/') . '\b/i';
                $normalized = preg_replace($pattern, $std, $normalized);
            } else {
                $normalized = str_replace($var, $std, $normalized);
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
        $standardized = apply_synonyms($normalized);
        
        $keywords = [];
        
        $synonym_map = get_synonym_map();
        foreach (array_keys($synonym_map) as $standard) {
            if (mb_strpos($standardized, $standard) !== false) {
                $keywords[] = $standard;
            }
        }
        
        // Fallback space-separated token matching (great for English/Thai combination)
        $split_text = preg_replace('/([0-9]+)/', ' $1 ', $standardized);
        $tokens = preg_split('/\s+/', $split_text, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($tokens as $token) {
            $keywords[] = $token;
        }
        
        return array_values(array_unique($keywords));
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
                    $is_w1_alpha = preg_match('/^[a-zA-Z0-9_]+$/', $w1);
                    $is_w2_alpha = preg_match('/^[a-zA-Z0-9_]+$/', $w2);
                    
                    if ($is_w1_alpha || $is_w2_alpha) {
                        // หากคำใดคำหนึ่งเป็นภาษาอังกฤษ/ตัวเลข บังคับให้ต้องตรงกันเป๊ะแบบ Case-Insensitive เท่านั้น
                        if (mb_strtolower($w1) === mb_strtolower($w2)) {
                            $count++;
                            break;
                        }
                    } else {
                        // หากเป็นภาษาไทย/ภาษาอื่น ให้ใช้การค้นหาแบบบางส่วน (Substring Match) ได้
                        if ($w1 === $w2 || mb_stripos($w1, $w2) !== false || mb_stripos($w2, $w1) !== false) {
                            $count++;
                            break; 
                        }
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
     * Calculate similarity score using Google Cloud Vision image labels with dynamic blacklist filtering
     */
    function calculate_image_label_score($item1, $item2, $max_score = 30) {
        $labels1 = !empty($item1['image_labels']) ? json_decode($item1['image_labels'], true) : [];
        $labels2 = !empty($item2['image_labels']) ? json_decode($item2['image_labels'], true) : [];
        
        if (!is_array($labels1)) $labels1 = [];
        if (!is_array($labels2)) $labels2 = [];

        // 1. ดึงข้อมูลสีทั้งหมดจาก color_map มาทำ Blacklist อัตโนมัติ (Dynamic Color Blacklisting)
        $color_blacklist = [];
        $color_map = [
            'black'  => ['black', 'ดำ', 'สีดำ'],
            'white'  => ['white', 'ขาว', 'สีขาว'],
            'red'    => ['red', 'แดง', 'สีแดง'],
            'blue'   => ['blue', 'น้ำเงิน', 'ฟ้า', 'สีน้ำเงิน', 'สีฟ้า'],
            'green'  => ['green', 'เขียว', 'สีเขียว'],
            'yellow' => ['yellow', 'เหลือง', 'สีเหลือง'],
            'pink'   => ['pink', 'ชมพู', 'สีชมพู'],
            'brown'  => ['brown', 'น้ำตาล', 'สีน้ำตาล'],
            'grey'   => ['grey', 'gray', 'เทา', 'สีเทา'],
            'purple' => ['purple', 'ม่วง', 'สีม่วง'],
            'orange' => ['orange', 'ส้ม', 'สีส้ม'],
            'gold'   => ['gold', 'ทอง', 'สีทอง'],
            'silver' => ['silver', 'เงิน', 'สีเงิน']
        ];
        foreach ($color_map as $key => $values) {
            $color_blacklist[] = $key;
            $color_blacklist = array_merge($color_blacklist, $values);
        }

        // 2. คำทั่วไป วัสดุ และการถ่ายภาพที่ไม่ใช่วัตถุหลัก (Generic/Materials Blacklist)
        $generic_blacklist = [
            // Generic terminology
            'peripheral', 'gadget', 'electronic device', 'technology', 'plastic', 
            'font', 'logo', 'brand', 'material', 'design', 'product', 'metal', 
            'computer hardware', 'input device', 'output device', 'multimedia',
            'rectangle', 'circle', 'line', 'pattern', 'text', 'screenshot', 
            'software', 'accessory', 'personal protective equipment', 'office supplies',
            'apparatus', 'device', 'instrument', 'component', 'equipment', 'object', 'machine',
            'home appliance', 'appliance', 'เครื่องใช้ในบ้าน', 'เครื่องใช้',
            'เครื่องจักร', 'เครื่องมือ', 'อุปกรณ์', 'เทคโนโลยี', 'การออกแบบ', 'ผลิตภัณฑ์', 'โลหะ', 'อุปกรณ์อิเล็กทรอนิกส์',
            
            // Materials
            'leather', 'wood', 'carbon fibers', 'plastic', 'glass', 'cotton', 'silk', 'satin', 'fabric', 'textile', 'wool', 'woolen', 'fur',
            'หนัง', 'ไม้', 'คาร์บอนไฟเบอร์', 'พลาสติก', 'แก้ว', 'ฝ้าย', 'ไหม', 'ผ้าซาติน', 'ผ้า', 'ขนสัตว์',
            
            // Photo & Art terms
            'close-up', 'macro photography', 'monochrome', 'contrast', 'lighting', 'tint', 'shade', 'shadow', 'reflection', 'refraction',
            'ภาพโคลสอัพ', 'ภาพถ่าย', 'ภาพขาวดำ', 'แสง', 'เงา', 'การสะท้อน'
        ];

        $blacklist = array_merge($color_blacklist, $generic_blacklist);
        $blacklist = array_unique(array_map(function($w) {
            return mb_strtolower(trim($w));
        }, $blacklist));
        
        // กรองเอาคุณลักษณะรอง สี และวัสดุออก (คำที่เหลืออยู่ทั้งหมดจะถูกถือเป็นวัตถุหลักโดยอัตโนมัติ)
        $labels1 = array_diff($labels1, $blacklist);
        $labels2 = array_diff($labels2, $blacklist);

        if (empty($labels1) || empty($labels2)) {
            return 0;
        }

        // ตรวจสอบความสอดคล้องของวัตถุหลักที่เหลือร่วมกัน
        $common_labels = array_intersect($labels1, $labels2);
        
        // หากไม่มีวัตถุหลักตรงกันเลย ให้คะแนนภาพเป็น 0 คะแนนทันที (ป้องกันสับสนระหว่างของคนละชิ้นที่มีสีเดียวกัน)
        if (empty($common_labels)) {
            return 0;
        }

        $score = 0;

        // 1. Image-to-Image Match (both have labels)
        $match_count = count($common_labels);
        if ($match_count > 0) {
            $min_total_count = min(count($labels1), count($labels2));
            if ($min_total_count > 0) {
                $score = min($max_score, round(($match_count / $min_total_count) * $max_score));
            }
        }

        // 2. Image-to-Text Match (cross matching labels to text) - คัดกรองเฉพาะคีย์เวิร์ดวัตถุหลักที่เหลืออยู่เท่านั้น
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

        // Match Item 1 core image labels to Item 2 text
        if (!empty($concepts1) && !empty($text_concepts2)) {
            $matches1 = array_intersect($concepts1, $text_concepts2);
            if (!empty($matches1)) {
                $score = max($score, min($max_score, 15 + count($matches1) * 5));
            }
        }

        // Match Item 2 core image labels to Item 1 text
        if (!empty($concepts2) && !empty($text_concepts1)) {
            $matches2 = array_intersect($concepts2, $text_concepts1);
            if (!empty($matches2)) {
                $score = max($score, min($max_score, 15 + count($matches2) * 5));
            }
        }

        return $score;
    }
}

if (!function_exists('extract_colors_from_item')) {
    /**
     * Extract standard colors from an item's text (title, description, secret description) and image labels
     * 
     * @param array $item Item data array
     * @return array Standardized color keys (e.g. ['black', 'pink'])
     */
    function extract_colors_from_item($item) {
        $color_map = [
            'black'  => ['black', 'ดำ', 'สีดำ'],
            'white'  => ['white', 'ขาว', 'สีขาว'],
            'red'    => ['red', 'แดง', 'สีแดง'],
            'blue'   => ['blue', 'น้ำเงิน', 'ฟ้า', 'สีน้ำเงิน', 'สีฟ้า'],
            'green'  => ['green', 'เขียว', 'สีเขียว'],
            'yellow' => ['yellow', 'เหลือง', 'สีเหลือง'],
            'pink'   => ['pink', 'ชมพู', 'สีชมพู'],
            'brown'  => ['brown', 'น้ำตาล', 'สีน้ำตาล'],
            'grey'   => ['grey', 'gray', 'เทา', 'สีเทา'],
            'purple' => ['purple', 'ม่วง', 'สีม่วง'],
            'orange' => ['orange', 'ส้ม', 'สีส้ม'],
            'gold'   => ['gold', 'ทอง', 'สีทอง'],
            'silver' => ['silver', 'เงิน', 'สีเงิน']
        ];

        $detected = [];
        
        // 1. Check title, description, and secret description text
        $text = mb_strtolower(
            ($item['title'] ?? '') . ' ' . 
            ($item['description'] ?? '') . ' ' . 
            ($item['secret_description'] ?? '')
        );

        foreach ($color_map as $color => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($text, $kw) !== false) {
                    $detected[] = $color;
                    break;
                }
            }
        }

        // 2. Check image labels
        $labels = !empty($item['image_labels']) ? json_decode($item['image_labels'], true) : [];
        if (is_array($labels)) {
            foreach ($labels as $label) {
                $label = mb_strtolower(trim($label));
                foreach ($color_map as $color => $keywords) {
                    if (in_array($label, $keywords)) {
                        $detected[] = $color;
                        break;
                    }
                }
            }
        }

        return array_values(array_unique($detected));
    }
}

if (!function_exists('parse_colors')) {
    /**
     * Parse color string into normalized individual color tokens
     */
    function parse_colors($color_str) {
        if (empty($color_str)) return [];
        
        $color_str = mb_strtolower($color_str, 'UTF-8');
        // Normalize double SARA E (เเ) to SARA AE (แ)
        $color_str = str_replace('เเ', 'แ', $color_str);
        // Replace delimiters and conjunctions with comma
        $color_str = str_replace(['และ', 'กับ', '-', '/', '\\', '+', ' ', 'สี'], ',', $color_str);
        
        $raw_colors = explode(',', $color_str);
        $parsed = [];
        
        $standard_colors = ['ดำ', 'ขาว', 'แดง', 'น้ำเงิน', 'ฟ้า', 'เขียว', 'เหลือง', 'ชมพู', 'น้ำตาล', 'เทา', 'ม่วง', 'ส้ม', 'ทอง', 'เงิน', 'ครีม'];
        
        foreach ($raw_colors as $color_part) {
            $color_part = trim($color_part);
            if (empty($color_part)) continue;
            
            $found = false;
            foreach ($standard_colors as $std) {
                if (mb_strpos($color_part, $std) !== false) {
                    $parsed[] = $std;
                    $found = true;
                }
            }
            if (!$found) {
                $parsed[] = $color_part;
            }
        }
        
        if (count($parsed) === 1) {
            $single_part = $parsed[0];
            $sub_colors = [];
            foreach ($standard_colors as $std) {
                if (mb_strpos($single_part, $std) !== false) {
                    $sub_colors[] = $std;
                }
            }
            if (count($sub_colors) > 1) {
                $parsed = $sub_colors;
            }
        }
        
        return array_values(array_unique($parsed));
    }
}

if (!function_exists('calculate_brand_model_match')) {
    /**
     * Calculate brand and model match indicator for tie-breaker sorting
     */
    function calculate_brand_model_match($item1, $item2) {
        $brand1 = mb_strtolower(trim($item1['brand'] ?? ''));
        $brand2 = mb_strtolower(trim($item2['brand'] ?? ''));
        $model1 = mb_strtolower(trim($item1['model'] ?? ''));
        $model2 = mb_strtolower(trim($item2['model'] ?? ''));
        
        $brand_match = false;
        $model_match = false;
        
        if (!empty($brand1) && !empty($brand2) && $brand1 === $brand2) {
            $brand_match = true;
        }
        
        if (!empty($model1) && !empty($model2) && $model1 === $model2) {
            $model_match = true;
        }
        
        if ($brand_match && $model_match) return 3;
        if ($brand_match) return 2;
        if ($model_match) return 1;
        return 0;
    }
}

if (!function_exists('calculate_match_score')) {
    /**
     * Calculate match score between two items (Symmetric)
     */
    function calculate_match_score($item1, $item2) {
        // 1. Serial Number (Highest Signal) - 100 points
        $clean_sn = function($sn) {
            $sn = mb_strtolower(trim($sn));
            $sn = str_replace(['sn:', 'sn', 's/n:', 's/n', 'imei:', 'imei'], '', $sn);
            return preg_replace('/[^a-z0-9]/', '', $sn);
        };

        $get_sn = function($item) use ($clean_sn) {
            if (!empty($item['serial_number'])) return $clean_sn($item['serial_number']);
            $text = mb_strtolower(($item['description'] ?? '') . ' ' . ($item['secret_description'] ?? ''));
            if (preg_match('/(?:s\/?n|serial|no|id|imei):?\s*([a-z0-9\-\/\.]+)/i', $text, $matches)) {
                return $clean_sn($matches[1]);
            }
            return null;
        };

        $s1 = $get_sn($item1);
        $s2 = $get_sn($item2);
        if ($s1 && $s2 && $s1 === $s2) return 100;

        $score = 0;

        // 2. Title Keywords (Max 20)
        $score += calculate_keyword_score($item1['title'], $item2['title'], 20);
        
        // 3. Description Keywords (Max 20) - Include Secret Description!
        $desc1 = ($item1['description'] ?? '') . ' ' . ($item1['secret_description'] ?? '');
        $desc2 = ($item2['description'] ?? '') . ' ' . ($item2['secret_description'] ?? '');
        $score += calculate_keyword_score($desc1, $desc2, 20);
        
        // 4. Structured Location Similarity (Max 20)
        $province_score = 0;
        if (!empty($item1['province']) && !empty($item2['province']) && $item1['province'] === $item2['province']) {
            $province_score = 5;
        }
        
        $district_score = 0;
        if (!empty($item1['district']) && !empty($item2['district']) && $item1['district'] === $item2['district']) {
            $district_score = 5;
        }
        
        $loc_detail_score = 0;
        $detail1 = $item1['location_detail'] ?? '';
        $detail2 = $item2['location_detail'] ?? '';
        
        if (!empty($detail1) && !empty($detail2)) {
            $wordsA = extract_keywords($detail1);
            $wordsB = extract_keywords($detail2);
            
            if (!empty($wordsA) && !empty($wordsB)) {
                $intersect = array_intersect($wordsA, $wordsB);
                $matched = count($intersect);
                $average = (count($wordsA) + count($wordsB)) / 2;
                
                $similarity = $matched / $average;
                $sim_percentage = $similarity * 100;
                
                if ($sim_percentage >= 90) {
                    $loc_detail_score = 10;
                } elseif ($sim_percentage >= 70) {
                    $loc_detail_score = 8;
                } elseif ($sim_percentage >= 50) {
                    $loc_detail_score = 5;
                } elseif ($sim_percentage >= 30) {
                    $loc_detail_score = 2;
                } else {
                    $loc_detail_score = 0;
                }
            }
        }
        $score += ($province_score + $district_score + $loc_detail_score);
        
        // 5. Category Match (Max 10)
        if (!empty($item1['category']) && !empty($item2['category']) && $item1['category'] === $item2['category']) {
            $score += 10;
        }

        // 6. Image Label Match (Max 25)
        $score += calculate_image_label_score($item1, $item2, 25);

        // 7. Color Match (Max 5)
        $color_score = 0;
        if (!empty($item1['color']) && !empty($item2['color'])) {
            $colors1 = parse_colors($item1['color']);
            $colors2 = parse_colors($item2['color']);
            
            if (!empty($colors1) && !empty($colors2)) {
                $intersect = array_intersect($colors1, $colors2);
                $match_count = count($intersect);
                
                if ($match_count === count($colors1) && $match_count === count($colors2)) {
                    $color_score = 5;
                } elseif ($match_count > 0) {
                    $color_score = 3;
                }
            }
        }
        $score += $color_score;

        // 8. Product Type Clash Penalty
        $get_item_product_types = function($title) {
            $title = mb_strtolower($title);
            $types = [];
            $map = [
                'phone' => ['phone', 'โทรศัพท์', 'มือถือ', 'ไอโฟน', 'iphone', 'samsung', 'oppo', 'vivo', 'realme', 'xiaomi'],
                'headphones' => ['headphones', 'หูฟัง', 'airpods', 'earbuds', 'headset'],
                'laptop' => ['laptop', 'notebook', 'คอมพิวเตอร์', 'โน๊ตบุ๊ค', 'macbook'],
                'wallet' => ['wallet', 'purse', 'กระเป๋าสตางค์', 'กระเป๋าเงิน', 'เป๋าตังค์'],
                'bag' => ['bag', 'backpack', 'กระเป๋าเป้', 'กระเป๋าถือ', 'กระเป๋าเดินทาง', 'กระเป๋าสะพาย'],
                'watch' => ['watch', 'smartwatch', 'นาฬิกา', 'apple watch', 'garmin'],
                'key' => ['key', 'keycard', 'กุญแจ', 'คีย์การ์ด', 'รีโมท']
            ];
            foreach ($map as $type => $keywords) {
                foreach ($keywords as $kw) {
                    if (mb_strpos($title, $kw) !== false) {
                        $types[] = $type;
                        break;
                    }
                }
            }
            return array_unique($types);
        };

        $types1 = $get_item_product_types($item1['title']);
        $types2 = $get_item_product_types($item2['title']);
        if (!empty($types1) && !empty($types2)) {
            $intersect = array_intersect($types1, $types2);
            if (empty($intersect)) {
                $score -= 45;
            }
        }

        // 9. Color-Based Clash Penalty
        $colors_clash1 = extract_colors_from_item($item1);
        $colors_clash2 = extract_colors_from_item($item2);

        if (!empty($colors_clash1) && !empty($colors_clash2)) {
            $common_colors = array_intersect($colors_clash1, $colors_clash2);
            if (empty($common_colors)) {
                $score -= 25;
            }
        }
        
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
        $sn = str_replace(['sn:', 'sn', 's/n:', 's/n', 'imei:', 'imei'], '', $sn);
        $clean = preg_replace('/[^a-z0-9]/', '', $sn);
        return !empty($clean) ? $clean : null;
    }
}
