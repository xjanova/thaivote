<script setup>
const endpoints = [
    { method: 'GET', path: '/api/elections', description: 'รายการการเลือกตั้งทั้งหมด' },
    { method: 'GET', path: '/api/elections/{id}', description: 'รายละเอียดการเลือกตั้ง' },
    { method: 'GET', path: '/api/elections/{id}/results', description: 'ผลการเลือกตั้ง' },
    { method: 'GET', path: '/api/provinces', description: 'รายการจังหวัดทั้งหมด' },
    { method: 'GET', path: '/api/provinces/{id}', description: 'รายละเอียดจังหวัด' },
    { method: 'GET', path: '/api/parties', description: 'รายการพรรคการเมืองทั้งหมด' },
    { method: 'GET', path: '/api/parties/{id}', description: 'รายละเอียดพรรคการเมือง' },
    { method: 'GET', path: '/api/news', description: 'ข่าวการเลือกตั้ง' },
]
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">API Documentation</h1>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Available Endpoints</h2>
                </div>

                <div class="divide-y divide-gray-200">
                    <div v-for="endpoint in endpoints" :key="endpoint.path" class="px-6 py-4">
                        <div class="flex items-center space-x-4">
                            <span
                                class="px-2 py-1 text-xs font-semibold rounded"
                                :class="{
                                    'bg-green-100 text-green-800': endpoint.method === 'GET',
                                    'bg-blue-100 text-blue-800': endpoint.method === 'POST',
                                    'bg-yellow-100 text-yellow-800': endpoint.method === 'PUT',
                                    'bg-red-100 text-red-800': endpoint.method === 'DELETE',
                                }"
                            >
                                {{ endpoint.method }}
                            </span>
                            <code class="text-sm font-mono text-gray-700">{{ endpoint.path }}</code>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">{{ endpoint.description }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
