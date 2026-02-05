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
        default: 300,
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
    const categories = props.data.filter(c => c.goalCount > 0);

    return {
        labels: categories.map(c => `${c.icon} ${c.name}`),
        datasets: [{
            label: 'Average Progress',
            data: categories.map(c => c.avgProgress),
            backgroundColor: categories.map(c => c.color + 'CC'), // Add alpha
            borderColor: categories.map(c => c.color),
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y', // Horizontal bar chart
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
                label: (context) => {
                    const category = props.data.find(c => `${c.icon} ${c.name}` === context.label);
                    return [
                        `Progress: ${context.parsed.x}%`,
                        `Goals: ${category?.goalCount || 0}`,
                        `Completed: ${category?.completedCount || 0}`,
                    ];
                },
            },
        },
    },
    scales: {
        x: {
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
        y: {
            ticks: {
                color: isDarkMode.value ? '#9CA3AF' : '#6B7280',
                font: { size: 12 },
            },
            grid: {
                display: false,
            },
            border: { display: false },
        },
    },
}));

const hasData = computed(() => {
    return props.data.some(c => c.goalCount > 0);
});
</script>

<template>
    <div :style="{ height: `${height}px` }">
        <Bar
            v-if="hasData"
            :key="isDarkMode"
            :data="chartData"
            :options="chartOptions"
        />
        <div
            v-else
            class="h-full flex flex-col items-center justify-center text-gray-500 dark:text-gray-400"
        >
            <div class="text-4xl mb-2">📊</div>
            <p class="font-medium">No goals yet</p>
            <p class="text-sm">Create goals to see category comparison</p>
        </div>
    </div>
</template>
