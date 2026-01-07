<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">แหล่งข่าว</h1>
                    <p class="text-gray-600">จัดการแหล่งข่าวสำหรับการดึงข้อมูลอัตโนมัติ</p>
                </div>
                <Link
                    :href="route('admin.sources.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <PlusIcon class="w-5 h-5" />
                    เพิ่มแหล่งข่าว
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="ค้นหาแหล่งข่าว..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @input="debouncedSearch"
                        />
                    </div>
                    <select
                        v-model="selectedType"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">ทุกประเภท</option>
                        <option v-for="type in types" :key="type" :value="type">
                            {{ type.toUpperCase() }}
                        </option>
                    </select>
                    <select
                        v-model="activeFilter"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">ทั้งหมด</option>
                        <option value="1">ใช้งาน</option>
                        <option value="0">ไม่ใช้งาน</option>
                    </select>
                </div>
            </div>

            <!-- Sources List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                แหล่งข่าว
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                ประเภท
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                จำนวนบทความ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                สถานะ
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="source in sources.data" :key="source.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="source.logo"
                                        :src="source.logo"
                                        :alt="source.name"
                                        class="w-10 h-10 rounded object-cover"
                                    />
                                    <div
                                        v-else
                                        class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center text-gray-500"
                                    >
                                        {{ source.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ source.name }}</p>
                                        <p v-if="source.name_th" class="text-sm text-gray-500">
                                            {{ source.name_th }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        source.type === 'rss'
                                            ? 'bg-orange-100 text-orange-800'
                                            : source.type === 'api'
                                            ? 'bg-blue-100 text-blue-800'
                                            : 'bg-purple-100 text-purple-800',
                                    ]"
                                >
                                    {{ source.type.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ source.articles_count || 0 }} บทความ
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        source.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800',
                                    ]"
                                    @click="toggleActive(source)"
                                >
                                    {{ source.is_active ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        class="text-green-600 hover:text-green-900"
                                        @click="fetchSource(source)"
                                    >
                                        ดึงข่าว
                                    </button>
                                    <Link
                                        :href="route('admin.sources.show', source.id)"
                                        class="text-gray-600 hover:text-gray-900"
                                    >
                                        ดู
                                    </Link>
                                    <Link
                                        :href="route('admin.sources.edit', source.id)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        แก้ไข
                                    </Link>
                                    <button
                                        class="text-red-600 hover:text-red-900"
                                        @click="confirmDelete(source)"
                                    >
                                        ลบ
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="sources.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                ไม่พบข้อมูลแหล่งข่าว
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="sources.links && sources.links.length > 3"
                    class="px-6 py-4 bg-gray-50 border-t border-gray-200"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">
                            แสดง {{ sources.from }} - {{ sources.to }} จาก
                            {{ sources.total }} รายการ
                        </p>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in sources.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 text-sm rounded',
                                    link.active
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-100',
                                    !link.url && 'opacity-50 cursor-not-allowed',
                                ]"
                                ><span v-html="link.label"
                            /></Link>
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
                        คุณต้องการลบแหล่งข่าว <strong>{{ sourceToDelete?.name }}</strong> หรือไม่?
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
                            @click="deleteSource"
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
    sources: Object,
    filters: Object,
    types: Array,
});

const search = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.type || '');
const activeFilter = ref(props.filters?.active ?? '');
const showDeleteModal = ref(false);
const sourceToDelete = ref(null);

const PlusIcon = {
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>',
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
        route('admin.sources.index'),
        {
            search: search.value || undefined,
            type: selectedType.value || undefined,
            active: activeFilter.value !== '' ? activeFilter.value : undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

const toggleActive = (source) => {
    router.post(route('admin.sources.toggle-active', source.id), {}, {
        preserveState: true,
    });
};

const fetchSource = (source) => {
    router.post(route('admin.sources.fetch', source.id), {}, {
        preserveState: true,
    });
};

const confirmDelete = (source) => {
    sourceToDelete.value = source;
    showDeleteModal.value = true;
};

const deleteSource = () => {
    router.delete(route('admin.sources.destroy', sourceToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            sourceToDelete.value = null;
        },
    });
};
</script>
