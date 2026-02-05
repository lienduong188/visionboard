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
    chartData: {
        type: Object,
        required: true,
    },
    title: {
        type: String,
        default: '',
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
    // Watch for dark mode changes
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

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        title: {
            display: !!props.title,
            text: props.title,
            color: isDarkMode.value ? '#F9FAFB' : '#111827',
            font: {
                size: 14,
                weight: 'bold',
            },
            padding: {
                bottom: 16,
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
            displayColors: false,
            callbacks: {
                label: (context) => {
                    return `Progress: ${context.parsed.y}%`;
                },
            },
        },
    },
    scales: {
        x: {
            ticks: {
                color: isDarkMode.value ? '#9CA3AF' : '#6B7280',
                maxTicksLimit: 7,
                font: {
                    size: 11,
                },
            },
            grid: {
                color: isDarkMode.value ? '#374151' : '#F3F4F6',
                drawBorder: false,
            },
            border: {
                display: false,
            },
        },
        y: {
            min: 0,
            max: 100,
            ticks: {
                color: isDarkMode.value ? '#9CA3AF' : '#6B7280',
                stepSize: 25,
                callback: (value) => `${value}%`,
                font: {
                    size: 11,
                },
            },
            grid: {
                color: isDarkMode.value ? '#374151' : '#F3F4F6',
                drawBorder: false,
            },
            border: {
                display: false,
            },
        },
    },
    interaction: {
        intersect: false,
        mode: 'index',
    },
    elements: {
        line: {
            tension: 0.4,
        },
        point: {
            radius: 4,
            hoverRadius: 6,
        },
    },
}));

// Apply theme colors to datasets
const themedChartData = computed(() => {
    const data = { ...props.chartData };
    if (data.datasets) {
        data.datasets = data.datasets.map(dataset => ({
            ...dataset,
            borderColor: dataset.borderColor || '#6366F1',
            backgroundColor: dataset.backgroundColor || 'rgba(99, 102, 241, 0.1)',
            pointBackgroundColor: dataset.borderColor || '#6366F1',
            pointBorderColor: isDarkMode.value ? '#1F2937' : '#FFFFFF',
            pointBorderWidth: 2,
            fill: true,
        }));
    }
    return data;
});

const hasData = computed(() => {
    return props.chartData?.labels?.length > 0 && props.chartData?.datasets?.[0]?.data?.length > 0;
});
</script>

<template>
    <div :style="{ height: `${height}px` }">
        <Line
            v-if="hasData"
            :key="isDarkMode"
            :data="themedChartData"
            :options="chartOptions"
        />
        <div
            v-else
            class="h-full flex flex-col items-center justify-center text-gray-500 dark:text-gray-400"
        >
            <div class="text-4xl mb-2">📊</div>
            <p class="font-medium">No progress data yet</p>
            <p class="text-sm">Update progress to see the chart</p>
        </div>
    </div>
</template>
