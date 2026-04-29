<?php
// กำหนดค่าการเชื่อมต่อฐานข้อมูล
$host = 'localhost';
$dbname = 'lost_found_db';
$username = 'root'; // ค่าเริ่มต้นของ XAMPP
$password = ''; // ค่าเริ่มต้นของ XAMPP ไม่มีรหัสผ่าน

try {
    // สร้างการเชื่อมต่อด้วย PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // ตั้งค่า Error Mode ให้เป็น Exception เพื่อให้ง่ายต่อการตรวจสอบข้อผิดพลาด
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // ตั้งค่าการดึงข้อมูลเริ่มต้นเป็นแบบ Associative Array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // กรณีที่เกิดข้อผิดพลาดในการเชื่อมต่อ
    die("Connection failed: " . $e->getMessage());
}
?>
