<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';
import { Line } from 'vue-chartjs';

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
    height: {
        type: Number,
        default: 280,
    },
});

const selectedPeriod = ref('30');

const periods = [
    { value: '7', label: '7 days' },
    { value: '30', label: '30 days' },
    { value: '90', label: '90 days' },
];

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
    const periodData = props.data[selectedPeriod.value];
    if (!periodData) return { labels: [], datasets: [] };

    return {
        labels: periodData.labels,
        datasets: [
            {
                label: 'Overall Progress',
                data: periodData.progress,
                borderColor: '#6366F1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                pointBackgroundColor: '#6366F1',
                pointBorderColor: isDarkMode.value ? '#1F2937' : '#FFFFFF',
                pointBorderWidth: 2,
                fill: true,
                tension: 0.4,
                yAxisID: 'y',
            },
            {
                label: 'Goals Completed',
                data: periodData.completed,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                pointBackgroundColor: '#10B981',
                pointBorderColor: isDarkMode.value ? '#1F2937' : '#FFFFFF',
                pointBorderWidth: 2,
                fill: true,
                tension: 0.4,
                yAxisID: 'y1',
            },
        ]
    };
});

const chartOptions = computed(() => {
    const maxCompleted = Math.max(
        ...(props.data[selectedPeriod.value]?.completed || [0]),
        1
    );

    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    color: isDarkMode.value ? '#E5E7EB' : '#374151',
                    usePointStyle: true,
                    padding: 20,
                    font: { size: 12 },
                },
            },
            tooltip: {
                backgroundColor: isDarkMode.value ? '#374151' : '#FFFFFF',
                titleColor: isDarkMode.value ? '#F9FAFB' : '#111827',
                bodyColor: isDarkMode.value ? '#E5E7EB' : '#374151',
                borderColor: isDarkMode.value ? '#4B5563' : '#E5E7EB',
                borderWidth: 1,
                cornerRadius: 8,
                padding: 12,
            },
        },
        scales: {
            x: {
                ticks: {
                    color: isDarkMode.value ? '#9CA3AF' : '#6B7280',
                    maxTicksLimit: 7,
                    font: { size: 11 },
                },
                grid: {
                    color: isDarkMode.value ? '#374151' : '#F3F4F6',
                    drawBorder: false,
                },
                border: { display: false },
            },
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                min: 0,
                max: 100,
                ticks: {
                    color: isDarkMode.value ? '#9CA3AF' : '#6B7280',
                    stepSize: 25,
                    callback: (value) => `${value}%`,
                    font: { size: 11 },
                },
                grid: {
                    color: isDarkMode.value ? '#374151' : '#F3F4F6',
                    drawBorder: false,
                },
                border: { display: false },
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                min: 0,
                max: Math.ceil(maxCompleted * 1.2),
                ticks: {
                    color: isDarkMode.value ? '#9CA3AF' : '#6B7280',
                    stepSize: 1,
                    font: { size: 11 },
                },
                grid: {
                    drawOnChartArea: false,
                },
                border: { display: false },
            },
        },
        elements: {
            point: {
                radius: 3,
                hoverRadius: 5,
            },
        },
    };
});

const hasData = computed(() => {
    const periodData = props.data[selectedPeriod.value];
    return periodData?.labels?.length > 0;
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Completion Trend
            </h3>
            <select
                v-model="selectedPeriod"
                class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
                <option
                    v-for="period in periods"
                    :key="period.value"
                    :value="period.value"
                >
                    {{ period.label }}
                </option>
            </select>
        </div>

        <div :style="{ height: `${height}px` }">
            <Line
                v-if="hasData"
                :key="`${isDarkMode}-${selectedPeriod}`"
                :data="chartData"
                :options="chartOptions"
            />
            <div
                v-else
                class="h-full flex flex-col items-center justify-center text-gray-500 dark:text-gray-400"
            >
                <div class="text-4xl mb-2">📈</div>
                <p class="font-medium">No data yet</p>
                <p class="text-sm">Progress data will appear over time</p>
            </div>
        </div>
    </div>
</template>
