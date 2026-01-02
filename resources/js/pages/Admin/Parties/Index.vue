<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">พรรคการเมือง</h1>
                    <p class="text-gray-600">จัดการข้อมูลพรรคการเมืองทั้งหมด</p>
                </div>
                <Link
                    :href="route('admin.parties.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <PlusIcon class="w-5 h-5" />
                    เพิ่มพรรคใหม่
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="ค้นหาพรรค..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @input="debouncedSearch"
                        />
                    </div>
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

            <!-- Party List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                พรรค
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                ย่อ
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                หัวหน้าพรรค
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                สี
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                สถานะ
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                            >
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="party in parties.data" :key="party.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="party.logo"
                                        :src="`/storage/${party.logo}`"
                                        :alt="party.name_th"
                                        class="w-10 h-10 rounded-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                                        :style="{ backgroundColor: party.color }"
                                    >
                                        {{ party.abbreviation.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ party.name_th }}</p>
                                        <p class="text-sm text-gray-500">{{ party.name_en }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ party.abbreviation }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ party.leader_name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-6 h-6 rounded"
                                        :style="{ backgroundColor: party.color }"
                                    ></div>
                                    <span class="text-sm text-gray-500">{{ party.color }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        party.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800',
                                    ]"
                                    @click="toggleActive(party)"
                                >
                                    {{ party.is_active ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="route('admin.parties.edit', party.id)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        แก้ไข
                                    </Link>
                                    <button
                                        class="text-red-600 hover:text-red-900"
                                        @click="confirmDelete(party)"
                                    >
                                        ลบ
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="parties.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                ไม่พบข้อมูลพรรคการเมือง
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="parties.links && parties.links.length > 3"
                    class="px-6 py-4 bg-gray-50 border-t border-gray-200"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">
                            แสดง {{ parties.from }} - {{ parties.to }} จาก
                            {{ parties.total }} รายการ
                        </p>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in parties.links"
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
                        คุณต้องการลบพรรค <strong>{{ partyToDelete?.name_th }}</strong> หรือไม่?
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
                            @click="deleteParty"
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
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    parties: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const activeFilter = ref(props.filters?.active || '');
const showDeleteModal = ref(false);
const partyToDelete = ref(null);

// Simple icon
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
        route('admin.parties.index'),
        {
            search: search.value || undefined,
            active: activeFilter.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

const toggleActive = (party) => {
    router.post(
        route('admin.parties.toggle-active', party.id),
        {},
        {
            preserveState: true,
        }
    );
};

const confirmDelete = (party) => {
    partyToDelete.value = party;
    showDeleteModal.value = true;
};

const deleteParty = () => {
    router.delete(route('admin.parties.destroy', partyToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            partyToDelete.value = null;
        },
    });
};
</script>
