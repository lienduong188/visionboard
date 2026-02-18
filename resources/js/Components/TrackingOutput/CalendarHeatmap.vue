<script setup>
import { computed } from 'vue';

const props = defineProps({
    heatmap: Array,
    restDays: Array,
});

const emit = defineEmits(['select-date']);

const DAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const gridData = computed(() => {
    if (!props.heatmap || props.heatmap.length === 0) return { weeks: [], months: [] };

    const weeks = [];
    let currentWeek = [];

    // Pad first week with null cells to align to Monday
    const firstDate = new Date(props.heatmap[0].date + 'T00:00:00');
    const firstDow = (firstDate.getDay() + 6) % 7; // Mon=0, Sun=6
    for (let i = 0; i < firstDow; i++) {
        currentWeek.push(null);
    }

    props.heatmap.forEach((day) => {
        const d = new Date(day.date + 'T00:00:00');
        const dow = (d.getDay() + 6) % 7;

        if (dow === 0 && currentWeek.length > 0) {
            weeks.push([...currentWeek]);
            currentWeek = [];
        }
        currentWeek.push(day);
    });

    if (currentWeek.length > 0) {
        // Pad last week to 7 days
        while (currentWeek.length < 7) currentWeek.push(null);
        weeks.push(currentWeek);
    }

    // Build month labels based on actual week positions
    const months = [];
    let lastMonth = -1;
    weeks.forEach((week, weekIdx) => {
        const firstDay = week.find(d => d !== null);
        if (firstDay) {
            const month = new Date(firstDay.date + 'T00:00:00').getMonth();
            if (month !== lastMonth) {
                months.push({ name: MONTH_NAMES[month], weekIndex: weekIdx });
                lastMonth = month;
            }
        }
    });

    // Add spanWeeks for proportional flex layout
    const monthsWithSpan = months.map((m, i) => ({
        ...m,
        spanWeeks: (i + 1 < months.length ? months[i + 1].weekIndex : weeks.length) - m.weekIndex,
    }));

    return { weeks, months: monthsWithSpan };
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
        <div class="flex w-full min-w-0">
            <!-- Weekday labels column -->
            <div class="flex flex-col shrink-0 pt-6 mr-2" style="gap: 3px; width: 22px;">
                <div
                    v-for="label in DAY_LABELS"
                    :key="label"
                    class="text-[9px] text-gray-400 dark:text-gray-500 text-right leading-none select-none"
                    style="height: 12px; line-height: 12px;"
                >{{ label }}</div>
            </div>

            <!-- Grid area: month labels + week columns -->
            <div class="flex-1 min-w-0">
                <!-- Month labels: proportional flex matching week columns -->
                <div class="flex" style="height: 20px; margin-bottom: 2px;">
                    <div
                        v-for="(month, idx) in gridData.months"
                        :key="idx"
                        class="text-[10px] text-gray-500 dark:text-gray-400 overflow-hidden whitespace-nowrap select-none"
                        :style="{ flex: month.spanWeeks }"
                    >{{ month.name }}</div>
                </div>

                <!-- Week columns: flex-1 fills full width -->
                <div class="flex" style="gap: 3px;">
                    <div
                        v-for="(week, wIdx) in gridData.weeks"
                        :key="wIdx"
                        class="flex flex-col flex-1"
                        style="gap: 3px; min-width: 6px;"
                    >
                        <div
                            v-for="(day, dIdx) in week"
                            :key="dIdx"
                            class="w-full rounded-sm cursor-pointer transition-all hover:ring-1 hover:ring-indigo-400"
                            :class="[
                                getCellClass(day),
                                isToday(day) ? 'ring-2 ring-indigo-500' : '',
                            ]"
                            style="aspect-ratio: 1;"
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
