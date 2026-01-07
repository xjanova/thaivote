<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">ผู้สมัครรับเลือกตั้ง</h1>
                    <p class="text-gray-600">จัดการข้อมูลผู้สมัครรับเลือกตั้งทั้งหมด</p>
                </div>
                <Link
                    :href="route('admin.candidates.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <PlusIcon class="w-5 h-5" />
                    เพิ่มผู้สมัคร
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-2">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="ค้นหาผู้สมัคร..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @input="debouncedSearch"
                        />
                    </div>
                    <select
                        v-model="selectedParty"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">ทุกพรรค</option>
                        <option v-for="party in parties" :key="party.id" :value="party.id">
                            {{ party.name_th }}
                        </option>
                    </select>
                    <select
                        v-model="selectedElection"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">ทุกการเลือกตั้ง</option>
                        <option
                            v-for="election in elections"
                            :key="election.id"
                            :value="election.id"
                        >
                            {{ election.name }}
                        </option>
                    </select>
                    <select
                        v-model="selectedType"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">ทุกประเภท</option>
                        <option value="constituency">แบ่งเขต</option>
                        <option value="party_list">บัญชีรายชื่อ</option>
                    </select>
                </div>
            </div>

            <!-- Candidates List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                ผู้สมัคร
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                พรรค
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                ประเภท
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                เขต/จังหวัด
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
                            v-for="candidate in candidates.data"
                            :key="candidate.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="candidate.photo"
                                        :src="`/storage/${candidate.photo}`"
                                        :alt="candidate.first_name"
                                        class="w-10 h-10 rounded-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500"
                                    >
                                        {{ candidate.first_name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ candidate.title }} {{ candidate.first_name }}
                                            {{ candidate.last_name }}
                                        </p>
                                        <p v-if="candidate.nickname" class="text-sm text-gray-500">
                                            ({{ candidate.nickname }})
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="candidate.party" class="flex items-center gap-2">
                                    <div
                                        class="w-4 h-4 rounded-full"
                                        :style="{ backgroundColor: candidate.party.color }"
                                    ></div>
                                    <span class="text-sm text-gray-900">{{
                                        candidate.party.name_th
                                    }}</span>
                                </div>
                                <span v-else class="text-sm text-gray-500">อิสระ</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        candidate.type === 'constituency'
                                            ? 'bg-blue-100 text-blue-800'
                                            : 'bg-purple-100 text-purple-800',
                                    ]"
                                >
                                    {{
                                        candidate.type === 'constituency'
                                            ? 'แบ่งเขต'
                                            : 'บัญชีรายชื่อ'
                                    }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <template v-if="candidate.constituency">
                                    {{ candidate.constituency.province?.name_th }} เขต
                                    {{ candidate.constituency.number }}
                                </template>
                                <template v-else-if="candidate.party_list_order">
                                    ลำดับที่ {{ candidate.party_list_order }}
                                </template>
                                <template v-else>-</template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="route('admin.candidates.show', candidate.id)"
                                        class="text-gray-600 hover:text-gray-900"
                                    >
                                        ดู
                                    </Link>
                                    <Link
                                        :href="route('admin.candidates.edit', candidate.id)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        แก้ไข
                                    </Link>
                                    <button
                                        class="text-red-600 hover:text-red-900"
                                        @click="confirmDelete(candidate)"
                                    >
                                        ลบ
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="candidates.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                ไม่พบข้อมูลผู้สมัคร
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="candidates.links && candidates.links.length > 3"
                    class="px-6 py-4 bg-gray-50 border-t border-gray-200"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">
                            แสดง {{ candidates.from }} - {{ candidates.to }} จาก
                            {{ candidates.total }} รายการ
                        </p>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in candidates.links"
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
                        คุณต้องการลบผู้สมัคร
                        <strong
                            >{{ candidateToDelete?.first_name }}
                            {{ candidateToDelete?.last_name }}</strong
                        >
                        หรือไม่?
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
                            @click="deleteCandidate"
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
    candidates: Object,
    filters: Object,
    parties: Array,
    elections: Array,
    provinces: Array,
});

const search = ref(props.filters?.search || '');
const selectedParty = ref(props.filters?.party_id || '');
const selectedElection = ref(props.filters?.election_id || '');
const selectedType = ref(props.filters?.type || '');
const showDeleteModal = ref(false);
const candidateToDelete = ref(null);

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
        route('admin.candidates.index'),
        {
            search: search.value || undefined,
            party_id: selectedParty.value || undefined,
            election_id: selectedElection.value || undefined,
            type: selectedType.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

const confirmDelete = (candidate) => {
    candidateToDelete.value = candidate;
    showDeleteModal.value = true;
};

const deleteCandidate = () => {
    router.delete(route('admin.candidates.destroy', candidateToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            candidateToDelete.value = null;
        },
    });
};
</script>
