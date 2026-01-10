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
    site_logo: null,
    site_favicon: null,
    maintenance_mode: props.settings.maintenance_mode || false,
    auto_refresh_interval: props.settings.auto_refresh_interval || 30,
    news_fetch_enabled: props.settings.news_fetch_enabled !== false,
    news_fetch_interval: props.settings.news_fetch_interval || 300,
    results_scrape_enabled: props.settings.results_scrape_enabled !== false,
    results_scrape_interval: props.settings.results_scrape_interval || 60,
});

const currentLogo = computed(() => {
    if (props.settings.site_logo) {
        return `/storage/${props.settings.site_logo}`;
    }
    return null;
});

const currentFavicon = computed(() => {
    if (props.settings.site_favicon) {
        return `/storage/${props.settings.site_favicon}`;
    }
    return null;
});

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.site_logo = file;
    }
};

const handleFaviconChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.site_favicon = file;
    }
};

const saveSettings = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            console.log('Settings saved successfully');
            // Reset file inputs after successful upload
            form.site_logo = null;
            form.site_favicon = null;
            // Reload page to show new logo/favicon
            window.location.reload();
        },
        onError: (errors) => {
            console.error('Failed to save settings:', errors);
        },
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
                <!-- Appearance Settings -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">รูปลักษณ์</h2>

                    <div class="space-y-4">
                        <!-- Logo Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"
                                >โลโก้เว็บไซต์</label
                            >
                            <div v-if="currentLogo" class="mb-2">
                                <img
                                    :src="currentLogo"
                                    alt="Current Logo"
                                    class="h-16 w-auto object-contain border border-gray-200 rounded p-2"
                                />
                            </div>
                            <input
                                type="file"
                                accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                @change="handleLogoChange"
                            />
                            <p class="mt-1 text-sm text-gray-500">
                                PNG, JPG, JPEG หรือ SVG (สูงสุด 2MB)
                            </p>
                            <p v-if="form.errors.site_logo" class="mt-1 text-sm text-red-600">
                                {{ form.errors.site_logo }}
                            </p>
                        </div>

                        <!-- Favicon Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"
                                >Favicon</label
                            >
                            <div v-if="currentFavicon" class="mb-2">
                                <img
                                    :src="currentFavicon"
                                    alt="Current Favicon"
                                    class="h-8 w-auto object-contain border border-gray-200 rounded p-1"
                                />
                            </div>
                            <input
                                type="file"
                                accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/x-icon"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                @change="handleFaviconChange"
                            />
                            <p class="mt-1 text-sm text-gray-500">
                                PNG, JPG, JPEG, SVG หรือ ICO (สูงสุด 1MB)
                            </p>
                            <p v-if="form.errors.site_favicon" class="mt-1 text-sm text-red-600">
                                {{ form.errors.site_favicon }}
                            </p>
                        </div>
                    </div>
                </div>

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
                <div class="flex justify-end gap-4">
                    <button
                        type="button"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm"
                        @click="() => console.log('Form data:', form)"
                    >
                        Debug Form
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium transition-all"
                        @click="() => console.log('Submit clicked, processing:', form.processing)"
                    >
                        <span v-if="form.processing">
                            <svg
                                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            กำลังบันทึก...
                        </span>
                        <span v-else>บันทึกการตั้งค่า</span>
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
