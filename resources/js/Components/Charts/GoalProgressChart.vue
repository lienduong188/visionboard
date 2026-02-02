<script setup>
import { computed } from 'vue';
import ProgressLineChart from './ProgressLineChart.vue';

const props = defineProps({
    goal: {
        type: Object,
        required: true,
    },
    progressLogs: {
        type: Array,
        default: () => [],
    },
});

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('ja-JP', { month: 'short', day: 'numeric' });
};

const chartData = computed(() => {
    const labels = [];
    const data = [];

    // If no progress logs, show current state as single point
    if (!props.progressLogs || props.progressLogs.length === 0) {
        if (props.goal) {
            labels.push(formatDate(new Date()));
            data.push(props.goal.progress || 0);
        }
        return {
            labels,
            datasets: [{
                label: 'Tiến độ (%)',
                data,
                borderColor: '#6366F1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
            }]
        };
    }

    // Sort logs by date ascending
    const sortedLogs = [...props.progressLogs].sort(
        (a, b) => new Date(a.logged_at) - new Date(b.logged_at)
    );

    // Add starting point if goal has start_date
    if (props.goal?.start_date) {
        const startDate = new Date(props.goal.start_date);
        const firstLogDate = new Date(sortedLogs[0].logged_at);

        // Only add start point if it's before the first log
        if (startDate < firstLogDate) {
            labels.push(formatDate(startDate));
            data.push(0);
        }
    }

    // Add log points
    sortedLogs.forEach(log => {
        labels.push(formatDate(log.logged_at));
        data.push(log.new_progress);
    });

    // Add current point if different from last log date
    const lastLogDate = sortedLogs[sortedLogs.length - 1]?.logged_at;
    const today = new Date().toDateString();
    const lastLogDay = lastLogDate ? new Date(lastLogDate).toDateString() : null;

    if (lastLogDay !== today && props.goal.progress !== sortedLogs[sortedLogs.length - 1]?.new_progress) {
        labels.push(formatDate(new Date()));
        data.push(props.goal.progress);
    }

    return {
        labels,
        datasets: [{
            label: 'Tiến độ (%)',
            data,
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
        }]
    };
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            📈 Biểu đồ tiến độ
        </h3>
        <ProgressLineChart
            :chart-data="chartData"
            :height="220"
        />
    </div>
</template>
