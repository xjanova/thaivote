<template>
    <AdminLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.candidates.index')" class="p-2 hover:bg-gray-100 rounded-lg">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ candidate.title }} {{ candidate.first_name }} {{ candidate.last_name }}
                        </h1>
                        <p class="text-gray-600">รายละเอียดผู้สมัคร</p>
                    </div>
                </div>
                <Link
                    :href="route('admin.candidates.edit', candidate.id)"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    แก้ไข
                </Link>
            </div>

            <!-- Profile Card -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start gap-6">
                        <div class="flex-shrink-0">
                            <img
                                v-if="candidate.photo"
                                :src="`/storage/${candidate.photo}`"
                                :alt="candidate.first_name"
                                class="w-32 h-32 rounded-lg object-cover"
                            />
                            <div
                                v-else
                                class="w-32 h-32 rounded-lg bg-gray-200 flex items-center justify-center text-gray-500 text-4xl font-bold"
                            >
                                {{ candidate.first_name?.charAt(0) }}
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span
                                    :class="[
                                        'px-3 py-1 text-sm font-medium rounded-full',
                                        candidate.type === 'constituency'
                                            ? 'bg-blue-100 text-blue-800'
                                            : 'bg-purple-100 text-purple-800',
                                    ]"
                                >
                                    {{ candidate.type === 'constituency' ? 'แบ่งเขต' : 'บัญชีรายชื่อ' }}
                                </span>
                                <span
                                    v-if="candidate.is_pm_candidate"
                                    class="px-3 py-1 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800"
                                >
                                    ผู้สมัครนายกฯ
                                </span>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900">
                                หมายเลข {{ candidate.candidate_number }}
                            </h2>
                            <div v-if="candidate.party" class="flex items-center gap-2 mt-2">
                                <div
                                    class="w-4 h-4 rounded-full"
                                    :style="{ backgroundColor: candidate.party.color }"
                                ></div>
                                <span class="text-gray-700">{{ candidate.party.name_th }}</span>
                            </div>
                            <p v-else class="text-gray-500 mt-2">ผู้สมัครอิสระ</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">ข้อมูลทั่วไป</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-gray-500">การเลือกตั้ง</dt>
                            <dd class="text-gray-900">{{ candidate.election?.name || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">เขตเลือกตั้ง</dt>
                            <dd class="text-gray-900">
                                <template v-if="candidate.constituency">
                                    {{ candidate.constituency.province?.name_th }} เขต {{ candidate.constituency.number }}
                                </template>
                                <template v-else-if="candidate.party_list_order">
                                    บัญชีรายชื่อลำดับที่ {{ candidate.party_list_order }}
                                </template>
                                <template v-else>-</template>
                            </dd>
                        </div>
                        <div v-if="candidate.nickname">
                            <dt class="text-sm text-gray-500">ชื่อเล่น</dt>
                            <dd class="text-gray-900">{{ candidate.nickname }}</dd>
                        </div>
                        <div v-if="candidate.birth_date">
                            <dt class="text-sm text-gray-500">วันเกิด</dt>
                            <dd class="text-gray-900">{{ formatDate(candidate.birth_date) }}</dd>
                        </div>
                        <div v-if="candidate.education">
                            <dt class="text-sm text-gray-500">การศึกษา</dt>
                            <dd class="text-gray-900">{{ candidate.education }}</dd>
                        </div>
                        <div v-if="candidate.occupation">
                            <dt class="text-sm text-gray-500">อาชีพ</dt>
                            <dd class="text-gray-900">{{ candidate.occupation }}</dd>
                        </div>
                    </dl>
                </div>

                <div v-if="candidate.biography" class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">ประวัติ</h3>
                    <p class="text-gray-700 whitespace-pre-line">{{ candidate.biography }}</p>
                </div>

                <div v-if="candidate.social_media && Object.keys(candidate.social_media).length > 0" class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">โซเชียลมีเดีย</h3>
                    <div class="flex flex-wrap gap-4">
                        <a
                            v-if="candidate.social_media.facebook"
                            :href="`https://facebook.com/${candidate.social_media.facebook}`"
                            target="_blank"
                            class="text-blue-600 hover:underline"
                        >
                            Facebook
                        </a>
                        <a
                            v-if="candidate.social_media.twitter"
                            :href="`https://twitter.com/${candidate.social_media.twitter}`"
                            target="_blank"
                            class="text-blue-400 hover:underline"
                        >
                            Twitter
                        </a>
                        <a
                            v-if="candidate.social_media.instagram"
                            :href="`https://instagram.com/${candidate.social_media.instagram}`"
                            target="_blank"
                            class="text-pink-600 hover:underline"
                        >
                            Instagram
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    candidate: Object,
});

const ArrowLeftIcon = {
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>',
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>
