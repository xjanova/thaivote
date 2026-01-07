<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
    partyId: {
        type: [String, Number],
        required: true
    }
})

const party = ref(null)
const loading = ref(true)

onMounted(async () => {
    try {
        const response = await fetch(`/api/parties/${props.partyId}`)
        if (response.ok) {
            party.value = await response.json()
        }
    } catch (error) {
        console.error('Failed to fetch party:', error)
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <div v-else-if="party" class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center space-x-6 mb-6">
                    <div
                        class="w-20 h-20 rounded-full flex items-center justify-center text-white text-2xl font-bold"
                        :style="{ backgroundColor: party.color || '#666' }"
                    >
                        {{ party.short_name?.[0] || party.name?.[0] || '?' }}
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ party.name }}</h1>
                        <p class="text-lg text-gray-500">{{ party.short_name }}</p>
                    </div>
                </div>
                <div v-if="party.description" class="prose max-w-none">
                    <p>{{ party.description }}</p>
                </div>
            </div>

            <div v-else class="bg-white shadow rounded-lg p-6">
                <p class="text-gray-600">ไม่พบข้อมูลพรรคการเมือง</p>
            </div>
        </div>
    </div>
</template>
