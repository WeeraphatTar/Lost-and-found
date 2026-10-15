<?php
$base_url = 'http://localhost/lost-and-found';
require_once '../includes/header.php';
?>

<div class="py-12 bg-background flex-grow">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-center text-primary mb-8">ศูนย์ช่วยเหลือ (Help Center)</h1>

        <!-- Content Box 1: คู่มือการใช้งานระบบ -->
        <div class="mb-8 bg-white p-6 sm:p-8 rounded-lg shadow-sm border border-gray-200">
            <h2 class="text-xl font-bold text-primary border-b border-gray-200 pb-3 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                คำแนะนำขั้นตอนการใช้งานระบบ
            </h2>
            <div class="space-y-4 text-gray-700 leading-relaxed">
                <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-accent text-white font-bold flex items-center justify-center">1</span>
                    <div>
                        <h3 class="font-bold text-primary mb-1">การลงประกาศแจ้งของหาย</h3>
                        <p class="text-sm text-gray-600">หากคุณทำสิ่งของหล่นหาย ให้เข้าสู่ระบบและกดปุ่ม "แจ้งของหาย" เพื่อระบุรายละเอียด ตำแหน่งที่คาดว่าทำหาย วันเวลา รูปภาพ และข้อมูลการติดต่อ</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-accent text-white font-bold flex items-center justify-center">2</span>
                    <div>
                        <h3 class="font-bold text-primary mb-1">การลงประกาศแจ้งพบของ</h3>
                        <p class="text-sm text-gray-600">หากคุณเก็บบันทึกทรัพย์สินของผู้อื่นได้ ให้กดเมนู "แจ้งพบของ" ถ่ายภาพและระบุรายละเอียดสถานที่จัดเก็บไว้กับตัว หรือสถานที่ฝากไว้ที่ส่วนกลาง</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-accent text-white font-bold flex items-center justify-center">3</span>
                    <div>
                        <h3 class="font-bold text-primary mb-1">การยืนยันสิทธิ์ความเป็นเจ้าของ (Claim)</h3>
                        <p class="text-sm text-gray-600">เมื่อพบบันทึกของที่คุณทำหาย สามารถส่งคำขอยืนยันสิทธิ์ พร้อมตอบคำถามลักษณะเฉพาะของทรัพย์สินนั้น เพื่อให้ผู้เก็บได้ตรวจสอบความถูกต้อง</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                    <span class="flex-shrink-0 w-8 h-8 rounded-full bg-accent text-white font-bold flex items-center justify-center">4</span>
                    <div>
                        <h3 class="font-bold text-primary mb-1">การส่งมอบและปิดประกาศ</h3>
                        <p class="text-sm text-gray-600">เมื่อยืนยันสิทธิ์ถูกต้องเรียบร้อยแล้ว ทั้งสองฝ่ายสามารถนัดหมายส่งมอบของ และทำการส่งมอบผ่านระบบเพื่อปิดสถานะประกาศ</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Box 2: ข้อแนะนำความปลอดภัย -->
        <div class="bg-white p-6 sm:p-8 rounded-lg shadow-sm border border-gray-200">
            <h2 class="text-xl font-bold text-primary border-b border-gray-200 pb-3 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                คำแนะนำความปลอดภัยในการรับ-ส่งมอบทรัพย์สิน
            </h2>
            <ul class="list-disc list-inside text-gray-700 space-y-3 leading-relaxed text-sm">
                <li>ควรนัดพบในสถานที่สาธารณะที่มีผู้คนและกล้องวงจรปิด เช่น จุดบริการส่วนกลาง อาคารเรียน หรือป้อมยาม</li>
                <li>หลีกเลี่ยงการนัดส่งมอบสิ่งของในสถานที่เปลี่ยวหรือในยามวิกาล</li>
                <li>ตรวจสอบสภาพสิ่งของและเอกสารยืนยันตนก่อนทำการส่งมอบทุกครั้ง</li>
                <li>ไม่มีค่าใช้จ่ายในการใช้งานระบบ หากมีการแอบอ้างเรียกเก็บเงินกรุณาแจ้งเจ้าหน้าที่ทันที</li>
            </ul>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
