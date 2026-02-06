<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TodayItemCard from '@/Components/TodayItemCard.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    items: Object, // { overdue: [], today: [], upcoming: [] }
    stats: Object,
});

// Filter state
const typeFilter = ref('all'); // 'all', 'milestone', 'todo', 'reminder'

// Filter items by type
const filterItems = (items) => {
    if (typeFilter.value === 'all') return items;
    return items.filter(item => item.type === typeFilter.value);
};

const filteredOverdue = computed(() => filterItems(props.items.overdue));
const filteredToday = computed(() => filterItems(props.items.today));
const filteredUpcoming = computed(() => filterItems(props.items.upcoming));

// Toggle actions
const toggleMilestone = (item) => {
    router.patch(route('milestones.toggle', [item.goal.id, item.id]), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const toggleTodo = (item) => {
    router.patch(route('milestone-todos.toggle', [
        item.goal.id,
        item.milestone.id,
        item.id
    ]), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const toggleItem = (item) => {
    if (item.type === 'milestone') {
        toggleMilestone(item);
    } else if (item.type === 'todo') {
        toggleTodo(item);
    }
    // Reminders don't toggle
};

// Toggle todo within a milestone
const toggleMilestoneTodo = ({ goalId, milestoneId, todoId }) => {
    router.patch(route('milestone-todos.toggle', [goalId, milestoneId, todoId]), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Navigate to goal detail
const goToGoal = (goalId) => {
    router.visit(route('goals.show', goalId));
};

// Dismiss reminder (skip today's notification)
const dismissReminder = (item) => {
    const currentFilter = typeFilter.value;
    router.patch(route('reminders.dismiss', [item.goal.id, item.id]), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Restore filter after page reload
            typeFilter.value = currentFilter;
        },
    });
};

// Format today's date
const todayDate = computed(() => {
    return new Date().toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

// Filter tabs config
const filterTabs = [
    { value: 'all', label: 'All' },
    { value: 'milestone', label: 'Milestones' },
    { value: 'reminder', label: 'Reminders' },
];
</script>

<template>
    <Head title="Today" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 sm:mb-8">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white hope:text-emerald-900">
                        Today
                    </h1>
                    <p class="mt-1 text-gray-600 dark:text-gray-400 hope:text-emerald-700 capitalize">
                        {{ todayDate }}
                    </p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div
                        class="bg-red-50 dark:bg-red-900/30 hope:bg-red-50 rounded-xl p-4 border border-red-200 dark:border-red-800 hope:border-red-200 cursor-help"
                        title="Tasks that are past their due date"
                    >
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                            {{ stats.overdue }}
                        </div>
                        <div class="text-sm text-red-700 dark:text-red-300">Overdue</div>
                    </div>
                    <div
                        class="bg-amber-50 dark:bg-amber-900/30 hope:bg-amber-50 rounded-xl p-4 border border-amber-200 dark:border-amber-800 hope:border-amber-200 cursor-help"
                        title="Tasks that need to be completed today"
                    >
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                            {{ stats.today }}
                        </div>
                        <div class="text-sm text-amber-700 dark:text-amber-300">Due Today</div>
                    </div>
                    <div
                        class="bg-blue-50 dark:bg-blue-900/30 hope:bg-blue-50 rounded-xl p-4 border border-blue-200 dark:border-blue-800 hope:border-blue-200 cursor-help"
                        title="Tasks due within the next 7 days"
                    >
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ stats.upcoming }}
                        </div>
                        <div class="text-sm text-blue-700 dark:text-blue-300">This Week</div>
                    </div>
                    <div
                        class="bg-gray-50 dark:bg-gray-800 hope:bg-emerald-50 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hope:border-emerald-200 cursor-help"
                        title="Total tasks (Overdue + Due Today + This Week)"
                    >
                        <div class="text-2xl font-bold text-gray-900 dark:text-white hope:text-emerald-800">
                            {{ stats.total }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 hope:text-emerald-700">Total</div>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-2">
                    <button
                        v-for="filter in filterTabs"
                        :key="filter.value"
                        @click="typeFilter = filter.value"
                        class="px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors"
                        :class="typeFilter === filter.value
                            ? 'bg-indigo-600 text-white hope:bg-emerald-600'
                            : 'bg-gray-100 dark:bg-gray-800 hope:bg-emerald-100 text-gray-600 dark:text-gray-400 hope:text-emerald-800 hover:bg-gray-200 dark:hover:bg-gray-700 hope:hover:bg-emerald-200'"
                    >
                        {{ filter.label }}
                    </button>
                </div>

                <!-- Overdue Section -->
                <section v-if="filteredOverdue.length > 0" class="mb-8">
                    <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Overdue</span>
                        <span class="text-sm font-normal bg-red-100 dark:bg-red-900/50 px-2 py-0.5 rounded-full">
                            {{ filteredOverdue.length }}
                        </span>
                    </h2>
                    <div class="space-y-3">
                        <TodayItemCard
                            v-for="item in filteredOverdue"
                            :key="`${item.type}-${item.id}`"
                            :item="item"
                            variant="overdue"
                            @toggle="toggleItem"
                            @toggle-todo="toggleMilestoneTodo"
                            @click-goal="goToGoal"
                            @dismiss-reminder="dismissReminder"
                        />
                    </div>
                </section>

                <!-- Today Section -->
                <section v-if="filteredToday.length > 0" class="mb-8">
                    <h2 class="text-lg font-semibold text-amber-600 dark:text-amber-400 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Due Today</span>
                        <span class="text-sm font-normal bg-amber-100 dark:bg-amber-900/50 px-2 py-0.5 rounded-full">
                            {{ filteredToday.length }}
                        </span>
                    </h2>
                    <div class="space-y-3">
                        <TodayItemCard
                            v-for="item in filteredToday"
                            :key="`${item.type}-${item.id}`"
                            :item="item"
                            variant="today"
                            @toggle="toggleItem"
                            @toggle-todo="toggleMilestoneTodo"
                            @click-goal="goToGoal"
                            @dismiss-reminder="dismissReminder"
                        />
                    </div>
                </section>

                <!-- Upcoming Section -->
                <section v-if="filteredUpcoming.length > 0" class="mb-8">
                    <h2 class="text-lg font-semibold text-blue-600 dark:text-blue-400 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>This Week</span>
                        <span class="text-sm font-normal bg-blue-100 dark:bg-blue-900/50 px-2 py-0.5 rounded-full">
                            {{ filteredUpcoming.length }}
                        </span>
                    </h2>
                    <div class="space-y-3">
                        <TodayItemCard
                            v-for="item in filteredUpcoming"
                            :key="`${item.type}-${item.id}`"
                            :item="item"
                            variant="upcoming"
                            @toggle="toggleItem"
                            @toggle-todo="toggleMilestoneTodo"
                            @click-goal="goToGoal"
                            @dismiss-reminder="dismissReminder"
                        />
                    </div>
                </section>

                <!-- Empty State -->
                <div
                    v-if="stats.total === 0"
                    class="text-center py-16"
                >
                    <div class="text-6xl mb-4">
                        {{ typeFilter === 'all' ? '🎉' : '📭' }}
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white hope:text-emerald-900 mb-2">
                        All caught up!
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 hope:text-emerald-700">
                        No pending tasks for this week.
                    </p>
                </div>

                <!-- Filtered Empty State -->
                <div
                    v-else-if="filteredOverdue.length === 0 && filteredToday.length === 0 && filteredUpcoming.length === 0"
                    class="text-center py-16"
                >
                    <div class="text-6xl mb-4">📭</div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white hope:text-emerald-900 mb-2">
                        No {{ typeFilter }}s found
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 hope:text-emerald-700">
                        Try selecting a different filter.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
