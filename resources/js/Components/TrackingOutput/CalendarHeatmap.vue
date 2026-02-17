<script setup>
import { computed } from 'vue';

const props = defineProps({
    heatmap: Array,
    restDays: Array,
});

const emit = defineEmits(['select-date']);

// Build grid data: weeks as columns, days as rows (Mon-Sun)
const gridData = computed(() => {
    if (!props.heatmap || props.heatmap.length === 0) return { weeks: [], months: [] };

    const weeks = [];
    const months = [];
    let currentWeek = [];
    let lastMonth = -1;

    // Pad first week with empty cells
    const firstDate = new Date(props.heatmap[0].date + 'T00:00:00');
    const firstDow = (firstDate.getDay() + 6) % 7; // Mon=0, Sun=6
    for (let i = 0; i < firstDow; i++) {
        currentWeek.push(null);
    }

    props.heatmap.forEach((day) => {
        const d = new Date(day.date + 'T00:00:00');
        const dow = (d.getDay() + 6) % 7; // Mon=0
        const month = d.getMonth();

        // Track month labels
        if (month !== lastMonth) {
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            months.push({ name: monthNames[month], weekIndex: weeks.length });
            lastMonth = month;
        }

        if (dow === 0 && currentWeek.length > 0) {
            weeks.push(currentWeek);
            currentWeek = [];
        }

        currentWeek.push(day);
    });

    if (currentWeek.length > 0) {
        weeks.push(currentWeek);
    }

    return { weeks, months };
});

const now = new Date();
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

const getCellClass = (day) => {
    if (!day) return 'bg-transparent';
    if (day.is_rest_day) return 'bg-blue-200 dark:bg-blue-800';
    if (day.date > today) return 'bg-gray-100 dark:bg-gray-800 opacity-50';
    if (day.count === 0) return 'bg-gray-200 dark:bg-gray-700';
    if (day.count === 1) return 'bg-green-300 dark:bg-green-700';
    if (day.count === 2) return 'bg-green-400 dark:bg-green-600';
    return 'bg-green-600 dark:bg-green-500';
};

const getTooltip = (day) => {
    if (!day) return '';
    if (day.is_rest_day) return `${day.date} - 😴 Rest Day`;
    if (day.count === 0) return `${day.date} - No outputs`;
    const h = Math.floor(day.duration / 60);
    const m = day.duration % 60;
    const dur = h > 0 ? `${h}h${m > 0 ? m + 'm' : ''}` : `${m}m`;
    return `${day.date} - ${day.count} output${day.count > 1 ? 's' : ''}, ${dur}`;
};

const isToday = (day) => day && day.date === today;
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 sm:p-6">
        <div class="overflow-x-auto">
            <!-- Month labels -->
            <div class="flex mb-1 ml-8" style="gap: 0;">
                <div
                    v-for="(month, idx) in gridData.months"
                    :key="idx"
                    class="text-xs text-gray-500 dark:text-gray-400"
                    :style="{ position: 'absolute', left: (month.weekIndex * 17 + 32) + 'px' }"
                >
                    {{ month.name }}
                </div>
            </div>

            <div class="relative mt-5">
                <!-- Weekday labels -->
                <div class="absolute left-0 top-0 flex flex-col" style="gap: 3px;">
                    <div class="h-[14px] w-7 text-[10px] text-gray-400 dark:text-gray-500 leading-[14px]">Mon</div>
                    <div class="h-[14px] w-7"></div>
                    <div class="h-[14px] w-7 text-[10px] text-gray-400 dark:text-gray-500 leading-[14px]">Wed</div>
                    <div class="h-[14px] w-7"></div>
                    <div class="h-[14px] w-7 text-[10px] text-gray-400 dark:text-gray-500 leading-[14px]">Fri</div>
                    <div class="h-[14px] w-7"></div>
                    <div class="h-[14px] w-7 text-[10px] text-gray-400 dark:text-gray-500 leading-[14px]">Sun</div>
                </div>

                <!-- Grid -->
                <div class="ml-8 flex" style="gap: 3px;">
                    <div
                        v-for="(week, wIdx) in gridData.weeks"
                        :key="wIdx"
                        class="flex flex-col"
                        style="gap: 3px;"
                    >
                        <div
                            v-for="(day, dIdx) in week"
                            :key="dIdx"
                            class="w-[14px] h-[14px] rounded-sm cursor-pointer transition-all hover:ring-2 hover:ring-indigo-400"
                            :class="[
                                getCellClass(day),
                                isToday(day) ? 'ring-2 ring-indigo-500' : '',
                            ]"
                            :title="getTooltip(day)"
                            @click="day && emit('select-date', day.date)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex items-center gap-4 mt-4 text-xs text-gray-500 dark:text-gray-400">
            <span>Less</span>
            <div class="flex gap-1">
                <div class="w-3 h-3 rounded-sm bg-gray-200 dark:bg-gray-700"></div>
                <div class="w-3 h-3 rounded-sm bg-green-300 dark:bg-green-700"></div>
                <div class="w-3 h-3 rounded-sm bg-green-400 dark:bg-green-600"></div>
                <div class="w-3 h-3 rounded-sm bg-green-600 dark:bg-green-500"></div>
            </div>
            <span>More</span>
            <div class="w-3 h-3 rounded-sm bg-blue-200 dark:bg-blue-800 ml-2"></div>
            <span>Rest</span>
        </div>
    </div>
</template>
