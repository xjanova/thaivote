<script setup>
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    users: Object,
    filters: Object,
});

const page = usePage();
const flash = page.props.flash || {};

const search = ref(props.filters?.search || '');
const adminOnly = ref(props.filters?.admin_only || false);

const doSearch = debounce(() => {
    router.get(
        route('admin.users.index'),
        { search: search.value, admin_only: adminOnly.value || undefined },
        { preserveState: true, replace: true }
    );
}, 300);

watch([search, adminOnly], doSearch);

const deleteUser = (user) => {
    if (confirm(`คุณต้องการลบผู้ใช้ "${user.name}" ใช่หรือไม่?`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
};

const toggleAdmin = (user) => {
    const action = user.is_admin ? 'ถอดสิทธิ์แอดมินจาก' : 'เพิ่มสิทธิ์แอดมินให้';
    if (confirm(`คุณต้องการ${action} "${user.name}" ใช่หรือไม่?`)) {
        router.post(route('admin.users.toggle-admin', user.id));
    }
};

const formatDate = (date) =>
    new Date(date).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
</script>

<template>
    <AdminLayout title="จัดการผู้ใช้งาน">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">จัดการผู้ใช้งาน</h1>
                    <p class="text-gray-500">จัดการผู้ใช้งานและสิทธิ์ผู้ดูแลระบบ</p>
                </div>
                <a
                    :href="route('admin.users.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    เพิ่มผู้ใช้งาน
                </a>
            </div>

            <!-- Flash Messages -->
            <div
                v-if="flash.success"
                class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash.error"
                class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg"
            >
                {{ flash.error }}
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="ค้นหาชื่อหรืออีเมล..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            v-model="adminOnly"
                            type="checkbox"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        แสดงเฉพาะแอดมิน
                    </label>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                ผู้ใช้งาน
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                อีเมล
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                สถานะ
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                สร้างเมื่อ
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div
                                        class="h-10 w-10 flex-shrink-0 rounded-full bg-gray-200 flex items-center justify-center"
                                    >
                                        <span class="text-gray-600 font-medium">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ user.name }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ user.email }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        user.is_admin
                                            ? 'bg-purple-100 text-purple-800'
                                            : 'bg-gray-100 text-gray-800',
                                    ]"
                                >
                                    {{ user.is_admin ? 'แอดมิน' : 'ผู้ใช้ทั่วไป' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="toggleAdmin(user)"
                                        :class="[
                                            'px-3 py-1 rounded text-xs font-medium',
                                            user.is_admin
                                                ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'
                                                : 'bg-purple-100 text-purple-700 hover:bg-purple-200',
                                        ]"
                                    >
                                        {{ user.is_admin ? 'ถอดสิทธิ์' : 'เพิ่มสิทธิ์' }}
                                    </button>
                                    <a
                                        :href="route('admin.users.edit', user.id)"
                                        class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium hover:bg-blue-200"
                                    >
                                        แก้ไข
                                    </a>
                                    <button
                                        @click="deleteUser(user)"
                                        class="px-3 py-1 bg-red-100 text-red-700 rounded text-xs font-medium hover:bg-red-200"
                                    >
                                        ลบ
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                ไม่พบผู้ใช้งาน
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="users.last_page > 1"
                    class="px-6 py-4 border-t border-gray-200 flex items-center justify-between"
                >
                    <div class="text-sm text-gray-500">
                        แสดง {{ users.from }} - {{ users.to }} จาก {{ users.total }} รายการ
                    </div>
                    <div class="flex gap-2">
                        <a
                            v-for="link in users.links"
                            :key="link.label"
                            :href="link.url"
                            :class="[
                                'px-3 py-1 rounded text-sm',
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : link.url
                                      ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                      : 'bg-gray-50 text-gray-400 cursor-not-allowed',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
