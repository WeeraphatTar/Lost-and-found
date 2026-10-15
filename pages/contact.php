<?php
$base_url = 'http://localhost/lost-and-found';
require_once '../includes/header.php';
?>

<div class="py-12 bg-background flex-grow">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-primary mb-3">ติดต่อผู้ดูแลระบบ (Contact Admin)</h1>
            <p class="text-gray-600 text-base max-w-2xl mx-auto">
                ยินดีให้ความช่วยเหลือเกี่ยวกับการตรวจสอบคำร้องเคลม การใช้งานระบบ และการประสานงาน
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <!-- Left Card: ข้อมูลการให้บริการและช่องทางติดต่อ -->
            <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-gray-200/80 flex flex-col justify-between">
                <div>
                    <h2 class="text-xl font-bold text-primary border-b border-gray-100 pb-4 mb-6 flex items-center gap-2.5">
                        <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h5m-5 0V11m0 0h-2m2 0h2m-2-4h-2m2 0h2"></path>
                        </svg>
                        ข้อมูลการให้บริการและช่องทางติดต่อ
                    </h2>
                    
                    <div class="space-y-6 text-sm text-gray-700">
                        <div class="flex items-start gap-3.5">
                            <div class="p-2.5 bg-blue-50 text-accent rounded-xl flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <div>
                                <strong class="text-primary font-bold text-base block mb-1">ขอบเขตการดูแล:</strong>
                                <p class="text-gray-600 leading-relaxed">
                                    ตรวจสอบและพิจารณาคำร้องขอรับของคืน (Claim Verification), ดูแลความปลอดภัยของประกาศ และช่วยเหลือกรณีเกิดข้อพิพาท
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5">
                            <div class="p-2.5 bg-blue-50 text-accent rounded-xl flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <strong class="text-primary font-bold text-base block mb-1">อีเมลผู้ดูแลระบบ:</strong>
                                <a href="mailto:admin@gmail.com" class="text-accent hover:underline font-semibold text-base">admin@gmail.com</a>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5">
                            <div class="p-2.5 bg-blue-50 text-accent rounded-xl flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <strong class="text-primary font-bold text-base block mb-1">ระยะเวลาดำเนินการ:</strong>
                                <p class="text-gray-600">ตรวจสอบและพิจารณาคำร้องเคลมภายใน 24 - 48 ชั่วโมง</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Card: กรณีที่ควรติดต่อผู้ดูแลระบบ -->
            <div class="bg-primary text-white p-6 sm:p-8 rounded-2xl shadow-md flex flex-col justify-between">
                <div>
                    <h2 class="text-xl font-bold border-b border-gray-700/60 pb-4 mb-6 flex items-center gap-2.5">
                        <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        กรณีที่ควรติดต่อผู้ดูแลระบบ
                    </h2>
                    
                    <ul class="space-y-4 text-sm text-gray-200 mb-8 leading-relaxed">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-accent flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>มีข้อสงสัยเกี่ยวกับผลการพิจารณาคำร้องขอรับของคืน (กรณีถูกปฏิเสธ)</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-accent flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>พบเห็นการแอบอ้างสิทธิ์ในทรัพย์สิน หรือพฤติกรรมน่าสงสัยในการนัดรับของ</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-accent flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>พบปัญหาทางเทคนิคเกี่ยวกับการแจ้งของหาย/พบของ, การจับคู่ Smart Matching หรือระบบแชท</span>
                        </li>
                    </ul>
                </div>

                <div>
                    <a href="mailto:admin@gmail.com" class="inline-flex items-center justify-center gap-2 w-full text-center px-6 py-3.5 bg-white text-primary font-bold text-base rounded-xl hover:bg-gray-100 transition shadow-sm hover:shadow">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        ส่งอีเมลถึงผู้ดูแลระบบ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
