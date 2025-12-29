<template>
    <div class="thailand-map-container relative" ref="mapContainer">
        <!-- Zoom controls -->
        <div class="absolute top-4 right-4 z-10 flex flex-col gap-2">
            <button @click="zoomIn" class="btn-zoom">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
            <button @click="zoomOut" class="btn-zoom">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
            </button>
            <button @click="resetZoom" class="btn-zoom">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </button>
        </div>

        <!-- Region filter -->
        <div class="absolute top-4 left-4 z-10">
            <select v-model="selectedRegion" class="px-4 py-2 rounded-lg bg-white shadow-md border-0 focus:ring-2 focus:ring-primary">
                <option value="">ทุกภาค</option>
                <option value="north">ภาคเหนือ</option>
                <option value="northeast">ภาคตะวันออกเฉียงเหนือ</option>
                <option value="central">ภาคกลาง</option>
                <option value="east">ภาคตะวันออก</option>
                <option value="west">ภาคตะวันตก</option>
                <option value="south">ภาคใต้</option>
            </select>
        </div>

        <!-- SVG Map -->
        <svg
            ref="mapSvg"
            :viewBox="viewBox"
            class="w-full h-full"
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
                    :d="province.path"
                    :fill="getProvinceColor(province)"
                    class="province-path"
                    @click="selectProvince(province)"
                    @mouseenter="showTooltip($event, province)"
                    @mouseleave="hideTooltip"
                />

                <!-- Constituency boundaries (when zoomed in) -->
                <g v-if="scale > 2 && selectedProvince">
                    <path
                        v-for="constituency in selectedProvince.constituencies"
                        :key="constituency.id"
                        :d="constituency.path"
                        fill="transparent"
                        stroke="#333"
                        stroke-width="0.5"
                        stroke-dasharray="2,2"
                        class="constituency-boundary"
                        @click="selectConstituency(constituency)"
                    />
                </g>
            </g>
        </svg>

        <!-- Tooltip -->
        <Transition name="fade">
            <div
                v-if="tooltip.visible"
                class="province-tooltip"
                :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
            >
                <h4 class="font-bold text-lg">{{ tooltip.province?.name_th }}</h4>
                <p class="text-sm text-gray-500">{{ tooltip.province?.name_en }}</p>
                <div class="mt-2 space-y-1" v-if="tooltip.province?.results">
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
                <div class="mt-2 pt-2 border-t border-gray-100" v-if="tooltip.province?.counting_progress">
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
        <div class="absolute bottom-4 left-4 z-10 bg-white rounded-lg shadow-lg p-4">
            <h5 class="font-semibold text-sm mb-2">พรรคที่ชนะ</h5>
            <div class="space-y-1">
                <div
                    v-for="party in topParties"
                    :key="party.id"
                    class="flex items-center gap-2"
                >
                    <div
                        class="w-4 h-4 rounded"
                        :style="{ backgroundColor: party.color }"
                    ></div>
                    <span class="text-sm">{{ party.abbreviation }}</span>
                    <span class="text-xs text-gray-500 ml-auto">{{ party.provinces_won }} จังหวัด</span>
                </div>
            </div>
        </div>

        <!-- Selected Province Panel -->
        <Transition name="slide-left">
            <div
                v-if="selectedProvince"
                class="absolute top-0 right-0 h-full w-80 bg-white shadow-2xl z-20 overflow-y-auto"
            >
                <div class="sticky top-0 bg-white border-b border-gray-100 p-4 flex items-center justify-between">
                    <h3 class="font-bold text-xl">{{ selectedProvince.name_th }}</h3>
                    <button @click="selectedProvince = null" class="p-2 hover:bg-gray-100 rounded-full">
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
                            <p class="text-xl font-bold">{{ selectedProvince.total_constituencies }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500">ผู้มีสิทธิ์</p>
                            <p class="text-xl font-bold">{{ formatNumber(selectedProvince.eligible_voters) }}</p>
                        </div>
                    </div>

                    <!-- Results by Party -->
                    <div>
                        <h4 class="font-semibold mb-3">ผลคะแนนรายพรรค</h4>
                        <div class="space-y-3">
                            <div
                                v-for="result in selectedProvince.results"
                                :key="result.party_id"
                                class="relative"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-2">
                                        <img
                                            v-if="result.party?.logo"
                                            :src="result.party.logo"
                                            class="w-6 h-6 rounded"
                                        />
                                        <span class="font-medium">{{ result.party?.name_th }}</span>
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
                                    <span>{{ result.vote_percentage }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Constituency List -->
                    <div>
                        <h4 class="font-semibold mb-3">รายเขตเลือกตั้ง</h4>
                        <div class="space-y-2">
                            <button
                                v-for="const in selectedProvince.constituencies"
                                :key="const.id"
                                @click="selectConstituency(const)"
                                class="w-full text-left p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">เขต {{ const.number }}</span>
                                    <div
                                        class="px-2 py-1 rounded text-white text-xs"
                                        :style="{ backgroundColor: const.winner?.party?.color }"
                                    >
                                        {{ const.winner?.party?.abbreviation }}
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ const.winner?.candidate?.full_name }}
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

const props = defineProps({
    electionId: {
        type: Number,
        required: true
    },
    provinces: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['province-selected', 'constituency-selected']);

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
const viewBox = ref('0 0 800 1000');

// Selection state
const selectedProvince = ref(null);
const selectedConstituency = ref(null);
const selectedRegion = ref('');

// Tooltip state
const tooltip = ref({
    visible: false,
    x: 0,
    y: 0,
    province: null
});

// Computed
const filteredProvinces = computed(() => {
    if (!selectedRegion.value) return props.provinces;
    return props.provinces.filter(p => p.region === selectedRegion.value);
});

const topParties = computed(() => {
    return resultsStore.nationalResults
        .sort((a, b) => b.total_seats - a.total_seats)
        .slice(0, 5)
        .map(r => ({
            ...r.party,
            provinces_won: resultsStore.getProvincesWonByParty(r.party_id)
        }));
});

// Methods
const getProvinceColor = (province) => {
    const results = resultsStore.getProvinceResults(province.id);
    if (!results.length) return '#E5E7EB'; // Gray for no data

    const winner = results.sort((a, b) => b.seats_won - a.seats_won)[0];
    return winner?.party?.color || '#E5E7EB';
};

const selectProvince = (province) => {
    selectedProvince.value = {
        ...province,
        results: resultsStore.getProvinceResults(province.id),
        constituencies: resultsStore.getConstituencies(province.id)
    };
    emit('province-selected', province);
};

const selectConstituency = (constituency) => {
    selectedConstituency.value = constituency;
    emit('constituency-selected', constituency);
};

const showTooltip = (event, province) => {
    const rect = mapContainer.value.getBoundingClientRect();
    tooltip.value = {
        visible: true,
        x: event.clientX - rect.left,
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
    if (!isPanning.value) return;
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
    return new Intl.NumberFormat('th-TH').format(num);
};

// Lifecycle
onMounted(() => {
    // Subscribe to real-time updates
    window.Echo.channel(`election.${props.electionId}`)
        .listen('ResultsUpdated', (event) => {
            resultsStore.updateResults(event.results);
        });
});
</script>

<style scoped>
.thailand-map-container {
    @apply w-full h-[600px] lg:h-[800px] bg-gradient-to-b from-blue-50 to-blue-100 rounded-2xl overflow-hidden;
}

.btn-zoom {
    @apply w-10 h-10 bg-white rounded-lg shadow-md flex items-center justify-center hover:bg-gray-50 transition-colors;
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
</style>
