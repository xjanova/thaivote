<template>
    <AdminLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('admin.news.index')"
                        class="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">รายละเอียดข่าว</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.news.edit', article.id)"
                        class="px-4 py-2 text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50"
                    >
                        แก้ไข
                    </Link>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-500">สถานะ</p>
                    <span
                        :class="[
                            'inline-block mt-1 px-3 py-1 text-sm font-medium rounded-full',
                            article.is_approved
                                ? 'bg-green-100 text-green-800'
                                : 'bg-yellow-100 text-yellow-800',
                        ]"
                    >
                        {{ article.is_approved ? 'เผยแพร่แล้ว' : 'รอตรวจสอบ' }}
                    </span>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-500">แหล่งข่าว</p>
                    <p class="text-lg font-semibold text-gray-900">
                        {{ article.source?.name || '-' }}
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-500">วันที่เผยแพร่</p>
                    <p class="text-lg font-semibold text-gray-900">
                        {{ formatDate(article.published_at) }}
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-500">ความรู้สึก</p>
                    <span
                        v-if="article.sentiment"
                        :class="[
                            'inline-block mt-1 px-3 py-1 text-sm font-medium rounded-full',
                            sentimentColors[article.sentiment],
                        ]"
                    >
                        {{ sentimentLabels[article.sentiment] }}
                    </span>
                    <p v-else class="text-gray-400">ไม่ระบุ</p>
                </div>
            </div>

            <!-- Article Content -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- Featured Image -->
                <img
                    v-if="article.image_url"
                    :src="article.image_url"
                    :alt="article.title"
                    class="w-full h-64 object-cover"
                />

                <div class="p-6 space-y-4">
                    <!-- Title -->
                    <h2 class="text-2xl font-bold text-gray-900">{{ article.title }}</h2>

                    <!-- Meta -->
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                        <span v-if="article.author">
                            <strong>ผู้เขียน:</strong> {{ article.author }}
                        </span>
                        <span v-if="article.url">
                            <a
                                :href="article.url"
                                target="_blank"
                                class="text-blue-600 hover:underline"
                            >
                                อ่านต้นฉบับ
                            </a>
                        </span>
                        <span
                            v-if="article.is_featured"
                            class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full"
                        >
                            ข่าวแนะนำ
                        </span>
                    </div>

                    <!-- Excerpt -->
                    <div
                        v-if="article.excerpt"
                        class="p-4 bg-gray-50 rounded-lg border-l-4 border-blue-500"
                    >
                        <p class="text-gray-700 italic">{{ article.excerpt }}</p>
                    </div>

                    <!-- Content -->
                    <div class="prose max-w-none">
                        <p class="whitespace-pre-wrap text-gray-700">{{ article.content }}</p>
                    </div>
                </div>
            </div>

            <!-- Related Parties -->
            <div
                v-if="article.parties && article.parties.length > 0"
                class="bg-white rounded-lg shadow p-6"
            >
                <h3 class="text-lg font-medium text-gray-900 mb-4">พรรคที่เกี่ยวข้อง</h3>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="party in article.parties"
                        :key="party.id"
                        class="px-3 py-1 rounded-full text-sm"
                        :style="{ backgroundColor: party.color + '20', color: party.color }"
                    >
                        {{ party.name_th }}
                    </span>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">การดำเนินการ</h3>
                <div class="flex flex-wrap gap-3">
                    <button
                        v-if="!article.is_approved"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                        @click="publish"
                    >
                        เผยแพร่
                    </button>
                    <button
                        v-else
                        class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700"
                        @click="unpublish"
                    >
                        ยกเลิกการเผยแพร่
                    </button>
                    <button
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                        @click="confirmDelete"
                    >
                        ลบข่าว
                    </button>
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
                    <p class="text-gray-600 mb-6">คุณต้องการลบข่าวนี้หรือไม่?</p>
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
    article: Object,
});

const showDeleteModal = ref(false);

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

const ArrowLeftIcon = {
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>',
};

const formatDate = (date) => {
    if (!date) {
        return '-';
    }
    return new Date(date).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const publish = () => {
    router.post(route('admin.news.publish', props.article.id));
};

const unpublish = () => {
    router.post(route('admin.news.unpublish', props.article.id));
};

const confirmDelete = () => {
    showDeleteModal.value = true;
};

const deleteArticle = () => {
    router.delete(route('admin.news.destroy', props.article.id));
};
</script>
