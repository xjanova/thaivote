<template>
    <AdminLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('admin.sources.index')"
                        class="p-2 hover:bg-gray-100 rounded-lg"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ source.name }}</h1>
                        <p class="text-gray-600">รายละเอียดแหล่งข่าว</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        class="px-4 py-2 text-green-700 bg-green-100 rounded-lg hover:bg-green-200"
                        @click="fetchNews"
                    >
                        ดึงข่าวตอนนี้
                    </button>
                    <Link
                        :href="route('admin.sources.edit', source.id)"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        แก้ไข
                    </Link>
                </div>
            </div>

            <!-- Source Info Card -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start gap-6">
                        <div class="flex-shrink-0">
                            <img
                                v-if="source.logo"
                                :src="source.logo"
                                :alt="source.name"
                                class="w-20 h-20 rounded-lg object-cover"
                            />
                            <div
                                v-else
                                class="w-20 h-20 rounded-lg bg-gray-200 flex items-center justify-center text-gray-500 text-2xl font-bold"
                            >
                                {{ source.name.charAt(0) }}
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span
                                    :class="[
                                        'px-3 py-1 text-sm font-medium rounded-full',
                                        source.type === 'rss'
                                            ? 'bg-orange-100 text-orange-800'
                                            : source.type === 'api'
                                              ? 'bg-blue-100 text-blue-800'
                                              : 'bg-purple-100 text-purple-800',
                                    ]"
                                >
                                    {{ source.type.toUpperCase() }}
                                </span>
                                <span
                                    :class="[
                                        'px-3 py-1 text-sm font-medium rounded-full',
                                        source.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800',
                                    ]"
                                >
                                    {{ source.is_active ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
                                </span>
                            </div>
                            <h2 v-if="source.name_th" class="text-lg text-gray-700">
                                {{ source.name_th }}
                            </h2>
                            <a
                                v-if="source.website"
                                :href="source.website"
                                target="_blank"
                                class="text-blue-600 hover:underline"
                            >
                                {{ source.website }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">จำนวนบทความ</p>
                    <p class="text-3xl font-bold text-gray-900">{{ source.articles_count || 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">ลำดับความสำคัญ</p>
                    <p class="text-3xl font-bold text-gray-900">{{ source.priority }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">ช่วงเวลาดึง</p>
                    <p class="text-3xl font-bold text-gray-900">{{ source.fetch_interval }}s</p>
                </div>
            </div>

            <!-- Details -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">รายละเอียดการเชื่อมต่อ</h3>
                    <dl class="grid grid-cols-1 gap-4">
                        <div v-if="source.rss_url">
                            <dt class="text-sm text-gray-500">RSS URL</dt>
                            <dd class="text-gray-900 break-all">{{ source.rss_url }}</dd>
                        </div>
                        <div v-if="source.api_endpoint">
                            <dt class="text-sm text-gray-500">API Endpoint</dt>
                            <dd class="text-gray-900 break-all">{{ source.api_endpoint }}</dd>
                        </div>
                        <div v-if="source.scrape_config">
                            <dt class="text-sm text-gray-500">Scrape Config</dt>
                            <dd class="text-gray-900">
                                <pre class="bg-gray-100 p-3 rounded text-sm overflow-auto">{{
                                    JSON.stringify(source.scrape_config, null, 2)
                                }}</pre>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div v-if="source.last_fetched_at" class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">การดึงข้อมูลล่าสุด</h3>
                    <p class="text-gray-600">{{ formatDate(source.last_fetched_at) }}</p>
                </div>
            </div>

            <!-- Recent Articles -->
            <div v-if="recentArticles.length > 0" class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">บทความล่าสุด</h3>
                </div>
                <ul class="divide-y divide-gray-200">
                    <li
                        v-for="article in recentArticles"
                        :key="article.id"
                        class="p-4 hover:bg-gray-50"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900">{{ article.title }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ formatDate(article.published_at) }}
                                </p>
                            </div>
                            <span
                                :class="[
                                    'px-2 py-1 text-xs font-medium rounded-full',
                                    article.is_approved
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-yellow-100 text-yellow-800',
                                ]"
                            >
                                {{ article.is_approved ? 'อนุมัติ' : 'รอตรวจสอบ' }}
                            </span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    source: Object,
    recentArticles: Array,
});

const ArrowLeftIcon = {
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>',
};

const formatDate = (dateString) => {
    if (!dateString) {
        return '-';
    }
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const fetchNews = () => {
    router.post(route('admin.sources.fetch', props.source.id));
};
</script>
