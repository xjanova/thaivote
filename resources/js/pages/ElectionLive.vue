<template>
    <div class="election-live">
        <!-- Hero Header -->
        <header class="hero-header">
            <div class="hero-bg"></div>
            <div class="hero-content container mx-auto px-4 py-8">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h1 class="text-4xl lg:text-5xl font-bold text-white mb-2">
                            <span class="text-gradient">ผลเลือกตั้ง</span> 2569
                        </h1>
                        <p class="text-white/70">
                            การเลือกตั้งสมาชิกสภาผู้แทนราษฎร 8 กุมภาพันธ์ 2569
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="live-badge">
                            <span class="pulse"></span>
                            <span class="text-sm font-semibold">ถ่ายทอดสด</span>
                        </div>
                        <div class="time-display">
                            <span class="text-2xl font-mono font-bold text-white">{{
                                currentTime
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid mt-8">
                    <div class="stat-card" v-for="(stat, i) in mainStats" :key="i">
                        <div class="stat-icon" :style="{ background: stat.gradient }">
                            <component :is="stat.icon" class="w-6 h-6" />
                        </div>
                        <div class="stat-info">
                            <span class="stat-value">
                                <CountUp :end-val="stat.value" :duration="2" />
                            </span>
                            <span class="stat-label">{{ stat.label }}</span>
                        </div>
                        <div v-if="stat.change" class="stat-change" :class="stat.changeType">
                            {{ stat.change }}
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- 3D Map Section -->
                <div class="lg:col-span-2">
                    <div class="section-card">
                        <div class="section-header">
                            <h2 class="section-title">
                                <svg
                                    class="w-6 h-6 text-orange-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
                                    />
                                </svg>
                                แผนที่ประเทศไทย
                            </h2>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-500">นับคะแนนแล้ว</span>
                                <div class="progress-ring">
                                    <svg viewBox="0 0 36 36" class="w-12 h-12">
                                        <circle
                                            cx="18"
                                            cy="18"
                                            r="16"
                                            fill="none"
                                            stroke="#e5e7eb"
                                            stroke-width="3"
                                        />
                                        <circle
                                            cx="18"
                                            cy="18"
                                            r="16"
                                            fill="none"
                                            stroke="url(#progress-gradient)"
                                            stroke-width="3"
                                            :stroke-dasharray="`${countingProgress}, 100`"
                                            stroke-linecap="round"
                                            transform="rotate(-90 18 18)"
                                        />
                                        <defs>
                                            <linearGradient
                                                id="progress-gradient"
                                                x1="0%"
                                                y1="0%"
                                                x2="100%"
                                                y2="0%"
                                            >
                                                <stop offset="0%" stop-color="#f97316" />
                                                <stop offset="100%" stop-color="#ef4444" />
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                    <span class="progress-text">{{ countingProgress }}%</span>
                                </div>
                            </div>
                        </div>

                        <ThailandMap3D
                            :election-id="electionId"
                            :results="results"
                            @province-selected="onProvinceSelected"
                            @view-details="viewProvinceDetails"
                        />
                    </div>
                </div>

                <!-- Side Panel -->
                <div class="space-y-6">
                    <!-- Party Rankings -->
                    <div class="section-card">
                        <div class="section-header">
                            <h2 class="section-title">
                                <svg
                                    class="w-6 h-6 text-orange-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                    />
                                </svg>
                                อันดับพรรค
                            </h2>
                        </div>

                        <div class="party-ranking">
                            <TransitionGroup name="ranking" tag="div" class="space-y-3">
                                <div
                                    v-for="(party, index) in topParties"
                                    :key="party.id"
                                    class="party-rank-item"
                                    :class="{ 'is-leading': index === 0 }"
                                >
                                    <div class="rank-badge" :class="`rank-${index + 1}`">
                                        {{ index + 1 }}
                                    </div>
                                    <div
                                        class="party-color"
                                        :style="{ backgroundColor: party.color }"
                                    ></div>
                                    <div class="party-info flex-1">
                                        <span class="party-name">{{ party.name_th }}</span>
                                        <div class="party-bar">
                                            <div
                                                class="party-bar-fill"
                                                :style="{
                                                    width: `${(party.seats / 500) * 100}%`,
                                                    backgroundColor: party.color,
                                                }"
                                            ></div>
                                        </div>
                                    </div>
                                    <div class="party-seats">
                                        <span class="seats-count">{{ party.seats }}</span>
                                        <span class="seats-label">ที่นั่ง</span>
                                    </div>
                                </div>
                            </TransitionGroup>
                        </div>
                    </div>

                    <!-- Constituency vs Party List -->
                    <div class="section-card">
                        <div class="section-header">
                            <h2 class="section-title">
                                <svg
                                    class="w-6 h-6 text-orange-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"
                                    />
                                </svg>
                                ส.ส. แบ่งเขต vs บัญชีรายชื่อ
                            </h2>
                        </div>

                        <div class="type-comparison">
                            <div class="type-item">
                                <div class="type-circle constituency">
                                    <span class="type-value">{{ constituencySeats }}</span>
                                </div>
                                <span class="type-label">แบ่งเขต</span>
                                <span class="type-total">/400</span>
                            </div>
                            <div class="type-divider">
                                <div class="divider-line"></div>
                                <span class="divider-vs">VS</span>
                                <div class="divider-line"></div>
                            </div>
                            <div class="type-item">
                                <div class="type-circle partylist">
                                    <span class="type-value">{{ partyListSeats }}</span>
                                </div>
                                <span class="type-label">บัญชีรายชื่อ</span>
                                <span class="type-total">/100</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Updates -->
                    <div class="section-card">
                        <div class="section-header">
                            <h2 class="section-title">
                                <svg
                                    class="w-6 h-6 text-orange-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"
                                    />
                                </svg>
                                อัปเดตล่าสุด
                            </h2>
                        </div>

                        <div class="updates-list">
                            <TransitionGroup name="update" tag="div">
                                <div
                                    v-for="update in recentUpdates"
                                    :key="update.id"
                                    class="update-item"
                                >
                                    <div class="update-time">{{ update.time }}</div>
                                    <div class="update-content">
                                        <span
                                            class="update-badge"
                                            :style="{ backgroundColor: update.party?.color }"
                                        ></span>
                                        <span class="update-text">{{ update.message }}</span>
                                    </div>
                                </div>
                            </TransitionGroup>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Section - Regional Breakdown -->
            <div class="section-card mt-8">
                <div class="section-header">
                    <h2 class="section-title">
                        <svg
                            class="w-6 h-6 text-orange-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        ผลคะแนนแยกภาค
                    </h2>
                </div>

                <div class="region-grid">
                    <div
                        v-for="region in regionalResults"
                        :key="region.key"
                        class="region-card"
                        :style="{ '--region-color': region.color }"
                    >
                        <div class="region-header">
                            <h3 class="region-name">{{ region.name_th }}</h3>
                            <span class="region-seats">{{ region.totalSeats }} ที่นั่ง</span>
                        </div>
                        <div class="region-parties">
                            <div
                                v-for="party in region.topParties.slice(0, 3)"
                                :key="party.id"
                                class="region-party"
                            >
                                <div
                                    class="region-party-color"
                                    :style="{ backgroundColor: party.color }"
                                ></div>
                                <span class="region-party-name">{{ party.abbreviation }}</span>
                                <span class="region-party-seats">{{ party.seats }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <div class="container mx-auto px-4 py-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">ข้อมูลจาก</span>
                        <a
                            href="https://www.ect.go.th"
                            target="_blank"
                            class="text-orange-500 hover:underline"
                        >
                            สำนักงานคณะกรรมการการเลือกตั้ง (กกต.)
                        </a>
                    </div>
                    <div class="text-gray-500 text-sm">อัปเดตล่าสุด: {{ lastUpdate }}</div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import ThailandMap3D from '@/components/map/ThailandMap3D.vue';
import { regions } from '@/data/provinces';

// Props from server
const props = defineProps({
    election: Object,
    initialResults: Object,
});

// State
const results = ref(props.initialResults || {});
const currentTime = ref('');
const lastUpdate = ref('');
const electionId = computed(() => props.election?.id || 1);

// Computed
const countingProgress = computed(() => {
    return results.value.national?.counting_progress || 0;
});

const topParties = computed(() => {
    return results.value.national?.parties?.slice(0, 10) || [];
});

const constituencySeats = computed(() => {
    return results.value.national?.constituency_seats || 0;
});

const partyListSeats = computed(() => {
    return results.value.national?.party_list_seats || 0;
});

const mainStats = computed(() => [
    {
        label: 'รวมคะแนนทั้งหมด',
        value: results.value.national?.total_votes || 0,
        gradient: 'linear-gradient(135deg, #f97316, #ef4444)',
        icon: 'ChartIcon',
    },
    {
        label: 'ผู้มาใช้สิทธิ',
        value: results.value.national?.voter_turnout || 0,
        gradient: 'linear-gradient(135deg, #3b82f6, #8b5cf6)',
        icon: 'UsersIcon',
        change: '+12.5%',
        changeType: 'positive',
    },
    {
        label: 'หน่วยที่นับเสร็จ',
        value: results.value.national?.stations_counted || 0,
        gradient: 'linear-gradient(135deg, #22c55e, #14b8a6)',
        icon: 'CheckIcon',
    },
    {
        label: 'บัตรเสีย',
        value: results.value.national?.invalid_votes || 0,
        gradient: 'linear-gradient(135deg, #6b7280, #4b5563)',
        icon: 'XIcon',
    },
]);

const regionalResults = computed(() => {
    return Object.entries(regions).map(([key, region]) => ({
        key,
        ...region,
        totalSeats: results.value.regions?.[key]?.total_seats || 0,
        topParties: results.value.regions?.[key]?.parties || [],
    }));
});

const recentUpdates = ref([
    { id: 1, time: '19:45', message: 'กรุงเทพฯ นับเสร็จ 90%', party: { color: '#FF6B00' } },
    {
        id: 2,
        time: '19:42',
        message: 'เชียงใหม่ ประกาศผลอย่างเป็นทางการ',
        party: { color: '#E31E25' },
    },
    { id: 3, time: '19:38', message: 'ขอนแก่น นับเสร็จ 100%', party: { color: '#0066B3' } },
]);

// Methods
const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
    lastUpdate.value = now.toLocaleString('th-TH');
};

const onProvinceSelected = (province) => {
    console.log('Province selected:', province);
};

const viewProvinceDetails = (province) => {
    router.visit(`/province/${province.id}`);
};

// Real-time updates
let echoChannel = null;
let timeInterval = null;

onMounted(() => {
    updateTime();
    timeInterval = setInterval(updateTime, 1000);

    // Subscribe to real-time updates
    if (window.Echo) {
        echoChannel = window.Echo.channel(`election.${electionId.value}`).listen(
            'ResultsUpdated',
            (event) => {
                results.value = event.results;
                recentUpdates.value.unshift({
                    id: Date.now(),
                    time: new Date().toLocaleTimeString('th-TH', {
                        hour: '2-digit',
                        minute: '2-digit',
                    }),
                    message: event.message || 'อัปเดตผลคะแนน',
                    party: event.party,
                });
                if (recentUpdates.value.length > 10) {
                    recentUpdates.value.pop();
                }
            }
        );
    }
});

onUnmounted(() => {
    clearInterval(timeInterval);
    echoChannel?.stopListening('ResultsUpdated');
});

// Icon components
const ChartIcon = {
    template:
        '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
};
const UsersIcon = {
    template:
        '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
};
const CheckIcon = {
    template:
        '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
};
const XIcon = {
    template:
        '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
};

// CountUp component
const CountUp = {
    props: ['endVal', 'duration'],
    setup(props) {
        const displayValue = ref(0);
        onMounted(() => {
            const step = props.endVal / (props.duration * 60);
            const animate = () => {
                if (displayValue.value < props.endVal) {
                    displayValue.value = Math.min(displayValue.value + step, props.endVal);
                    requestAnimationFrame(animate);
                }
            };
            animate();
        });
        return { displayValue };
    },
    template: '<span>{{ Math.floor(displayValue).toLocaleString("th-TH") }}</span>',
};
</script>

<style scoped>
.election-live {
    @apply min-h-screen bg-gray-900;
}

/* Hero Header */
.hero-header {
    @apply relative overflow-hidden;
}

.hero-bg {
    @apply absolute inset-0;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
}

.hero-bg::before {
    content: '';
    @apply absolute inset-0;
    background:
        radial-gradient(circle at 20% 50%, rgba(249, 115, 22, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 50%, rgba(239, 68, 68, 0.1) 0%, transparent 50%);
}

.hero-content {
    @apply relative z-10;
}

.text-gradient {
    background: linear-gradient(135deg, #f97316, #ef4444);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.live-badge {
    @apply flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-full;
}

.pulse {
    @apply w-3 h-3 bg-white rounded-full animate-pulse;
}

.time-display {
    @apply bg-black/30 backdrop-blur-sm px-4 py-2 rounded-xl;
}

/* Stats Grid */
.stats-grid {
    @apply grid grid-cols-2 lg:grid-cols-4 gap-4;
}

.stat-card {
    @apply flex items-center gap-4 bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/10;
}

.stat-icon {
    @apply w-12 h-12 rounded-xl flex items-center justify-center text-white;
}

.stat-value {
    @apply text-2xl font-bold text-white;
}

.stat-label {
    @apply text-sm text-white/60;
}

.stat-change {
    @apply text-xs px-2 py-1 rounded-full ml-auto;
}

.stat-change.positive {
    @apply bg-green-500/20 text-green-400;
}

/* Section Cards */
.section-card {
    @apply bg-white rounded-3xl shadow-xl overflow-hidden;
}

.section-header {
    @apply flex items-center justify-between p-6 border-b border-gray-100;
}

.section-title {
    @apply flex items-center gap-3 text-xl font-bold text-gray-800;
}

/* Progress Ring */
.progress-ring {
    @apply relative;
}

.progress-text {
    @apply absolute inset-0 flex items-center justify-center text-sm font-bold text-gray-700;
}

/* Party Rankings */
.party-ranking {
    @apply p-4;
}

.party-rank-item {
    @apply flex items-center gap-3 p-3 rounded-xl bg-gray-50 transition-all hover:bg-gray-100;
}

.party-rank-item.is-leading {
    @apply bg-gradient-to-r from-orange-50 to-red-50 border-2 border-orange-200;
}

.rank-badge {
    @apply w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm;
}

.rank-1 {
    @apply bg-yellow-400 text-yellow-900;
}
.rank-2 {
    @apply bg-gray-300 text-gray-700;
}
.rank-3 {
    @apply bg-orange-300 text-orange-800;
}
.rank-badge:not(.rank-1):not(.rank-2):not(.rank-3) {
    @apply bg-gray-200 text-gray-600;
}

.party-color {
    @apply w-4 h-4 rounded;
}

.party-name {
    @apply font-medium text-gray-800 block;
}

.party-bar {
    @apply w-full h-1.5 bg-gray-200 rounded-full mt-1 overflow-hidden;
}

.party-bar-fill {
    @apply h-full rounded-full transition-all duration-1000;
}

.party-seats {
    @apply text-right;
}

.seats-count {
    @apply text-2xl font-bold text-gray-800 block;
}

.seats-label {
    @apply text-xs text-gray-500;
}

/* Type Comparison */
.type-comparison {
    @apply flex items-center justify-center gap-6 p-6;
}

.type-item {
    @apply text-center;
}

.type-circle {
    @apply w-24 h-24 rounded-full flex items-center justify-center mb-2;
}

.type-circle.constituency {
    background: linear-gradient(135deg, #f97316, #ea580c);
}

.type-circle.partylist {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.type-value {
    @apply text-3xl font-bold text-white;
}

.type-label {
    @apply text-sm font-medium text-gray-700 block;
}

.type-total {
    @apply text-xs text-gray-400;
}

.type-divider {
    @apply flex flex-col items-center;
}

.divider-line {
    @apply w-px h-8 bg-gray-300;
}

.divider-vs {
    @apply text-gray-400 font-bold py-2;
}

/* Updates */
.updates-list {
    @apply p-4 max-h-60 overflow-y-auto;
}

.update-item {
    @apply flex items-start gap-3 py-3 border-b border-gray-100 last:border-0;
}

.update-time {
    @apply text-xs font-mono text-gray-400 w-12;
}

.update-content {
    @apply flex items-center gap-2;
}

.update-badge {
    @apply w-2 h-2 rounded-full;
}

.update-text {
    @apply text-sm text-gray-700;
}

/* Region Grid */
.region-grid {
    @apply grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 p-6;
}

.region-card {
    @apply bg-gray-50 rounded-2xl p-4 border-l-4;
    border-color: var(--region-color);
}

.region-header {
    @apply flex items-center justify-between mb-3;
}

.region-name {
    @apply font-bold text-gray-800;
}

.region-seats {
    @apply text-xs text-gray-500;
}

.region-parties {
    @apply space-y-2;
}

.region-party {
    @apply flex items-center gap-2;
}

.region-party-color {
    @apply w-3 h-3 rounded;
}

.region-party-name {
    @apply text-sm text-gray-600 flex-1;
}

.region-party-seats {
    @apply text-sm font-bold text-gray-800;
}

/* Footer */
.footer {
    @apply bg-gray-800 border-t border-gray-700;
}

/* Animations */
.ranking-move,
.ranking-enter-active,
.ranking-leave-active {
    transition: all 0.5s ease;
}

.ranking-enter-from,
.ranking-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

.ranking-leave-active {
    position: absolute;
}

.update-move,
.update-enter-active,
.update-leave-active {
    transition: all 0.3s ease;
}

.update-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.update-leave-to {
    opacity: 0;
}
</style>
