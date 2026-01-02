<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">ข่าว</h1>
                    <p class="text-gray-600">จัดการข่าวและบทความ</p>
                </div>
                <Link
                    :href="route('admin.news.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <PlusIcon class="w-5 h-5" />
                    เพิ่มข่าวใหม่
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="ค้นหาข่าว..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @input="debouncedSearch"
                        />
                    </div>
                    <select
                        v-model="sourceFilter"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">แหล่งข่าวทั้งหมด</option>
                        <option v-for="source in sources" :key="source.id" :value="source.id">
                            {{ source.name }}
                        </option>
                    </select>
                    <select
                        v-model="approvedFilter"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">สถานะทั้งหมด</option>
                        <option value="1">เผยแพร่แล้ว</option>
                        <option value="0">รอตรวจสอบ</option>
                    </select>
                    <select
                        v-model="sentimentFilter"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">ความรู้สึกทั้งหมด</option>
                        <option value="positive">เชิงบวก</option>
                        <option value="neutral">กลาง</option>
                        <option value="negative">เชิงลบ</option>
                    </select>
                </div>
            </div>

            <!-- Articles List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                ข่าว
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                แหล่งข่าว
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                วันที่
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                ความรู้สึก
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                สถานะ
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                            >
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="article in articles.data"
                            :key="article.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-3">
                                    <img
                                        v-if="article.image_url"
                                        :src="article.image_url"
                                        :alt="article.title"
                                        class="w-16 h-12 object-cover rounded"
                                    />
                                    <div
                                        v-else
                                        class="w-16 h-12 bg-gray-100 rounded flex items-center justify-center"
                                    >
                                        <span class="text-gray-400 text-xs">No image</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate">
                                            {{ article.title }}
                                        </p>
                                        <p v-if="article.author" class="text-sm text-gray-500">
                                            {{ article.author }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ article.source?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(article.published_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    v-if="article.sentiment"
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        sentimentColors[article.sentiment],
                                    ]"
                                >
                                    {{ sentimentLabels[article.sentiment] }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="article.is_approved"
                                        class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800"
                                        @click="unpublishArticle(article)"
                                    >
                                        เผยแพร่แล้ว
                                    </button>
                                    <button
                                        v-else
                                        class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800"
                                        @click="publishArticle(article)"
                                    >
                                        รอตรวจสอบ
                                    </button>
                                    <span
                                        v-if="article.is_featured"
                                        class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800"
                                    >
                                        แนะนำ
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="route('admin.news.show', article.id)"
                                        class="text-gray-600 hover:text-gray-900"
                                    >
                                        ดู
                                    </Link>
                                    <Link
                                        :href="route('admin.news.edit', article.id)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        แก้ไข
                                    </Link>
                                    <button
                                        class="text-red-600 hover:text-red-900"
                                        @click="confirmDelete(article)"
                                    >
                                        ลบ
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="articles.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                ไม่พบข่าว
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="articles.links && articles.links.length > 3"
                    class="px-6 py-4 bg-gray-50 border-t border-gray-200"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">
                            แสดง {{ articles.from }} - {{ articles.to }} จาก
                            {{ articles.total }} รายการ
                        </p>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in articles.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 text-sm rounded',
                                    link.active
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-100',
                                    !link.url && 'opacity-50 cursor-not-allowed',
                                ]"
                            >
                                <span v-html="link.label" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div
                    class="fixed inset-0 bg-black opacity-30"
                    @click="showDeleteModal = false"
                ></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">ยืนยันการลบ</h3>
                    <p class="text-gray-600 mb-6">
                        คุณต้องการลบข่าว
                        <strong class="truncate">{{ articleToDelete?.title }}</strong> หรือไม่?
                    </p>
                    <div class="flex justify-end gap-3">
                        <button
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                            @click="showDeleteModal = false"
                        >
                            ยกเลิก
                        </button>
                        <button
                            class="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700"
                            @click="deleteArticle"
                        >
                            ลบ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    articles: Object,
    sources: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const sourceFilter = ref(props.filters?.source || '');
const approvedFilter = ref(props.filters?.approved ?? '');
const sentimentFilter = ref(props.filters?.sentiment || '');
const showDeleteModal = ref(false);
const articleToDelete = ref(null);

const sentimentLabels = {
    positive: 'เชิงบวก',
    neutral: 'กลาง',
    negative: 'เชิงลบ',
};

const sentimentColors = {
    positive: 'bg-green-100 text-green-800',
    neutral: 'bg-gray-100 text-gray-800',
    negative: 'bg-red-100 text-red-800',
};

const PlusIcon = {
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>',
};

const formatDate = (date) => {
    if (!date) {
        return '-';
    }
    return new Date(date).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
};

const applyFilters = () => {
    router.get(
        route('admin.news.index'),
        {
            search: search.value || undefined,
            source: sourceFilter.value || undefined,
            approved: approvedFilter.value !== '' ? approvedFilter.value : undefined,
            sentiment: sentimentFilter.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

const publishArticle = (article) => {
    router.post(route('admin.news.publish', article.id), {}, { preserveState: true });
};

const unpublishArticle = (article) => {
    router.post(route('admin.news.unpublish', article.id), {}, { preserveState: true });
};

const confirmDelete = (article) => {
    articleToDelete.value = article;
    showDeleteModal.value = true;
};

const deleteArticle = () => {
    router.delete(route('admin.news.destroy', articleToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            articleToDelete.value = null;
        },
    });
};
</script>
