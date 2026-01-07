<template>
    <AdminLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('admin.candidates.index')"
                    class="p-2 hover:bg-gray-100 rounded-lg"
                >
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">เพิ่มผู้สมัครใหม่</h1>
                    <p class="text-gray-600">กรอกข้อมูลผู้สมัครรับเลือกตั้ง</p>
                </div>
            </div>

            <!-- Form -->
            <form class="bg-white rounded-lg shadow" @submit.prevent="submit">
                <!-- Basic Info -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">ข้อมูลพื้นฐาน</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >คำนำหน้า</label
                            >
                            <input
                                v-model="form.title"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="นาย/นาง/นางสาว"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ชื่อ <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.first_name"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            />
                            <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.first_name }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                นามสกุล <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.last_name"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            />
                            <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.last_name }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >ชื่อเล่น</label
                            >
                            <input
                                v-model="form.nickname"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                หมายเลขผู้สมัคร <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.candidate_number"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            />
                            <p
                                v-if="form.errors.candidate_number"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.candidate_number }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >วันเกิด</label
                            >
                            <input
                                v-model="form.birth_date"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Election & Party -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">การเลือกตั้ง และพรรค</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                การเลือกตั้ง <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.election_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                required
                            >
                                <option value="">เลือกการเลือกตั้ง</option>
                                <option
                                    v-for="election in elections"
                                    :key="election.id"
                                    :value="election.id"
                                >
                                    {{ election.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.election_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.election_id }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >พรรคการเมือง</label
                            >
                            <select
                                v-model="form.party_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">อิสระ</option>
                                <option v-for="party in parties" :key="party.id" :value="party.id">
                                    {{ party.name_th }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ประเภท <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.type"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                required
                            >
                                <option value="constituency">แบ่งเขต</option>
                                <option value="party_list">บัญชีรายชื่อ</option>
                            </select>
                        </div>
                        <div v-if="form.type === 'party_list'">
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >ลำดับบัญชีรายชื่อ</label
                            >
                            <input
                                v-model="form.party_list_order"
                                type="number"
                                min="1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Constituency -->
                <div v-if="form.type === 'constituency'" class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">เขตเลือกตั้ง</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >จังหวัด</label
                            >
                            <select
                                v-model="selectedProvince"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                @change="loadConstituencies"
                            >
                                <option value="">เลือกจังหวัด</option>
                                <option
                                    v-for="province in provinces"
                                    :key="province.id"
                                    :value="province.id"
                                >
                                    {{ province.name_th }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >เขตเลือกตั้ง</label
                            >
                            <select
                                v-model="form.constituency_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                :disabled="!selectedProvince"
                            >
                                <option value="">เลือกเขต</option>
                                <option v-for="c in localConstituencies" :key="c.id" :value="c.id">
                                    เขต {{ c.number }} {{ c.name ? `- ${c.name}` : '' }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Photo -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">รูปภาพ</h2>
                    <div class="flex items-center gap-4">
                        <div
                            v-if="photoPreview"
                            class="w-24 h-24 rounded-lg overflow-hidden bg-gray-100"
                        >
                            <img :src="photoPreview" class="w-full h-full object-cover" />
                        </div>
                        <div
                            v-else
                            class="w-24 h-24 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400"
                        >
                            <PhotoIcon class="w-10 h-10" />
                        </div>
                        <div>
                            <input
                                ref="photoInput"
                                type="file"
                                accept="image/png,image/jpeg"
                                class="hidden"
                                @change="onPhotoChange"
                            />
                            <button
                                type="button"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                                @click="$refs.photoInput.click()"
                            >
                                เลือกรูปภาพ
                            </button>
                            <p class="mt-1 text-xs text-gray-500">PNG, JPG ขนาดไม่เกิน 2MB</p>
                        </div>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">ข้อมูลเพิ่มเติม</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >การศึกษา</label
                            >
                            <input
                                v-model="form.education"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >อาชีพ</label
                            >
                            <input
                                v-model="form.occupation"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"
                                >ประวัติ</label
                            >
                            <textarea
                                v-model="form.biography"
                                rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- PM Candidate -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <input
                            id="is_pm_candidate"
                            v-model="form.is_pm_candidate"
                            type="checkbox"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        />
                        <label for="is_pm_candidate" class="text-sm font-medium text-gray-700">
                            ผู้สมัครนายกรัฐมนตรี
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="p-6 flex items-center justify-end gap-3">
                    <Link
                        :href="route('admin.candidates.index')"
                        class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                    >
                        ยกเลิก
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'กำลังบันทึก...' : 'บันทึก' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps({
    parties: Array,
    elections: Array,
    provinces: Array,
    constituencies: Array,
});

const ArrowLeftIcon = {
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>',
};
const PhotoIcon = {
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>',
};

const form = useForm({
    election_id: '',
    party_id: '',
    constituency_id: '',
    candidate_number: '',
    title: '',
    first_name: '',
    last_name: '',
    nickname: '',
    photo: null,
    biography: '',
    birth_date: '',
    education: '',
    occupation: '',
    type: 'constituency',
    party_list_order: null,
    is_pm_candidate: false,
});

const selectedProvince = ref('');
const localConstituencies = ref(props.constituencies || []);
const photoPreview = ref(null);

const loadConstituencies = async () => {
    if (!selectedProvince.value) {
        localConstituencies.value = [];
        return;
    }

    try {
        const response = await fetch(
            route('admin.candidates.constituencies', selectedProvince.value)
        );
        const data = await response.json();
        localConstituencies.value = data.constituencies;
    } catch (error) {
        console.error('Failed to load constituencies:', error);
    }
};

const onPhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('admin.candidates.store'), {
        forceFormData: true,
    });
};
</script>
