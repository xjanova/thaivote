<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">การเลือกตั้ง</h1>
                    <p class="text-gray-600">จัดการข้อมูลการเลือกตั้งทั้งหมด</p>
                </div>
                <Link
                    :href="route('admin.elections.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <PlusIcon class="w-5 h-5" />
                    สร้างการเลือกตั้งใหม่
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="ค้นหาการเลือกตั้ง..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @input="debouncedSearch"
                        />
                    </div>
                    <select
                        v-model="statusFilter"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">สถานะทั้งหมด</option>
                        <option v-for="status in statuses" :key="status" :value="status">
                            {{ statusLabels[status] }}
                        </option>
                    </select>
                    <select
                        v-model="typeFilter"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">ประเภททั้งหมด</option>
                        <option v-for="type in types" :key="type" :value="type">
                            {{ typeLabels[type] }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Election List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                การเลือกตั้ง
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                ประเภท
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                วันเลือกตั้ง
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                สถานะ
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                ผู้สมัคร
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                            >
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="election in elections.data"
                            :key="election.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-medium text-gray-900">{{ election.name }}</p>
                                    <p v-if="election.name_en" class="text-sm text-gray-500">
                                        {{ election.name_en }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800"
                                >
                                    {{ typeLabels[election.type] || election.type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(election.election_date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        statusColors[election.status],
                                    ]"
                                >
                                    {{ statusLabels[election.status] || election.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ election.candidates_count || 0 }} คน
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        :class="[
                                            'px-2 py-1 text-xs rounded',
                                            election.is_active
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-gray-100 text-gray-600',
                                        ]"
                                        @click="toggleActive(election)"
                                    >
                                        {{ election.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                    <Link
                                        :href="route('admin.elections.show', election.id)"
                                        class="text-gray-600 hover:text-gray-900"
                                    >
                                        ดู
                                    </Link>
                                    <Link
                                        :href="route('admin.elections.edit', election.id)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        แก้ไข
                                    </Link>
                                    <button
                                        class="text-orange-600 hover:text-orange-900"
                                        @click="duplicateElection(election)"
                                    >
                                        คัดลอก
                                    </button>
                                    <button
                                        class="text-red-600 hover:text-red-900"
                                        @click="confirmDelete(election)"
                                    >
                                        ลบ
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="elections.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                ไม่พบข้อมูลการเลือกตั้ง
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="elections.links && elections.links.length > 3"
                    class="px-6 py-4 bg-gray-50 border-t border-gray-200"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">
                            แสดง {{ elections.from }} - {{ elections.to }} จาก
                            {{ elections.total }} รายการ
                        </p>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in elections.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 text-sm rounded',
                                    link.active
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-white text-gray-700 hover:bg-gray-100',
                                    !link.url && 'opacity-50 cursor-not-allowed',
                                ]"
                            >
                                <span v-html="link.label" />
                            </Link>
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
                        คุณต้องการลบการเลือกตั้ง
                        <strong>{{ electionToDelete?.name }}</strong> หรือไม่?
                        การดำเนินการนี้ไม่สามารถย้อนกลับได้
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
                            @click="deleteElection"
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
    elections: Object,
    filters: Object,
    statuses: Array,
    types: Array,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const typeFilter = ref(props.filters?.type || '');
const showDeleteModal = ref(false);
const electionToDelete = ref(null);

const statusLabels = {
    upcoming: 'รอดำเนินการ',
    ongoing: 'กำลังดำเนินการ',
    counting: 'นับคะแนน',
    completed: 'เสร็จสิ้น',
    cancelled: 'ยกเลิก',
};

const statusColors = {
    upcoming: 'bg-yellow-100 text-yellow-800',
    ongoing: 'bg-green-100 text-green-800',
    counting: 'bg-blue-100 text-blue-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
};

const typeLabels = {
    general: 'เลือกตั้งทั่วไป',
    senate: 'เลือกตั้ง สว.',
    local: 'เลือกตั้งท้องถิ่น',
    referendum: 'ประชามติ',
    'by-election': 'เลือกตั้งซ่อม',
};

const PlusIcon = {
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>',
};

const formatDate = (date) => {
    if (!date) {
        return '-';
    }
    return new Date(date).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
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
        route('admin.elections.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            type: typeFilter.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

const toggleActive = (election) => {
    router.post(route('admin.elections.toggle-active', election.id), {}, { preserveState: true });
};

const duplicateElection = (election) => {
    router.post(route('admin.elections.duplicate', election.id));
};

const confirmDelete = (election) => {
    electionToDelete.value = election;
    showDeleteModal.value = true;
};

const deleteElection = () => {
    router.delete(route('admin.elections.destroy', electionToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            electionToDelete.value = null;
        },
    });
};
</script>
