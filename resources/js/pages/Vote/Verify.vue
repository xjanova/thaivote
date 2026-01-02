<template>
    <div class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
        <div class="max-w-xl w-full bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Experimental Warning Banner -->
            <div class="bg-yellow-500 text-yellow-900 px-6 py-3">
                <div class="flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <span><strong>Experimental:</strong> ฟีเจอร์นี้ยังอยู่ในระหว่างพัฒนา</span>
                </div>
            </div>

            <div class="p-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">ตรวจสอบการลงคะแนน</h1>
                <p class="text-gray-600 mb-6">
                    ตรวจสอบว่าคะแนนของคุณถูกบันทึกบน Blockchain อย่างถูกต้อง
                </p>

                <form class="space-y-4" @submit.prevent="verify">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Transaction Hash
                        </label>
                        <input
                            v-model="transactionHash"
                            type="text"
                            placeholder="0x..."
                            disabled
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled
                        class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg opacity-50 cursor-not-allowed"
                    >
                        ตรวจสอบ (เร็วๆ นี้)
                    </button>
                </form>

                <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium text-gray-900 mb-2">การตรวจสอบจะแสดง:</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>- สถานะการยืนยันบน Blockchain</li>
                        <li>- หมายเลข Block ที่บันทึก</li>
                        <li>- เวลาที่บันทึก</li>
                        <li>- Proof of Inclusion</li>
                    </ul>
                </div>

                <div class="mt-6 text-center">
                    <a href="/vote" class="text-blue-600 hover:text-blue-800">
                        กลับไปหน้าลงคะแนน
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const transactionHash = ref('');

const verify = () => {
    // This feature is experimental and not yet implemented
};
</script>
