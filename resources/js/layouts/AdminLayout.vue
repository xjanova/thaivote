<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-[#1A1A2E] text-white">
            <!-- Logo -->
            <div class="h-16 flex items-center px-6 border-b border-white/10">
                <span class="text-xl font-bold">ThaiVote Admin</span>
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-1">
                <a
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 px-4 py-3 rounded-lg transition-colors',
                        isActive(item.href) ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white hover:bg-white/5'
                    ]"
                >
                    <component :is="item.icon" class="w-5 h-5" />
                    <span>{{ item.name }}</span>
                </a>
            </nav>

            <!-- Footer -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <span class="font-semibold">A</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium">Admin User</p>
                        <p class="text-xs text-gray-400">admin@thaivote.com</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="pl-64">
            <!-- Top Bar -->
            <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6">
                <div class="flex items-center gap-4">
                    <button class="p-2 hover:bg-gray-100 rounded-lg">
                        <MenuIcon class="w-5 h-5 text-gray-500" />
                    </button>
                    <div class="relative">
                        <input
                            type="search"
                            placeholder="ค้นหา..."
                            class="w-64 pl-10 pr-4 py-2 bg-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                        <SearchIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Notifications -->
                    <button class="relative p-2 hover:bg-gray-100 rounded-lg">
                        <BellIcon class="w-5 h-5 text-gray-500" />
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>

                    <!-- Settings -->
                    <button class="p-2 hover:bg-gray-100 rounded-lg">
                        <CogIcon class="w-5 h-5 text-gray-500" />
                    </button>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const navigation = [
    { name: 'Dashboard', href: '/admin', icon: 'HomeIcon' },
    { name: 'การเลือกตั้ง', href: '/admin/elections', icon: 'CalendarIcon' },
    { name: 'พรรคการเมือง', href: '/admin/parties', icon: 'UsersIcon' },
    { name: 'ข่าวสาร', href: '/admin/news', icon: 'NewspaperIcon' },
    { name: 'แหล่งข้อมูล', href: '/admin/sources', icon: 'DatabaseIcon' },
    { name: 'คีย์เวิร์ด', href: '/admin/keywords', icon: 'TagIcon' },
    { name: 'Blockchain', href: '/admin/blockchain', icon: 'CubeIcon' },
    { name: 'API Keys', href: '/admin/api-keys', icon: 'KeyIcon' },
    { name: 'ตั้งค่า', href: '/admin/settings', icon: 'CogIcon' },
];

const currentPath = computed(() => window.location.pathname);

const isActive = (href) => {
    if (href === '/admin') {
        return currentPath.value === '/admin';
    }
    return currentPath.value.startsWith(href);
};
</script>
