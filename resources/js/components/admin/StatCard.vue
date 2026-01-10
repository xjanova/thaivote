<template>
    <div class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">{{ title }}</p>
                <p class="text-3xl font-bold mt-1" :class="textColor">{{ formatValue(value) }}</p>
                <p v-if="change" class="text-sm mt-1" :class="changeColor">
                    {{ change > 0 ? '+' : '' }}{{ change }}% จากเมื่อวาน
                </p>
            </div>
            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', bgColor]">
                <component :is="resolvedIcon" class="w-6 h-6" :class="iconColor" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, h } from 'vue';

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
});

const colorClasses = {
    blue: {
        bg: 'bg-blue-100',
        icon: 'text-blue-600',
        text: 'text-blue-600',
    },
    green: {
        bg: 'bg-green-100',
        icon: 'text-green-600',
        text: 'text-green-600',
    },
    purple: {
        bg: 'bg-purple-100',
        icon: 'text-purple-600',
        text: 'text-purple-600',
    },
    orange: {
        bg: 'bg-orange-100',
        icon: 'text-orange-600',
        text: 'text-orange-600',
    },
    red: {
        bg: 'bg-red-100',
        icon: 'text-red-600',
        text: 'text-red-600',
    },
};

const bgColor = computed(() => colorClasses[props.color]?.bg || colorClasses.blue.bg);
const iconColor = computed(() => colorClasses[props.color]?.icon || colorClasses.blue.icon);
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
