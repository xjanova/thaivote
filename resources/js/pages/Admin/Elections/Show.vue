<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('admin.elections.index')"
                        class="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ election.name }}</h1>
                        <p v-if="election.name_en" class="text-gray-600">{{ election.name_en }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.elections.edit', election.id)"
                        class="px-4 py-2 text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50"
                    >
                        แก้ไข
                    </Link>
                </div>
            </div>

            <!-- Status & Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-500">สถานะ</p>
                    <span
                        :class="[
                            'inline-block mt-1 px-3 py-1 text-sm font-medium rounded-full',
                            statusColors[election.status],
                        ]"
                    >
                        {{ statusLabels[election.status] }}
                    </span>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-500">ประเภท</p>
                    <p class="text-lg font-semibold text-gray-900">
                        {{ typeLabels[election.type] }}
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-500">วันเลือกตั้ง</p>
                    <p class="text-lg font-semibold text-gray-900">
                        {{ formatDate(election.election_date) }}
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-500">Active</p>
                    <span
                        :class="[
                            'inline-block mt-1 px-3 py-1 text-sm font-medium rounded-full',
                            election.is_active
                                ? 'bg-green-100 text-green-800'
                                : 'bg-gray-100 text-gray-600',
                        ]"
                    >
                        {{ election.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-500">ผู้มีสิทธิเลือกตั้ง</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ formatNumber(election.total_eligible_voters || 0) }}
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-500">ผู้มาใช้สิทธิ</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ formatNumber(election.total_votes_cast || 0) }}
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-500">อัตราการมาใช้สิทธิ</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ election.voter_turnout || 0 }}%
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-500">ผู้สมัคร</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ election.candidates_count || 0 }}
                    </p>
                </div>
            </div>

            <!-- Description -->
            <div v-if="election.description" class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-2">คำอธิบาย</h2>
                <p class="text-gray-600">{{ election.description }}</p>
            </div>

            <!-- Election Stats -->
            <div v-if="election.stats" class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">สถิติการนับคะแนน</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">บัตรดี</p>
                        <p class="text-xl font-semibold">
                            {{ formatNumber(election.stats.valid_votes || 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">บัตรเสีย</p>
                        <p class="text-xl font-semibold">
                            {{ formatNumber(election.stats.invalid_votes || 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">ไม่ประสงค์ลงคะแนน</p>
                        <p class="text-xl font-semibold">
                            {{ formatNumber(election.stats.no_vote || 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">ความคืบหน้า</p>
                        <p class="text-xl font-semibold">
                            {{ election.stats.counting_progress || 0 }}%
                        </p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-4">
                    <div class="flex justify-between text-sm text-gray-500 mb-1">
                        <span>เขตที่นับแล้ว</span>
                        <span
                            >{{ election.stats.constituencies_counted || 0 }} /
                            {{ election.stats.constituencies_total || 0 }}</span
                        >
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div
                            class="bg-blue-600 h-2 rounded-full"
                            :style="{
                                width: `${(election.stats.constituencies_counted / election.stats.constituencies_total) * 100 || 0}%`,
                            }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Recent Candidates -->
            <div
                v-if="election.candidates && election.candidates.length > 0"
                class="bg-white rounded-lg shadow p-6"
            >
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-gray-900">ผู้สมัครล่าสุด</h2>
                    <Link
                        :href="route('admin.candidates.index', { election_id: election.id })"
                        class="text-sm text-blue-600 hover:text-blue-800"
                    >
                        ดูทั้งหมด
                    </Link>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="candidate in election.candidates"
                        :key="candidate.id"
                        class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg"
                    >
                        <div
                            class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                            :style="{ backgroundColor: candidate.party?.color || '#6b7280' }"
                        >
                            {{ candidate.first_name?.charAt(0) }}
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">
                                {{ candidate.title }} {{ candidate.first_name }}
                                {{ candidate.last_name }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ candidate.party?.name_th || 'ไม่สังกัดพรรค' }}
                            </p>
                        </div>
                        <span class="text-sm text-gray-500">
                            หมายเลข {{ candidate.candidate_number }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">การดำเนินการ</h2>
                <div class="flex flex-wrap gap-3">
                    <button
                        :disabled="election.status === 'ongoing'"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
                        @click="updateStatus('ongoing')"
                    >
                        เริ่มการเลือกตั้ง
                    </button>
                    <button
                        :disabled="election.status === 'counting'"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        @click="updateStatus('counting')"
                    >
                        เริ่มนับคะแนน
                    </button>
                    <button
                        :disabled="election.status === 'completed'"
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 disabled:opacity-50"
                        @click="updateStatus('completed')"
                    >
                        ประกาศผล
                    </button>
                    <button
                        :disabled="election.status === 'cancelled'"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
                        @click="updateStatus('cancelled')"
                    >
                        ยกเลิก
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    election: Object,
});

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

const ArrowLeftIcon = {
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>',
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

const formatNumber = (num) => new Intl.NumberFormat('th-TH').format(num);

const updateStatus = (status) => {
    router.post(route('admin.elections.status', props.election.id), { status });
};
</script>
