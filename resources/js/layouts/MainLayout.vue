<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Navigation Bar -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <!-- Logo & Brand -->
                    <div class="flex items-center gap-3">
                        <a href="/" class="flex items-center gap-3">
                            <div v-if="siteLogo" class="flex items-center">
                                <img
                                    :src="siteLogo"
                                    alt="Logo"
                                    class="h-12 w-auto object-contain"
                                />
                            </div>
                            <div v-else class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg"
                                >
                                    <svg
                                        class="w-6 h-6 text-white"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                                <h1 class="text-xl font-bold text-gray-900">{{ siteName }}</h1>
                            </div>
                        </a>
                    </div>

                    <!-- Desktop Menu -->
                    <div class="hidden md:flex items-center gap-8">
                        <a
                            v-for="item in navigation"
                            :key="item.name"
                            :href="item.href"
                            :class="[
                                'text-sm font-medium transition-colors',
                                isActive(item.href)
                                    ? 'text-indigo-600'
                                    : 'text-gray-700 hover:text-indigo-600',
                            ]"
                        >
                            {{ item.name }}
                        </a>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button
                        class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <svg
                            v-if="!mobileMenuOpen"
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                        <svg
                            v-else
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div v-if="mobileMenuOpen" class="md:hidden border-t border-gray-200 bg-white">
                <div class="px-4 py-3 space-y-2">
                    <a
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive(item.href)
                                ? 'bg-indigo-50 text-indigo-600'
                                : 'text-gray-700 hover:bg-gray-50',
                        ]"
                        @click="mobileMenuOpen = false"
                    >
                        {{ item.name }}
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Brand -->
                    <div class="col-span-1 md:col-span-2">
                        <div class="flex items-center gap-3 mb-4">
                            <div v-if="siteLogo" class="flex items-center">
                                <img
                                    :src="siteLogo"
                                    alt="Logo"
                                    class="h-10 w-auto object-contain"
                                />
                            </div>
                            <div v-else class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center"
                                >
                                    <svg
                                        class="w-5 h-5 text-white"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">{{ siteName }}</h3>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm">
                            ระบบรายงานผลเลือกตั้งแบบเรียลไทม์สำหรับประเทศไทย
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-4">เมนู</h4>
                        <ul class="space-y-2">
                            <li v-for="item in navigation" :key="item.name">
                                <a
                                    :href="item.href"
                                    class="text-gray-600 hover:text-indigo-600 text-sm transition-colors"
                                >
                                    {{ item.name }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Contact -->
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-4">ติดต่อ</h4>
                        <ul class="space-y-2 text-gray-600 text-sm">
                            <li>อีเมล: info@thaivote.com</li>
                            <li>โทร: 02-XXX-XXXX</li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-200 mt-8 pt-8">
                    <p class="text-center text-gray-500 text-sm">
                        &copy; {{ new Date().getFullYear() }} {{ siteName }}. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const mobileMenuOpen = ref(false);
const settings = ref({});

// Fetch settings on mount
onMounted(async () => {
    try {
        const response = await axios.get('/admin/settings/api');
        if (response.data && response.data.data) {
            settings.value = response.data.data;
        }
    } catch (error) {
        console.error('Failed to load settings:', error);
        settings.value = {
            site_name: 'ThaiVote',
            site_logo: '',
        };
    }
});

const siteLogo = computed(() => {
    if (settings.value.site_logo) {
        return `/storage/${settings.value.site_logo}`;
    }
    return null;
});

const siteName = computed(() => settings.value.site_name || 'ThaiVote');

const navigation = [
    { name: 'หน้าแรก', href: '/' },
    { name: 'ผลการเลือกตั้ง', href: '/results' },
    { name: 'พรรคการเมือง', href: '/parties' },
    { name: 'ข่าวสาร', href: '/news' },
    { name: 'แผนที่', href: '/map' },
];

const page = usePage();
const currentPath = computed(() => page.url);

const isActive = (href) => {
    if (href === '/') {
        return currentPath.value === '/';
    }
    return currentPath.value.startsWith(href);
};
</script>
