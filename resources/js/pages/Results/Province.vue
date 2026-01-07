<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
    provinceId: {
        type: [String, Number],
        required: true
    }
})

const province = ref(null)
const loading = ref(true)

onMounted(async () => {
    try {
        const response = await fetch(`/api/provinces/${props.provinceId}`)
        if (response.ok) {
            province.value = await response.json()
        }
    } catch (error) {
        console.error('Failed to fetch province:', error)
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">
                ผลการเลือกตั้ง - {{ province?.name_th || 'จังหวัด' }}
            </h1>

            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <div v-else class="bg-white shadow rounded-lg p-6">
                <p class="text-gray-600">กำลังรอข้อมูลผลการเลือกตั้งจังหวัด...</p>
            </div>
        </div>
    </div>
</template>
