<script setup>
import { ref, onMounted } from 'vue';

const news = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await fetch('/api/news');
        if (response.ok) {
            news.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to fetch news:', error);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">ข่าวการเลือกตั้ง</h1>

            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <div v-else-if="news.length === 0" class="bg-white shadow rounded-lg p-6">
                <p class="text-gray-600">ยังไม่มีข่าวในขณะนี้</p>
            </div>

            <div v-else class="space-y-4">
                <article
                    v-for="article in news"
                    :key="article.id"
                    class="bg-white shadow rounded-lg p-6"
                >
                    <h2 class="text-xl font-semibold text-gray-900">{{ article.title }}</h2>
                    <p class="mt-2 text-gray-600">{{ article.excerpt }}</p>
                </article>
            </div>
        </div>
    </div>
</template>
