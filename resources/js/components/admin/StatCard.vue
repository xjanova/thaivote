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
                <component :is="icon" class="w-6 h-6" :class="iconColor" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

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

const formatValue = (val) => {
    if (typeof val === 'number') {
        return new Intl.NumberFormat('th-TH').format(val);
    }
    return val;
};
</script>
