<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GoalCard from '@/Components/GoalCard.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    goals: Array,
    goalsByCategory: Object,
    categories: Array,
    stats: Object,
    view: String,
});

const currentView = ref(props.view || 'board');
const selectedCategory = ref(null);

const filteredGoals = computed(() => {
    if (!selectedCategory.value) return props.goals;
    return props.goals.filter(goal => goal.category_id === selectedCategory.value);
});

const filteredGoalsByCategory = computed(() => {
    if (!selectedCategory.value) return props.goalsByCategory;
    return { [selectedCategory.value]: props.goalsByCategory[selectedCategory.value] };
});

const switchView = (view) => {
    currentView.value = view;
    router.get(route('goals.index'), { view }, { preserveState: true, preserveScroll: true });
};

const getCategoryById = (id) => {
    return props.categories.find(c => c.id === parseInt(id));
};
</script>

<template>
    <Head title="Vision Board 2026" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                        🎯 v!t's 2026 Vision Board
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Track your goals and make 2026 your best year!
                    </p>
                </div>
                <Link
                    :href="route('goals.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors"
                >
                    <span>+</span>
                    <span>Add Goal</span>
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Goals</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                        <div class="text-3xl font-bold text-green-500">{{ stats.completed }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Completed</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                        <div class="text-3xl font-bold text-blue-500">{{ stats.in_progress }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">In Progress</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                        <div class="text-3xl font-bold text-gray-500">{{ stats.not_started }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Not Started</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                        <div class="text-3xl font-bold text-indigo-500">{{ stats.overall_progress }}%</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Overall Progress</div>
                        <div class="mt-2 h-2 bg-gray-200 dark:bg-gray-700 rounded-full">
                            <div
                                class="h-full bg-indigo-500 rounded-full transition-all duration-500"
                                :style="{ width: `${stats.overall_progress}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- View Toggle & Filters -->
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <!-- Category Filter -->
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            @click="selectedCategory = null"
                            class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors"
                            :class="selectedCategory === null
                                ? 'bg-indigo-600 text-white'
                                : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'"
                        >
                            All
                        </button>
                        <button
                            v-for="category in categories"
                            :key="category.id"
                            @click="selectedCategory = category.id"
                            class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors"
                            :class="selectedCategory === category.id
                                ? 'text-white'
                                : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'"
                            :style="selectedCategory === category.id ? { backgroundColor: category.color } : {}"
                        >
                            {{ category.icon }} {{ category.name }}
                        </button>
                    </div>

                    <!-- View Toggle -->
                    <div class="flex items-center bg-gray-200 dark:bg-gray-700 rounded-lg p-1">
                        <button
                            @click="switchView('board')"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors"
                            :class="currentView === 'board'
                                ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                        >
                            🎨 Board
                        </button>
                        <button
                            @click="switchView('dashboard')"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-colors"
                            :class="currentView === 'dashboard'
                                ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                        >
                            📊 Dashboard
                        </button>
                    </div>
                </div>

                <!-- Board View (Pinterest-like) -->
                <div v-if="currentView === 'board'" class="space-y-8">
                    <!-- Pinned Goals -->
                    <div v-if="filteredGoals.some(g => g.is_pinned)" class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            📍 Pinned Goals
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            <GoalCard
                                v-for="goal in filteredGoals.filter(g => g.is_pinned)"
                                :key="goal.id"
                                :goal="goal"
                            />
                        </div>
                    </div>

                    <!-- Goals by Category -->
                    <div
                        v-for="(categoryGoals, categoryId) in filteredGoalsByCategory"
                        :key="categoryId"
                        class="mb-8"
                    >
                        <div
                            v-if="getCategoryById(categoryId)"
                            class="flex items-center gap-3 mb-4"
                        >
                            <span
                                class="text-2xl w-10 h-10 flex items-center justify-center rounded-lg"
                                :style="{ backgroundColor: `${getCategoryById(categoryId).color}20` }"
                            >
                                {{ getCategoryById(categoryId).icon }}
                            </span>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ getCategoryById(categoryId).name }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ categoryGoals.length }} goal{{ categoryGoals.length > 1 ? 's' : '' }}
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            <GoalCard
                                v-for="goal in categoryGoals.filter(g => !g.is_pinned)"
                                :key="goal.id"
                                :goal="goal"
                                :show-category="false"
                            />
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="filteredGoals.length === 0"
                        class="text-center py-16"
                    >
                        <div class="text-6xl mb-4">🎯</div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            No goals yet
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            Start building your vision board by adding your first goal!
                        </p>
                        <Link
                            :href="route('goals.create')"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors"
                        >
                            <span>+</span>
                            <span>Add Your First Goal</span>
                        </Link>
                    </div>
                </div>

                <!-- Dashboard View -->
                <div v-else class="space-y-6">
                    <!-- Progress Overview -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            📈 Progress Overview
                        </h3>
                        <div class="space-y-4">
                            <div
                                v-for="goal in filteredGoals"
                                :key="goal.id"
                                class="group"
                            >
                                <Link
                                    :href="route('goals.show', goal.id)"
                                    class="block"
                                >
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="flex items-center gap-2">
                                            <span>{{ goal.category?.icon }}</span>
                                            <span class="font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                                {{ goal.title }}
                                            </span>
                                            <span v-if="goal.is_pinned" class="text-sm">📍</span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span
                                                v-if="goal.target_value && goal.unit"
                                                class="text-sm text-gray-500 dark:text-gray-400"
                                            >
                                                {{ goal.current_value?.toLocaleString() || 0 }}/{{ goal.target_value?.toLocaleString() }} {{ goal.unit }}
                                            </span>
                                            <span
                                                class="font-bold"
                                                :class="goal.progress >= 100 ? 'text-green-500' : 'text-gray-900 dark:text-white'"
                                            >
                                                {{ goal.progress }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="{
                                                'bg-green-500': goal.progress >= 100,
                                                'bg-emerald-500': goal.progress >= 75 && goal.progress < 100,
                                                'bg-yellow-500': goal.progress >= 50 && goal.progress < 75,
                                                'bg-orange-500': goal.progress >= 25 && goal.progress < 50,
                                                'bg-red-500': goal.progress < 25,
                                            }"
                                            :style="{ width: `${goal.progress}%` }"
                                        ></div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Goals by Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- In Progress -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
                            <h3 class="text-lg font-semibold text-blue-600 mb-4 flex items-center gap-2">
                                🚀 In Progress
                            </h3>
                            <div class="space-y-3">
                                <Link
                                    v-for="goal in filteredGoals.filter(g => g.status === 'in_progress')"
                                    :key="goal.id"
                                    :href="route('goals.show', goal.id)"
                                    class="block p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                                >
                                    <div class="flex items-center gap-2 mb-1">
                                        <span>{{ goal.category?.icon }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ goal.title }}</span>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ goal.progress }}% complete
                                    </div>
                                </Link>
                                <div
                                    v-if="!filteredGoals.some(g => g.status === 'in_progress')"
                                    class="text-sm text-gray-500 dark:text-gray-400 text-center py-4"
                                >
                                    No goals in progress
                                </div>
                            </div>
                        </div>

                        <!-- Not Started -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
                            <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-400 mb-4 flex items-center gap-2">
                                📋 Not Started
                            </h3>
                            <div class="space-y-3">
                                <Link
                                    v-for="goal in filteredGoals.filter(g => g.status === 'not_started')"
                                    :key="goal.id"
                                    :href="route('goals.show', goal.id)"
                                    class="block p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <div class="flex items-center gap-2 mb-1">
                                        <span>{{ goal.category?.icon }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ goal.title }}</span>
                                    </div>
                                    <div
                                        v-if="goal.target_date"
                                        class="text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        Target: {{ new Date(goal.target_date).toLocaleDateString('ja-JP') }}
                                    </div>
                                </Link>
                                <div
                                    v-if="!filteredGoals.some(g => g.status === 'not_started')"
                                    class="text-sm text-gray-500 dark:text-gray-400 text-center py-4"
                                >
                                    All goals have been started!
                                </div>
                            </div>
                        </div>

                        <!-- Completed -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
                            <h3 class="text-lg font-semibold text-green-600 mb-4 flex items-center gap-2">
                                ✅ Completed
                            </h3>
                            <div class="space-y-3">
                                <Link
                                    v-for="goal in filteredGoals.filter(g => g.status === 'completed')"
                                    :key="goal.id"
                                    :href="route('goals.show', goal.id)"
                                    class="block p-3 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors"
                                >
                                    <div class="flex items-center gap-2">
                                        <span>{{ goal.category?.icon }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ goal.title }}</span>
                                        <span class="text-green-500">✓</span>
                                    </div>
                                </Link>
                                <div
                                    v-if="!filteredGoals.some(g => g.status === 'completed')"
                                    class="text-sm text-gray-500 dark:text-gray-400 text-center py-4"
                                >
                                    No completed goals yet
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Deadlines -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            📅 Upcoming Deadlines
                        </h3>
                        <div class="space-y-3">
                            <Link
                                v-for="goal in filteredGoals
                                    .filter(g => g.target_date && g.status !== 'completed')
                                    .sort((a, b) => new Date(a.target_date) - new Date(b.target_date))
                                    .slice(0, 5)"
                                :key="goal.id"
                                :href="route('goals.show', goal.id)"
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <span>{{ goal.category?.icon }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ goal.title }}</span>
                                </div>
                                <div
                                    class="text-sm font-medium"
                                    :class="{
                                        'text-red-500': new Date(goal.target_date) < new Date(),
                                        'text-orange-500': new Date(goal.target_date) - new Date() < 7 * 24 * 60 * 60 * 1000,
                                        'text-gray-500 dark:text-gray-400': new Date(goal.target_date) - new Date() >= 7 * 24 * 60 * 60 * 1000,
                                    }"
                                >
                                    {{ new Date(goal.target_date).toLocaleDateString('ja-JP', { month: 'short', day: 'numeric' }) }}
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
