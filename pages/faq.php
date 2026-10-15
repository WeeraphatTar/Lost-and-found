<?php
$base_url = 'http://localhost/lost-and-found';
require_once '../includes/header.php';
?>

<div class="py-12 bg-background flex-grow">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-center text-primary mb-8">คำถามที่พบบ่อย (Frequently Asked Questions)</h1>

        <div class="bg-white p-6 sm:p-8 rounded-lg shadow-sm border border-gray-200">
            <div class="space-y-6 divide-y divide-gray-100">
                <div class="pt-4 first:pt-0">
                    <h3 class="font-bold text-primary text-lg mb-2 flex items-center gap-2">
                        <span class="text-accent font-extrabold">Q:</span> ข้อมูลเบอร์โทรศัพท์ของฉันจะถูกเปิดเผยหรือไม่?
                    </h3>
                    <p class="text-gray-600 bg-gray-50 p-4 rounded-lg border border-gray-100 leading-relaxed text-sm">
                        <strong class="text-primary">A:</strong> ระบบจะไม่แสดงเบอร์โทรศัพท์ของคุณต่อสาธารณะ ข้อมูลส่วนนี้จะถูกใช้เพื่อการติดต่อกรณีที่ยืนยันสิทธิ์สำเร็จหรือมีเรื่องเร่งด่วนโดยเจ้าหน้าที่เท่านั้น
                    </p>
                </div>

                <div class="pt-6">
                    <h3 class="font-bold text-primary text-lg mb-2 flex items-center gap-2">
                        <span class="text-accent font-extrabold">Q:</span> ถ้าเจอของหาย แต่ไม่สะดวกนำไปฝากไว้ที่ส่วนกลาง ควรทำอย่างไร?
                    </h3>
                    <p class="text-gray-600 bg-gray-50 p-4 rounded-lg border border-gray-100 leading-relaxed text-sm">
                        <strong class="text-primary">A:</strong> คุณสามารถกรอกฟอร์ม "แจ้งพบของ" และเลือกระบุสถานที่จัดเก็บเป็น "เก็บไว้กับตัว" ได้ เมื่อมีผู้ติดต่อมาอ้างสิทธิ์และตรวจสอบถูกต้องแล้ว ค่อยนัดหมายสถานที่ส่งมอบกันอีกครั้ง
                    </p>
                </div>

                <div class="pt-6">
                    <h3 class="font-bold text-primary text-lg mb-2 flex items-center gap-2">
                        <span class="text-accent font-extrabold">Q:</span> การยืนยันสิทธิ์ความเป็นเจ้าของมีขั้นตอนอย่างไร?
                    </h3>
                    <p class="text-gray-600 bg-gray-50 p-4 rounded-lg border border-gray-100 leading-relaxed text-sm">
                        <strong class="text-primary">A:</strong> ผู้ที่ทำของหายจะต้องตอบคำถามถึง "ลักษณะเฉพาะ" ที่ผู้พบได้ตั้งไว้ (เช่น ตำหนิเฉพาะ, รหัสผ่านทดสอบ, รูปวอลเปเปอร์หน้าจอ) หากข้อมูลตรงกัน จึงจะถือว่าเป็นการยืนยันสิทธิ์เบื้องต้น
                    </p>
                </div>

                <div class="pt-6">
                    <h3 class="font-bold text-primary text-lg mb-2 flex items-center gap-2">
                        <span class="text-accent font-extrabold">Q:</span> หากได้รับทรัพย์สินคืนแล้ว ต้องทำอย่างไรต่อ?
                    </h3>
                    <p class="text-gray-600 bg-gray-50 p-4 rounded-lg border border-gray-100 leading-relaxed text-sm">
                        <strong class="text-primary">A:</strong> คุณสามารถกดปุ่ม "ปิดประกาศ" หรือ "ยืนยันรับของแล้ว" ในหน้าจัดการประกาศของคุณ เพื่ออัปเดตสถานะประกาศเป็นคืนของสำเร็จแล้ว
                    </p>
                </div>

                <div class="pt-6">
                    <h3 class="font-bold text-primary text-lg mb-2 flex items-center gap-2">
                        <span class="text-accent font-extrabold">Q:</span> หากพบการประกาศเท็จหรือแอบอ้างสิทธิ์ สามารถแจ้งใครได้บ้าง?
                    </h3>
                    <p class="text-gray-600 bg-gray-50 p-4 rounded-lg border border-gray-100 leading-relaxed text-sm">
                        <strong class="text-primary">A:</strong> สามารถกดปุ่มแจ้งรายงาน หรือติดต่อเจ้าหน้าที่ผ่านหน้า "ติดต่อเจ้าหน้าที่" ทางระบบจะดำเนินการตรวจสอบและระงับบัญชีผู้กระทำผิดทันที
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
