<script setup>
import { ref, computed, watch } from 'vue';
import ProgressLineChart from './ProgressLineChart.vue';

const props = defineProps({
    goals: {
        type: Array,
        required: true,
    },
});

const selectedPeriod = ref('30');

const periods = [
    { value: '7', label: '7 Days' },
    { value: '30', label: '30 Days' },
    { value: '90', label: '90 Days' },
];

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ja-JP', {
        month: 'short',
        day: 'numeric'
    });
};

const chartData = computed(() => {
    const days = parseInt(selectedPeriod.value);
    const today = new Date();
    const labels = [];
    const data = [];

    // Calculate current overall progress
    const currentOverallProgress = props.goals.length > 0
        ? Math.round(props.goals.reduce((sum, g) => sum + (g.progress || 0), 0) / props.goals.length)
        : 0;

    // Collect all progress logs from all goals
    const allLogs = [];
    props.goals.forEach(goal => {
        if (goal.progress_logs) {
            goal.progress_logs.forEach(log => {
                allLogs.push({
                    ...log,
                    goalId: goal.id,
                    goalProgress: goal.progress
                });
            });
        }
    });

    // Sort logs by date
    allLogs.sort((a, b) => new Date(a.logged_at) - new Date(b.logged_at));

    // Generate date labels
    const startDate = new Date(today);
    startDate.setDate(startDate.getDate() - days);

    // Create date map for progress calculation
    const dateProgress = {};
    let currentDate = new Date(startDate);

    while (currentDate <= today) {
        const dateStr = currentDate.toISOString().split('T')[0];
        dateProgress[dateStr] = null; // Will be calculated
        currentDate.setDate(currentDate.getDate() + 1);
    }

    // Calculate progress for each date based on logs
    // Simple approach: interpolate between known points
    const dates = Object.keys(dateProgress).sort();

    // If we have logs, build progress timeline
    if (allLogs.length > 0) {
        // Group logs by goal to track each goal's progress over time
        const goalProgressTimeline = {};
        props.goals.forEach(goal => {
            goalProgressTimeline[goal.id] = { current: goal.progress || 0 };
        });

        // For each date, estimate overall progress
        dates.forEach((dateStr, index) => {
            const date = new Date(dateStr);

            // Count logs up to this date for each goal
            const progressByGoal = {};
            props.goals.forEach(goal => {
                const logsForGoal = allLogs
                    .filter(l => l.goalId === goal.id && new Date(l.logged_at) <= date);

                if (logsForGoal.length > 0) {
                    progressByGoal[goal.id] = logsForGoal[logsForGoal.length - 1].new_progress;
                } else {
                    // If no logs before this date, assume 0 or interpolate
                    progressByGoal[goal.id] = 0;
                }
            });

            // Calculate average progress for this date
            const totalProgress = Object.values(progressByGoal).reduce((sum, p) => sum + p, 0);
            const avgProgress = props.goals.length > 0
                ? Math.round(totalProgress / props.goals.length)
                : 0;

            labels.push(formatDate(date));
            data.push(avgProgress);
        });
    } else {
        // No logs - show flat line at current progress
        dates.forEach(dateStr => {
            labels.push(formatDate(new Date(dateStr)));
            data.push(currentOverallProgress);
        });
    }

    // Ensure last point is current overall progress
    if (data.length > 0) {
        data[data.length - 1] = currentOverallProgress;
    }

    return {
        labels,
        datasets: [{
            label: 'Overall Progress (%)',
            data,
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
        }]
    };
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                📈 Progress Trend
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

        <ProgressLineChart
            :chart-data="chartData"
            :height="220"
        />
    </div>
</template>
