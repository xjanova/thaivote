<template>
    <AdminLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('admin.elections.index')"
                    class="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100"
                >
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">สร้างการเลือกตั้งใหม่</h1>
                    <p class="text-gray-600">กรอกข้อมูลการเลือกตั้งที่ต้องการสร้าง</p>
                </div>
            </div>

            <!-- Form -->
            <form class="bg-white rounded-lg shadow p-6 space-y-6" @submit.prevent="submit">
                <!-- Basic Info -->
                <div class="space-y-4">
                    <h2 class="text-lg font-medium text-gray-900 border-b pb-2">ข้อมูลพื้นฐาน</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ชื่อการเลือกตั้ง (ไทย) <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.name }"
                                placeholder="เช่น เลือกตั้ง สส. 2566"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ชื่อการเลือกตั้ง (อังกฤษ)
                            </label>
                            <input
                                v-model="form.name_en"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g., General Election 2023"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ประเภท <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.type"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option v-for="type in types" :key="type" :value="type">
                                    {{ typeLabels[type] }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                สถานะ <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.status"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option v-for="status in statuses" :key="status" :value="status">
                                    {{ statusLabels[status] }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">คำอธิบาย</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับการเลือกตั้ง"
                        ></textarea>
                    </div>
                </div>

                <!-- Date & Time -->
                <div class="space-y-4">
                    <h2 class="text-lg font-medium text-gray-900 border-b pb-2">วันและเวลา</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                วันเลือกตั้ง <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.election_date"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.election_date }"
                            />
                            <p v-if="form.errors.election_date" class="mt-1 text-sm text-red-500">
                                {{ form.errors.election_date }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >เวลาเริ่ม</label
                            >
                            <input
                                v-model="form.start_time"
                                type="time"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >เวลาสิ้นสุด</label
                            >
                            <input
                                v-model="form.end_time"
                                type="time"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Voter Info -->
                <div class="space-y-4">
                    <h2 class="text-lg font-medium text-gray-900 border-b pb-2">
                        ข้อมูลผู้มีสิทธิเลือกตั้ง
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                จำนวนผู้มีสิทธิเลือกตั้ง
                            </label>
                            <input
                                v-model.number="form.total_eligible_voters"
                                type="number"
                                min="0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="0"
                            />
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="space-y-4">
                    <h2 class="text-lg font-medium text-gray-900 border-b pb-2">การตั้งค่า</h2>

                    <div class="flex items-center gap-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                        />
                        <label for="is_active" class="text-sm text-gray-700">
                            เปิดใช้งานการเลือกตั้งนี้ (จะปิดการเลือกตั้งอื่นที่เปิดอยู่)
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <Link
                        :href="route('admin.elections.index')"
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                    >
                        ยกเลิก
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'กำลังบันทึก...' : 'สร้างการเลือกตั้ง' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineProps({
    statuses: Array,
    types: Array,
});

const form = useForm({
    name: '',
    name_en: '',
    type: 'general',
    description: '',
    election_date: '',
    start_time: '08:00',
    end_time: '17:00',
    status: 'upcoming',
    total_eligible_voters: null,
    is_active: false,
});

const statusLabels = {
    upcoming: 'รอดำเนินการ',
    ongoing: 'กำลังดำเนินการ',
    counting: 'นับคะแนน',
    completed: 'เสร็จสิ้น',
    cancelled: 'ยกเลิก',
};

const typeLabels = {
    general: 'เลือกตั้งทั่วไป',
    senate: 'เลือกตั้ง สว.',
    local: 'เลือกตั้งท้องถิ่น',
    referendum: 'ประชามติ',
    'by-election': 'เลือกตั้งซ่อม',
};

const ArrowLeftIcon = {
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>',
};

const submit = () => {
    form.post(route('admin.elections.store'));
};
</script>
