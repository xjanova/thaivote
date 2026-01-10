<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    is_admin: false,
});

const submit = () => {
    form.post(route('admin.users.store'));
};
</script>

<template>
    <AdminLayout title="เพิ่มผู้ใช้งาน">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <a
                    :href="route('admin.users.index')"
                    class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 mb-4"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                    กลับ
                </a>
                <h1 class="text-2xl font-bold text-gray-900">เพิ่มผู้ใช้งาน</h1>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="bg-white rounded-lg shadow p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="ชื่อผู้ใช้งาน"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="email@example.com"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="อย่างน้อย 8 ตัวอักษร"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"
                            >ยืนยันรหัสผ่าน</label
                        >
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="ยืนยันรหัสผ่าน"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input
                        id="is_admin"
                        v-model="form.is_admin"
                        type="checkbox"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                    <label for="is_admin" class="text-sm text-gray-700"> กำหนดให้เป็นแอดมิน </label>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a
                        :href="route('admin.users.index')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                    >
                        ยกเลิก
                    </a>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'กำลังบันทึก...' : 'สร้างผู้ใช้งาน' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
