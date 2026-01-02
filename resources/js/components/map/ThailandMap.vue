<template>
    <div ref="mapContainer" class="thailand-map-container relative">
        <!-- Zoom controls -->
        <div class="absolute top-4 right-4 z-10 flex flex-col gap-2">
            <button class="btn-zoom" title="ซูมเข้า" @click="zoomIn">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
            <button class="btn-zoom" title="ซูมออก" @click="zoomOut">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
            </button>
            <button class="btn-zoom" title="รีเซ็ต" @click="resetZoom">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </button>
        </div>

        <!-- Region filter -->
        <div class="absolute top-4 left-4 z-10">
            <select v-model="selectedRegion" class="px-4 py-2 rounded-lg bg-white shadow-md border-0 focus:ring-2 focus:ring-primary text-sm">
                <option value="">ทุกภาค</option>
                <option v-for="(region, key) in regions" :key="key" :value="key">
                    {{ region.name_th }}
                </option>
            </select>
        </div>

        <!-- SVG Map -->
        <svg
            ref="mapSvg"
            :viewBox="viewBox"
            class="w-full h-full cursor-grab"
            :class="{ 'cursor-grabbing': isPanning }"
            @mousedown="startPan"
            @mousemove="pan"
            @mouseup="endPan"
            @mouseleave="endPan"
            @wheel.prevent="handleWheel"
            @touchstart="handleTouchStart"
            @touchmove="handleTouchMove"
            @touchend="handleTouchEnd"
        >
            <g :transform="`translate(${panX}, ${panY}) scale(${scale})`">
                <!-- Province paths -->
                <path
                    v-for="province in filteredProvinces"
                    :key="province.code"
                    :d="getProvincePath(province.code)"
                    :fill="getProvinceColor(province)"
                    :stroke="selectedProvince?.code === province.code ? '#1F2937' : '#ffffff'"
                    :stroke-width="selectedProvince?.code === province.code ? 2 : 1"
                    class="province-path transition-all duration-300"
                    @click="selectProvince(province)"
                    @mouseenter="showTooltip($event, province)"
                    @mouseleave="hideTooltip"
                />

                <!-- Province labels (when zoomed in) -->
                <g v-if="scale > 1.5">
                    <text
                        v-for="province in filteredProvinces"
                        :key="'label-' + province.code"
                        :x="getProvinceCenter(province.code).x"
                        :y="getProvinceCenter(province.code).y"
                        class="province-label"
                        text-anchor="middle"
                        dominant-baseline="middle"
                    >
                        {{ province.name_th }}
                    </text>
                </g>
            </g>
        </svg>

        <!-- Hexagon button for detail view -->
        <Transition name="scale">
            <button
                v-if="selectedProvince"
                class="hexagon-button"
                title="ดูรายละเอียด"
                @click="openProvinceDetail(selectedProvince)"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </Transition>

        <!-- Tooltip -->
        <Transition name="fade">
            <div
                v-if="tooltip.visible"
                class="province-tooltip"
                :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
            >
                <div class="flex items-center gap-2 mb-2">
                    <div
                        class="w-4 h-4 rounded"
                        :style="{ backgroundColor: getLeadingPartyColor(tooltip.province) }"
                    ></div>
                    <h4 class="font-bold text-lg">{{ tooltip.province?.name_th }}</h4>
                </div>
                <p class="text-sm text-gray-500">{{ tooltip.province?.name_en }}</p>
                <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-gray-500">เขตเลือกตั้ง:</span>
                        <span class="font-semibold ml-1">{{ tooltip.province?.constituencies }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">ประชากร:</span>
                        <span class="font-semibold ml-1">{{ formatNumber(tooltip.province?.population) }}</span>
                    </div>
                </div>
                <div v-if="tooltip.province?.results?.length" class="mt-2 space-y-1">
                    <div
                        v-for="result in tooltip.province.results.slice(0, 3)"
                        :key="result.party_id"
                        class="flex items-center gap-2"
                    >
                        <div
                            class="w-3 h-3 rounded-full"
                            :style="{ backgroundColor: result.party?.color }"
                        ></div>
                        <span class="text-sm">{{ result.party?.abbreviation }}</span>
                        <span class="text-sm font-semibold ml-auto">{{ result.seats_won }} ที่นั่ง</span>
                    </div>
                </div>
                <div v-if="tooltip.province?.counting_progress !== undefined" class="mt-2 pt-2 border-t border-gray-100">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">นับคะแนน</span>
                        <span class="font-semibold">{{ tooltip.province.counting_progress }}%</span>
                    </div>
                    <div class="w-full h-1 bg-gray-200 rounded-full mt-1">
                        <div
                            class="h-full bg-green-500 rounded-full transition-all duration-500"
                            :style="{ width: tooltip.province.counting_progress + '%' }"
                        ></div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Legend -->
        <div class="absolute bottom-4 left-4 z-10 bg-white rounded-lg shadow-lg p-4 max-w-xs">
            <h5 class="font-semibold text-sm mb-2">พรรคที่นำ</h5>
            <div class="space-y-1 max-h-40 overflow-y-auto">
                <div
                    v-for="party in topParties"
                    :key="party.id"
                    class="flex items-center gap-2"
                >
                    <div
                        class="w-4 h-4 rounded"
                        :style="{ backgroundColor: party.color }"
                    ></div>
                    <span class="text-sm">{{ party.name_th || party.abbreviation }}</span>
                    <span class="text-xs text-gray-500 ml-auto">{{ party.provinces_won }} จังหวัด</span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500">
                <p>รวม {{ totalConstituencies }} เขต</p>
            </div>
        </div>

        <!-- Selected Province Panel -->
        <Transition name="slide-left">
            <div
                v-if="selectedProvince && showDetailPanel"
                class="absolute top-0 right-0 h-full w-80 bg-white shadow-2xl z-20 overflow-y-auto"
            >
                <div class="sticky top-0 bg-white border-b border-gray-100 p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-4 h-4 rounded"
                            :style="{ backgroundColor: getLeadingPartyColor(selectedProvince) }"
                        ></div>
                        <h3 class="font-bold text-xl">{{ selectedProvince.name_th }}</h3>
                    </div>
                    <button class="p-2 hover:bg-gray-100 rounded-full" @click="closeDetailPanel">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 space-y-4">
                    <!-- Province Stats -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500">เขตเลือกตั้ง</p>
                            <p class="text-xl font-bold">{{ selectedProvince.constituencies }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500">ประชากร</p>
                            <p class="text-xl font-bold">{{ formatCompactNumber(selectedProvince.population) }}</p>
                        </div>
                    </div>

                    <!-- Results by Party -->
                    <div v-if="selectedProvinceResults.length">
                        <h4 class="font-semibold mb-3">ผลคะแนนรายพรรค</h4>
                        <div class="space-y-3">
                            <div
                                v-for="result in selectedProvinceResults"
                                :key="result.party_id"
                                class="relative"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-6 h-6 rounded flex items-center justify-center text-white text-xs font-bold"
                                            :style="{ backgroundColor: result.party?.color }"
                                        >
                                            {{ result.party?.abbreviation?.charAt(0) }}
                                        </div>
                                        <span class="font-medium text-sm">{{ result.party?.name_th }}</span>
                                    </div>
                                    <span class="font-bold">{{ result.seats_won }} ที่นั่ง</span>
                                </div>
                                <div class="result-bar">
                                    <div
                                        class="result-bar-fill"
                                        :style="{
                                            width: result.vote_percentage + '%',
                                            backgroundColor: result.party?.color
                                        }"
                                    ></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>{{ formatNumber(result.total_votes) }} คะแนน</span>
                                    <span>{{ result.vote_percentage?.toFixed(1) }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Constituency List -->
                    <div>
                        <h4 class="font-semibold mb-3">รายเขตเลือกตั้ง ({{ selectedProvince.constituencies }} เขต)</h4>
                        <div class="space-y-2">
                            <button
                                v-for="constituency in provinceConstituencies"
                                :key="constituency.id"
                                class="w-full text-left p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                @click="selectConstituency(constituency)"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">เขต {{ constituency.number }}</span>
                                    <div
                                        v-if="constituency.winner"
                                        class="px-2 py-1 rounded text-white text-xs"
                                        :style="{ backgroundColor: constituency.winner?.party?.color }"
                                    >
                                        {{ constituency.winner?.party?.abbreviation }}
                                    </div>
                                    <span v-else class="text-xs text-gray-400">กำลังนับ...</span>
                                </div>
                                <p v-if="constituency.winner" class="text-sm text-gray-500 mt-1">
                                    {{ constituency.winner?.candidate?.full_name }}
                                </p>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useResultsStore } from '@/stores/results';
import { provinces as provinceData, provincePaths, regions, totalConstituencies } from '@/data/provinces';
import { getConstituenciesByProvinceId } from '@/data/constituencies';

const props = defineProps({
    electionId: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['provinceSelected', 'constituencySelected']);

const resultsStore = useResultsStore();
const mapContainer = ref(null);
const mapSvg = ref(null);

// Map state
const scale = ref(1);
const panX = ref(0);
const panY = ref(0);
const isPanning = ref(false);
const startX = ref(0);
const startY = ref(0);
const viewBox = ref('0 0 500 1200');

// Selection state
const selectedProvince = ref(null);
const selectedConstituency = ref(null);
const selectedRegion = ref('');
const showDetailPanel = ref(false);

// Tooltip state
const tooltip = ref({
    visible: false,
    x: 0,
    y: 0,
    province: null
});

// Computed
const filteredProvinces = computed(() => {
    const provinces = provinceData.map(p => ({
        ...p,
        path: provincePaths[p.code]
    }));
    if (!selectedRegion.value) {return provinces;}
    return provinces.filter(p => p.region === selectedRegion.value);
});

const topParties = computed(() => {
    const partyWins = {};

    provinceData.forEach(province => {
        const results = resultsStore.getProvinceResults(province.id);
        if (results.length) {
            const winner = results.sort((a, b) => b.seats_won - a.seats_won)[0];
            if (winner?.party) {
                if (!partyWins[winner.party.id]) {
                    partyWins[winner.party.id] = {
                        ...winner.party,
                        provinces_won: 0
                    };
                }
                partyWins[winner.party.id].provinces_won++;
            }
        }
    });

    return Object.values(partyWins)
        .sort((a, b) => b.provinces_won - a.provinces_won)
        .slice(0, 6);
});

const selectedProvinceResults = computed(() => {
    if (!selectedProvince.value) {return [];}
    return resultsStore.getProvinceResults(selectedProvince.value.id)
        .sort((a, b) => b.seats_won - a.seats_won);
});

const provinceConstituencies = computed(() => {
    if (!selectedProvince.value) {return [];}
    const constituencies = getConstituenciesByProvinceId(selectedProvince.value.id);
    return constituencies.map(c => ({
        ...c,
        winner: resultsStore.constituencyResults.find(r => r.constituency_id === c.id)
    }));
});

// Methods
const getProvincePath = (code) => provincePaths[code] || '';

const getProvinceCenter = (code) => {
    const path = provincePaths[code];
    if (!path) {return { x: 0, y: 0 };}

    // Simple center calculation from path
    const matches = path.match(/[ML]\s*(\d+),(\d+)/g);
    if (!matches) {return { x: 0, y: 0 };}

    let sumX = 0, sumY = 0;
    matches.forEach(m => {
        const coords = m.match(/(\d+),(\d+)/);
        if (coords) {
            sumX += parseInt(coords[1]);
            sumY += parseInt(coords[2]);
        }
    });

    return {
        x: sumX / matches.length,
        y: sumY / matches.length
    };
};

const getProvinceColor = (province) => {
    const results = resultsStore.getProvinceResults(province.id);
    if (!results.length) {
        // Return region color if no results
        return regions[province.region]?.color || '#E5E7EB';
    }

    const winner = results.sort((a, b) => b.seats_won - a.seats_won)[0];
    return winner?.party?.color || '#E5E7EB';
};

const getLeadingPartyColor = (province) => {
    if (!province) {return '#E5E7EB';}
    const results = resultsStore.getProvinceResults(province.id);
    if (!results.length) {return regions[province.region]?.color || '#E5E7EB';}

    const winner = results.sort((a, b) => b.seats_won - a.seats_won)[0];
    return winner?.party?.color || '#E5E7EB';
};

const selectProvince = (province) => {
    selectedProvince.value = province;
    emit('provinceSelected', province);
};

const openProvinceDetail = (province) => {
    showDetailPanel.value = true;
    emit('provinceSelected', province);
};

const closeDetailPanel = () => {
    showDetailPanel.value = false;
};

const selectConstituency = (constituency) => {
    selectedConstituency.value = constituency;
    emit('constituencySelected', constituency);
};

const showTooltip = (event, province) => {
    const rect = mapContainer.value.getBoundingClientRect();
    tooltip.value = {
        visible: true,
        x: event.clientX - rect.left + 15,
        y: event.clientY - rect.top - 10,
        province: {
            ...province,
            results: resultsStore.getProvinceResults(province.id),
            counting_progress: resultsStore.getProvinceCountingProgress(province.id)
        }
    };
};

const hideTooltip = () => {
    tooltip.value.visible = false;
};

// Zoom & Pan
const zoomIn = () => {
    scale.value = Math.min(scale.value * 1.5, 10);
};

const zoomOut = () => {
    scale.value = Math.max(scale.value / 1.5, 0.5);
};

const resetZoom = () => {
    scale.value = 1;
    panX.value = 0;
    panY.value = 0;
};

const handleWheel = (event) => {
    const delta = event.deltaY > 0 ? 0.9 : 1.1;
    scale.value = Math.max(0.5, Math.min(10, scale.value * delta));
};

const startPan = (event) => {
    isPanning.value = true;
    startX.value = event.clientX - panX.value;
    startY.value = event.clientY - panY.value;
};

const pan = (event) => {
    if (!isPanning.value) {return;}
    panX.value = event.clientX - startX.value;
    panY.value = event.clientY - startY.value;
};

const endPan = () => {
    isPanning.value = false;
};

// Touch handlers
let lastTouchDistance = 0;

const handleTouchStart = (event) => {
    if (event.touches.length === 2) {
        lastTouchDistance = Math.hypot(
            event.touches[0].clientX - event.touches[1].clientX,
            event.touches[0].clientY - event.touches[1].clientY
        );
    } else if (event.touches.length === 1) {
        startX.value = event.touches[0].clientX - panX.value;
        startY.value = event.touches[0].clientY - panY.value;
        isPanning.value = true;
    }
};

const handleTouchMove = (event) => {
    if (event.touches.length === 2) {
        const distance = Math.hypot(
            event.touches[0].clientX - event.touches[1].clientX,
            event.touches[0].clientY - event.touches[1].clientY
        );
        const delta = distance / lastTouchDistance;
        scale.value = Math.max(0.5, Math.min(10, scale.value * delta));
        lastTouchDistance = distance;
    } else if (event.touches.length === 1 && isPanning.value) {
        panX.value = event.touches[0].clientX - startX.value;
        panY.value = event.touches[0].clientY - startY.value;
    }
};

const handleTouchEnd = () => {
    isPanning.value = false;
};

// Utilities
const formatNumber = (num) => {
    if (!num) {return '0';}
    return new Intl.NumberFormat('th-TH').format(num);
};

const formatCompactNumber = (num) => {
    if (!num) {return '0';}
    if (num >= 1000000) {
        return `${(num / 1000000).toFixed(1)  }M`;
    }
    if (num >= 1000) {
        return `${(num / 1000).toFixed(0)  }K`;
    }
    return num.toString();
};

// Lifecycle
onMounted(() => {
    // Subscribe to real-time updates
    if (window.Echo) {
        window.Echo.channel(`election.${props.electionId}`)
            .listen('ResultsUpdated', (event) => {
                resultsStore.updateResults(event.results);
            });
    }
});
</script>

<style scoped>
@reference "tailwindcss";

.thailand-map-container {
    @apply w-full h-[600px] lg:h-[800px] bg-gradient-to-b from-blue-50 to-blue-100 rounded-2xl overflow-hidden;
}

.btn-zoom {
    @apply w-10 h-10 bg-white rounded-lg shadow-md flex items-center justify-center hover:bg-gray-50 transition-colors;
}

.province-path {
    cursor: pointer;
    transition: all 0.2s ease;
}

.province-path:hover {
    filter: brightness(1.1);
    stroke: #1F2937;
    stroke-width: 2;
}

.province-label {
    font-size: 6px;
    fill: #374151;
    pointer-events: none;
    font-weight: 500;
}

.province-tooltip {
    @apply absolute bg-white rounded-xl shadow-xl p-4 z-30 min-w-[200px] pointer-events-none;
    transform: translateY(-100%);
}

.hexagon-button {
    @apply absolute bottom-24 right-4 z-10 w-14 h-14 text-white rounded-xl shadow-lg flex items-center justify-center hover:scale-110 transition-transform;
    background: linear-gradient(to bottom right, var(--color-primary), #ea580c);
    clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
}

.result-bar {
    @apply w-full h-2 bg-gray-100 rounded-full overflow-hidden;
}

.result-bar-fill {
    @apply h-full rounded-full transition-all duration-500;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-left-enter-active,
.slide-left-leave-active {
    transition: transform 0.3s ease;
}

.slide-left-enter-from,
.slide-left-leave-to {
    transform: translateX(100%);
}

.scale-enter-active,
.scale-leave-active {
    transition: all 0.3s ease;
}

.scale-enter-from,
.scale-leave-to {
    transform: scale(0);
    opacity: 0;
}
</style>
