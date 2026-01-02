<template>
    <AdminLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('admin.news.index')"
                    class="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100"
                >
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">แก้ไขข่าว</h1>
                    <p class="text-gray-600 truncate max-w-md">{{ article.title }}</p>
                </div>
            </div>

            <!-- Form -->
            <form class="bg-white rounded-lg shadow p-6 space-y-6" @submit.prevent="submit">
                <!-- Basic Info -->
                <div class="space-y-4">
                    <h2 class="text-lg font-medium text-gray-900 border-b pb-2">ข้อมูลข่าว</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            หัวข้อข่าว <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :class="{ 'border-red-500': form.errors.title }"
                        />
                        <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">สรุปข่าว</label>
                        <textarea
                            v-model="form.excerpt"
                            rows="2"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            เนื้อหา <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="form.content"
                            rows="10"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :class="{ 'border-red-500': form.errors.content }"
                        ></textarea>
                        <p v-if="form.errors.content" class="mt-1 text-sm text-red-500">
                            {{ form.errors.content }}
                        </p>
                    </div>
                </div>

                <!-- Source & Author -->
                <div class="space-y-4">
                    <h2 class="text-lg font-medium text-gray-900 border-b pb-2">แหล่งข่าว</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                แหล่งข่าว <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.news_source_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option
                                    v-for="source in sources"
                                    :key="source.id"
                                    :value="source.id"
                                >
                                    {{ source.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >ผู้เขียน</label
                            >
                            <input
                                v-model="form.author"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >URL ข่าวต้นฉบับ</label
                            >
                            <input
                                v-model="form.url"
                                type="url"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >URL รูปภาพ</label
                            >
                            <input
                                v-model="form.image_url"
                                type="url"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="space-y-4">
                    <h2 class="text-lg font-medium text-gray-900 border-b pb-2">ข้อมูลเพิ่มเติม</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                วันที่เผยแพร่ <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.published_at"
                                type="datetime-local"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >ความรู้สึก</label
                            >
                            <select
                                v-model="form.sentiment"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">ไม่ระบุ</option>
                                <option
                                    v-for="sentiment in sentiments"
                                    :key="sentiment"
                                    :value="sentiment"
                                >
                                    {{ sentimentLabels[sentiment] }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >คะแนนความรู้สึก</label
                            >
                            <input
                                v-model.number="form.sentiment_score"
                                type="number"
                                min="-1"
                                max="1"
                                step="0.1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="space-y-4">
                    <h2 class="text-lg font-medium text-gray-900 border-b pb-2">การตั้งค่า</h2>

                    <div class="flex flex-wrap gap-6">
                        <div class="flex items-center gap-2">
                            <input
                                id="is_approved"
                                v-model="form.is_approved"
                                type="checkbox"
                                class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                            />
                            <label for="is_approved" class="text-sm text-gray-700">เผยแพร่</label>
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                id="is_featured"
                                v-model="form.is_featured"
                                type="checkbox"
                                class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                            />
                            <label for="is_featured" class="text-sm text-gray-700">ข่าวแนะนำ</label>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <Link
                        :href="route('admin.news.index')"
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                    >
                        ยกเลิก
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'กำลังบันทึก...' : 'บันทึกการเปลี่ยนแปลง' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    article: Object,
    sources: Array,
    sentiments: Array,
});

const formatDateForInput = (date) => {
    if (!date) {
        return '';
    }
    const d = new Date(date);
    return d.toISOString().slice(0, 16);
};

const form = useForm({
    news_source_id: props.article.news_source_id,
    title: props.article.title,
    excerpt: props.article.excerpt || '',
    content: props.article.content,
    url: props.article.url || '',
    image_url: props.article.image_url || '',
    author: props.article.author || '',
    published_at: formatDateForInput(props.article.published_at),
    sentiment: props.article.sentiment || '',
    sentiment_score: props.article.sentiment_score,
    is_featured: props.article.is_featured,
    is_approved: props.article.is_approved,
});

const sentimentLabels = {
    positive: 'เชิงบวก',
    neutral: 'กลาง',
    negative: 'เชิงลบ',
};

const ArrowLeftIcon = {
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>',
};

const submit = () => {
    form.put(route('admin.news.update', props.article.id));
};
</script>
