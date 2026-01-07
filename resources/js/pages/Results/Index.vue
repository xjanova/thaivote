<script setup>
import { ref, onMounted } from 'vue'

const results = ref([])
const loading = ref(true)

onMounted(async () => {
    try {
        const response = await fetch('/api/results')
        if (response.ok) {
            results.value = await response.json()
        }
    } catch (error) {
        console.error('Failed to fetch results:', error)
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">ผลการเลือกตั้ง</h1>

            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <div v-else class="bg-white shadow rounded-lg p-6">
                <p class="text-gray-600">กำลังรอข้อมูลผลการเลือกตั้ง...</p>
            </div>
        </div>
    </div>
</template>
