<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import OutputStatsBar from '@/Components/TrackingOutput/OutputStatsBar.vue';
import DayOutputGroup from '@/Components/TrackingOutput/DayOutputGroup.vue';
import OutputFormModal from '@/Components/TrackingOutput/OutputFormModal.vue';
import CalendarHeatmap from '@/Components/TrackingOutput/CalendarHeatmap.vue';
import SpinWheel from '@/Components/TrackingOutput/SpinWheel.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    outputs: Object,
    streakData: Object,
    stats: Object,
    heatmap: Array,
    goals: Array,
    restDays: Array,
    currentDate: String,
    currentView: String,
    categories: Object,
    durationPresets: Array,
    isPublic: { type: Boolean, default: false },
});

const viewMode = ref(props.currentView || 'list');
const showFormModal = ref(false);
const editingOutput = ref(null);
const defaultDate = ref(null);
const defaultTitle = ref(null);
const categoryFilter = ref('all');
const showSpinWheel = ref(false);

// Get sorted dates (desc) from outputs
const sortedDates = computed(() => {
    if (!props.outputs) return [];
    return Object.keys(props.outputs).sort((a, b) => b.localeCompare(a));
});

// Local date helpers (avoid UTC conversion issues)
const getLocalDateStr = (d) => {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
};

const today = getLocalDateStr(new Date());
const tomorrowDate = new Date();
tomorrowDate.setDate(tomorrowDate.getDate() + 1);
const tomorrow = getLocalDateStr(tomorrowDate);

// Build list of dates to show (today + tomorrow + dates with outputs)
const displayDates = computed(() => {
    const trackingStart = '2026-02-17';
    const trackingEnd = '2027-02-05';

    const dateSet = new Set();

    // Add tomorrow and all dates with outputs (kể cả planned tương lai)
    dateSet.add(tomorrow);
    for (const d of sortedDates.value) dateSet.add(d);

    // Thêm tất cả ngày từ tracking start đến hôm nay (kể cả ngày bị miss)
    const cur = new Date(trackingStart + 'T00:00:00');
    const todayDate = new Date(today + 'T00:00:00');
    while (cur <= todayDate) {
        const y = cur.getFullYear();
        const m = String(cur.getMonth() + 1).padStart(2, '0');
        const d = String(cur.getDate()).padStart(2, '0');
        dateSet.add(`${y}-${m}-${d}`);
        cur.setDate(cur.getDate() + 1);
    }

    // Filter within tracking range and sort desc
    return [...dateSet]
        .filter(d => d >= trackingStart && d <= trackingEnd)
        .sort((a, b) => b.localeCompare(a));
});

// Get filtered outputs for a specific date
const getOutputsForDate = (date) => {
    const outputs = props.outputs?.[date] || [];
    if (categoryFilter.value === 'all') return outputs;
    return outputs.filter(o => o.category === categoryFilter.value);
};

const isToday = (date) => date === today;
const isTomorrow = (date) => date === tomorrow;

// Calculate day number from tracking start
const getDayNumber = (date) => {
    const start = new Date('2026-02-17T00:00:00');
    const d = new Date(date + 'T00:00:00');
    const diff = Math.floor((d - start) / 86400000) + 1;
    return diff > 0 ? diff : null;
};

const isRestDay = (date) => {
    return props.restDays?.includes(date);
};

// Ngày bị miss: đã qua, không có output done, không phải rest day
const isMissed = (date) => {
    if (date >= today) return false;
    if (props.restDays?.includes(date)) return false;
    const outputs = props.outputs?.[date] || [];
    return !outputs.some(o => o.status === 'done');
};

// Modal handlers
const openAddModal = (date, title = null) => {
    editingOutput.value = null;
    defaultDate.value = date || today;
    defaultTitle.value = title;
    showFormModal.value = true;
};

// Spin wheel handler
const onWheelResult = (label) => {
    showSpinWheel.value = false;
    openAddModal(today, label);
};

const openEditModal = (output) => {
    editingOutput.value = output;
    defaultDate.value = null;
    showFormModal.value = true;
};

const closeModal = () => {
    showFormModal.value = false;
    editingOutput.value = null;
    defaultTitle.value = null;
    defaultDate.value = null;
};

const deleteOutput = (id) => {
    if (!confirm('Delete this output?')) return;
    router.delete(route('tracking-output.destroy', id), {
        preserveScroll: true,
    });
};

const toggleRestDay = (date) => {
    router.post(route('tracking-output.rest-day'), {
        rest_date: date,
    }, { preserveScroll: true });
};

const selectDateFromCalendar = (date) => {
    viewMode.value = 'list';
    // Scroll to the date if visible, or open add modal
    const el = document.getElementById(`day-${date}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        openAddModal(date);
    }
};

const switchView = (view) => {
    viewMode.value = view;
};
</script>

<template>
    <Head title="Daily Output Tracker" />

    <component
        :is="isPublic ? 'div' : AuthenticatedLayout"
        :class="isPublic ? 'min-h-screen bg-gray-100 dark:bg-gray-900' : ''"
    >
        <!-- Public header (only when not logged in) -->
        <div v-if="isPublic" class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
                <span class="font-semibold text-gray-900 dark:text-white">🚀 v!t's Daily Output Tracker</span>
                <div class="flex items-center gap-3">
                    <ThemeSwitcher />
                    <a :href="route('login')" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                        Login →
                    </a>
                </div>
            </div>
        </div>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                                Daily Output Tracker
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                17/2/2026 → 5/2/2027
                                <span v-if="streakData.day_number > 0">
                                    &middot; Day #{{ streakData.day_number }} / {{ streakData.total_days }}
                                </span>
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Analytics Link -->
                            <Link
                                v-if="!isPublic"
                                :href="route('tracking-output.analytics')"
                                class="px-3 py-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-lg text-sm font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors border border-indigo-200 dark:border-indigo-700"
                            >
                                📊 Analytics
                            </Link>

                            <!-- View Toggle -->
                            <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                                <button
                                    @click="switchView('list')"
                                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                                    :class="viewMode === 'list'
                                        ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                >
                                    📋 List
                                </button>
                                <button
                                    @click="switchView('calendar')"
                                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                                    :class="viewMode === 'calendar'
                                        ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                >
                                    📅 Calendar
                                </button>
                            </div>

                            <!-- Spin Wheel Button (owner only) -->
                            <button
                                v-if="!isPublic"
                                @click="showSpinWheel = true"
                                class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg text-sm font-medium hover:from-purple-600 hover:to-pink-600 transition-all"
                            >
                                🎰 Spin
                            </button>

                            <!-- Add Button (owner only) -->
                            <button
                                v-if="!isPublic"
                                @click="openAddModal(today)"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors"
                            >
                                + Add
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Bar -->
                <OutputStatsBar :streak-data="streakData" :stats="stats" />

                <!-- Category Filter -->
                <div class="mb-4 flex flex-wrap gap-2">
                    <button
                        @click="categoryFilter = 'all'"
                        class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
                        :class="categoryFilter === 'all'
                            ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'"
                    >
                        All
                    </button>
                    <button
                        v-for="(cat, key) in categories"
                        :key="key"
                        @click="categoryFilter = key"
                        class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
                        :class="categoryFilter === key
                            ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'"
                    >
                        {{ cat.icon }} {{ cat.label }}
                    </button>
                </div>

                <!-- Calendar Heatmap View -->
                <div v-if="viewMode === 'calendar'" class="mb-6">
                    <CalendarHeatmap
                        :heatmap="heatmap"
                        :rest-days="restDays"
                        @select-date="selectDateFromCalendar"
                    />
                </div>

                <!-- List View -->
                <div v-if="viewMode === 'list'" class="space-y-4">
                    <div
                        v-for="date in displayDates"
                        :key="date"
                        :id="`day-${date}`"
                    >
                        <DayOutputGroup
                            :date="date"
                            :outputs="getOutputsForDate(date)"
                            :categories="categories"
                            :is-today="isToday(date)"
                            :is-tomorrow="isTomorrow(date)"
                            :is-rest-day="isRestDay(date)"
                            :is-missed="isMissed(date)"
                            :day-number="getDayNumber(date)"
                            :is-public="isPublic"
                            @add="openAddModal"
                            @edit="openEditModal"
                            @delete="deleteOutput"
                            @toggle-rest="toggleRestDay"
                        />
                    </div>

                    <!-- Empty state -->
                    <div v-if="displayDates.length === 0" class="text-center py-12">
                        <p class="text-gray-400 dark:text-gray-500 text-lg">
                            No outputs yet.
                        </p>
                    </div>
                </div>

                <!-- Rest Day Toggle (owner only) -->
                <div v-if="!isPublic" class="mt-6 text-center">
                    <button
                        @click="toggleRestDay(today)"
                        class="text-sm transition-colors"
                        :class="isRestDay(today)
                            ? 'text-blue-500 hover:text-blue-700'
                            : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                    >
                        {{ isRestDay(today) ? '😴 Remove Rest Day (Today)' : '😴 Mark Today as Rest Day' }}
                        <span v-if="!isRestDay(today) && streakData.rest_days_available > 0" class="text-xs">
                            ({{ streakData.rest_days_available }} available)
                        </span>
                        <span v-if="!isRestDay(today) && streakData.rest_days_available === 0" class="text-xs text-red-400">
                            (0 available - streak will reset!)
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Form Modal (owner only) -->
        <OutputFormModal
            v-if="!isPublic"
            :show="showFormModal"
            :output="editingOutput"
            :categories="categories"
            :goals="goals"
            :duration-presets="durationPresets"
            :default-date="defaultDate"
            :default-title="defaultTitle"
            @close="closeModal"
        />

        <!-- Spin Wheel Modal (owner only) -->
        <SpinWheel
            v-if="!isPublic"
            :show="showSpinWheel"
            @close="showSpinWheel = false"
            @result="onWheelResult"
        />
    </component>
</template>
