<script setup>
const props = defineProps({
    streakData: Object,
    stats: Object,
});

const formatDuration = (minutes) => {
    if (!minutes) return '0m';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h${m}m`;
};
</script>

<template>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <!-- Streak -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center shadow-sm">
            <div class="text-2xl font-bold text-orange-500">
                <span v-if="streakData.current_streak > 0">🔥</span>
                <span v-else>💤</span>
                {{ streakData.current_streak }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Streak</div>
        </div>

        <!-- Rest Days -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center shadow-sm">
            <div class="text-2xl font-bold text-blue-500">
                😴 {{ streakData.rest_days_available }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Rest Days</div>
        </div>

        <!-- Day Number -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center shadow-sm">
            <div class="text-2xl font-bold text-purple-500">
                #{{ streakData.day_number }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">/ {{ streakData.total_days }} days</div>
        </div>

        <!-- Completion Rate -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center shadow-sm">
            <div class="text-2xl font-bold text-green-500">
                {{ stats.completion_rate }}%
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Rate</div>
        </div>

        <!-- Avg Duration -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center shadow-sm">
            <div class="text-2xl font-bold text-indigo-500">
                {{ formatDuration(stats.avg_duration_per_day) }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Avg/day</div>
        </div>

        <!-- Total Outputs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center shadow-sm">
            <div class="text-2xl font-bold text-gray-700 dark:text-gray-300">
                {{ stats.total_outputs }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Total</div>
        </div>
    </div>
</template>
