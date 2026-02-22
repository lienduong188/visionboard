<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    categoryStats: Array,
    ranked: Array,
    totalWeightedScore: Number,
    totalTime: Number,
    weeklyTrend: Array,
    recommendations: Array,
    categories: Object,
});

// Format minutes → "2h 30m" or "45m"
const fmtTime = (min) => {
    if (!min) return '0m';
    const h = Math.floor(min / 60);
    const m = min % 60;
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
};

// Flywheel quadrant SVG
// X axis = compound (1-10), Y axis = impact (1-10)
// Each category = bubble, size = time ratio
const svgWidth = 400;
const svgHeight = 340;
const padL = 40, padR = 20, padT = 20, padB = 40;
const innerW = svgWidth - padL - padR;
const innerH = svgHeight - padT - padB;

const toX = (compound) => padL + ((compound - 1) / 9) * innerW;
const toY = (impact) => padT + innerH - ((impact - 1) / 9) * innerH;

const bubbles = computed(() => {
    const maxTime = Math.max(...props.categoryStats.map(c => c.total_time), 1);
    return props.categoryStats.map(c => {
        const r = c.total_time > 0
            ? 8 + (c.total_time / maxTime) * 22
            : 6;
        return {
            ...c,
            cx: toX(c.compound),
            cy: toY(c.impact),
            r,
        };
    });
});

const priorityConfig = {
    warning: { bg: 'bg-amber-50 dark:bg-amber-900/20', border: 'border-amber-300 dark:border-amber-700', badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200', icon: '⚠️', label: 'Cân nhắc' },
    high:    { bg: 'bg-blue-50 dark:bg-blue-900/20',   border: 'border-blue-300 dark:border-blue-700',   badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',   icon: '🚀', label: 'Cần đầu tư' },
    good:    { bg: 'bg-green-50 dark:bg-green-900/20', border: 'border-green-300 dark:border-green-700', badge: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200', icon: '✅', label: 'Cân bằng tốt' },
    great:   { bg: 'bg-purple-50 dark:bg-purple-900/20', border: 'border-purple-300 dark:border-purple-700', badge: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200', icon: '🏆', label: 'Xuất sắc' },
};

// Weekly trend bar chart (simple SVG)
const trendMax = computed(() => Math.max(...props.weeklyTrend.map(w => w.weighted_score), 1));

// Quadrant labels
const quadrants = [
    { x: padL + innerW * 0.02, y: padT + innerH * 0.05, label: 'HIGH IMPACT', sub: 'Low Compound', color: '#6366f1' },
    { x: padL + innerW * 0.52, y: padT + innerH * 0.05, label: '🔥 FLYWHEEL ZONE', sub: 'High × High', color: '#10b981' },
    { x: padL + innerW * 0.02, y: padT + innerH * 0.55, label: 'LOW PRIORITY', sub: 'Low × Low', color: '#9ca3af' },
    { x: padL + innerW * 0.52, y: padT + innerH * 0.55, label: 'HIGH COMPOUND', sub: 'Low Impact', color: '#f59e0b' },
];

const hoveredBubble = ref(null);
</script>

<template>
    <Head title="Output Analytics" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        📊 Output Analytics
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Phân tích giá trị vòng quay (Flywheel)</p>
                </div>
                <Link :href="route('tracking-output.index')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                    ← Về Output Tracker
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Top KPI Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
                        <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ totalWeightedScore.toLocaleString() }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Tổng Flywheel Score</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">∑ (time × flywheel/100)</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
                        <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ fmtTime(totalTime) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Tổng thời gian</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Tất cả categories</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                            {{ ranked.find(r => r.total_time > 0)?.icon || '—' }}
                            {{ ranked.find(r => r.total_time > 0)?.label || 'Chưa có' }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Top Flywheel Activity</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ ranked.find(r => r.total_time > 0) ? `Score ${ranked.find(r => r.total_time > 0).flywheel}/100` : '' }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                            {{ categoryStats.filter(c => c.total_time > 0).length }}/{{ categoryStats.length }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Categories đã làm</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Độ đa dạng output</div>
                    </div>
                </div>

                <!-- Flywheel Matrix -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">Ma trận Flywheel</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mb-3">Trục Y = Impact dài hạn · Trục X = Khả năng tích lũy · Kích thước = Thời gian đầu tư</p>
                        <div class="relative overflow-x-auto">
                            <svg :width="svgWidth" :height="svgHeight" class="w-full max-w-full">
                                <!-- Grid lines -->
                                <line :x1="padL + innerW/2" :y1="padT" :x2="padL + innerW/2" :y2="padT + innerH"
                                    stroke="#e5e7eb" stroke-width="1" stroke-dasharray="4,4" class="dark:opacity-20" />
                                <line :x1="padL" :y1="padT + innerH/2" :x2="padL + innerW" :y2="padT + innerH/2"
                                    stroke="#e5e7eb" stroke-width="1" stroke-dasharray="4,4" class="dark:opacity-20" />

                                <!-- Quadrant backgrounds -->
                                <rect :x="padL" :y="padT" :width="innerW/2" :height="innerH/2"
                                    fill="#6366f1" fill-opacity="0.04" />
                                <rect :x="padL + innerW/2" :y="padT" :width="innerW/2" :height="innerH/2"
                                    fill="#10b981" fill-opacity="0.07" />
                                <rect :x="padL" :y="padT + innerH/2" :width="innerW/2" :height="innerH/2"
                                    fill="#9ca3af" fill-opacity="0.04" />
                                <rect :x="padL + innerW/2" :y="padT + innerH/2" :width="innerW/2" :height="innerH/2"
                                    fill="#f59e0b" fill-opacity="0.04" />

                                <!-- Quadrant labels -->
                                <text v-for="q in quadrants" :key="q.label"
                                    :x="q.x" :y="q.y"
                                    :fill="q.color" font-size="9" font-weight="600" opacity="0.8">
                                    {{ q.label }}
                                </text>
                                <text v-for="q in quadrants" :key="q.sub"
                                    :x="q.x" :y="q.y + 12"
                                    :fill="q.color" font-size="8" opacity="0.6">
                                    {{ q.sub }}
                                </text>

                                <!-- Axis labels -->
                                <text :x="padL + innerW/2" :y="svgHeight - 4" text-anchor="middle" font-size="10" fill="#9ca3af">Compound (tích lũy)</text>
                                <text :x="14" :y="padT + innerH/2" text-anchor="middle" font-size="10" fill="#9ca3af"
                                    transform="rotate(-90, 14, 230)">Impact</text>

                                <!-- Axis ticks -->
                                <text :x="padL" :y="svgHeight - 8" text-anchor="middle" font-size="8" fill="#d1d5db">1</text>
                                <text :x="padL + innerW" :y="svgHeight - 8" text-anchor="middle" font-size="8" fill="#d1d5db">10</text>
                                <text :x="padL - 8" :y="padT + innerH" text-anchor="end" font-size="8" fill="#d1d5db">1</text>
                                <text :x="padL - 8" :y="padT + 4" text-anchor="end" font-size="8" fill="#d1d5db">10</text>

                                <!-- Bubbles -->
                                <g v-for="b in bubbles" :key="b.key"
                                    @mouseenter="hoveredBubble = b.key"
                                    @mouseleave="hoveredBubble = null"
                                    class="cursor-pointer">
                                    <circle
                                        :cx="b.cx" :cy="b.cy" :r="b.r"
                                        :fill="b.flywheel >= 63 ? '#10b981' : b.flywheel >= 40 ? '#6366f1' : '#9ca3af'"
                                        :fill-opacity="b.total_time > 0 ? 0.8 : 0.2"
                                        :stroke="hoveredBubble === b.key ? '#1f2937' : 'white'"
                                        stroke-width="2"
                                        class="transition-all duration-200"
                                    />
                                    <text :x="b.cx" :y="b.cy - b.r - 4" text-anchor="middle" font-size="11">{{ b.icon }}</text>
                                    <text v-if="hoveredBubble === b.key || b.total_time > 0"
                                        :x="b.cx" :y="b.cy + b.r + 12" text-anchor="middle" font-size="8" fill="#6b7280">
                                        {{ b.label }}
                                    </text>
                                </g>

                                <!-- Tooltip for hovered -->
                                <g v-if="hoveredBubble" >
                                    <template v-for="b in bubbles" :key="b.key">
                                        <template v-if="b.key === hoveredBubble">
                                            <rect
                                                :x="Math.min(b.cx + 10, svgWidth - 110)"
                                                :y="Math.max(b.cy - 40, 4)"
                                                width="105" height="52" rx="6"
                                                fill="#1f2937" fill-opacity="0.92" />
                                            <text :x="Math.min(b.cx + 62, svgWidth - 57)" :y="Math.max(b.cy - 27, 17)"
                                                text-anchor="middle" font-size="10" fill="white" font-weight="600">
                                                {{ b.icon }} {{ b.label }}
                                            </text>
                                            <text :x="Math.min(b.cx + 62, svgWidth - 57)" :y="Math.max(b.cy - 14, 30)"
                                                text-anchor="middle" font-size="9" fill="#d1fae5">
                                                Flywheel: {{ b.flywheel }}/100
                                            </text>
                                            <text :x="Math.min(b.cx + 62, svgWidth - 57)" :y="Math.max(b.cy - 1, 43)"
                                                text-anchor="middle" font-size="9" fill="#bfdbfe">
                                                {{ fmtTime(b.total_time) }} · {{ b.time_ratio }}%
                                            </text>
                                            <text :x="Math.min(b.cx + 62, svgWidth - 57)" :y="Math.max(b.cy + 12, 56)"
                                                text-anchor="middle" font-size="9" fill="#fde68a">
                                                I={{ b.impact }} × C={{ b.compound }}
                                            </text>
                                        </template>
                                    </template>
                                </g>
                            </svg>
                        </div>
                </div>

                <!-- Category Rankings -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">Giá trị vòng quay theo Category</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Flywheel = Impact × Compound · Màu thanh = Weighted Score (time × flywheel)</p>
                    <div class="space-y-4">
                        <div v-for="(cat, idx) in ranked" :key="cat.key" class="flex items-center gap-3">
                            <span class="text-sm text-gray-400 dark:text-gray-500 w-5 shrink-0">{{ idx + 1 }}</span>
                            <span class="text-xl shrink-0">{{ cat.icon }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ cat.label }}</span>
                                    <div class="flex items-center gap-3 text-sm">
                                        <span class="text-gray-400">{{ fmtTime(cat.total_time) }}</span>
                                        <span class="font-semibold"
                                            :class="cat.flywheel >= 63 ? 'text-emerald-600 dark:text-emerald-400' : cat.flywheel >= 40 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400'">
                                            {{ cat.flywheel }}/100
                                        </span>
                                    </div>
                                </div>
                                <!-- Flywheel score bar (fixed) -->
                                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full mb-1">
                                    <div class="h-2 rounded-full transition-all duration-500"
                                        :class="cat.flywheel >= 63 ? 'bg-emerald-400' : cat.flywheel >= 40 ? 'bg-indigo-400' : 'bg-gray-300'"
                                        :style="{ width: cat.flywheel + '%' }"></div>
                                </div>
                                <!-- Weighted score bar (actual usage) -->
                                <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full">
                                    <div class="h-1.5 rounded-full bg-amber-400 transition-all duration-500"
                                        :style="{ width: totalWeightedScore > 0 ? (cat.weighted_score / totalWeightedScore * 100) + '%' : '0%' }"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-400 mt-1">
                                    <span>I={{ cat.impact }} × C={{ cat.compound }}</span>
                                    <span>Weighted: {{ cat.weighted_score }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 flex gap-5 text-sm text-gray-400">
                        <div class="flex items-center gap-1.5">
                            <div class="w-4 h-2 bg-emerald-400 rounded"></div> Flywheel score
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-4 h-1.5 bg-amber-400 rounded"></div> Thời gian × Flywheel
                        </div>
                    </div>
                </div>

                <!-- Recommendations -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">💡 Gợi ý tối ưu vòng quay</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Dựa trên ma trận Flywheel, đây là những điều chỉnh nên làm:</p>
                    <div v-if="recommendations.length === 0" class="text-sm text-gray-400 text-center py-4">
                        Chưa có đủ data để phân tích. Hãy thêm nhiều outputs hơn!
                    </div>
                    <div class="flex flex-col gap-3">
                        <div v-for="rec in recommendations" :key="rec.category"
                            class="rounded-lg p-4 border"
                            :class="[priorityConfig[rec.priority]?.bg, priorityConfig[rec.priority]?.border]">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">{{ rec.icon }}</span>
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ rec.label }}</span>
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0"
                                    :class="priorityConfig[rec.priority]?.badge">
                                    {{ priorityConfig[rec.priority]?.icon }} {{ priorityConfig[rec.priority]?.label }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">{{ rec.message }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">→ {{ rec.action }}</p>
                        </div>
                    </div>
                </div>

                <!-- Weekly Flywheel Trend -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">📈 Xu hướng Flywheel Score theo tuần</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Flywheel score tích lũy mỗi tuần (thời gian × giá trị tích lũy)</p>
                    <div class="flex items-end gap-1 h-32 overflow-x-auto pb-6 relative">
                        <div v-for="(week, idx) in weeklyTrend" :key="idx"
                            class="flex flex-col items-center gap-1 flex-shrink-0"
                            style="min-width: 52px">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ week.weighted_score }}</span>
                            <div class="w-10 rounded-t-md transition-all duration-500"
                                :style="{ height: trendMax > 0 ? (week.weighted_score / trendMax * 96) + 'px' : '4px' }"
                                :class="week.weighted_score >= trendMax * 0.8 ? 'bg-emerald-400' : week.weighted_score >= trendMax * 0.4 ? 'bg-indigo-400' : 'bg-gray-200 dark:bg-gray-600'">
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 text-center leading-tight" style="font-size: 9px">
                                {{ week.week }}
                            </div>
                        </div>
                        <div v-if="weeklyTrend.length === 0" class="w-full text-center text-sm text-gray-400 py-8">
                            Chưa có data
                        </div>
                    </div>
                </div>

                <!-- Category Detail Table -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-4">Chi tiết Giá trị Vòng quay</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-sm text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700">
                                    <th class="text-left pb-3">Category</th>
                                    <th class="text-center pb-3">Impact</th>
                                    <th class="text-center pb-3">Compound</th>
                                    <th class="text-center pb-3">Flywheel</th>
                                    <th class="text-right pb-3">Thời gian</th>
                                    <th class="text-right pb-3">% Tổng</th>
                                    <th class="text-right pb-3">Outputs</th>
                                    <th class="text-right pb-3">Avg ⭐</th>
                                    <th class="text-right pb-3">Weighted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="cat in ranked" :key="cat.key"
                                    class="border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="py-3">
                                        <span class="mr-1.5">{{ cat.icon }}</span>
                                        <span class="font-medium text-gray-700 dark:text-gray-200">{{ cat.label }}</span>
                                    </td>
                                    <td class="text-center py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <div class="h-2 rounded-sm bg-indigo-400"
                                                :style="{ width: (cat.impact / 10 * 40) + 'px' }"></div>
                                            <span class="text-sm text-gray-500">{{ cat.impact }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <div class="h-2 rounded-sm bg-amber-400"
                                                :style="{ width: (cat.compound / 10 * 40) + 'px' }"></div>
                                            <span class="text-sm text-gray-500">{{ cat.compound }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-3">
                                        <span class="font-bold"
                                            :class="cat.flywheel >= 63 ? 'text-emerald-600 dark:text-emerald-400' : cat.flywheel >= 40 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400'">
                                            {{ cat.flywheel }}
                                        </span>
                                        <span class="text-xs text-gray-300 dark:text-gray-600">/100</span>
                                    </td>
                                    <td class="text-right py-3 text-gray-600 dark:text-gray-300">{{ fmtTime(cat.total_time) }}</td>
                                    <td class="text-right py-3">
                                        <span class="text-sm px-2 py-0.5 rounded-full"
                                            :class="cat.time_ratio >= 20 ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' : 'text-gray-400'">
                                            {{ cat.time_ratio }}%
                                        </span>
                                    </td>
                                    <td class="text-right py-3 text-gray-500 dark:text-gray-400">{{ cat.total_count }}</td>
                                    <td class="text-right py-3 text-gray-500 dark:text-gray-400">
                                        {{ cat.avg_rating ? cat.avg_rating + ' ⭐' : '—' }}
                                    </td>
                                    <td class="text-right py-3 font-semibold text-amber-600 dark:text-amber-400">{{ cat.weighted_score }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-200 dark:border-gray-600">
                                    <td colspan="4" class="pt-3 text-sm text-gray-400">Tổng cộng</td>
                                    <td class="text-right pt-3 font-semibold text-gray-700 dark:text-gray-200">{{ fmtTime(totalTime) }}</td>
                                    <td class="text-right pt-3 text-gray-400">100%</td>
                                    <td class="text-right pt-3 font-semibold text-gray-700 dark:text-gray-200">
                                        {{ categoryStats.reduce((s, c) => s + c.total_count, 0) }}
                                    </td>
                                    <td></td>
                                    <td class="text-right pt-3 font-bold text-amber-600 dark:text-amber-400">{{ totalWeightedScore }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Flywheel Explanation -->
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-5 border border-indigo-100 dark:border-indigo-800">
                    <h3 class="text-base font-semibold text-indigo-800 dark:text-indigo-300 mb-3">📖 Cách đọc Ma trận Flywheel</h3>
                    <div class="flex flex-col gap-3 text-sm text-indigo-700 dark:text-indigo-300">
                        <div>
                            <p class="font-semibold mb-1">🟢 Flywheel Zone (cao-cao)</p>
                            <p class="text-indigo-600 dark:text-indigo-400">Impact cao + Compound cao = Hoạt động vàng. Mỗi phút bỏ ra tạo ra giá trị tích lũy theo thời gian. Ưu tiên số 1.</p>
                        </div>
                        <div>
                            <p class="font-semibold mb-1">🟣 High Impact (cao-thấp)</p>
                            <p class="text-indigo-600 dark:text-indigo-400">Impact cao nhưng compound thấp = Quan trọng nhưng không tích lũy tốt. Làm vừa đủ, không quá nhiều.</p>
                        </div>
                        <div>
                            <p class="font-semibold mb-1">🟡 High Compound (thấp-cao)</p>
                            <p class="text-indigo-600 dark:text-indigo-400">Compound cao nhưng impact thấp = Có thể xây dựng kỹ năng nhưng chưa rõ giá trị. Cần xem xét lại.</p>
                        </div>
                        <div>
                            <p class="font-semibold mb-1">⬜ Low Priority (thấp-thấp)</p>
                            <p class="text-indigo-600 dark:text-indigo-400">Cả hai đều thấp = Tránh đầu tư nhiều thời gian vào đây. Chỉ làm khi cần thiết.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
