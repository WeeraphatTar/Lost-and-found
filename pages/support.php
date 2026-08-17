<?php
$base_url = 'http://localhost/lost-and-found';
require_once '../includes/header.php';
?>

<div class="py-12 bg-background flex-grow">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-center text-primary mb-12">ศูนย์ช่วยเหลือ (Support Center)</h1>

        <div class="mb-12 bg-white p-6 sm:p-8 rounded-lg shadow-sm border border-gray-200">
            <h2 class="text-xl font-bold text-primary border-b border-gray-200 pb-3 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                คำแนะนำเมื่อทำของหาย
            </h2>
            <ul class="list-disc list-inside text-gray-700 space-y-3 leading-relaxed">
                <li>ตั้งสติและทบทวนสถานที่ล่าสุดที่คาดว่าทำหล่นหาย</li>
                <li>สอบถามจุดประชาสัมพันธ์หรือป้อมยามในบริเวณใกล้เคียง</li>
                <li>กรอกฟอร์ม <strong class="text-primary font-semibold">"แจ้งของหาย"</strong> ในระบบของเรา โดยระบุรายละเอียดให้ชัดเจนที่สุด</li>
                <li>หมั่นเข้ามาตรวจสอบการแจ้งเตือน หรือค้นหาในหน้า "รายการประกาศ" เผื่อมีผู้พบเจอแล้วนำมาลงประกาศ</li>
            </ul>
        </div>

        <div class="mb-12 bg-white p-6 sm:p-8 rounded-lg shadow-sm border border-gray-200">
            <h2 class="text-xl font-bold text-primary border-b border-gray-200 pb-3 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                คำถามที่พบบ่อย (FAQs)
            </h2>
            
            <div class="space-y-6">
                <div>
                    <h4 class="font-bold text-primary mb-2">Q: ข้อมูลเบอร์โทรศัพท์ของฉันจะถูกเปิดเผยหรือไม่?</h4>
                    <p class="text-gray-600 bg-gray-50 p-4 rounded-md border border-gray-100">A: ระบบจะไม่แสดงเบอร์โทรศัพท์ของคุณต่อสาธารณะ ข้อมูลส่วนนี้จะถูกใช้เพื่อการติดต่อกรณีที่ยืนยันสิทธิ์สำเร็จหรือมีเรื่องเร่งด่วนเท่านั้น</p>
                </div>

                <div>
                    <h4 class="font-bold text-primary mb-2">Q: ถ้าเจอของหาย แต่ไม่สะดวกนำไปฝากไว้ที่ส่วนกลาง ควรทำอย่างไร?</h4>
                    <p class="text-gray-600 bg-gray-50 p-4 rounded-md border border-gray-100">A: คุณสามารถกรอกฟอร์ม "แจ้งพบของ" และเลือกระบุสถานที่จัดเก็บเป็น "เก็บไว้กับตัว" ได้ เมื่อมีผู้ติดต่อมาอ้างสิทธิ์และตรวจสอบถูกต้องแล้ว ค่อยนัดหมายสถานที่ส่งมอบกันอีกครั้ง</p>
                </div>

                <div>
                    <h4 class="font-bold text-primary mb-2">Q: การยืนยันสิทธิ์ความเป็นเจ้าของมีขั้นตอนอย่างไร?</h4>
                    <p class="text-gray-600 bg-gray-50 p-4 rounded-md border border-gray-100">A: ผู้ที่ทำของหายจะต้องตอบคำถามถึง "ลักษณะเฉพาะ" ที่ผู้พบได้ตั้งไว้ (เช่น ตำหนิ, รหัสผ่าน, รูปหน้าจอ) หากข้อมูลตรงกัน จึงจะถือว่าเป็นการยืนยันสิทธิ์เบื้องต้น</p>
                </div>
            </div>
        </div>

        <div class="bg-primary text-white rounded-lg p-8 text-center shadow-md">
            <h3 class="text-2xl font-bold mb-3">ต้องการความช่วยเหลือเพิ่มเติม?</h3>
            <p class="text-gray-300 mb-6 max-w-2xl mx-auto">หากคุณพบปัญหาการใช้งานระบบ แจ้งเบาะแส หรือต้องการติดต่อผู้ดูแลระบบ สามารถติดต่อเราได้ทันที</p>
            <a href="mailto:support@lostfound.example.com" class="inline-block px-6 py-3 bg-white text-primary font-bold rounded-md hover:bg-gray-100 transition shadow-sm">ติดต่อผู้ดูแลระบบ</a>
        </div>

    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
