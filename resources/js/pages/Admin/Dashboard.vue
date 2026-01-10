<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500"
                        >อัปเดตล่าสุด: {{ formatTime(lastUpdated) }}</span
                    >
                    <button
                        class="btn btn-primary text-sm"
                        :disabled="isLoading"
                        @click="refreshAll"
                    >
                        <span
                            class="w-4 h-4 mr-1 inline-block"
                            :class="{ 'animate-spin': isLoading }"
                            >↻</span
                        >
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
                        <div v-if="sourcesLoading" class="space-y-3">
                            <div
                                v-for="i in 4"
                                :key="i"
                                class="h-12 bg-gray-100 rounded-lg animate-pulse"
                            ></div>
                        </div>
                        <div
                            v-else-if="sources.length === 0"
                            class="text-center text-gray-500 py-4"
                        >
                            ยังไม่มีแหล่งข้อมูล
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="source in sources"
                                :key="source.id"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="[
                                            'w-3 h-3 rounded-full',
                                            source.status === 'active'
                                                ? 'bg-green-500'
                                                : source.has_error
                                                  ? 'bg-red-500'
                                                  : 'bg-gray-400',
                                        ]"
                                    ></div>
                                    <div>
                                        <span class="font-medium">{{ source.name }}</span>
                                        <span class="text-xs text-gray-500 ml-2">{{
                                            source.type
                                        }}</span>
                                    </div>
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
                    <div v-if="newsLoading" class="divide-y divide-gray-100">
                        <div v-for="i in 3" :key="i" class="p-4">
                            <div class="h-4 bg-gray-100 rounded animate-pulse mb-2"></div>
                            <div class="h-3 bg-gray-100 rounded animate-pulse w-1/2"></div>
                        </div>
                    </div>
                    <div v-else-if="recentNews.length === 0" class="p-4 text-center text-gray-500">
                        ยังไม่มีข่าว
                    </div>
                    <div v-else class="divide-y divide-gray-100">
                        <div v-for="news in recentNews" :key="news.id" class="p-4 hover:bg-gray-50">
                            <p class="text-sm font-medium line-clamp-2">{{ news.title }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ news.source }} • {{ news.time }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="font-semibold">รอการอนุมัติ</h3>
                        <span
                            v-if="pendingCount > 0"
                            class="px-2 py-1 bg-red-100 text-red-600 text-xs rounded-full"
                        >
                            {{ pendingCount }}
                        </span>
                    </div>
                    <div v-if="pendingLoading" class="divide-y divide-gray-100">
                        <div v-for="i in 2" :key="i" class="p-4">
                            <div class="h-4 bg-gray-100 rounded animate-pulse mb-2"></div>
                            <div class="h-3 bg-gray-100 rounded animate-pulse w-1/3"></div>
                        </div>
                    </div>
                    <div
                        v-else-if="pendingItems.length === 0"
                        class="p-4 text-center text-gray-500"
                    >
                        ไม่มีรายการรอดำเนินการ
                    </div>
                    <div v-else class="divide-y divide-gray-100">
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
                                <button
                                    class="p-1 text-green-600 hover:bg-green-50 rounded"
                                    title="อนุมัติ"
                                    @click="approveItem(item)"
                                >
                                    ✓
                                </button>
                                <button
                                    class="p-1 text-red-600 hover:bg-red-50 rounded"
                                    title="ปฏิเสธ"
                                    @click="rejectItem(item)"
                                >
                                    ✕
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
                        <div v-if="logsLoading" class="space-y-2">
                            <div
                                v-for="i in 5"
                                :key="i"
                                class="h-8 bg-gray-100 rounded animate-pulse"
                            ></div>
                        </div>
                        <div v-else-if="logs.length === 0" class="text-center text-gray-500">
                            ไม่มีล็อก
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="log in logs"
                                :key="log.id"
                                class="flex items-start gap-2 text-sm"
                            >
                                <span
                                    :class="[
                                        'px-1 py-0.5 text-xs rounded',
                                        log.level === 'error'
                                            ? 'bg-red-100 text-red-600'
                                            : log.level === 'warning'
                                              ? 'bg-yellow-100 text-yellow-600'
                                              : 'bg-gray-100 text-gray-600',
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
import { ref, onMounted, onUnmounted } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StatCard from '@/components/admin/StatCard.vue';
import Chart from 'chart.js/auto';
import axios from 'axios';

const lastUpdated = ref(new Date());
const trafficChart = ref(null);
const chartInstance = ref(null);
const isLoading = ref(false);

// Loading states
const statsLoading = ref(true);
const sourcesLoading = ref(true);
const newsLoading = ref(true);
const pendingLoading = ref(true);
const logsLoading = ref(true);

// Data
const stats = ref({
    activeElections: 0,
    totalParties: 0,
    todayNews: 0,
    activeSources: 0,
});

const sources = ref([]);
const recentNews = ref([]);
const pendingCount = ref(0);
const pendingItems = ref([]);
const logs = ref([]);

// Auto-refresh interval
let refreshInterval = null;

const formatTime = (date) =>
    new Intl.DateTimeFormat('th-TH', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);

// API calls
const fetchStats = async () => {
    statsLoading.value = true;
    try {
        const response = await axios.get('/admin/api/dashboard/stats');
        // Ensure we have valid numeric values
        stats.value = {
            activeElections: Number(response.data?.activeElections) || 0,
            totalParties: Number(response.data?.totalParties) || 0,
            todayNews: Number(response.data?.todayNews) || 0,
            activeSources: Number(response.data?.activeSources) || 0,
        };
    } catch (error) {
        console.error('Failed to fetch stats:', error);
        // Keep existing values or reset to 0 on error
        stats.value = {
            activeElections: stats.value.activeElections || 0,
            totalParties: stats.value.totalParties || 0,
            todayNews: stats.value.todayNews || 0,
            activeSources: stats.value.activeSources || 0,
        };
    } finally {
        statsLoading.value = false;
    }
};

const fetchSources = async () => {
    sourcesLoading.value = true;
    try {
        const response = await axios.get('/admin/api/dashboard/sources');
        sources.value = response.data;
    } catch (error) {
        console.error('Failed to fetch sources:', error);
    } finally {
        sourcesLoading.value = false;
    }
};

const fetchRecentNews = async () => {
    newsLoading.value = true;
    try {
        const response = await axios.get('/admin/api/dashboard/recent-news');
        recentNews.value = response.data;
    } catch (error) {
        console.error('Failed to fetch news:', error);
    } finally {
        newsLoading.value = false;
    }
};

const fetchPending = async () => {
    pendingLoading.value = true;
    try {
        const response = await axios.get('/admin/api/dashboard/pending');
        pendingItems.value = response.data.items;
        pendingCount.value = response.data.count;
    } catch (error) {
        console.error('Failed to fetch pending:', error);
    } finally {
        pendingLoading.value = false;
    }
};

const fetchLogs = async () => {
    logsLoading.value = true;
    try {
        const response = await axios.get('/admin/api/dashboard/logs');
        logs.value = response.data;
    } catch (error) {
        console.error('Failed to fetch logs:', error);
    } finally {
        logsLoading.value = false;
    }
};

const fetchTraffic = async () => {
    try {
        const response = await axios.get('/admin/api/dashboard/traffic');
        updateChart(response.data);
    } catch (error) {
        console.error('Failed to fetch traffic:', error);
    }
};

const updateChart = (data) => {
    if (chartInstance.value) {
        chartInstance.value.data.labels = data.labels;
        chartInstance.value.data.datasets[0].data = data.data;
        chartInstance.value.update();
    }
};

const initChart = () => {
    if (trafficChart.value) {
        chartInstance.value = new Chart(trafficChart.value, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Users',
                        data: [],
                        borderColor: '#FF6B35',
                        backgroundColor: 'rgba(255, 107, 53, 0.1)',
                        fill: true,
                        tension: 0.4,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: { beginAtZero: true },
                },
            },
        });
    }
};

const refreshAll = async () => {
    isLoading.value = true;
    await Promise.all([
        fetchStats(),
        fetchSources(),
        fetchRecentNews(),
        fetchPending(),
        fetchLogs(),
        fetchTraffic(),
    ]);
    lastUpdated.value = new Date();
    isLoading.value = false;
};

const approveItem = async (item) => {
    try {
        await axios.post(`/admin/api/${item.entity_type}/${item.entity_id}/approve`);
        await fetchPending();
    } catch (error) {
        console.error('Failed to approve:', error);
        alert('ไม่สามารถอนุมัติได้');
    }
};

const rejectItem = async (item) => {
    try {
        await axios.post(`/admin/api/${item.entity_type}/${item.entity_id}/reject`);
        await fetchPending();
    } catch (error) {
        console.error('Failed to reject:', error);
        alert('ไม่สามารถปฏิเสธได้');
    }
};

onMounted(() => {
    initChart();
    refreshAll();

    // Auto-refresh every 60 seconds
    refreshInterval = setInterval(() => {
        refreshAll();
    }, 60000);
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    if (chartInstance.value) {
        chartInstance.value.destroy();
    }
});
</script>
