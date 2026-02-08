<template>
    <div class="relative">
        <canvas ref="chartCanvas"></canvas>
        <div v-if="centerLabel" class="donut-center">
            <p class="text-3xl font-bold">{{ centerValue }}</p>
            <p class="text-xs text-white/50">{{ centerLabel }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Chart, DoughnutController, ArcElement, Tooltip, Legend } from 'chart.js';

Chart.register(DoughnutController, ArcElement, Tooltip, Legend);

const props = defineProps({
    results: { type: Array, default: () => [] },
    maxItems: { type: Number, default: 10 },
    centerLabel: { type: String, default: '' },
    centerValue: { type: [String, Number], default: '' },
});

const chartCanvas = ref(null);
let chartInstance = null;

function buildChart() {
    if (!chartCanvas.value) return;
    if (chartInstance) chartInstance.destroy();

    const top = props.results.slice(0, props.maxItems);
    const others = props.results.slice(props.maxItems);
    const othersTotal = others.reduce((s, r) => s + (r.total_seats || 0), 0);

    const labels = top.map(r => r.party?.name_th || r.party?.abbreviation || 'ไม่ทราบ');
    const data = top.map(r => r.total_seats || 0);
    const colors = top.map(r => r.party?.color || '#6b7280');

    if (othersTotal > 0) {
        labels.push('พรรคอื่น');
        data.push(othersTotal);
        colors.push('#4b5563');
    }

    const remaining = 500 - data.reduce((s, v) => s + v, 0);
    if (remaining > 0) {
        labels.push('ยังไม่นับ');
        data.push(remaining);
        colors.push('rgba(255,255,255,0.04)');
    }

    chartInstance = new Chart(chartCanvas.value, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors,
                borderColor: 'rgba(0,0,0,0.3)',
                borderWidth: 1,
                hoverBorderColor: '#fff',
                hoverBorderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(17,24,39,0.95)',
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,0.7)',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 10,
                    callbacks: {
                        label(ctx) {
                            const val = ctx.parsed;
                            const pct = ((val / 500) * 100).toFixed(1);
                            return ` ${ctx.label}: ${val} ที่นั่ง (${pct}%)`;
                        },
                    },
                },
            },
            animation: {
                animateRotate: true,
                duration: 1200,
                easing: 'easeOutQuart',
            },
        },
    });
}

onMounted(buildChart);
watch(() => props.results, buildChart, { deep: true });
onUnmounted(() => { if (chartInstance) chartInstance.destroy(); });
</script>

<style scoped>
.donut-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    pointer-events: none;
}
</style>
