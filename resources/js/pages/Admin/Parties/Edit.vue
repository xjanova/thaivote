<template>
    <AdminLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('admin.parties.index')"
                    class="p-2 hover:bg-gray-100 rounded-lg"
                >
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">แก้ไขพรรค {{ party.name_th }}</h1>
                    <p class="text-gray-600">แก้ไขข้อมูลพรรคการเมือง</p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="bg-white rounded-lg shadow">
                <!-- Basic Info -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">ข้อมูลพื้นฐาน</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ชื่อพรรค (ไทย) <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name_th"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                            <p v-if="form.errors.name_th" class="mt-1 text-sm text-red-600">{{ form.errors.name_th }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ชื่อพรรค (อังกฤษ) <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name_en"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                            <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-600">{{ form.errors.name_en }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ชื่อย่อ <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.abbreviation"
                                type="text"
                                maxlength="20"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                            <p v-if="form.errors.abbreviation" class="mt-1 text-sm text-red-600">{{ form.errors.abbreviation }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">หมายเลขพรรค</label>
                            <input
                                v-model="form.party_number"
                                type="number"
                                min="1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                    </div>
                </div>

                <!-- Colors & Logo -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">สี และ โลโก้</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                สีหลัก <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model="form.color"
                                    type="color"
                                    class="w-12 h-12 rounded cursor-pointer"
                                >
                                <input
                                    v-model="form.color"
                                    type="text"
                                    pattern="^#[0-9A-Fa-f]{6}$"
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    required
                                >
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">สีรอง</label>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model="form.secondary_color"
                                    type="color"
                                    class="w-12 h-12 rounded cursor-pointer"
                                >
                                <input
                                    v-model="form.secondary_color"
                                    type="text"
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">โลโก้พรรค</label>
                            <div class="flex items-center gap-4">
                                <div class="w-20 h-20 rounded-lg overflow-hidden bg-gray-100">
                                    <img
                                        v-if="logoPreview || party.logo"
                                        :src="logoPreview || `/storage/${party.logo}`"
                                        class="w-full h-full object-cover"
                                    >
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                        <PhotoIcon class="w-8 h-8" />
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <input
                                        type="file"
                                        accept="image/png,image/jpeg,image/svg+xml"
                                        @change="onLogoChange"
                                        class="hidden"
                                        ref="logoInput"
                                    >
                                    <button
                                        type="button"
                                        @click="$refs.logoInput.click()"
                                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                                    >
                                        เปลี่ยนโลโก้
                                    </button>
                                    <p class="mt-1 text-xs text-gray-500">PNG, JPG, SVG ขนาดไม่เกิน 2MB</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leader Info -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">ข้อมูลหัวหน้าพรรค</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อหัวหน้าพรรค</label>
                            <input
                                v-model="form.leader_name"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ปีที่ก่อตั้ง</label>
                            <input
                                v-model="form.founded_year"
                                type="number"
                                min="1900"
                                :max="new Date().getFullYear()"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">ข้อมูลติดต่อ</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">เว็บไซต์</label>
                            <input
                                v-model="form.website"
                                type="url"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Facebook Page</label>
                            <input
                                v-model="form.facebook_page"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Twitter Handle</label>
                            <input
                                v-model="form.twitter_handle"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ที่ตั้งสำนักงานใหญ่</label>
                            <input
                                v-model="form.headquarters"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">รายละเอียดเพิ่มเติม</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">สโลแกน</label>
                            <input
                                v-model="form.slogan"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียดพรรค</label>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Active Status -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            id="is_active"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <label for="is_active" class="text-sm font-medium text-gray-700">
                            เปิดใช้งานพรรค
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="p-6 flex items-center justify-end gap-3">
                    <Link
                        :href="route('admin.parties.index')"
                        class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                    >
                        ยกเลิก
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'กำลังบันทึก...' : 'บันทึก' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    party: Object,
});

const ArrowLeftIcon = { template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>' };
const PhotoIcon = { template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>' };

const form = useForm({
    name_th: props.party.name_th,
    name_en: props.party.name_en,
    abbreviation: props.party.abbreviation,
    color: props.party.color || '#666666',
    secondary_color: props.party.secondary_color || '',
    logo: null,
    description: props.party.description || '',
    slogan: props.party.slogan || '',
    website: props.party.website || '',
    facebook_page: props.party.facebook_page || '',
    twitter_handle: props.party.twitter_handle || '',
    leader_name: props.party.leader_name || '',
    founded_year: props.party.founded_year,
    headquarters: props.party.headquarters || '',
    party_number: props.party.party_number,
    is_active: props.party.is_active,
});

const logoPreview = ref(null);

const onLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('admin.parties.update', props.party.id), {
        forceFormData: true,
        _method: 'PUT',
    });
};
</script>
