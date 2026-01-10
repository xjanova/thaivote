<template>
    <div
        class="card p-6 transition-all duration-200 hover:shadow-lg hover:-translate-y-1 group"
        :class="{ 'animate-pulse': loading }"
    >
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                    {{ title }}
                </p>

                <!-- Value with loading skeleton -->
                <div v-if="loading" class="h-10 bg-gray-200 rounded w-32 mt-2 animate-pulse"></div>
                <p v-else class="text-3xl font-bold mt-2 transition-all duration-500" :class="textColor">
                    {{ displayValue }}
                </p>

                <!-- Trend Indicator -->
                <div v-if="!loading && change !== null" class="flex items-center gap-1 mt-2">
                    <component
                        :is="change >= 0 ? ArrowUpIcon : ArrowDownIcon"
                        :class="[
                            'w-4 h-4 transition-transform group-hover:scale-110',
                            change >= 0 ? 'text-green-600' : 'text-red-600',
                        ]"
                    />
                    <span class="text-sm font-medium" :class="changeColor">
                        {{ Math.abs(change) }}%
                    </span>
                    <span class="text-xs text-gray-500">จากเมื่อวาน</span>
                </div>
            </div>

            <!-- Icon with gradient background -->
            <div
                :class="[
                    'w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-300',
                    'group-hover:scale-110 group-hover:rotate-3',
                    bgGradient,
                ]"
            >
                <component :is="resolvedIcon" class="w-7 h-7 text-white" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, h, ref, watch, onMounted } from 'vue';

// Arrow icons for trend
const ArrowUpIcon = {
    template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd" /></svg>`,
};

const ArrowDownIcon = {
    template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clip-rule="evenodd" /></svg>`,
};

// Define icons as simple SVG components
const icons = {
    CalendarIcon: {
        template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>`,
    },
    UsersIcon: {
        template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>`,
    },
    NewspaperIcon: {
        template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z" /></svg>`,
    },
    DatabaseIcon: {
        template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>`,
    },
    ChartBarIcon: {
        template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>`,
    },
};

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [Number, String],
        required: true,
    },
    icon: {
        type: String,
        default: 'ChartBarIcon',
    },
    color: {
        type: String,
        default: 'blue',
    },
    change: {
        type: Number,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

// Animated value display
const displayValue = ref(0);
const animateValue = (target) => {
    if (typeof target !== 'number') {
        displayValue.value = target;
        return;
    }

    const duration = 1000;
    const start = displayValue.value || 0;
    const increment = (target - start) / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= target) || (increment < 0 && current <= target)) {
            current = target;
            clearInterval(timer);
        }
        displayValue.value = formatValue(Math.round(current));
    }, 16);
};

watch(() => props.value, (newVal) => {
    if (!props.loading) {
        animateValue(newVal);
    }
}, { immediate: true });

onMounted(() => {
    if (!props.loading) {
        animateValue(props.value);
    }
});

const colorClasses = {
    blue: {
        bg: 'bg-blue-100',
        icon: 'text-blue-600',
        text: 'text-blue-600',
        gradient: 'bg-gradient-to-br from-blue-500 to-blue-600',
    },
    green: {
        bg: 'bg-green-100',
        icon: 'text-green-600',
        text: 'text-green-600',
        gradient: 'bg-gradient-to-br from-green-500 to-green-600',
    },
    purple: {
        bg: 'bg-purple-100',
        icon: 'text-purple-600',
        text: 'text-purple-600',
        gradient: 'bg-gradient-to-br from-purple-500 to-purple-600',
    },
    orange: {
        bg: 'bg-orange-100',
        icon: 'text-orange-600',
        text: 'text-orange-600',
        gradient: 'bg-gradient-to-br from-orange-500 to-orange-600',
    },
    red: {
        bg: 'bg-red-100',
        icon: 'text-red-600',
        text: 'text-red-600',
        gradient: 'bg-gradient-to-br from-red-500 to-red-600',
    },
};

const bgGradient = computed(() => colorClasses[props.color]?.gradient || colorClasses.blue.gradient);
const textColor = computed(() => colorClasses[props.color]?.text || 'text-gray-900');
const changeColor = computed(() => (props.change >= 0 ? 'text-green-600' : 'text-red-600'));
const resolvedIcon = computed(() => icons[props.icon] || icons.ChartBarIcon);

const formatValue = (val) => {
    if (typeof val === 'number') {
        return new Intl.NumberFormat('th-TH').format(val);
    }
    return val;
};
</script>
