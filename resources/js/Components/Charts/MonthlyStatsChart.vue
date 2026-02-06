<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend
} from 'chart.js';
import { Bar } from 'vue-chartjs';

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend
);

const props = defineProps({
    data: {
        type: Array,
        required: true,
    },
    height: {
        type: Number,
        default: 250,
    },
});

// Dark mode detection
const isDarkMode = ref(false);

const checkDarkMode = () => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
};

let observer = null;

onMounted(() => {
    checkDarkMode();
    observer = new MutationObserver(checkDarkMode);
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }
});

const chartData = computed(() => {
    return {
        labels: props.data.map(d => d.month),
        datasets: [{
            label: 'Goals Completed',
            data: props.data.map(d => d.completed),
            backgroundColor: props.data.map((d, i) => {
                // Highlight current month
                const currentMonth = new Date().getMonth();
                return i === currentMonth
                    ? '#6366F1'
                    : (isDarkMode.value ? '#4B5563' : '#E5E7EB');
            }),
            borderColor: props.data.map((d, i) => {
                const currentMonth = new Date().getMonth();
                return i === currentMonth ? '#4F46E5' : 'transparent';
            }),
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
        }]
    };
});

const chartOptions = computed(() => {
    const maxCompleted = Math.max(...props.data.map(d => d.completed), 1);

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: isDarkMode.value ? '#374151' : '#FFFFFF',
                titleColor: isDarkMode.value ? '#F9FAFB' : '#111827',
                bodyColor: isDarkMode.value ? '#E5E7EB' : '#374151',
                borderColor: isDarkMode.value ? '#4B5563' : '#E5E7EB',
                borderWidth: 1,
                cornerRadius: 8,
                padding: 12,
                callbacks: {
                    title: (items) => {
                        const index = items[0].dataIndex;
                        return props.data[index].monthFull;
                    },
                    label: (context) => {
                        const count = context.parsed.y;
                        return `${count} goals completed`;
                    },
                },
            },
        },
        scales: {
            x: {
                ticks: {
                    color: isDarkMode.value ? '#9CA3AF' : '#6B7280',
                    font: { size: 11 },
                },
                grid: {
                    display: false,
                },
                border: { display: false },
            },
            y: {
                min: 0,
                max: Math.ceil(maxCompleted * 1.2) || 5,
                ticks: {
                    color: isDarkMode.value ? '#9CA3AF' : '#6B7280',
                    stepSize: 1,
                    font: { size: 11 },
                },
                grid: {
                    color: isDarkMode.value ? '#374151' : '#F3F4F6',
                    drawBorder: false,
                },
                border: { display: false },
            },
        },
    };
});

const totalCompleted = computed(() => {
    return props.data.reduce((sum, d) => sum + d.completed, 0);
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" title="Số goals hoàn thành trong từng tháng của năm">
                Monthly Completions
            </h3>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                Total: {{ totalCompleted }} goals
            </span>
        </div>

        <div :style="{ height: `${height}px` }">
            <Bar
                :key="isDarkMode"
                :data="chartData"
                :options="chartOptions"
            />
        </div>
    </div>
</template>
