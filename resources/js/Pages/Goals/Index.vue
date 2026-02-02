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
    coreGoals: Array,
    regularGoals: Array,
    goalsByCategory: Object,
    categories: Array,
    stats: Object,
    view: String,
});

const currentView = computed(() => props.view || 'visionboard');

// Expanded state for core goals milestones
const expandedCoreGoals = ref({});
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

// Toggle expanded state for core goal milestones
const toggleCoreGoal = (goalId) => {
    expandedCoreGoals.value[goalId] = !expandedCoreGoals.value[goalId];
};

// Toggle milestone completion
const toggleMilestone = (goalId, milestone) => {
    router.patch(route('milestones.toggle', [goalId, milestone.id]), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Filter core goals based on selected category
const filteredCoreGoals = computed(() => {
    const goals = props.coreGoals || [];
    if (!selectedCategory.value) return goals;
    return goals.filter(goal => goal.category_id === selectedCategory.value);
});

// Filter regular goals based on selected category
const filteredRegularGoals = computed(() => {
    const goals = props.regularGoals || [];
    if (!selectedCategory.value) return goals;
    return goals.filter(goal => goal.category_id === selectedCategory.value);
});

// Local reactive copy of regular goals for drag & drop in Plan view
const regularGoalsList = ref([...(props.regularGoals || [])]);

watch(() => props.regularGoals, (newGoals) => {
    regularGoalsList.value = [...(newGoals || [])];
}, { deep: true });

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
                <!-- Stats Cards (only in Plan view) -->
                <div v-if="currentView === 'plan'" class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
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

                <!-- Category Filter (only in Plan view) -->
                <div v-if="currentView === 'plan'" class="flex flex-wrap items-center gap-2 mb-6">
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

                <!-- VisionBoard View (3 Core Goals) -->
                <div v-if="currentView === 'visionboard'" class="relative overflow-hidden">
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
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-4 sm:mt-6 max-w-xs mx-auto italic">
                                    "Mỗi ngày đi một đoạn nhỏ, <br>đủ bền để đi xa, <br>đủ chậm để không bỏ quên mình."
                                </p>
                            </div>
                        </div>

                        <!-- Orbiting Core Goals (3 trục trung tâm) -->
                        <div
                            class="absolute inset-0 flex items-center justify-center orbit-container"
                            :class="{ 'orbit-paused': orbitPaused }"
                        >
                            <OrbitGoalCard
                                v-for="(goal, index) in filteredCoreGoals"
                                :key="goal.id"
                                :goal="goal"
                                :index="index"
                                :total="filteredCoreGoals.length"
                                :radius="orbitRadius"
                                :is-mobile="orbitSize < 500"
                            />
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="filteredCoreGoals.length === 0"
                        class="absolute inset-0 flex items-center justify-center"
                    >
                        <div class="text-center">
                            <div class="text-6xl mb-4">🎯</div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                Chưa có Core Goals
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-6">
                                Thêm 3 trục trung tâm để xoay quanh Vision Board!
                            </p>
                            <Link
                                :href="route('goals.create')"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors"
                            >
                                <span>+</span>
                                <span>Thêm Core Goal</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Plan View -->
                <div v-if="currentView === 'plan'" class="space-y-8">
                    <!-- Core Goals Section (with Milestones) -->
                    <div v-if="filteredCoreGoals.length > 0" class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            🎯 Core Goals - Trục Trung Tâm
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">(click để xem milestones)</span>
                        </h3>
                        <div class="space-y-4">
                            <div
                                v-for="goal in filteredCoreGoals"
                                :key="goal.id"
                                class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden"
                            >
                                <!-- Goal Header (clickable to expand) -->
                                <div
                                    @click="toggleCoreGoal(goal.id)"
                                    class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">{{ goal.category?.icon }}</span>
                                            <div>
                                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ goal.title }}</h4>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ goal.milestones?.length || 0 }} milestones
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <div class="text-right">
                                                <div class="font-bold text-lg" :class="goal.progress >= 100 ? 'text-green-500' : 'text-indigo-600 dark:text-indigo-400'">
                                                    {{ goal.progress }}%
                                                </div>
                                                <div class="w-24 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                    <div
                                                        class="h-full bg-indigo-500 rounded-full transition-all duration-500"
                                                        :style="{ width: `${goal.progress}%` }"
                                                    ></div>
                                                </div>
                                            </div>
                                            <svg
                                                class="w-5 h-5 text-gray-400 transition-transform"
                                                :class="{ 'rotate-180': expandedCoreGoals[goal.id] }"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Milestones (expandable) -->
                                <div
                                    v-show="expandedCoreGoals[goal.id]"
                                    class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50"
                                >
                                    <div class="p-4 space-y-2">
                                        <div
                                            v-for="milestone in goal.milestones"
                                            :key="milestone.id"
                                            class="flex items-center gap-3 p-2 rounded-lg hover:bg-white dark:hover:bg-gray-800 transition-colors"
                                        >
                                            <button
                                                @click.stop="toggleMilestone(goal.id, milestone)"
                                                class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                                                :class="milestone.is_completed
                                                    ? 'bg-green-500 border-green-500 text-white'
                                                    : 'border-gray-300 dark:border-gray-600 hover:border-indigo-500'"
                                            >
                                                <svg v-if="milestone.is_completed" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                            <span
                                                class="flex-1"
                                                :class="milestone.is_completed ? 'text-gray-400 line-through' : 'text-gray-700 dark:text-gray-300'"
                                            >
                                                {{ milestone.title }}
                                            </span>
                                            <span
                                                v-if="milestone.target_date"
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {{ new Date(milestone.target_date).toLocaleDateString('ja-JP', { month: 'short', day: 'numeric' }) }}
                                            </span>
                                        </div>
                                        <div v-if="!goal.milestones?.length" class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                                            Chưa có milestones. <Link :href="route('goals.show', goal.id)" class="text-indigo-600 hover:underline">Thêm milestones</Link>
                                        </div>
                                    </div>
                                    <div class="px-4 pb-4">
                                        <Link
                                            :href="route('goals.show', goal.id)"
                                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                                        >
                                            Xem chi tiết goal →
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Other Goals Section -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            📋 Other Goals
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">(drag to reorder)</span>
                        </h3>
                        <draggable
                            v-model="regularGoalsList"
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
                        <div
                            v-if="filteredRegularGoals.length === 0"
                            class="text-center py-8 text-gray-500 dark:text-gray-400"
                        >
                            <div class="text-4xl mb-2">📝</div>
                            <p>Chưa có goals thường. Tất cả goals đều là Core Goals!</p>
                        </div>
                    </div>

                    <!-- Empty State (no goals at all) -->
                    <div
                        v-if="filteredCoreGoals.length === 0 && filteredRegularGoals.length === 0"
                        class="text-center py-16"
                    >
                        <div class="text-6xl mb-4">🎯</div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            Chưa có goals
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            Bắt đầu bằng việc thêm goal đầu tiên!
                        </p>
                        <Link
                            :href="route('goals.create')"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors"
                        >
                            <span>+</span>
                            <span>Thêm Goal</span>
                        </Link>
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
