<template>
    <div class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
        <div class="max-w-2xl w-full bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Experimental Warning Banner -->
            <div class="bg-yellow-500 text-yellow-900 px-6 py-4">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <div>
                        <h3 class="font-bold text-lg">Experimental Feature</h3>
                        <p class="text-sm">ฟีเจอร์นี้ยังอยู่ในระหว่างพัฒนา</p>
                    </div>
                </div>
            </div>

            <div class="p-8 text-center">
                <!-- Blockchain Icon -->
                <div
                    class="w-24 h-24 mx-auto mb-6 bg-blue-100 rounded-full flex items-center justify-center"
                >
                    <svg
                        class="w-12 h-12 text-blue-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                        />
                    </svg>
                </div>

                <h1 class="text-3xl font-bold text-gray-900 mb-4">Blockchain Voting</h1>

                <p class="text-gray-600 mb-6">
                    ระบบลงคะแนนบน Blockchain เป็นฟีเจอร์ทดลองที่กำลังพัฒนา ระบบนี้ใช้เทคโนโลยี
                    Zero-Knowledge Proof และ Smart Contract
                    เพื่อรับประกันความโปร่งใสและความเป็นส่วนตัวในการลงคะแนน
                </p>

                <div class="bg-gray-50 rounded-lg p-6 mb-6 text-left">
                    <h2 class="font-semibold text-gray-900 mb-3">คุณสมบัติที่กำลังพัฒนา:</h2>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                            การลงทะเบียนผู้มีสิทธิ์ลงคะแนน
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                            การเชื่อมต่อ Wallet (MetaMask)
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                            การลงคะแนนแบบเข้ารหัส
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                            การตรวจสอบผลลัพธ์บน Blockchain
                        </li>
                    </ul>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a
                        href="/"
                        class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                    >
                        กลับหน้าหลัก
                    </a>
                    <button
                        disabled
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg opacity-50 cursor-not-allowed"
                    >
                        เข้าสู่ระบบลงคะแนน (เร็วๆ นี้)
                    </button>
                </div>
            </div>

            <!-- Footer Notice -->
            <div class="bg-gray-50 px-6 py-4 text-center text-sm text-gray-500">
                <p>ระบบนี้ยังไม่พร้อมใช้งานจริง กรุณาใช้ระบบลงคะแนนปกติตามหน่วยเลือกตั้ง</p>
            </div>
        </div>
    </div>
</template>

<script setup>
// Blockchain voting is experimental - this is a placeholder page
</script>
