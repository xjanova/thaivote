<template>
    <AdminLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.parties.index')" class="p-2 hover:bg-gray-100 rounded-lg">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ party.name_th }}</h1>
                        <p class="text-gray-600">{{ party.name_en }}</p>
                    </div>
                </div>
                <Link
                    :href="route('admin.parties.edit', party.id)"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    แก้ไข
                </Link>
            </div>

            <!-- Party Profile Card -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start gap-6">
                        <div class="flex-shrink-0">
                            <img
                                v-if="party.logo"
                                :src="`/storage/${party.logo}`"
                                :alt="party.name_th"
                                class="w-24 h-24 rounded-lg object-cover"
                            />
                            <div
                                v-else
                                class="w-24 h-24 rounded-lg flex items-center justify-center text-white text-3xl font-bold"
                                :style="{ backgroundColor: party.color }"
                            >
                                {{ party.abbreviation?.charAt(0) }}
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span
                                    class="px-3 py-1 text-sm font-medium rounded-full"
                                    :style="{
                                        backgroundColor: party.color + '20',
                                        color: party.color,
                                    }"
                                >
                                    {{ party.abbreviation }}
                                </span>
                                <span
                                    v-if="party.party_number"
                                    class="px-3 py-1 text-sm font-medium rounded-full bg-gray-100 text-gray-800"
                                >
                                    หมายเลข {{ party.party_number }}
                                </span>
                                <span
                                    :class="[
                                        'px-3 py-1 text-sm font-medium rounded-full',
                                        party.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800',
                                    ]"
                                >
                                    {{ party.is_active ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
                                </span>
                            </div>
                            <p v-if="party.slogan" class="text-gray-600 italic mb-2">
                                "{{ party.slogan }}"
                            </p>
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-6 h-6 rounded"
                                    :style="{ backgroundColor: party.color }"
                                    title="สีหลัก"
                                ></div>
                                <div
                                    v-if="party.secondary_color"
                                    class="w-6 h-6 rounded"
                                    :style="{ backgroundColor: party.secondary_color }"
                                    title="สีรอง"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">ผู้สมัครทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900">{{ party.candidates?.length || 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">ผลคะแนนระดับชาติ</p>
                    <p class="text-3xl font-bold text-gray-900">{{ party.national_results?.length || 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-500">ผลคะแนนระดับจังหวัด</p>
                    <p class="text-3xl font-bold text-gray-900">{{ party.province_results?.length || 0 }}</p>
                </div>
            </div>

            <!-- Details -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">ข้อมูลพรรค</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="party.leader_name">
                            <dt class="text-sm text-gray-500">หัวหน้าพรรค</dt>
                            <dd class="text-gray-900 flex items-center gap-2">
                                <img
                                    v-if="party.leader_photo"
                                    :src="`/storage/${party.leader_photo}`"
                                    class="w-8 h-8 rounded-full object-cover"
                                />
                                {{ party.leader_name }}
                            </dd>
                        </div>
                        <div v-if="party.founded_year">
                            <dt class="text-sm text-gray-500">ปีที่ก่อตั้ง</dt>
                            <dd class="text-gray-900">พ.ศ. {{ party.founded_year + 543 }}</dd>
                        </div>
                        <div v-if="party.headquarters">
                            <dt class="text-sm text-gray-500">ที่ตั้งสำนักงานใหญ่</dt>
                            <dd class="text-gray-900">{{ party.headquarters }}</dd>
                        </div>
                        <div v-if="party.website">
                            <dt class="text-sm text-gray-500">เว็บไซต์</dt>
                            <dd>
                                <a
                                    :href="party.website"
                                    target="_blank"
                                    class="text-blue-600 hover:underline"
                                >
                                    {{ party.website }}
                                </a>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div v-if="party.description" class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">รายละเอียด</h3>
                    <p class="text-gray-700 whitespace-pre-line">{{ party.description }}</p>
                </div>

                <div
                    v-if="party.facebook_page || party.twitter_handle"
                    class="p-6 border-b border-gray-200"
                >
                    <h3 class="text-lg font-medium text-gray-900 mb-4">โซเชียลมีเดีย</h3>
                    <div class="flex flex-wrap gap-4">
                        <a
                            v-if="party.facebook_page"
                            :href="`https://facebook.com/${party.facebook_page}`"
                            target="_blank"
                            class="text-blue-600 hover:underline"
                        >
                            Facebook: {{ party.facebook_page }}
                        </a>
                        <a
                            v-if="party.twitter_handle"
                            :href="`https://twitter.com/${party.twitter_handle.replace('@', '')}`"
                            target="_blank"
                            class="text-blue-400 hover:underline"
                        >
                            Twitter: {{ party.twitter_handle }}
                        </a>
                    </div>
                </div>

                <div v-if="party.api_key" class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">API Key</h3>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 px-3 py-2 bg-gray-100 rounded font-mono text-sm">
                            {{ party.api_key }}
                        </code>
                        <button
                            class="px-3 py-2 text-gray-600 hover:bg-gray-100 rounded"
                            @click="copyApiKey"
                        >
                            คัดลอก
                        </button>
                    </div>
                </div>
            </div>

            <!-- Candidates List -->
            <div v-if="party.candidates && party.candidates.length > 0" class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        ผู้สมัครในสังกัด ({{ party.candidates.length }} คน)
                    </h3>
                </div>
                <ul class="divide-y divide-gray-200">
                    <li
                        v-for="candidate in party.candidates.slice(0, 10)"
                        :key="candidate.id"
                        class="p-4 hover:bg-gray-50"
                    >
                        <div class="flex items-center gap-3">
                            <img
                                v-if="candidate.photo"
                                :src="`/storage/${candidate.photo}`"
                                class="w-10 h-10 rounded-full object-cover"
                            />
                            <div
                                v-else
                                class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500"
                            >
                                {{ candidate.first_name?.charAt(0) }}
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">
                                    {{ candidate.title }} {{ candidate.first_name }}
                                    {{ candidate.last_name }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    หมายเลข {{ candidate.candidate_number }} -
                                    {{ candidate.type === 'constituency' ? 'แบ่งเขต' : 'บัญชีรายชื่อ' }}
                                </p>
                            </div>
                            <Link
                                :href="route('admin.candidates.show', candidate.id)"
                                class="text-blue-600 hover:underline text-sm"
                            >
                                ดู
                            </Link>
                        </div>
                    </li>
                </ul>
                <div
                    v-if="party.candidates.length > 10"
                    class="p-4 text-center border-t border-gray-200"
                >
                    <Link
                        :href="route('admin.candidates.index', { party_id: party.id })"
                        class="text-blue-600 hover:underline"
                    >
                        ดูผู้สมัครทั้งหมด ({{ party.candidates.length }} คน)
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    party: Object,
});

const ArrowLeftIcon = {
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>',
};

const copyApiKey = () => {
    if (props.party.api_key) {
        navigator.clipboard.writeText(props.party.api_key);
    }
};
</script>
