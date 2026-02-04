<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GoalCard from '@/Components/GoalCard.vue';
import UnifiedFloating from '@/Components/UnifiedFloating.vue';
import GoalEditModal from '@/Components/GoalEditModal.vue';
import ThemeWordsWaterfall from '@/Components/ThemeWordsWaterfall.vue';
import ThemeWordsManager from '@/Components/ThemeWordsManager.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import draggable from 'vuedraggable';
import { marked } from 'marked';

// Markdown parser configuration
marked.setOptions({
    breaks: true, // Convert line breaks to <br>
    gfm: true,    // GitHub Flavored Markdown
});

// Render markdown memo
const renderMemo = (memo) => {
    if (!memo) return '';
    return marked.parse(memo);
};

const props = defineProps({
    goals: Array,
    coreGoals: Array,
    regularGoals: Array,
    goalsByCategory: Object,
    categories: Array,
    stats: Object,
    view: String,
    themeWords: Array,
    themeWordsEffect: String,
});

const currentView = computed(() => props.view || 'visionboard');

// Expanded state for core goals milestones
const expandedCoreGoals = ref({});
const selectedCategory = ref(null);
const drag = ref(false);

// Modal state
const showEditModal = ref(false);
const selectedGoalId = ref(null);

// Computed to get the latest goal data from props
const selectedGoal = computed(() => {
    if (!selectedGoalId.value) return null;
    return props.goals.find(g => g.id === selectedGoalId.value) || null;
});

// Theme Words Manager state
const showThemeWordsManager = ref(false);

// Theme Words radius (between center text and core goals)
const themeWordsRadius = computed(() => orbitRadius.value * 0.62);

// Calculate core goals count excluding selected goal
const modalCoreGoalsCount = computed(() => {
    if (!selectedGoal.value) return props.coreGoals?.length || 0;
    // If selected goal is already a core goal, don't count it
    if (selectedGoal.value.is_core_goal) {
        return (props.coreGoals?.length || 0) - 1;
    }
    return props.coreGoals?.length || 0;
});

const openGoalModal = (goal) => {
    selectedGoalId.value = goal.id;
    showEditModal.value = true;
};

const closeGoalModal = () => {
    showEditModal.value = false;
    selectedGoalId.value = null;
};

const onGoalSaved = () => {
    router.reload({ preserveScroll: true });
};

const onGoalDeleted = () => {
    router.reload({ preserveScroll: true });
};

// Responsive floating container size - full viewport
const floatingWidth = ref(900);
const floatingHeight = ref(700);
const orbitSize = ref(900); // For backwards compat
const orbitRadius = ref(300);

const updateOrbitSize = () => {
    const width = window.innerWidth;
    const height = window.innerHeight;

    // Use FULL viewport for floating area (minus navbar only)
    floatingWidth.value = width - 20;
    floatingHeight.value = height - 100; // Only navbar, no header in VisionBoard

    // orbitSize is the smaller dimension (for center area calculations)
    orbitSize.value = Math.min(floatingWidth.value, floatingHeight.value);

    if (width < 480) {
        orbitRadius.value = 110;
    } else if (width < 640) {
        orbitRadius.value = 150;
    } else if (width < 768) {
        orbitRadius.value = 180;
    } else if (width < 1024) {
        orbitRadius.value = 230;
    } else {
        orbitRadius.value = 300;
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

// Vietnamese tooltips for categories
const categoryTooltips = {
    'career-finance': 'Sự nghiệp, thu nhập, đầu tư, quản lý nợ',
    'health-fitness': 'Sức khỏe thể chất, tập luyện, dinh dưỡng',
    'relationships': 'Gia đình, bạn bè, tình yêu, mối quan hệ',
    'personal-growth': 'Học hỏi, kỹ năng, chứng chỉ, phát triển bản thân',
    'travel-experiences': 'Du lịch, phiêu lưu, trải nghiệm mới',
    'creativity-hobbies': 'Dự án sáng tạo, sở thích, side projects',
    'mindfulness-spirituality': 'Thiền định, biết ơn, sức khỏe tinh thần',
    'giving-back': 'Từ thiện, tình nguyện, đóng góp cộng đồng',
};

const getCategoryTooltip = (category) => {
    return categoryTooltips[category.slug] || category.description || '';
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

// Toggle milestone todo completion
const toggleMilestoneTodo = (goalId, milestone, todo) => {
    router.patch(route('milestone-todos.toggle', [goalId, milestone.id, todo.id]), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Expanded state for milestone todos
const expandedMilestoneTodos = ref({});

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
        <!-- Header only shown in Plan view -->
        <template v-if="currentView === 'plan'" #header>
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

        <div :class="currentView === 'visionboard' ? 'py-2' : 'py-6'">
            <div :class="currentView === 'visionboard' ? 'px-2' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'">
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
                        title="Hiển thị tất cả goals"
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
                        :title="getCategoryTooltip(category)"
                    >
                        {{ category.icon }} {{ category.name }}
                    </button>
                </div>

                <!-- VisionBoard View (3 Core Goals) -->
                <div v-if="currentView === 'visionboard'" class="relative overflow-hidden">
                    <!-- Floating Container - Full viewport width -->
                    <div
                        class="relative mx-auto transition-all duration-300"
                        :style="{ width: floatingWidth + 'px', height: floatingHeight + 'px' }"
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
                        <div class="absolute inset-0 flex items-center justify-center z-20 pointer-events-none">
                            <div class="text-center sparkle-container">
                                <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-400 dark:via-purple-400 dark:to-pink-400 leading-tight">
                                    VISION
                                </h1>
                                <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-400 dark:via-purple-400 dark:to-pink-400 leading-tight">
                                    BOARD
                                </h1>
                                <h2 class="text-4xl sm:text-5xl md:text-6xl font-black mt-2 sparkle-year">
                                    2026
                                </h2>
                                <!-- Sparkle stars -->
                                <span class="star star-1">✦</span>
                                <span class="star star-2">★</span>
                                <span class="star star-3">✦</span>
                                <span class="star star-4">★</span>
                                <span class="star star-5">✦</span>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-4 sm:mt-6 max-w-xs mx-auto italic">
                                    "Mỗi ngày đi một đoạn nhỏ, <br>đủ bền để đi xa, <br>đủ chậm để không bỏ quên mình."
                                </p>
                            </div>
                        </div>

                        <!-- Unified Floating (Goals + Theme Words Orbit) -->
                        <UnifiedFloating
                            v-if="themeWordsEffect === 'orbit' || filteredCoreGoals.length > 0"
                            :goals="filteredCoreGoals"
                            :words="themeWordsEffect === 'orbit' ? (themeWords || []) : []"
                            :container-width="floatingWidth"
                            :container-height="floatingHeight"
                            :is-mobile="floatingWidth < 500"
                            @goal-click="openGoalModal"
                        />

                        <!-- Theme Words Waterfall Effect (random 100 positive words from system) - renders above goals -->
                        <ThemeWordsWaterfall
                            v-if="themeWordsEffect === 'waterfall'"
                            :container-width="floatingWidth"
                            :container-height="floatingHeight"
                        />
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

                    <!-- Theme Words Manager Toggle Button -->
                    <button
                        @click="showThemeWordsManager = !showThemeWordsManager"
                        class="absolute bottom-4 right-4 p-3 bg-white dark:bg-gray-800 rounded-full shadow-lg hover:shadow-xl transition-all hover:scale-110 z-10"
                        :class="{ 'ring-2 ring-indigo-500': showThemeWordsManager }"
                        title="Theme Words"
                    >
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                        </svg>
                    </button>

                    <!-- Theme Words Manager Panel -->
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 translate-y-2"
                    >
                        <div
                            v-if="showThemeWordsManager"
                            class="absolute bottom-16 right-4 z-20"
                        >
                            <ThemeWordsManager
                                :words="themeWords || []"
                                :current-effect="themeWordsEffect || 'orbit'"
                                @close="showThemeWordsManager = false"
                            />
                        </div>
                    </Transition>
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
                                <!-- Goal Header -->
                                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div
                                            @click="openGoalModal(goal)"
                                            class="flex items-center gap-3 cursor-pointer flex-1"
                                            title="Click để edit goal"
                                        >
                                            <span class="text-2xl">{{ goal.category?.icon }}</span>
                                            <div>
                                                <h4 class="font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                                    {{ goal.title }}
                                                </h4>
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
                                            <button
                                                @click="toggleCoreGoal(goal.id)"
                                                class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                                                title="Xem milestones"
                                            >
                                                <svg
                                                    class="w-5 h-5 text-gray-400 transition-transform"
                                                    :class="{ 'rotate-180': expandedCoreGoals[goal.id] }"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </button>
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
                                            class="rounded-lg transition-colors"
                                            :class="milestone.is_soft ? 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' : 'hover:bg-white dark:hover:bg-gray-800'"
                                        >
                                            <div class="flex items-center gap-3 p-2">
                                                <button
                                                    @click.stop="toggleMilestone(goal.id, milestone)"
                                                    class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors flex-shrink-0"
                                                    :class="milestone.is_completed
                                                        ? 'bg-green-500 border-green-500 text-white'
                                                        : milestone.is_soft
                                                            ? 'border-amber-400 dark:border-amber-600 hover:border-amber-500'
                                                            : 'border-gray-300 dark:border-gray-600 hover:border-indigo-500'"
                                                >
                                                    <svg v-if="milestone.is_completed" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <span v-if="milestone.is_soft" class="text-amber-500" title="Soft milestone - nhắc nhở nhẹ, không tính vào progress">🔔</span>
                                                        <span
                                                            :class="milestone.is_completed
                                                                ? 'text-gray-400 line-through'
                                                                : milestone.is_soft
                                                                    ? 'text-amber-700 dark:text-amber-300'
                                                                    : 'text-gray-700 dark:text-gray-300'"
                                                        >
                                                            {{ milestone.title }}
                                                        </span>
                                                    </div>
                                                    <div v-if="milestone.memo" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                        <span class="mr-0.5">📝</span>
                                                        <span class="memo-content prose prose-xs prose-gray dark:prose-invert max-w-none inline line-clamp-2" v-html="renderMemo(milestone.memo)"></span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2 flex-shrink-0">
                                                    <!-- Todo count badge -->
                                                    <button
                                                        v-if="milestone.todos?.length"
                                                        @click.stop="expandedMilestoneTodos[milestone.id] = !expandedMilestoneTodos[milestone.id]"
                                                        class="text-xs px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900"
                                                        :title="`${milestone.todos.filter(t => t.is_completed).length}/${milestone.todos.length} todos`"
                                                    >
                                                        📋 {{ milestone.todos.filter(t => t.is_completed).length }}/{{ milestone.todos.length }}
                                                    </button>
                                                    <span
                                                        v-if="milestone.due_date"
                                                        class="text-xs text-gray-500 dark:text-gray-400"
                                                    >
                                                        {{ new Date(milestone.due_date).toLocaleDateString('ja-JP', { month: 'short', day: 'numeric' }) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Milestone Todos (expandable) -->
                                            <div
                                                v-if="milestone.todos?.length && expandedMilestoneTodos[milestone.id]"
                                                class="ml-8 pb-2 pr-2 space-y-1"
                                            >
                                                <div
                                                    v-for="todo in milestone.todos"
                                                    :key="todo.id"
                                                    class="flex items-center gap-2 text-sm p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700/50"
                                                >
                                                    <button
                                                        @click.stop="toggleMilestoneTodo(goal.id, milestone, todo)"
                                                        class="w-4 h-4 rounded border flex items-center justify-center transition-colors flex-shrink-0"
                                                        :class="todo.is_completed
                                                            ? 'bg-green-500 border-green-500 text-white'
                                                            : 'border-gray-300 dark:border-gray-500 hover:border-green-400'"
                                                    >
                                                        <svg v-if="todo.is_completed" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </button>
                                                    <span
                                                        class="flex-1"
                                                        :class="todo.is_completed ? 'text-gray-400 line-through' : 'text-gray-600 dark:text-gray-400'"
                                                    >
                                                        {{ todo.title }}
                                                    </span>
                                                    <span
                                                        v-if="todo.end_date"
                                                        class="text-xs text-gray-400"
                                                    >
                                                        → {{ new Date(todo.end_date).toLocaleDateString('ja-JP', { month: 'short', day: 'numeric' }) }}
                                                    </span>
                                                </div>
                                            </div>
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
                                    @click="openGoalModal"
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

        <!-- Goal Edit Modal -->
        <GoalEditModal
            :show="showEditModal"
            :goal="selectedGoal"
            :categories="categories"
            :core-goals-count="modalCoreGoalsCount"
            @close="closeGoalModal"
            @saved="onGoalSaved"
            @deleted="onGoalDeleted"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
/* Sparkle effect for center text */
.sparkle-container {
    position: relative;
}

.sparkle-year {
    background: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.star {
    position: absolute;
    color: #a5b4fc;
    opacity: 0;
    pointer-events: none;
    text-shadow: 0 0 8px rgba(167, 139, 250, 0.8);
}

.star-1 {
    top: 0%;
    left: 20%;
    font-size: 16px;
    animation: twinkle 2s ease-in-out infinite;
}

.star-2 {
    top: 20%;
    right: 5%;
    font-size: 12px;
    animation: twinkle 2.5s ease-in-out infinite 0.5s;
}

.star-3 {
    top: 55%;
    left: 0%;
    font-size: 10px;
    animation: twinkle 1.8s ease-in-out infinite 1s;
}

.star-4 {
    bottom: 25%;
    right: 10%;
    font-size: 14px;
    animation: twinkle 2.2s ease-in-out infinite 0.3s;
}

.star-5 {
    bottom: 5%;
    left: 30%;
    font-size: 8px;
    animation: twinkle 2s ease-in-out infinite 1.5s;
}

@keyframes twinkle {
    0%, 100% {
        opacity: 0;
        transform: scale(0.5) rotate(0deg);
    }
    50% {
        opacity: 1;
        transform: scale(1.2) rotate(180deg);
    }
}
</style>
