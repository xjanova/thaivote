<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Live Banner -->
        <div v-if="isLive" class="bg-red-600 text-white py-2">
            <div class="container mx-auto px-4 flex items-center justify-center gap-2">
                <span class="pulse-live pr-4">LIVE</span>
                <span>กำลังนับคะแนนสด</span>
                <span class="mx-2">|</span>
                <span>อัปเดตล่าสุด: {{ formatTime(lastUpdated) }}</span>
            </div>
        </div>

        <!-- Header -->
        <header class="bg-white shadow-sm sticky top-0 z-40">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <img src="/images/logo.png" alt="ThaiVote" class="h-10">
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">ThaiVote</h1>
                            <p class="text-sm text-gray-500">{{ election?.name }}</p>
                        </div>
                    </div>
                    <nav class="hidden md:flex items-center gap-6">
                        <a href="#results" class="nav-link active">ผลเลือกตั้ง</a>
                        <a href="#map" class="nav-link">แผนที่</a>
                        <a href="#news" class="nav-link">ข่าวสาร</a>
                        <a href="#parties" class="nav-link">พรรค</a>
                    </nav>
                    <div class="flex items-center gap-3">
                        <button class="btn btn-outline text-sm">ดาวน์โหลดข้อมูล</button>
                        <button class="btn btn-primary text-sm">เข้าสู่ระบบ</button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8">
            <!-- Stats Overview -->
            <section class="mb-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="card p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">นับคะแนนแล้ว</p>
                                <p class="text-3xl font-bold text-primary">{{ stats?.counting_progress?.toFixed(1) }}%</p>
                            </div>
                            <div class="w-16 h-16">
                                <svg viewBox="0 0 36 36" class="circular-chart">
                                    <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                    <path class="circle" :stroke-dasharray="`${stats?.counting_progress || 0}, 100`" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="card p-6">
                        <p class="text-sm text-gray-500">ผู้มาใช้สิทธิ์</p>
                        <p class="text-3xl font-bold">{{ formatNumber(stats?.total_votes_cast) }}</p>
                        <p class="text-sm text-gray-400">{{ stats?.voter_turnout?.toFixed(1) }}%</p>
                    </div>
                    <div class="card p-6">
                        <p class="text-sm text-gray-500">หน่วยเลือกตั้ง</p>
                        <p class="text-3xl font-bold">{{ formatNumber(stats?.stations_counted) }}</p>
                        <p class="text-sm text-gray-400">จาก {{ formatNumber(stats?.stations_total) }}</p>
                    </div>
                    <div class="card p-6">
                        <p class="text-sm text-gray-500">บัตรเสีย</p>
                        <p class="text-3xl font-bold text-red-500">{{ formatNumber(stats?.invalid_votes) }}</p>
                        <p class="text-sm text-gray-400">{{ ((stats?.invalid_votes / stats?.total_votes_cast) * 100 || 0).toFixed(1) }}%</p>
                    </div>
                </div>
            </section>

            <!-- Main Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Results -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Party Results -->
                    <section id="results" class="card">
                        <div class="card-header flex items-center justify-between">
                            <h2 class="text-xl font-bold">ผลคะแนนรายพรรค</h2>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="resultView = 'seats'"
                                    :class="['px-3 py-1 rounded-lg text-sm', resultView === 'seats' ? 'bg-primary text-white' : 'bg-gray-100']"
                                >
                                    ที่นั่ง
                                </button>
                                <button
                                    @click="resultView = 'votes'"
                                    :class="['px-3 py-1 rounded-lg text-sm', resultView === 'votes' ? 'bg-primary text-white' : 'bg-gray-100']"
                                >
                                    คะแนน
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Seats visualization -->
                            <div class="mb-6">
                                <div class="flex items-center gap-1 h-12 rounded-xl overflow-hidden">
                                    <div
                                        v-for="result in sortedResults"
                                        :key="result.party_id"
                                        class="h-full transition-all duration-1000"
                                        :style="{
                                            width: (result.total_seats / 500 * 100) + '%',
                                            backgroundColor: result.party?.color
                                        }"
                                        :title="`${result.party?.name_th}: ${result.total_seats} ที่นั่ง`"
                                    ></div>
                                </div>
                                <div class="flex justify-between mt-2 text-sm text-gray-500">
                                    <span>0</span>
                                    <span class="text-center">เป้าหมาย 251 ที่นั่ง</span>
                                    <span>500</span>
                                </div>
                            </div>

                            <!-- Party list -->
                            <div class="space-y-4">
                                <div
                                    v-for="(result, index) in sortedResults"
                                    :key="result.party_id"
                                    class="flex items-center gap-4 p-4 rounded-xl hover:bg-gray-50 transition-colors"
                                    :class="{ 'bg-yellow-50': index === 0 }"
                                >
                                    <div class="w-8 text-center font-bold text-gray-400">{{ index + 1 }}</div>
                                    <div
                                        class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold"
                                        :style="{ backgroundColor: result.party?.color }"
                                    >
                                        {{ result.party?.abbreviation }}
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-semibold">{{ result.party?.name_th }}</h4>
                                        <p class="text-sm text-gray-500">{{ result.party?.leader_name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold">{{ result.total_seats }}</p>
                                        <p class="text-sm text-gray-500">ที่นั่ง</p>
                                    </div>
                                    <div class="text-right min-w-[100px]">
                                        <p class="font-semibold">{{ formatNumber(result.total_votes) }}</p>
                                        <p class="text-sm text-gray-500">{{ result.vote_percentage?.toFixed(2) }}%</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <div
                                            v-if="result.total_seats >= 251"
                                            class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold"
                                        >
                                            เสียงข้างมาก
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Map Section -->
                    <section id="map" class="card">
                        <div class="card-header">
                            <h2 class="text-xl font-bold">แผนที่ผลเลือกตั้ง</h2>
                        </div>
                        <div class="card-body p-0">
                            <ThailandMap
                                :election-id="electionId"
                                @province-selected="onProvinceSelected"
                                @constituency-selected="onConstituencySelected"
                            />
                        </div>
                    </section>
                </div>

                <!-- Right Column: News & Updates -->
                <div class="space-y-8">
                    <!-- Breaking News -->
                    <section class="card">
                        <div class="card-header flex items-center gap-2">
                            <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                            <h2 class="font-bold">ข่าวด่วน</h2>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <a
                                v-for="news in breakingNews"
                                :key="news.id"
                                :href="news.url"
                                class="block p-4 hover:bg-gray-50 transition-colors"
                            >
                                <p class="text-xs text-gray-400 mb-1">{{ formatTime(news.published_at) }}</p>
                                <h4 class="font-medium line-clamp-2">{{ news.title }}</h4>
                                <div class="flex items-center gap-2 mt-2">
                                    <img :src="news.source?.logo" class="h-4">
                                    <span class="text-xs text-gray-500">{{ news.source?.name }}</span>
                                </div>
                            </a>
                        </div>
                        <div class="card-body pt-0">
                            <a href="/news" class="btn btn-outline w-full text-sm">ดูข่าวทั้งหมด</a>
                        </div>
                    </section>

                    <!-- Trending Parties -->
                    <section class="card">
                        <div class="card-header">
                            <h2 class="font-bold">พรรคที่ถูกพูดถึงมากที่สุด</h2>
                        </div>
                        <div class="card-body space-y-3">
                            <div
                                v-for="party in trendingParties"
                                :key="party.id"
                                class="flex items-center gap-3"
                            >
                                <div
                                    class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-sm"
                                    :style="{ backgroundColor: party.color }"
                                >
                                    {{ party.abbreviation }}
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-sm">{{ party.name_th }}</h4>
                                    <p class="text-xs text-gray-500">{{ formatNumber(party.mentions) }} mentions</p>
                                </div>
                                <div :class="['text-sm font-semibold', party.trend > 0 ? 'text-green-500' : 'text-red-500']">
                                    {{ party.trend > 0 ? '+' : '' }}{{ party.trend }}%
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Live Feed -->
                    <section class="card">
                        <div class="card-header">
                            <h2 class="font-bold">การอัปเดตล่าสุด</h2>
                        </div>
                        <div class="card-body max-h-96 overflow-y-auto">
                            <div class="space-y-4">
                                <div
                                    v-for="update in liveFeed"
                                    :key="update.id"
                                    class="flex gap-3 pb-4 border-b border-gray-100 last:border-0"
                                >
                                    <div class="w-2 h-2 mt-2 rounded-full bg-primary flex-shrink-0"></div>
                                    <div>
                                        <p class="text-sm">{{ update.message }}</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ formatTime(update.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-12 mt-12">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="font-bold text-lg mb-4">ThaiVote</h3>
                        <p class="text-gray-400 text-sm">
                            ระบบรายงานผลเลือกตั้งแบบเรียลไทม์ รวบรวมข้อมูลจากหลายแหล่ง
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">ลิงก์</h4>
                        <ul class="space-y-2 text-gray-400 text-sm">
                            <li><a href="/about" class="hover:text-white">เกี่ยวกับเรา</a></li>
                            <li><a href="/api-docs" class="hover:text-white">API Documentation</a></li>
                            <li><a href="/privacy" class="hover:text-white">นโยบายความเป็นส่วนตัว</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">สำหรับพรรค</h4>
                        <ul class="space-y-2 text-gray-400 text-sm">
                            <li><a href="/party/register" class="hover:text-white">ลงทะเบียนพรรค</a></li>
                            <li><a href="/party/api" class="hover:text-white">เชื่อมต่อ API</a></li>
                            <li><a href="/party/support" class="hover:text-white">ช่วยเหลือ</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">ติดต่อ</h4>
                        <ul class="space-y-2 text-gray-400 text-sm">
                            <li>contact@thaivote.com</li>
                            <li>02-xxx-xxxx</li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
                    <p>&copy; 2024 ThaiVote. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useResultsStore } from '@/stores/results';
import ThailandMap from '@/components/map/ThailandMap.vue';

const props = defineProps({
    electionId: {
        type: Number,
        default: 1
    }
});

const resultsStore = useResultsStore();

// State
const resultView = ref('seats');
const isLive = ref(true);
const lastUpdated = ref(new Date());
const breakingNews = ref([]);
const trendingParties = ref([]);
const liveFeed = ref([]);

// Computed
const election = computed(() => resultsStore.election);
const stats = computed(() => resultsStore.stats);

const sortedResults = computed(() => {
    return [...resultsStore.nationalResults].sort((a, b) => {
        if (resultView.value === 'seats') {
            return b.total_seats - a.total_seats;
        }
        return b.total_votes - a.total_votes;
    });
});

// Methods
const formatNumber = (num) => {
    if (!num) return '0';
    return new Intl.NumberFormat('th-TH').format(num);
};

const formatTime = (date) => {
    if (!date) return '';
    return new Intl.DateTimeFormat('th-TH', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    }).format(new Date(date));
};

const onProvinceSelected = (province) => {
    console.log('Province selected:', province);
};

const onConstituencySelected = (constituency) => {
    console.log('Constituency selected:', constituency);
};

const fetchNews = async () => {
    try {
        const response = await axios.get('/api/news/breaking');
        breakingNews.value = response.data;
    } catch (error) {
        console.error('Failed to fetch news:', error);
    }
};

const fetchTrending = async () => {
    try {
        const response = await axios.get('/api/parties/trending');
        trendingParties.value = response.data;
    } catch (error) {
        console.error('Failed to fetch trending:', error);
    }
};

// Lifecycle
onMounted(async () => {
    await resultsStore.fetchElection(props.electionId);
    await fetchNews();
    await fetchTrending();

    // Subscribe to real-time updates
    window.Echo.channel(`election.${props.electionId}`)
        .listen('ResultsUpdated', (event) => {
            resultsStore.updateResults(event.results);
            lastUpdated.value = new Date();
        })
        .listen('NewsPublished', (event) => {
            breakingNews.value.unshift(event.news);
            breakingNews.value = breakingNews.value.slice(0, 10);
        });
});

onUnmounted(() => {
    window.Echo.leave(`election.${props.electionId}`);
});
</script>

<style scoped>
@reference "tailwindcss";

.nav-link {
    @apply text-gray-600 transition-colors;
    &:hover {
        color: var(--color-primary);
    }
}

.nav-link.active {
    @apply font-semibold;
    color: var(--color-primary);
}

.circular-chart {
    display: block;
    max-width: 100%;
    max-height: 100%;
}

.circle-bg {
    fill: none;
    stroke: #eee;
    stroke-width: 3.8;
}

.circle {
    fill: none;
    stroke-width: 2.8;
    stroke-linecap: round;
    stroke: var(--color-primary);
    animation: progress 1s ease-out forwards;
}

@keyframes progress {
    0% { stroke-dasharray: 0 100; }
}

.text-primary {
    color: var(--color-primary);
}

.bg-primary {
    background-color: var(--color-primary);
}
</style>
