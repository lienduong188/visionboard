<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    outputs: { type: Object, default: () => ({}) }, // grouped by date
    movementTypes: { type: Object, default: () => ({}) },
    selectedType: { type: String, default: 'all' },
});

const emit = defineEmits(['update:selectedType']);

const summaryMode = ref('weekly'); // 'weekly' | 'monthly'
const tableView = ref('table'); // 'table' | 'summary'
const selectedType = computed({
    get: () => props.selectedType,
    set: (val) => emit('update:selectedType', val),
});

// All movement outputs (done), sorted desc
const allMovementOutputs = computed(() => {
    const list = [];
    for (const [date, dayOutputs] of Object.entries(props.outputs)) {
        for (const o of dayOutputs) {
            if (o.category === 'movement' && o.status === 'done') {
                list.push({ ...o, _date: date });
            }
        }
    }
    return list.sort((a, b) => b._date.localeCompare(a._date));
});

// Filtered by selectedType
const movementOutputs = computed(() => {
    if (selectedType.value === 'all') return allMovementOutputs.value;
    return allMovementOutputs.value.filter(o => (o.movement_type || 'other') === selectedType.value);
});

// Count per type for filter badges
const typeCounts = computed(() => {
    const counts = { all: allMovementOutputs.value.length };
    for (const o of allMovementOutputs.value) {
        const t = o.movement_type || 'other';
        counts[t] = (counts[t] || 0) + 1;
    }
    return counts;
});

// Format date to Japanese style
const formatDateJa = (dateStr) => {
    const d = new Date(dateStr + 'T00:00:00');
    return `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日`;
};

// Format decimal, strip trailing zeros
const formatKm = (val) => {
    if (!val && val !== 0) return '—';
    const n = parseFloat(val);
    return n % 1 === 0 ? n.toString() : n.toFixed(2).replace(/\.?0+$/, '');
};

// Calculate pace from distance_km and duration_hms
const calcPace = (distKm, hms) => {
    if (!distKm || !hms) return '—';
    const dist = parseFloat(distKm);
    if (!dist || dist <= 0) return '—';
    const parts = hms.split(':');
    if (parts.length !== 3) return '—';
    const totalSec = parseInt(parts[0]) * 3600 + parseInt(parts[1]) * 60 + parseInt(parts[2]);
    if (!totalSec) return '—';
    const paceSecPerKm = totalSec / dist;
    const paceMin = Math.floor(paceSecPerKm / 60);
    const paceSec = Math.round(paceSecPerKm % 60);
    return `${paceMin}:${String(paceSec).padStart(2, '0')}`;
};

const getMovementTypeInfo = (key) => {
    return props.movementTypes[key] || { icon: '⚡', ja: key };
};

const ratingEmoji = (r) => {
    const map = { 1: '😞', 2: '😐', 3: '😊', 4: '😄', 5: '🤩' };
    return r ? map[r] || '—' : '—';
};

// Duration in total seconds from HH:MM:SS
const hmsTotalSeconds = (hms) => {
    if (!hms) return 0;
    const parts = hms.split(':');
    if (parts.length !== 3) return 0;
    return parseInt(parts[0]) * 3600 + parseInt(parts[1]) * 60 + parseInt(parts[2]);
};

// Format seconds as H:MM:SS or MM:SS
const formatSeconds = (totalSec) => {
    if (!totalSec) return '—';
    const h = Math.floor(totalSec / 3600);
    const m = Math.floor((totalSec % 3600) / 60);
    const s = totalSec % 60;
    if (h > 0) return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    return `${m}:${String(s).padStart(2, '0')}`;
};

// Parse week key from date
const getWeekKey = (dateStr) => {
    const d = new Date(dateStr + 'T00:00:00');
    // Get Monday of the week
    const day = d.getDay() || 7;
    d.setDate(d.getDate() - day + 1);
    return d.toISOString().slice(0, 10);
};

const getMonthKey = (dateStr) => dateStr.slice(0, 7);

const aggregateBy = (keyFn) => {
    const map = {};
    for (const o of movementOutputs.value) {
        const key = keyFn(o._date);
        if (!map[key]) {
            map[key] = { key, sessions: 0, totalKm: 0, totalSec: 0, totalKcal: 0, hrSum: 0, hrCount: 0, types: {} };
        }
        const bucket = map[key];
        bucket.sessions++;
        bucket.totalKm += parseFloat(o.distance_km || 0);
        bucket.totalSec += hmsTotalSeconds(o.duration_hms);
        bucket.totalKcal += parseInt(o.kcal || 0);
        if (o.heart_rate) { bucket.hrSum += parseInt(o.heart_rate); bucket.hrCount++; }
        const type = o.movement_type || 'other';
        bucket.types[type] = (bucket.types[type] || 0) + 1;
    }
    return Object.values(map).sort((a, b) => b.key.localeCompare(a.key));
};

const weeklySummary = computed(() => aggregateBy(getWeekKey));
const monthlySummary = computed(() => aggregateBy(getMonthKey));

const summaryData = computed(() => summaryMode.value === 'weekly' ? weeklySummary.value : monthlySummary.value);

const formatWeekLabel = (key) => {
    const start = new Date(key + 'T00:00:00');
    const end = new Date(key + 'T00:00:00');
    end.setDate(end.getDate() + 6);
    return `${start.getMonth() + 1}/${start.getDate()} - ${end.getMonth() + 1}/${end.getDate()}`;
};

const formatMonthLabel = (key) => {
    const [y, m] = key.split('-');
    return `${y}年${parseInt(m)}月`;
};

const getSummaryLabel = (key) => {
    return summaryMode.value === 'weekly' ? formatWeekLabel(key) : formatMonthLabel(key);
};

// Avg pace for a bucket
const bucketPace = (bucket) => {
    if (!bucket.totalKm || !bucket.totalSec) return '—';
    const paceSecPerKm = bucket.totalSec / bucket.totalKm;
    const paceMin = Math.floor(paceSecPerKm / 60);
    const paceSec = Math.round(paceSecPerKm % 60);
    return `${paceMin}:${String(paceSec).padStart(2, '0')}`;
};
</script>

<template>
    <div class="space-y-4">
        <!-- Filter by movement type -->
        <div class="flex flex-wrap gap-1.5">
            <button
                @click="selectedType = 'all'"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium transition-colors"
                :class="selectedType === 'all'
                    ? 'bg-orange-500 text-white'
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-orange-100 dark:hover:bg-orange-900/30'"
            >
                All
                <span class="opacity-70">{{ typeCounts.all || 0 }}</span>
            </button>
            <button
                v-for="(info, key) in movementTypes"
                :key="key"
                @click="selectedType = key"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium transition-colors"
                :class="selectedType === key
                    ? 'bg-orange-500 text-white'
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-orange-100 dark:hover:bg-orange-900/30'"
                v-show="typeCounts[key]"
            >
                {{ info.icon }} {{ info.ja }}
                <span class="opacity-70">{{ typeCounts[key] || 0 }}</span>
            </button>
        </div>

        <!-- Toggle: Table / Summary -->
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200">🏃 Movement Log</h2>
            <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                <button
                    @click="tableView = 'table'"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                    :class="tableView === 'table'
                        ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                >
                    📋 Log
                </button>
                <button
                    @click="tableView = 'summary'"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                    :class="tableView === 'summary'
                        ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                >
                    📊 Summary
                </button>
            </div>
        </div>

        <!-- TABLE VIEW -->
        <div v-if="tableView === 'table'">
            <div v-if="movementOutputs.length === 0" class="text-center py-8 text-gray-400 dark:text-gray-500">
                No movement logs yet.
            </div>
            <div v-else class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            <th class="px-3 py-2 text-left whitespace-nowrap">日付</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">内容</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">種類</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">距離 (km)</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">時間</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">ペース</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">心拍数</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">ケイデンス</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">kcal</th>
                            <th class="px-3 py-2 text-center whitespace-nowrap">気分</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">メモ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr
                            v-for="o in movementOutputs"
                            :key="o.id"
                            class="bg-white dark:bg-gray-900 hover:bg-orange-50 dark:hover:bg-orange-900/10 transition-colors"
                        >
                            <td class="px-3 py-2 whitespace-nowrap text-gray-500 dark:text-gray-400 text-xs">
                                {{ formatDateJa(o._date) }}
                            </td>
                            <td class="px-3 py-2 font-medium text-gray-800 dark:text-gray-200 max-w-[160px] truncate" :title="o.title">
                                {{ o.title }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span v-if="o.movement_type" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300">
                                    {{ getMovementTypeInfo(o.movement_type).icon }}
                                    {{ getMovementTypeInfo(o.movement_type).ja }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-3 py-2 text-right font-mono text-gray-700 dark:text-gray-300">
                                {{ o.distance_km ? formatKm(o.distance_km) : '—' }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono text-gray-700 dark:text-gray-300">
                                {{ o.duration_hms || '—' }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono text-gray-700 dark:text-gray-300">
                                {{ calcPace(o.distance_km, o.duration_hms) }}
                            </td>
                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">
                                {{ o.heart_rate || '—' }}
                            </td>
                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">
                                {{ o.cadence || '—' }}
                            </td>
                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">
                                {{ o.kcal || '—' }}
                            </td>
                            <td class="px-3 py-2 text-center text-lg">
                                {{ ratingEmoji(o.rating) }}
                            </td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400 max-w-[200px] truncate text-xs" :title="o.note">
                                {{ o.note || '' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SUMMARY VIEW -->
        <div v-if="tableView === 'summary'" class="space-y-4">
            <!-- Toggle Weekly/Monthly -->
            <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1 w-fit">
                <button
                    @click="summaryMode = 'weekly'"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                    :class="summaryMode === 'weekly'
                        ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400'"
                >
                    週別
                </button>
                <button
                    @click="summaryMode = 'monthly'"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                    :class="summaryMode === 'monthly'
                        ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400'"
                >
                    月別
                </button>
            </div>

            <div v-if="summaryData.length === 0" class="text-center py-8 text-gray-400 dark:text-gray-500">
                No data yet.
            </div>
            <div v-else class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            <th class="px-3 py-2 text-left whitespace-nowrap">{{ summaryMode === 'weekly' ? '週' : '月' }}</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">Sessions</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">Total km</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">Total Time</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">Avg Pace</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">Avg HR</th>
                            <th class="px-3 py-2 text-right whitespace-nowrap">Total kcal</th>
                            <th class="px-3 py-2 text-left whitespace-nowrap">Types</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr
                            v-for="bucket in summaryData"
                            :key="bucket.key"
                            class="bg-white dark:bg-gray-900 hover:bg-orange-50 dark:hover:bg-orange-900/10 transition-colors"
                        >
                            <td class="px-3 py-2 font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ getSummaryLabel(bucket.key) }}
                            </td>
                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">
                                {{ bucket.sessions }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono font-semibold text-orange-600 dark:text-orange-400">
                                {{ bucket.totalKm > 0 ? formatKm(bucket.totalKm) : '—' }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono text-gray-700 dark:text-gray-300">
                                {{ formatSeconds(bucket.totalSec) }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono text-gray-700 dark:text-gray-300">
                                {{ bucketPace(bucket) }}
                            </td>
                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">
                                {{ bucket.hrCount > 0 ? Math.round(bucket.hrSum / bucket.hrCount) : '—' }}
                            </td>
                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">
                                {{ bucket.totalKcal > 0 ? bucket.totalKcal.toLocaleString() : '—' }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="(count, type) in bucket.types"
                                        :key="type"
                                        class="inline-flex items-center gap-0.5 text-xs px-1.5 py-0.5 rounded bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300"
                                    >
                                        {{ movementTypes[type]?.icon || '⚡' }} {{ count }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
