<template>
    <canvas ref="chartCanvas"></canvas>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

const props = defineProps({
    results: { type: Array, default: () => [] },
    maxItems: { type: Number, default: 10 },
    valueKey: { type: String, default: 'total_votes' },
    labelSuffix: { type: String, default: 'คะแนน' },
});

const chartCanvas = ref(null);
let chartInstance = null;

function buildChart() {
    if (!chartCanvas.value) return;
    if (chartInstance) chartInstance.destroy();

    const top = props.results.slice(0, props.maxItems);
    const labels = top.map(r => r.party?.abbreviation || r.party?.name_th?.substring(0, 6) || '?');
    const data = top.map(r => r[props.valueKey] || 0);
    const colors = top.map(r => r.party?.color || '#6b7280');

    chartInstance = new Chart(chartCanvas.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors.map(c => c + 'cc'),
                borderColor: colors,
                borderWidth: 1,
                borderRadius: 6,
                barPercentage: 0.7,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                    ticks: {
                        color: 'rgba(255,255,255,0.3)',
                        font: { size: 10 },
                        callback(v) {
                            if (v >= 1_000_000) return (v / 1_000_000).toFixed(1) + 'M';
                            if (v >= 1_000) return (v / 1_000).toFixed(0) + 'K';
                            return v;
                        },
                    },
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        color: 'rgba(255,255,255,0.6)',
                        font: { size: 11, weight: 'bold' },
                    },
                },
            },
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
                        title(items) {
                            const idx = items[0]?.dataIndex;
                            return top[idx]?.party?.name_th || '';
                        },
                        label(ctx) {
                            return ` ${new Intl.NumberFormat('th-TH').format(ctx.parsed.x)} ${props.labelSuffix}`;
                        },
                    },
                },
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart',
            },
        },
    });
}

onMounted(buildChart);
watch(() => props.results, buildChart, { deep: true });
onUnmounted(() => { if (chartInstance) chartInstance.destroy(); });
</script>
