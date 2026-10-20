# 🔍 Lost & Found System - ระบบแจ้งของหายและติดตามทรัพย์สินคืน

ระบบแจ้งของหายและติดตามทรัพย์สินคืน (Lost and Found System) พัฒนาโดยใช้ **PHP, Tailwind CSS, MySQL, Google Cloud Vision API, Google OAuth 2.0 และ PHPMailer (Gmail SMTP)** ออกแบบมาเพื่อช่วยเหลือผู้ใช้งานในการประกาศสืบค้นทรัพย์สินที่สูญหาย ประสานงานแจ้งพบของ และยืนยันสิทธิ์ความเป็นเจ้าของได้อย่างแม่นยำและปลอดภัย

---

## 🛠️ เครื่องมือและเทคโนโลยีที่ใช้ (Tech Stack)

* **Frontend:** HTML5, CSS3, Tailwind CSS, JavaScript (Fetch API / Asynchronous)
* **Backend:** PHP (Procedural & Modular Architecture)
* **Database:** MySQL
* **Third-Party APIs & Libraries:** 
  * **Google Cloud Vision API (v1):** วิเคราะห์รูปภาพสำหรับ Smart Matching
  * **Google OAuth 2.0 / OpenID Connect:** ระบบยืนยันตัวตนผ่าน Google Account
  * **PHPMailer (Gmail SMTP):** จัดส่งอีเมลแจ้งเตือนและรหัส OTP
* **Version Control:** Git & GitHub

---

## 🧩 ระบบหลัก (Main Features)

### 📌 Item Management
* **การแจ้งของหาย / แจ้งพบของ (Lost & Found Posting):** ลงประกาศระบุหมวดหมู่ สถานที่ วันเวลา และรูปภาพประกอบ
* **ข้อมูลลับเฉพาะ (Secret Description):** ระบบบังคับแยกกรอกรายละเอียดซ่อนเร้น (เช่น ตำหนิ, รหัสผ่าน, สิ่งของภายใน) เพื่อใช้ในการพิสูจน์สิทธิ์ความเป็นเจ้าของ

### 🤖 Smart Matching System
* **Multi-Signal Scoring Algorithm:** คำนวณคะแนนความคล้ายคลึงระหว่างประกาศของหายและพบของโดยอัตโนมัติ
* **Google Cloud Vision AI Integration:** นำ Image Labels มาวิเคราะห์เปรียบเทียบหาจุดตัดของคำ (Intersection) ร่วมกับหมวดหมู่ สถานที่ และสี
* **Clash Penalties:** ตรรกะหักคะแนนกรณีประเภทสิ่งของหรือสีขัดแย้งกันโดยสิ้นเชิง

### 🛡️ Claim & Dispute Resolution
* **Two-Factor Claim Verification:** ผู้ขอรับของคืนต้องกรอกคำอธิบายตอบโจทย์ข้อมูลลับ พร้อมแนบรูปภาพหลักฐาน
* **Centralized Admin Verification:** พอร์ทัลผู้ดูแลระบบสำหรับเปรียบเทียบข้อมูลลับของผู้เก็บได้กับหลักฐานของผู้เคลมแบบคู่ขนานก่อนกดอนุมัติ/ปฏิเสธ
* **Meeting Point Unlocking:** ปลดล็อกสถานที่จัดเก็บของและเปิดห้องสนทนาประสานงานเฉพาะคำร้องที่ได้รับการอนุมัติแล้วเท่านั้น

---

## 👥 ผู้พัฒนา (Developed By)

* **ชื่อโปรเจกต์:** Lost & Found System
* **ผู้พัฒนา:** วิรากานต์ งามขำ และ วีรภัทร ทัพเจริญ นิสิตสาขาวิชาเทคโนโลยีสารสนเทศเพื่ออุตสาหกรรมดิจิทัล คณะวิทยาการสารสนเทศ มหาวิทยาลัยบูรพา
