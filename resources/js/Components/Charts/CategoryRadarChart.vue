<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
    Chart as ChartJS,
    RadialLinearScale,
    PointElement,
    LineElement,
    Filler,
    Tooltip,
    Legend
} from 'chart.js';
import { Radar } from 'vue-chartjs';

// Register Chart.js components
ChartJS.register(
    RadialLinearScale,
    PointElement,
    LineElement,
    Filler,
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
            label: 'Progress',
            data: categories.map(c => c.avgProgress),
            backgroundColor: 'rgba(99, 102, 241, 0.2)',
            borderColor: '#6366F1',
            borderWidth: 2,
            pointBackgroundColor: categories.map(c => c.color),
            pointBorderColor: isDarkMode.value ? '#1F2937' : '#FFFFFF',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8,
        }]
    };
});

const chartOptions = computed(() => ({
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
                label: (context) => {
                    const category = props.data.find(c => `${c.icon} ${c.name}` === context.label);
                    return [
                        `Progress: ${context.parsed.r}%`,
                        `Goals: ${category?.goalCount || 0}`,
                        `Completed: ${category?.completedCount || 0}`,
                    ];
                },
            },
        },
    },
    scales: {
        r: {
            min: 0,
            max: 100,
            beginAtZero: true,
            ticks: {
                stepSize: 25,
                color: isDarkMode.value ? '#9CA3AF' : '#6B7280',
                backdropColor: 'transparent',
                font: { size: 10 },
                callback: (value) => `${value}%`,
            },
            pointLabels: {
                color: isDarkMode.value ? '#E5E7EB' : '#374151',
                font: { size: 11 },
            },
            grid: {
                color: isDarkMode.value ? '#374151' : '#E5E7EB',
            },
            angleLines: {
                color: isDarkMode.value ? '#374151' : '#E5E7EB',
            },
        },
    },
}));

const hasData = computed(() => {
    return props.data.some(c => c.goalCount > 0);
});
</script>

<template>
    <div :style="{ height: `${height}px` }">
        <Radar
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
