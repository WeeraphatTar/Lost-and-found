# Setup Guide: โปรเจค Lost & Found (สำหรับเพื่อนร่วมทีม)

เพื่อให้คุณสามารถรันโปรเจคนี้บนเครื่องของคุณได้เหมือนกับที่ฉันทำ ให้ทำตามขั้นตอนดังนี้:

## 1. เตรียมความพร้อม (Prerequisites)
*   ติดตั้ง **XAMPP** (แนะนำเวอร์ชันที่รองรับ PHP 8.x)
*   ดาวน์โหลด/Clone โปรเจคนี้ไปไว้ที่โฟลเดอร์: `C:\xampp\htdocs\Lost_found`

---

## 2. การตั้งค่าฐานข้อมูล (phpMyAdmin)
ขั้นตอนสำคัญเพื่อให้ระบบสามารถดึงข้อมูลได้:

1.  เปิด **XAMPP Control Panel** และกด Start ที่ **Apache** และ **MySQL**
2.  เปิด Browser แล้วเข้าไปที่: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
3.  ที่แถบเมนูด้านบน เลือก **Import (นำเข้า)**
4.  กดปุ่ม **Choose File (เลือกไฟล์)** แล้วเลือกไฟล์ `lost_found_db.sql` ที่อยู่ในโฟลเดอร์หลักของโปรเจค
5.  เลื่อนลงมาด้านล่างสุดแล้วกดปุ่ม **Import (นำเข้า)** หรือ **Go**
    *   *หมายเหตุ: ไฟล์ SQL นี้จะทำการสร้างฐานข้อมูลชื่อ `lost_found_db` และ Table ต่างๆ ให้โดยอัตโนมัติ*

---

## 3. ตรวจสอบการเชื่อมต่อฐานข้อมูล
ไฟล์ที่ใช้ตั้งค่าฐานข้อมูลคือ `config/database.php` หากคุณใช้ค่าเริ่มต้นของ XAMPP (root ไม่มีรหัสผ่าน) ไม่ต้องแก้ไขอะไร:

```php
// config/database.php
$host = 'localhost';
$dbname = 'lost_found_db';
$username = 'root';
$password = '';
```

---

## 4. วิธีเข้าใช้งานโปรเจค
เมื่อตั้งค่าฐานข้อมูลเสร็จแล้ว คุณสามารถเข้าใช้งานได้ผ่าน URL:
[http://localhost/Lost_found](http://localhost/Lost_found)

---

## สรุปสิ่งที่เพื่อนต้องทำ (Checklist)
- [ ] นำโปรเจคไปวางใน `htdocs/Lost_found`
- [ ] Start Apache & MySQL ใน XAMPP
- [ ] Import ไฟล์ `lost_found_db.sql` เข้าไปใน phpMyAdmin
- [ ] เข้าหน้าเว็บผ่าน `localhost/Lost_found`
