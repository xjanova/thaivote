<template>
    <AdminLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('admin.sources.index')" class="p-2 hover:bg-gray-100 rounded-lg">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">เพิ่มแหล่งข่าวใหม่</h1>
                    <p class="text-gray-600">กรอกข้อมูลแหล่งข่าวสำหรับดึงข้อมูลอัตโนมัติ</p>
                </div>
            </div>

            <!-- Form -->
            <form class="bg-white rounded-lg shadow" @submit.prevent="submit">
                <!-- Basic Info -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">ข้อมูลพื้นฐาน</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ชื่อ (อังกฤษ) <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="BBC Thai"
                                required
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.name }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >ชื่อ (ไทย)</label
                            >
                            <input
                                v-model="form.name_th"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="บีบีซีไทย"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >เว็บไซต์</label
                            >
                            <input
                                v-model="form.website"
                                type="url"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="https://www.bbc.com/thai"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >URL โลโก้</label
                            >
                            <input
                                v-model="form.logo"
                                type="url"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="https://example.com/logo.png"
                            />
                        </div>
                    </div>
                </div>

                <!-- Source Type -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">ประเภทแหล่งข้อมูล</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ประเภท <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.type"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                required
                            >
                                <option v-for="type in types" :key="type" :value="type">
                                    {{ type.toUpperCase() }}
                                </option>
                            </select>
                        </div>

                        <div v-if="form.type === 'rss'">
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >RSS URL</label
                            >
                            <input
                                v-model="form.rss_url"
                                type="url"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="https://example.com/rss/feed.xml"
                            />
                        </div>

                        <div v-if="form.type === 'api'">
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >API Endpoint</label
                            >
                            <input
                                v-model="form.api_endpoint"
                                type="url"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="https://api.example.com/news"
                            />
                        </div>

                        <div v-if="form.type === 'scrape'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Scrape Config (JSON)
                            </label>
                            <textarea
                                v-model="scrapeConfigText"
                                rows="6"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                                placeholder='{"url": "...", "selectors": {...}}'
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                รูปแบบ JSON สำหรับกำหนด selectors ในการดึงข้อมูล
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">การตั้งค่า</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >ลำดับความสำคัญ</label
                            >
                            <input
                                v-model="form.priority"
                                type="number"
                                min="0"
                                max="100"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p class="mt-1 text-xs text-gray-500">0-100 (ยิ่งสูงยิ่งสำคัญ)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ช่วงเวลาดึงข้อมูล (วินาที)
                            </label>
                            <input
                                v-model="form.fetch_interval"
                                type="number"
                                min="60"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p class="mt-1 text-xs text-gray-500">ขั้นต่ำ 60 วินาที</p>
                        </div>
                    </div>
                </div>

                <!-- Active Status -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        />
                        <label for="is_active" class="text-sm font-medium text-gray-700">
                            เปิดใช้งานแหล่งข่าว
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="p-6 flex items-center justify-end gap-3">
                    <Link
                        :href="route('admin.sources.index')"
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
import { ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    types: Array,
});

const ArrowLeftIcon = {
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>',
};

const form = useForm({
    name: '',
    name_th: '',
    website: '',
    logo: '',
    rss_url: '',
    api_endpoint: '',
    scrape_config: null,
    type: 'rss',
    priority: 50,
    fetch_interval: 300,
    is_active: true,
});

const scrapeConfigText = ref('');

watch(scrapeConfigText, (value) => {
    try {
        form.scrape_config = value ? JSON.parse(value) : null;
    } catch {
        form.scrape_config = null;
    }
});

const submit = () => {
    form.post(route('admin.sources.store'));
};
</script>
