<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100">
        <!-- Toast Notifications -->
        <Toast />

        <!-- Mobile Overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden transition-opacity"
            @click="sidebarOpen = false"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-72 bg-gradient-to-b from-indigo-900 via-indigo-800 to-indigo-900 text-white transition-all duration-300 shadow-2xl',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            ]"
        >
            <!-- Logo Section -->
            <div class="h-20 flex items-center justify-between px-6 border-b border-white/10 bg-gradient-to-r from-indigo-900 to-indigo-800">
                <div class="flex items-center gap-3">
                    <div v-if="siteLogo" class="flex items-center">
                        <img :src="siteLogo" alt="Logo" class="h-12 w-auto object-contain" />
                    </div>
                    <div v-else class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold">{{ siteName }}</h1>
                            <p class="text-xs text-indigo-200">Admin Panel</p>
                        </div>
                    </div>
                </div>
                <button
                    class="lg:hidden p-2 hover:bg-white/10 rounded-lg transition-colors"
                    @click="sidebarOpen = false"
                >
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-1.5 overflow-y-auto" style="max-height: calc(100vh - 180px)">
                <a
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'group flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-200 relative overflow-hidden',
                        isActive(item.href)
                            ? 'bg-white/15 text-white shadow-lg backdrop-blur-sm border border-white/20'
                            : 'text-indigo-200 hover:text-white hover:bg-white/10',
                    ]"
                    @click="sidebarOpen = false"
                >
                    <component
                        :is="item.icon"
                        :class="[
                            'w-5 h-5 flex-shrink-0 transition-transform duration-200',
                            isActive(item.href) ? 'scale-110' : 'group-hover:scale-110'
                        ]"
                    />
                    <span class="font-medium text-sm">{{ item.name }}</span>
                    <div
                        v-if="isActive(item.href)"
                        class="absolute right-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-gradient-to-b from-blue-400 to-indigo-400 rounded-l-full"
                    ></div>
                </a>
            </nav>

            <!-- User Profile Footer -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/10 bg-gradient-to-r from-indigo-950 to-indigo-900">
                <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-all cursor-pointer">
                    <div class="relative">
                        <div
                            class="w-11 h-11 bg-gradient-to-br from-blue-400 to-purple-500 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg ring-2 ring-white/20"
                        >
                            <span class="font-bold text-sm">{{ userInitials }}</span>
                        </div>
                        <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-green-400 border-2 border-indigo-900 rounded-full"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate text-white">{{ userName }}</p>
                        <p class="text-xs text-indigo-300 truncate">{{ userEmail }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="lg:pl-72 min-h-screen">
            <!-- Top Bar -->
            <header class="sticky top-0 z-30 h-20 bg-white/80 backdrop-blur-xl shadow-sm border-b border-gray-200/50">
                <div class="h-full px-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <button
                            class="p-2.5 hover:bg-gray-100 rounded-xl lg:hidden transition-colors"
                            @click="sidebarOpen = !sidebarOpen"
                        >
                            <MenuIcon class="w-5 h-5 text-gray-600" />
                        </button>
                        <!-- Logo สำหรับ Mobile/Tablet -->
                        <div v-if="siteLogo" class="flex lg:hidden items-center">
                            <img :src="siteLogo" alt="Logo" class="h-8 w-auto object-contain" />
                        </div>
                        <div class="relative hidden md:block">
                            <input
                                type="search"
                                placeholder="ค้นหา..."
                                class="w-80 pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm"
                            />
                            <SearchIcon
                                class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Notifications -->
                        <button class="relative p-2.5 hover:bg-gray-100 rounded-xl transition-colors group">
                            <BellIcon class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 transition-colors" />
                            <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
                        </button>

                        <!-- Settings -->
                        <a
                            href="/admin/settings"
                            class="p-2.5 hover:bg-gray-100 rounded-xl transition-colors group"
                        >
                            <CogIcon class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 transition-colors" />
                        </a>

                        <!-- Profile Dropdown -->
                        <div class="hidden sm:block ml-2 pl-2 border-l border-gray-200">
                            <div class="flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-gray-50 cursor-pointer transition-colors">
                                <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-sm">
                                    <span class="font-semibold text-xs text-white">{{ userInitials }}</span>
                                </div>
                                <div class="hidden lg:block">
                                    <p class="text-sm font-semibold text-gray-900">{{ userName }}</p>
                                    <p class="text-xs text-gray-500">Admin</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6 md:p-8 lg:p-10">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Toast from '@/components/Toast.vue';

// Sidebar state for mobile
const sidebarOpen = ref(false);

// Settings
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
            site_favicon: '',
        };
    }
});

// Site logo and name from settings
const siteLogo = computed(() => {
    if (settings.value.site_logo) {
        return `/storage/${settings.value.site_logo}`;
    }
    return null;
});

const siteName = computed(() => settings.value.site_name || 'ThaiVote');

// Icon components
const HomeIcon = {
    template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>',
};
const CalendarIcon = {
    template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>',
};
const UsersIcon = {
    template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>',
};
const UserIcon = {
    template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>',
};
const NewspaperIcon = {
    template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" /></svg>',
};
const CogIcon = {
    template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>',
};
const BellIcon = {
    template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>',
};
const SearchIcon = {
    template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>',
};
const MenuIcon = {
    template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>',
};
const XMarkIcon = {
    template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>',
};
const UserGroupIcon = {
    template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>',
};
const RssIcon = {
    template: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12.75 19.5v-.75a7.5 7.5 0 0 0-7.5-7.5H4.5m0-6.75h.75c7.87 0 14.25 6.38 14.25 14.25v.75M6 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>',
};

const navigation = [
    { name: 'Dashboard', href: '/admin', icon: HomeIcon },
    { name: 'การเลือกตั้ง', href: '/admin/elections', icon: CalendarIcon },
    { name: 'พรรคการเมือง', href: '/admin/parties', icon: UsersIcon },
    { name: 'ผู้สมัคร', href: '/admin/candidates', icon: UserIcon },
    { name: 'ข่าวสาร', href: '/admin/news', icon: NewspaperIcon },
    { name: 'แหล่งข่าว', href: '/admin/sources', icon: RssIcon },
    { name: 'ผู้ใช้งาน', href: '/admin/users', icon: UserGroupIcon },
    { name: 'ตั้งค่า', href: '/admin/settings', icon: CogIcon },
];

const page = usePage();
const currentPath = computed(() => page.url);

const isActive = (href) => {
    if (href === '/admin') {
        return currentPath.value === '/admin';
    }
    return currentPath.value.startsWith(href);
};

// User info from props
const userName = computed(() => page.props.auth?.user?.name || 'Admin User');
const userEmail = computed(() => page.props.auth?.user?.email || 'admin@thaivote.com');
const userInitials = computed(() => {
    const name = userName.value;
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});
</script>
