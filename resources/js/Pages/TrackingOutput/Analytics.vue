<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

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

// Circle packing: scatter bubbles randomly, no overlap
const W = 700, H = 300;
const packedBubbles = computed(() => {
    const maxTime = Math.max(...props.categoryStats.map(c => c.total_time), 1);
    const sorted = [...props.categoryStats].sort((a, b) => b.total_time - a.total_time);
    const placed = [];
    for (const c of sorted) {
        const r = c.total_time > 0 ? 18 + (c.total_time / maxTime) * 38 : 14;
        let x = W / 2, y = H / 2;
        for (let i = 0; i < 800; i++) {
            const tx = r + 6 + Math.random() * (W - 2 * r - 12);
            const ty = r + 6 + Math.random() * (H - 2 * r - 12);
            if (!placed.some(p => Math.hypot(p.x - tx, p.y - ty) < p.r + r + 8)) {
                x = tx; y = ty; break;
            }
        }
        placed.push({ ...c, x, y, r });
    }
    return placed;
});

const priorityConfig = {
    warning: { bg: 'bg-amber-50 dark:bg-amber-900/20', border: 'border-amber-300 dark:border-amber-700', badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200', icon: '⚠️', label: 'Consider' },
    high:    { bg: 'bg-blue-50 dark:bg-blue-900/20',   border: 'border-blue-300 dark:border-blue-700',   badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',   icon: '🚀', label: 'Invest More' },
    good:    { bg: 'bg-green-50 dark:bg-green-900/20', border: 'border-green-300 dark:border-green-700', badge: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200', icon: '✅', label: 'Well Balanced' },
    great:   { bg: 'bg-purple-50 dark:bg-purple-900/20', border: 'border-purple-300 dark:border-purple-700', badge: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200', icon: '🏆', label: 'Excellent' },
};

// Weekly trend bar chart (simple SVG)
const trendMax = computed(() => Math.max(...props.weeklyTrend.map(w => w.weighted_score), 1));

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
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Flywheel value analysis</p>
                </div>
                <Link :href="route('tracking-output.index')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                    ← Back to Output Tracker
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Top KPI Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
                        <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ totalWeightedScore.toLocaleString() }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Total Flywheel Score</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">∑ (time × flywheel/100)</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
                        <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ fmtTime(totalTime) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Total Time</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">All categories</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                            {{ ranked.find(r => r.total_time > 0)?.icon || '—' }}
                            {{ ranked.find(r => r.total_time > 0)?.label || 'None yet' }}
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
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Active Categories</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Output diversity</div>
                    </div>
                </div>

                <!-- Category Bubbles -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">Category Bubbles</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mb-5">Size = Time invested · Color = Flywheel score</p>
                    <svg :viewBox="`0 0 ${W} ${H}`" class="w-full h-auto">
                        <g v-for="b in packedBubbles" :key="b.key">
                            <title>{{ b.label }}: {{ fmtTime(b.total_time) }} · Flywheel {{ b.flywheel }}/100</title>
                            <circle
                                :cx="b.x" :cy="b.y" :r="b.r"
                                :fill="b.total_time > 0
                                    ? (b.flywheel >= 63 ? '#d1fae5' : b.flywheel >= 40 ? '#e0e7ff' : '#f3f4f6')
                                    : '#f9fafb'"
                                :stroke="b.total_time > 0
                                    ? (b.flywheel >= 63 ? '#6ee7b7' : b.flywheel >= 40 ? '#a5b4fc' : '#d1d5db')
                                    : '#e5e7eb'"
                                :stroke-width="b.total_time > 0 ? 2 : 1"
                                :opacity="b.total_time > 0 ? 1 : 0.4"
                            />
                            <text :x="b.x" :y="b.y"
                                text-anchor="middle" dominant-baseline="middle"
                                :font-size="b.r * 0.9">{{ b.icon }}</text>
                            <text :x="b.x" :y="b.y + b.r + 13"
                                text-anchor="middle" font-size="10" fill="#6b7280">{{ b.label }}</text>
                            <text v-if="b.total_time > 0"
                                :x="b.x" :y="b.y + b.r + 24"
                                text-anchor="middle" font-size="9" fill="#9ca3af">{{ fmtTime(b.total_time) }}</text>
                        </g>
                    </svg>
                </div>

                <!-- Category Rankings -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">Flywheel Value by Category</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Flywheel = Impact × Compound · Bar color = Weighted Score (time × flywheel)</p>
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
                            <div class="w-4 h-1.5 bg-amber-400 rounded"></div> Time × Flywheel
                        </div>
                    </div>
                </div>

                <!-- Recommendations -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">💡 Flywheel Optimization Tips</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Based on the Flywheel matrix, here are recommended adjustments:</p>
                    <div v-if="recommendations.length === 0" class="text-sm text-gray-400 text-center py-4">
                        Not enough data to analyze. Add more outputs!
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
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">📈 Weekly Flywheel Score Trend</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Weekly accumulated Flywheel score (time × compound value)</p>
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
                            No data yet
                        </div>
                    </div>
                </div>

                <!-- Category Detail Table -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-4">Flywheel Value Details</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-sm text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700">
                                    <th class="text-left pb-3">Category</th>
                                    <th class="text-center pb-3">Impact</th>
                                    <th class="text-center pb-3">Compound</th>
                                    <th class="text-center pb-3">Flywheel</th>
                                    <th class="text-right pb-3">Time</th>
                                    <th class="text-right pb-3">% Total</th>
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
                                    <td colspan="4" class="pt-3 text-sm text-gray-400">Total</td>
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


            </div>
        </div>
    </AuthenticatedLayout>
</template>
