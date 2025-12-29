<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">อัปเดตล่าสุด: {{ formatTime(lastUpdated) }}</span>
                    <button @click="refresh" class="btn btn-primary text-sm">
                        <RefreshIcon class="w-4 h-4 mr-1" />
                        รีเฟรช
                    </button>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    title="การเลือกตั้งที่ใช้งาน"
                    :value="stats.activeElections"
                    icon="CalendarIcon"
                    color="blue"
                />
                <StatCard
                    title="พรรคการเมือง"
                    :value="stats.totalParties"
                    icon="UsersIcon"
                    color="purple"
                />
                <StatCard
                    title="ข่าววันนี้"
                    :value="stats.todayNews"
                    icon="NewspaperIcon"
                    color="green"
                />
                <StatCard
                    title="แหล่งข้อมูล"
                    :value="stats.activeSources"
                    icon="DatabaseIcon"
                    color="orange"
                />
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Real-time Traffic -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="font-semibold">ทราฟฟิกแบบเรียลไทม์</h3>
                    </div>
                    <div class="card-body">
                        <canvas ref="trafficChart" height="200"></canvas>
                    </div>
                </div>

                <!-- Source Status -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="font-semibold">สถานะแหล่งข้อมูล</h3>
                    </div>
                    <div class="card-body">
                        <div class="space-y-3">
                            <div
                                v-for="source in sources"
                                :key="source.id"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="[
                                            'w-3 h-3 rounded-full',
                                            source.status === 'active' ? 'bg-green-500' : 'bg-red-500'
                                        ]"
                                    ></div>
                                    <span>{{ source.name }}</span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ source.last_fetched }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent News -->
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="font-semibold">ข่าวล่าสุด</h3>
                        <a href="/admin/news" class="text-sm text-primary">ดูทั้งหมด</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="news in recentNews"
                            :key="news.id"
                            class="p-4 hover:bg-gray-50"
                        >
                            <p class="text-sm font-medium line-clamp-2">{{ news.title }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ news.source }} • {{ news.time }}</p>
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="font-semibold">รอการอนุมัติ</h3>
                        <span class="px-2 py-1 bg-red-100 text-red-600 text-xs rounded-full">
                            {{ pendingCount }}
                        </span>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="item in pendingItems"
                            :key="item.id"
                            class="p-4 flex items-center justify-between"
                        >
                            <div>
                                <p class="text-sm font-medium">{{ item.title }}</p>
                                <p class="text-xs text-gray-500">{{ item.type }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button class="p-1 text-green-600 hover:bg-green-50 rounded">
                                    <CheckIcon class="w-5 h-5" />
                                </button>
                                <button class="p-1 text-red-600 hover:bg-red-50 rounded">
                                    <XIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Logs -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="font-semibold">ล็อกระบบ</h3>
                    </div>
                    <div class="card-body max-h-64 overflow-y-auto">
                        <div class="space-y-2">
                            <div
                                v-for="log in logs"
                                :key="log.id"
                                class="flex items-start gap-2 text-sm"
                            >
                                <span
                                    :class="[
                                        'px-1 py-0.5 text-xs rounded',
                                        log.level === 'error' ? 'bg-red-100 text-red-600' :
                                        log.level === 'warning' ? 'bg-yellow-100 text-yellow-600' :
                                        'bg-gray-100 text-gray-600'
                                    ]"
                                >
                                    {{ log.level }}
                                </span>
                                <div class="flex-1">
                                    <p class="text-gray-700">{{ log.message }}</p>
                                    <p class="text-xs text-gray-400">{{ log.time }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StatCard from '@/components/admin/StatCard.vue';
import Chart from 'chart.js/auto';

const lastUpdated = ref(new Date());
const trafficChart = ref(null);

const stats = ref({
    activeElections: 1,
    totalParties: 45,
    todayNews: 156,
    activeSources: 12,
});

const sources = ref([
    { id: 1, name: 'กกต. Official', status: 'active', last_fetched: '2 นาทีที่แล้ว' },
    { id: 2, name: 'Thai PBS', status: 'active', last_fetched: '5 นาทีที่แล้ว' },
    { id: 3, name: 'ไทยรัฐ', status: 'active', last_fetched: '3 นาทีที่แล้ว' },
    { id: 4, name: 'มติชน', status: 'error', last_fetched: '15 นาทีที่แล้ว' },
]);

const recentNews = ref([
    { id: 1, title: 'กกต.เปิดเผยผลคะแนนเลือกตั้งอย่างเป็นทางการ', source: 'Thai PBS', time: '5 นาทีที่แล้ว' },
    { id: 2, title: 'พรรคก้าวไกลประกาศชัยชนะในหลายจังหวัด', source: 'มติชน', time: '12 นาทีที่แล้ว' },
    { id: 3, title: 'ประชาชนแห่ออกมาใช้สิทธิ์คึกคัก', source: 'ไทยรัฐ', time: '20 นาทีที่แล้ว' },
]);

const pendingCount = ref(5);
const pendingItems = ref([
    { id: 1, title: 'ข่าวใหม่รอตรวจสอบ', type: 'News' },
    { id: 2, title: 'พรรคใหม่ลงทะเบียน', type: 'Party' },
]);

const logs = ref([
    { id: 1, level: 'info', message: 'Scraper completed successfully', time: '10:45:32' },
    { id: 2, level: 'warning', message: 'Source มติชน response slow', time: '10:43:15' },
    { id: 3, level: 'error', message: 'Failed to parse results from source #4', time: '10:40:00' },
]);

const formatTime = (date) => {
    return new Intl.DateTimeFormat('th-TH', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const refresh = () => {
    lastUpdated.value = new Date();
};

onMounted(() => {
    // Initialize chart
    if (trafficChart.value) {
        new Chart(trafficChart.value, {
            type: 'line',
            data: {
                labels: ['10:00', '10:15', '10:30', '10:45', '11:00', '11:15'],
                datasets: [{
                    label: 'Users',
                    data: [1200, 1900, 3000, 5000, 4200, 3500],
                    borderColor: '#FF6B35',
                    backgroundColor: 'rgba(255, 107, 53, 0.1)',
                    fill: true,
                    tension: 0.4,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>
