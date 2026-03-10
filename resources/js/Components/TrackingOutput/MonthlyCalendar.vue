<script setup>
import { computed } from 'vue';

const props = defineProps({
    heatmap: Array,
    restDays: Array,
    categoryFilter: { type: String, default: 'all' },
    movementTypes: { type: Object, default: () => ({}) },
});

// Màu sắc cho từng movement type
const MOVEMENT_TYPE_COLORS = {
    running:       { light: 'bg-orange-200 dark:bg-orange-800 text-orange-800 dark:text-orange-200', strong: 'bg-orange-400 dark:bg-orange-500 text-white' },
    trail_running: { light: 'bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200',     strong: 'bg-amber-500 dark:bg-amber-600 text-white' },
    gym:           { light: 'bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-purple-200', strong: 'bg-purple-500 dark:bg-purple-600 text-white' },
    hiking:        { light: 'bg-teal-200 dark:bg-teal-800 text-teal-800 dark:text-teal-200',         strong: 'bg-teal-500 dark:bg-teal-600 text-white' },
    other:         { light: 'bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200',         strong: 'bg-gray-500 dark:bg-gray-400 text-white' },
};

const isMovementMode = computed(() => props.categoryFilter === 'movement');

const emit = defineEmits(['select-date']);

const DAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const MONTH_NAMES = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

const TRACKING_START = '2026-02-17';
const TRACKING_END = '2027-02-06';

const now = new Date();
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

// Build map date → heatmap data
const heatmapMap = computed(() => {
    const map = {};
    if (props.heatmap) {
        for (const d of props.heatmap) {
            map[d.date] = d;
        }
    }
    return map;
});

// Build list of months to display (Feb 2026 → Feb 2027)
const months = computed(() => {
    const result = [];
    // Start from Feb 2026, end at Feb 2027
    const periods = [
        { year: 2026, month: 2 }, { year: 2026, month: 3 }, { year: 2026, month: 4 },
        { year: 2026, month: 5 }, { year: 2026, month: 6 }, { year: 2026, month: 7 },
        { year: 2026, month: 8 }, { year: 2026, month: 9 }, { year: 2026, month: 10 },
        { year: 2026, month: 11 }, { year: 2026, month: 12 },
        { year: 2027, month: 1 }, { year: 2027, month: 2 },
    ];

    for (const { year, month } of periods) {
        const weeks = buildMonth(year, month);
        result.push({ year, month, label: `${MONTH_NAMES[month - 1]} ${year}`, weeks });
    }
    return result;
});

function buildMonth(year, month) {
    // How many days in this month
    const daysInMonth = new Date(year, month, 0).getDate();
    const weeks = [];
    let currentWeek = [];

    // First day of month
    const firstDate = new Date(year, month - 1, 1);
    const firstDow = (firstDate.getDay() + 6) % 7; // Mon=0, Sun=6

    // Pad start
    for (let i = 0; i < firstDow; i++) currentWeek.push(null);

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        currentWeek.push(dateStr);

        if (currentWeek.length === 7) {
            weeks.push([...currentWeek]);
            currentWeek = [];
        }
    }

    // Pad end
    if (currentWeek.length > 0) {
        while (currentWeek.length < 7) currentWeek.push(null);
        weeks.push(currentWeek);
    }

    return weeks;
}

const getCellClass = (dateStr) => {
    if (!dateStr) return '';
    // Outside tracking range
    if (dateStr < TRACKING_START || dateStr > TRACKING_END) {
        return 'bg-gray-50 dark:bg-gray-800/50 text-gray-300 dark:text-gray-700 cursor-default';
    }
    const data = heatmapMap.value[dateStr];
    if (data?.is_rest_day) return 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 cursor-pointer hover:ring-1 hover:ring-blue-400';
    if (dateStr > today) return 'bg-white dark:bg-gray-800 text-gray-400 dark:text-gray-500 cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/20';
    if (!data || data.count === 0) return 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/20';

    // Movement mode: dùng màu theo type
    if (isMovementMode.value && data.dominantType) {
        const colors = MOVEMENT_TYPE_COLORS[data.dominantType] || MOVEMENT_TYPE_COLORS.other;
        return (data.count === 1 ? colors.light : colors.strong) + ' cursor-pointer hover:ring-1 hover:ring-offset-0';
    }

    if (data.count === 1) return 'bg-green-200 dark:bg-green-900/60 text-green-800 dark:text-green-200 cursor-pointer hover:ring-1 hover:ring-green-400';
    if (data.count === 2) return 'bg-green-300 dark:bg-green-700/70 text-green-800 dark:text-green-100 cursor-pointer hover:ring-1 hover:ring-green-400';
    return 'bg-green-500 dark:bg-green-600 text-white cursor-pointer hover:ring-1 hover:ring-green-400';
};

const isToday = (dateStr) => dateStr === today;
const isInRange = (dateStr) => dateStr && dateStr >= TRACKING_START && dateStr <= TRACKING_END;

const getDayNumber = (dateStr) => {
    if (!dateStr || dateStr < TRACKING_START || dateStr > today) return null;
    const start = new Date(TRACKING_START + 'T00:00:00');
    const d = new Date(dateStr + 'T00:00:00');
    return Math.floor((d - start) / 86400000) + 1;
};

const getTooltip = (dateStr) => {
    if (!dateStr || !isInRange(dateStr)) return '';
    const [y, m, d] = dateStr.split('-').map(Number);
    const data = heatmapMap.value[dateStr];
    if (data?.is_rest_day) return `${d}/${m} 😴 Rest Day`;
    if (!data || data.count === 0) return `${d}/${m} — No outputs`;
    const h = Math.floor(data.duration / 60);
    const mn = data.duration % 60;
    const dur = h > 0 ? `${h}h${mn > 0 ? mn + 'm' : ''}` : `${mn}m`;
    return `${d}/${m} — ${data.count} output${data.count > 1 ? 's' : ''}, ${dur}`;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Grid of months: 2 cols on mobile, 3 on md, 4 on lg -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div
                v-for="mon in months"
                :key="`${mon.year}-${mon.month}`"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3"
            >
                <!-- Month header -->
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2 text-center">
                    {{ mon.label }}
                </div>

                <!-- Day labels -->
                <div class="grid grid-cols-7 gap-0.5 mb-1">
                    <div
                        v-for="label in DAY_LABELS"
                        :key="label"
                        class="text-center text-[10px] text-gray-400 dark:text-gray-500 font-medium"
                    >{{ label }}</div>
                </div>

                <!-- Weeks -->
                <div class="grid grid-cols-7 gap-0.5">
                    <template v-for="(week, wIdx) in mon.weeks" :key="wIdx">
                        <div
                            v-for="(dateStr, dIdx) in week"
                            :key="dIdx"
                            class="aspect-square flex items-center justify-center rounded text-[11px] font-medium transition-all"
                            :class="[
                                dateStr ? getCellClass(dateStr) : '',
                                isToday(dateStr) ? 'ring-2 ring-indigo-500 font-bold' : '',
                            ]"
                            :title="getTooltip(dateStr)"
                            @click="dateStr && isInRange(dateStr) && emit('select-date', dateStr)"
                        >
                            <span v-if="dateStr">{{ parseInt(dateStr.split('-')[2]) }}</span>
                        </div>
                    </template>
                </div>

                <!-- Month summary -->
                <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 flex justify-between text-[10px] text-gray-400 dark:text-gray-500">
                    <span>
                        {{
                            heatmap
                                ?.filter(d => d.date.startsWith(`${mon.year}-${String(mon.month).padStart(2, '0')}`) && d.count > 0)
                                .length ?? 0
                        }} ngày active
                    </span>
                    <span>
                        {{
                            (() => {
                                const total = heatmap
                                    ?.filter(d => d.date.startsWith(`${mon.year}-${String(mon.month).padStart(2, '0')}`))
                                    .reduce((sum, d) => sum + (d.duration || 0), 0) ?? 0;
                                const h = Math.floor(total / 60);
                                const m = total % 60;
                                return total === 0 ? '' : h > 0 ? `${h}h${m > 0 ? m + 'm' : ''}` : `${m}m`;
                            })()
                        }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex items-center flex-wrap gap-3 text-xs text-gray-500 dark:text-gray-400">
            <!-- Movement type legend -->
            <template v-if="isMovementMode">
                <div class="w-4 h-4 rounded bg-gray-100 dark:bg-gray-700"></div>
                <span>None</span>
                <template v-for="(info, key) in movementTypes" :key="key">
                    <div class="w-4 h-4 rounded" :class="MOVEMENT_TYPE_COLORS[key]?.strong || 'bg-gray-400 text-white'"></div>
                    <span>{{ info.icon }} {{ info.ja }}</span>
                </template>
            </template>
            <!-- Default green legend -->
            <template v-else>
                <span>Less</span>
                <div class="flex gap-1">
                    <div class="w-4 h-4 rounded bg-gray-100 dark:bg-gray-700"></div>
                    <div class="w-4 h-4 rounded bg-green-200 dark:bg-green-900/60"></div>
                    <div class="w-4 h-4 rounded bg-green-300 dark:bg-green-700/70"></div>
                    <div class="w-4 h-4 rounded bg-green-500 dark:bg-green-600"></div>
                </div>
                <span>More</span>
            </template>
            <div class="w-4 h-4 rounded bg-blue-100 dark:bg-blue-900/50 ml-2"></div>
            <span>Rest</span>
            <div class="w-4 h-4 rounded ring-2 ring-indigo-500 ml-2"></div>
            <span>Today</span>
        </div>
    </div>
</template>
