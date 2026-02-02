<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GoalCard from '@/Components/GoalCard.vue';
import OrbitGoalCard from '@/Components/OrbitGoalCard.vue';
import OverviewProgressChart from '@/Components/Charts/OverviewProgressChart.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps({
    goals: Array,
    goalsByCategory: Object,
    categories: Array,
    stats: Object,
    view: String,
});

const currentView = computed(() => props.view || 'orbit');
const selectedCategory = ref(null);
const drag = ref(false);
const orbitPaused = ref(false);

// Responsive orbit size
const orbitSize = ref(900);
const orbitRadius = ref(280);

const updateOrbitSize = () => {
    const width = window.innerWidth;
    if (width < 480) {
        orbitSize.value = 320;
        orbitRadius.value = 100;
    } else if (width < 640) {
        orbitSize.value = 400;
        orbitRadius.value = 130;
    } else if (width < 768) {
        orbitSize.value = 500;
        orbitRadius.value = 160;
    } else if (width < 1024) {
        orbitSize.value = 650;
        orbitRadius.value = 200;
    } else {
        orbitSize.value = 900;
        orbitRadius.value = 280;
    }
};

// Initialize and listen for resize
if (typeof window !== 'undefined') {
    updateOrbitSize();
    window.addEventListener('resize', updateOrbitSize);
}

// Local reactive copy of goals for drag & drop
const localGoals = ref([...props.goals]);

// Watch for prop changes
watch(() => props.goals, (newGoals) => {
    localGoals.value = [...newGoals];
}, { deep: true });

// Separate refs for pinned and unpinned goals to enable proper drag & drop
const pinnedGoalsList = ref(props.goals.filter(g => g.is_pinned));
const unpinnedGoalsList = ref(props.goals.filter(g => !g.is_pinned));

watch(() => props.goals, (newGoals) => {
    pinnedGoalsList.value = newGoals.filter(g => g.is_pinned);
    unpinnedGoalsList.value = newGoals.filter(g => !g.is_pinned);
}, { deep: true });

const filteredPinnedGoals = computed(() => {
    if (!selectedCategory.value) return pinnedGoalsList.value;
    return pinnedGoalsList.value.filter(g => g.category_id === selectedCategory.value);
});

const filteredUnpinnedGoals = computed(() => {
    if (!selectedCategory.value) return unpinnedGoalsList.value;
    return unpinnedGoalsList.value.filter(g => g.category_id === selectedCategory.value);
});

const filteredGoals = computed(() => {
    if (!selectedCategory.value) return localGoals.value;
    return localGoals.value.filter(goal => goal.category_id === selectedCategory.value);
});

const filteredGoalsByCategory = computed(() => {
    const result = {};
    props.categories.forEach(cat => {
        const goals = unpinnedGoalsList.value.filter(g => g.category_id === cat.id);
        if (goals.length > 0) {
            result[cat.id] = goals;
        }
    });
    if (selectedCategory.value) {
        return { [selectedCategory.value]: result[selectedCategory.value] || [] };
    }
    return result;
});

const getCategoryById = (id) => {
    return props.categories.find(c => c.id === parseInt(id));
};

const saveOrder = () => {
    drag.value = false;
    // Combine pinned and unpinned goals and update sort_order
    const allGoals = [...pinnedGoalsList.value, ...unpinnedGoalsList.value];
    const goalsToUpdate = allGoals.map((goal, index) => ({
        id: goal.id,
        sort_order: index,
    }));

    router.post(route('goals.reorder'), { goals: goalsToUpdate }, {
        preserveScroll: true,
        preserveState: true,
    });
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

                <!-- Category Filter -->
                <div class="flex flex-wrap items-center gap-2 mb-6">
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

                <!-- Orbit View -->
                <div v-if="currentView === 'orbit'" class="relative overflow-hidden">
                    <!-- Orbit Container -->
                    <div
                        class="relative mx-auto transition-all duration-300"
                        :style="{ width: orbitSize + 'px', height: orbitSize + 'px' }"
                        @mouseenter="orbitPaused = true"
                        @mouseleave="orbitPaused = false"
                        @touchstart="orbitPaused = true"
                        @touchend="orbitPaused = false"
                    >
                        <!-- Orbit Rings (decorative) - responsive -->
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div
                                class="absolute rounded-full border border-gray-200 dark:border-gray-700 opacity-50"
                                :style="{ width: (orbitSize * 0.8) + 'px', height: (orbitSize * 0.8) + 'px' }"
                            ></div>
                            <div
                                class="absolute rounded-full border border-gray-200 dark:border-gray-700 opacity-40"
                                :style="{ width: (orbitSize * 0.62) + 'px', height: (orbitSize * 0.62) + 'px' }"
                            ></div>
                            <div
                                class="absolute rounded-full border border-gray-200 dark:border-gray-700 opacity-30"
                                :style="{ width: (orbitSize * 0.44) + 'px', height: (orbitSize * 0.44) + 'px' }"
                            ></div>
                        </div>

                        <!-- Center Title - responsive text -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center z-0">
                                <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-400 dark:via-purple-400 dark:to-pink-400 leading-tight">
                                    VISION
                                </h1>
                                <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-400 dark:via-purple-400 dark:to-pink-400 leading-tight">
                                    BOARD
                                </h1>
                                <h2 class="text-4xl sm:text-5xl md:text-6xl font-black text-gray-900 dark:text-white mt-2">
                                    2026
                                </h2>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-2 sm:mt-4">
                                    {{ filteredGoals.length }} goals
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 hidden sm:block">
                                    Hover to pause
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 sm:hidden">
                                    Tap to pause
                                </p>
                            </div>
                        </div>

                        <!-- Orbiting Goals -->
                        <div
                            class="absolute inset-0 flex items-center justify-center orbit-container"
                            :class="{ 'orbit-paused': orbitPaused }"
                        >
                            <OrbitGoalCard
                                v-for="(goal, index) in filteredGoals"
                                :key="goal.id"
                                :goal="goal"
                                :index="index"
                                :total="filteredGoals.length"
                                :radius="orbitRadius"
                                :is-mobile="orbitSize < 500"
                            />
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="filteredGoals.length === 0"
                        class="absolute inset-0 flex items-center justify-center"
                    >
                        <div class="text-center">
                            <div class="text-6xl mb-4">🪐</div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                No goals in orbit yet
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">
                                Add goals to see them orbit around your vision!
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
                </div>

                <!-- Board View (Pinterest-like) -->
                <div v-if="currentView === 'board'" class="space-y-8">
                    <!-- Pinned Goals -->
                    <div v-if="filteredPinnedGoals.length > 0" class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            📍 Pinned Goals
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">(drag to reorder)</span>
                        </h3>
                        <draggable
                            v-model="pinnedGoalsList"
                            item-key="id"
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
                            ghost-class="opacity-50"
                            drag-class="shadow-2xl"
                            :animation="200"
                            @start="drag = true"
                            @end="saveOrder"
                        >
                            <template #item="{ element }">
                                <GoalCard
                                    v-if="!selectedCategory || element.category_id === selectedCategory"
                                    :goal="element"
                                    class="cursor-grab active:cursor-grabbing"
                                />
                            </template>
                        </draggable>
                    </div>

                    <!-- All Unpinned Goals (draggable across categories) -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            🎯 All Goals
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">(drag to reorder)</span>
                        </h3>
                        <draggable
                            v-model="unpinnedGoalsList"
                            item-key="id"
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
                            ghost-class="opacity-50"
                            drag-class="shadow-2xl"
                            :animation="200"
                            @start="drag = true"
                            @end="saveOrder"
                        >
                            <template #item="{ element }">
                                <GoalCard
                                    v-if="!selectedCategory || element.category_id === selectedCategory"
                                    :goal="element"
                                    class="cursor-grab active:cursor-grabbing"
                                />
                            </template>
                        </draggable>
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
                    <!-- Progress Trend Chart -->
                    <OverviewProgressChart :goals="filteredGoals" />

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

<style scoped>
.orbit-container {
    animation: orbit-rotate 60s linear infinite;
}

.orbit-container.orbit-paused {
    animation-play-state: paused;
}

.orbit-container.orbit-paused :deep(.card-inner) {
    animation-play-state: paused;
}

@keyframes orbit-rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
