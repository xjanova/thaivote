<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    articleId: {
        type: [String, Number],
        required: true,
    },
});

const article = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await fetch(`/api/news/${props.articleId}`);
        if (response.ok) {
            article.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to fetch article:', error);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <article v-else-if="article" class="bg-white shadow rounded-lg p-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ article.title }}</h1>
                <!-- eslint-disable-next-line vue/no-v-html -- Content is sanitized server-side -->
                <div class="prose max-w-none" v-html="article.content"></div>
            </article>

            <div v-else class="bg-white shadow rounded-lg p-6">
                <p class="text-gray-600">ไม่พบบทความ</p>
            </div>
        </div>
    </div>
</template>
