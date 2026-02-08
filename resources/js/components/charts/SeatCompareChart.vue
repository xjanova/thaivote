<template>
    <canvas ref="chartCanvas"></canvas>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({
    results: { type: Array, default: () => [] },
    maxItems: { type: Number, default: 10 },
});

const chartCanvas = ref(null);
let chartInstance = null;

function buildChart() {
    if (!chartCanvas.value) return;
    if (chartInstance) chartInstance.destroy();

    const top = props.results.slice(0, props.maxItems);
    const labels = top.map(r => r.party?.abbreviation || r.party?.name_th?.substring(0, 6) || '?');

    chartInstance = new Chart(chartCanvas.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'แบ่งเขต',
                    data: top.map(r => r.constituency_seats || 0),
                    backgroundColor: top.map(r => (r.party?.color || '#6b7280') + 'cc'),
                    borderColor: top.map(r => r.party?.color || '#6b7280'),
                    borderWidth: 1,
                    borderRadius: { topLeft: 6, topRight: 6 },
                    barPercentage: 0.7,
                },
                {
                    label: 'บัญชีรายชื่อ',
                    data: top.map(r => r.party_list_seats || 0),
                    backgroundColor: top.map(r => (r.party?.color || '#6b7280') + '55'),
                    borderColor: top.map(r => (r.party?.color || '#6b7280') + '99'),
                    borderWidth: 1,
                    borderRadius: { topLeft: 6, topRight: 6 },
                    barPercentage: 0.7,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: {
                        color: 'rgba(255,255,255,0.6)',
                        font: { size: 10, weight: 'bold' },
                    },
                },
                y: {
                    stacked: true,
                    grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                    ticks: {
                        color: 'rgba(255,255,255,0.3)',
                        font: { size: 10 },
                    },
                },
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: 'rgba(255,255,255,0.5)',
                        font: { size: 10 },
                        boxWidth: 12,
                        boxHeight: 12,
                        borderRadius: 3,
                        useBorderRadius: true,
                        padding: 12,
                    },
                },
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
                            return ` ${ctx.dataset.label}: ${ctx.parsed.y} ที่นั่ง`;
                        },
                        footer(items) {
                            const idx = items[0]?.dataIndex;
                            const r = top[idx];
                            if (!r) return '';
                            return `รวม: ${r.total_seats || 0} ที่นั่ง`;
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
