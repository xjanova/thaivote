<script setup>
import { ref, onMounted } from 'vue';

const parties = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await fetch('/api/parties');
        if (response.ok) {
            parties.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to fetch parties:', error);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">พรรคการเมือง</h1>

            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <div v-else-if="parties.length === 0" class="bg-white shadow rounded-lg p-6">
                <p class="text-gray-600">ยังไม่มีข้อมูลพรรคการเมือง</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="party in parties"
                    :key="party.id"
                    class="bg-white shadow rounded-lg p-6"
                >
                    <div class="flex items-center space-x-4">
                        <div
                            class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold"
                            :style="{ backgroundColor: party.color || '#666' }"
                        >
                            {{ party.short_name?.[0] || party.name?.[0] || '?' }}
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ party.name }}</h2>
                            <p class="text-sm text-gray-500">{{ party.short_name }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
