<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const form = useForm({
    site_name: props.settings.site_name || 'ThaiVote',
    site_description: props.settings.site_description || '',
    maintenance_mode: props.settings.maintenance_mode || false,
    auto_refresh_interval: props.settings.auto_refresh_interval || 30,
    news_fetch_enabled: props.settings.news_fetch_enabled !== false,
    news_fetch_interval: props.settings.news_fetch_interval || 300,
    results_scrape_enabled: props.settings.results_scrape_enabled !== false,
    results_scrape_interval: props.settings.results_scrape_interval || 60,
});

const saveSettings = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout title="ตั้งค่าระบบ">
        <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">ตั้งค่าระบบ</h1>

            <!-- Flash Messages -->
            <div
                v-if="flash.success"
                class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg"
            >
                {{ flash.success }}
            </div>

            <div
                v-if="flash.error"
                class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg"
            >
                {{ flash.error }}
            </div>

            <form class="space-y-6" @submit.prevent="saveSettings">
                <!-- General Settings -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">ตั้งค่าทั่วไป</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700"
                                >ชื่อเว็บไซต์</label
                            >
                            <input
                                v-model="form.site_name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                            <p v-if="form.errors.site_name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.site_name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">คำอธิบาย</label>
                            <textarea
                                v-model="form.site_description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                            <p
                                v-if="form.errors.site_description"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.site_description }}
                            </p>
                        </div>

                        <div class="flex items-center">
                            <input
                                id="maintenance_mode"
                                v-model="form.maintenance_mode"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <label for="maintenance_mode" class="ml-2 block text-sm text-gray-700"
                                >โหมดบำรุงรักษา</label
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700"
                                >รีเฟรชอัตโนมัติ (วินาที)</label
                            >
                            <input
                                v-model.number="form.auto_refresh_interval"
                                type="number"
                                min="10"
                                max="600"
                                class="mt-1 block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                            <p class="mt-1 text-sm text-gray-500">10-600 วินาที</p>
                            <p
                                v-if="form.errors.auto_refresh_interval"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.auto_refresh_interval }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- News Settings -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">ตั้งค่าข่าวสาร</h2>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input
                                id="news_fetch_enabled"
                                v-model="form.news_fetch_enabled"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <label for="news_fetch_enabled" class="ml-2 block text-sm text-gray-700"
                                >เปิดใช้งานดึงข่าวอัตโนมัติ</label
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700"
                                >ความถี่ในการดึงข่าว (วินาที)</label
                            >
                            <input
                                v-model.number="form.news_fetch_interval"
                                type="number"
                                min="60"
                                max="3600"
                                :disabled="!form.news_fetch_enabled"
                                class="mt-1 block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"
                            />
                            <p class="mt-1 text-sm text-gray-500">60-3600 วินาที</p>
                            <p
                                v-if="form.errors.news_fetch_interval"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.news_fetch_interval }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Results Scraping Settings -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">ตั้งค่าดึงผลคะแนน</h2>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input
                                id="results_scrape_enabled"
                                v-model="form.results_scrape_enabled"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <label
                                for="results_scrape_enabled"
                                class="ml-2 block text-sm text-gray-700"
                                >เปิดใช้งานดึงผลคะแนนอัตโนมัติ</label
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700"
                                >ความถี่ในการดึงผลคะแนน (วินาที)</label
                            >
                            <input
                                v-model.number="form.results_scrape_interval"
                                type="number"
                                min="30"
                                max="600"
                                :disabled="!form.results_scrape_enabled"
                                class="mt-1 block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"
                            />
                            <p class="mt-1 text-sm text-gray-500">30-600 วินาที</p>
                            <p
                                v-if="form.errors.results_scrape_interval"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.results_scrape_interval }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 font-medium"
                    >
                        {{ form.processing ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
