<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="max-w-md w-full mx-4">
            <!-- Logo / Brand -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">ThaiVote</h1>
                <p class="text-gray-600 mt-2">ระบบรายงานผลเลือกตั้งประเทศไทย</p>
            </div>

            <!-- Register Card -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">สมัครสมาชิก</h2>

                <form @submit.prevent="submit">
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                ชื่อ
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="ชื่อของคุณ"
                                required
                                autofocus
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                อีเมล
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="email@example.com"
                                required
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="password"
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                รหัสผ่าน
                            </label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)"
                                required
                            />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="password_confirmation"
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                ยืนยันรหัสผ่าน
                            </label>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="ยืนยันรหัสผ่าน"
                                required
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full mt-6 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        {{ form.processing ? 'กำลังสมัคร...' : 'สมัครสมาชิก' }}
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        มีบัญชีอยู่แล้ว?
                        <Link href="/login" class="text-blue-600 hover:underline">
                            เข้าสู่ระบบ
                        </Link>
                    </p>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="mt-6 text-center">
                <Link href="/" class="text-sm text-gray-500 hover:text-gray-700">
                    &larr; กลับหน้าแรก
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
