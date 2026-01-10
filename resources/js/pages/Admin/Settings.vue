<script setup>
import { ref } from 'vue';

const settings = ref({
    siteName: 'ThaiVote',
    siteDescription: 'ระบบรายงานผลเลือกตั้งแบบเรียลไทม์',
    maintenanceMode: false,
});

const saving = ref(false);

const saveSettings = async () => {
    saving.value = true;
    try {
        // TODO: Implement settings save
        await new Promise((resolve) => setTimeout(resolve, 1000));
        alert('บันทึกการตั้งค่าเรียบร้อยแล้ว');
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">ตั้งค่าระบบ</h1>

            <div class="bg-white shadow rounded-lg p-6">
                <form class="space-y-6" @submit.prevent="saveSettings">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ชื่อเว็บไซต์</label>
                        <input
                            v-model="settings.siteName"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">คำอธิบาย</label>
                        <textarea
                            v-model="settings.siteDescription"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center">
                        <input
                            v-model="settings.maintenanceMode"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label class="ml-2 block text-sm text-gray-700">โหมดบำรุงรักษา</label>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="saving"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ saving ? 'กำลังบันทึก...' : 'บันทึก' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
