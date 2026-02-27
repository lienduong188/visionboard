<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { formatNumber, formatLocalDate } from '@/utils/formatNumber';
import { marked } from 'marked';
import GoalProgressChart from '@/Components/Charts/GoalProgressChart.vue';

// Markdown config
marked.setOptions({ breaks: true, gfm: true });

const props = defineProps({
    show: Boolean,
    goal: Object,
});

const emit = defineEmits(['close', 'edit', 'deleted']);

const activeTab = ref('info');

// Reset tab when modal opens
watch(() => props.goal?.id, () => {
    activeTab.value = 'info';
});

const close = () => emit('close');
const openEdit = () => emit('edit');

// Handle ESC key
const handleKeydown = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});

const progressColor = computed(() => {
    if (!props.goal) return 'bg-gray-300';
    if (props.goal.progress >= 100) return 'bg-green-500';
    if (props.goal.progress >= 75) return 'bg-emerald-500';
    if (props.goal.progress >= 50) return 'bg-yellow-500';
    if (props.goal.progress >= 25) return 'bg-orange-500';
    return 'bg-red-500';
});

const statusBadge = computed(() => {
    const badges = {
        'not_started': { text: 'Not Started', class: 'bg-gray-500', icon: '📋' },
        'in_progress': { text: 'In Progress', class: 'bg-blue-500', icon: '🚀' },
        'completed': { text: 'Completed', class: 'bg-green-500', icon: '✅' },
        'paused': { text: 'Paused', class: 'bg-yellow-500', icon: '⏸️' },
        'cancelled': { text: 'Cancelled', class: 'bg-red-500', icon: '❌' },
    };
    return badges[props.goal?.status] || badges['not_started'];
});

const priorityBadge = computed(() => {
    const badges = {
        'high': { text: 'High', class: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300', icon: '🔥' },
        'medium': { text: 'Medium', class: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300', icon: '⭐' },
        'low': { text: 'Low', class: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300', icon: '📌' },
    };
    return badges[props.goal?.priority] || badges['medium'];
});

const formatDate = (date) => date ? formatLocalDate(date) : '—';

const formatDateTime = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const daysRemaining = computed(() => {
    if (!props.goal?.target_date) return null;
    const target = new Date(props.goal.target_date);
    const today = new Date();
    return Math.ceil((target - today) / (1000 * 60 * 60 * 24));
});

const completedMilestones = computed(() => {
    if (!props.goal?.milestones) return 0;
    return props.goal.milestones.filter(m => m.is_completed && !m.is_soft).length;
});

const totalMilestones = computed(() => {
    if (!props.goal?.milestones) return 0;
    return props.goal.milestones.filter(m => !m.is_soft).length;
});

const activeRemindersCount = computed(() => {
    return props.goal?.reminders?.filter(r => r.is_active).length || 0;
});

const renderMemo = (memo) => {
    if (!memo) return '';
    return marked.parse(memo);
};

// Tab class helper
const tabClass = (tab) => {
    const base = 'shrink-0 px-4 sm:px-5 py-3 min-h-[52px] text-sm font-semibold border-b-2 transition-colors whitespace-nowrap text-center flex items-center justify-center gap-1';
    if (activeTab.value === tab) {
        return `${base} border-indigo-500 text-indigo-600 dark:text-indigo-400`;
    }
    return `${base} border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300`;
};

// Frequency text helper
const getFrequencyText = (reminder) => {
    switch (reminder.frequency) {
        case 'daily': return 'Daily';
        case 'weekly':
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const selectedDays = (reminder.days_of_week || []).map(d => days[d]).join(', ');
            return `Weekly (${selectedDays || 'Not set'})`;
        case 'monthly':
            return `Monthly (Day ${reminder.day_of_month || 1})`;
        case 'specific_dates':
            const dates = reminder.specific_dates || [];
            return `${dates.length} specific date(s)`;
        default:
            return reminder.frequency;
    }
};

// Reference type icon
const getReferenceIcon = (type) => {
    switch (type) {
        case 'link': return '🔗';
        case 'file': return '📎';
        case 'text': return '📝';
        default: return '📄';
    }
};

// Check if file is an image
const isImageFile = (fileName) => {
    if (!fileName) return false;
    const ext = fileName.split('.').pop()?.toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext);
};

const deleteGoal = () => {
    if (!props.goal) return;
    if (confirm('Are you sure you want to delete this goal? This action cannot be undone.')) {
        router.delete(route('goals.destroy', props.goal.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit('deleted');
                close();
            },
        });
    }
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show && goal"
                class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                    @click="close"
                ></div>

                <!-- Modal -->
                <div class="relative w-full max-w-3xl max-h-[90vh] overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-2xl flex flex-col">
                    <!-- Cover Image / Header -->
                    <div
                        class="relative h-40 bg-cover flex-shrink-0"
                        :style="goal.cover_image_url
                            ? { backgroundImage: `url(${goal.cover_image_url})`, backgroundPosition: goal.cover_image_position || '50% 50%' }
                            : { backgroundColor: `${goal.category?.color}30` }"
                    >
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>

                        <!-- Close Button -->
                        <button
                            @click="close"
                            class="absolute top-3 right-3 p-2 bg-white/20 hover:bg-white/30 backdrop-blur rounded-full text-white transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>

                        <!-- Category & Core Badge -->
                        <div class="absolute top-3 left-3 flex items-center gap-2">
                            <span
                                v-if="goal.category"
                                class="px-2 py-1 text-xs font-medium rounded-full bg-white/20 backdrop-blur text-white"
                            >
                                {{ goal.category.icon }} {{ goal.category.name }}
                            </span>
                            <span
                                v-if="goal.is_core_goal"
                                class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-500/80 backdrop-blur text-white"
                            >
                                ⭐ Core
                            </span>
                        </div>

                        <!-- Title on Cover -->
                        <div class="absolute bottom-3 left-4 right-4">
                            <div v-if="goal.is_pinned" class="text-yellow-400 text-xs mb-1">📍 Pinned</div>
                            <h2 class="text-xl font-bold text-white line-clamp-2">
                                {{ goal.title }}
                            </h2>
                            <p v-if="goal.slogan" class="text-white/80 text-sm italic mt-1 line-clamp-1">
                                "{{ goal.slogan }}"
                            </p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900/50 flex-shrink-0">
                        <div class="flex justify-between text-sm mb-1">
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full text-white"
                                    :class="statusBadge.class"
                                >
                                    {{ statusBadge.text }}
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full"
                                    :class="priorityBadge.class"
                                >
                                    {{ priorityBadge.icon }} {{ priorityBadge.text }}
                                </span>
                            </div>
                            <span class="font-bold" :class="goal.progress >= 100 ? 'text-green-500' : 'text-gray-900 dark:text-white'">
                                {{ goal.progress }}%
                            </span>
                        </div>
                        <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="progressColor"
                                :style="{ width: `${goal.progress}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="flex overflow-x-auto border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex-shrink-0 scrollbar-hide">
                        <button @click="activeTab = 'info'" :class="tabClass('info')">
                            📋 Info
                        </button>
                        <button @click="activeTab = 'milestones'" :class="tabClass('milestones')">
                            🎯 <span class="sm:hidden">Miles.</span><span class="hidden sm:inline">Milestones</span>
                            <span v-if="goal.milestones?.length" class="ml-1 text-xs opacity-70">
                                ({{ completedMilestones }}/{{ totalMilestones }})
                            </span>
                        </button>
                        <button @click="activeTab = 'reminders'" :class="tabClass('reminders')">
                            🔔 <span class="sm:hidden">Remind</span><span class="hidden sm:inline">Reminders</span>
                            <span v-if="activeRemindersCount" class="ml-1 text-xs text-green-500">
                                ({{ activeRemindersCount }})
                            </span>
                        </button>
                        <button @click="activeTab = 'refs'" :class="tabClass('refs')">
                            📚 Refs
                            <span v-if="goal.references?.length" class="ml-1 text-xs opacity-70">
                                ({{ goal.references.length }})
                            </span>
                        </button>
                        <button @click="activeTab = 'history'" :class="tabClass('history')">
                            📈 <span class="sm:hidden">Hist.</span><span class="hidden sm:inline">History</span>
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="flex-1 overflow-y-auto p-4">
                        <!-- INFO TAB -->
                        <div v-show="activeTab === 'info'" class="space-y-4">
                            <!-- Value Progress -->
                            <div v-if="goal.target_value && goal.unit" class="bg-indigo-50 dark:bg-indigo-900/30 rounded-lg p-3 text-center">
                                <span class="text-lg font-semibold text-indigo-600 dark:text-indigo-400">
                                    {{ formatNumber(goal.current_value || 0) }}
                                </span>
                                <span class="text-gray-500 dark:text-gray-400">
                                    / {{ formatNumber(goal.target_value) }} {{ goal.unit }}
                                </span>
                            </div>

                            <!-- Description -->
                            <div v-if="goal.description">
                                <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Description</h3>
                                <div
                                    class="text-gray-700 dark:text-gray-300 text-sm prose prose-sm prose-gray dark:prose-invert max-w-none"
                                    v-html="renderMemo(goal.description)"
                                ></div>
                            </div>

                            <!-- Dates -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Start Date</div>
                                    <div class="font-medium text-sm text-gray-900 dark:text-white">{{ formatDate(goal.start_date) }}</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Target Date</div>
                                    <div class="font-medium text-sm text-gray-900 dark:text-white">{{ formatDate(goal.target_date) }}</div>
                                    <div v-if="daysRemaining !== null" class="text-xs mt-1">
                                        <span v-if="daysRemaining < 0" class="text-red-500 font-medium">
                                            {{ Math.abs(daysRemaining) }} days overdue
                                        </span>
                                        <span v-else-if="daysRemaining === 0" class="text-yellow-500 font-medium">
                                            Due today!
                                        </span>
                                        <span v-else-if="daysRemaining <= 7" class="text-orange-500 font-medium">
                                            {{ daysRemaining }} days left
                                        </span>
                                        <span v-else class="text-green-500">
                                            {{ daysRemaining }} days left
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="grid grid-cols-4 gap-2">
                                <div class="text-center p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                                    <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ goal.milestones?.length || 0 }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Milestones</div>
                                </div>
                                <div class="text-center p-2 bg-amber-50 dark:bg-amber-900/30 rounded-lg">
                                    <div class="text-lg font-bold text-amber-600 dark:text-amber-400">
                                        {{ activeRemindersCount }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Reminders</div>
                                </div>
                                <div class="text-center p-2 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                                    <div class="text-lg font-bold text-purple-600 dark:text-purple-400">
                                        {{ goal.references?.length || 0 }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">References</div>
                                </div>
                                <div class="text-center p-2 bg-green-50 dark:bg-green-900/30 rounded-lg">
                                    <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                        {{ goal.progress_logs?.length || 0 }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Logs</div>
                                </div>
                            </div>
                        </div>

                        <!-- MILESTONES TAB -->
                        <div v-show="activeTab === 'milestones'">
                            <div v-if="goal.milestones?.length" class="space-y-2">
                                <div
                                    v-for="milestone in goal.milestones"
                                    :key="milestone.id"
                                    class="rounded-lg p-3"
                                    :class="milestone.is_soft ? 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' : 'bg-gray-50 dark:bg-gray-700/50'"
                                >
                                    <div class="flex items-start gap-3">
                                        <span
                                            class="flex-shrink-0 mt-0.5"
                                            :class="milestone.is_completed ? 'text-green-500' : milestone.is_soft ? 'text-amber-500' : 'text-gray-400'"
                                        >
                                            {{ milestone.is_completed ? '✓' : milestone.is_soft ? '🔔' : '○' }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="font-medium text-sm"
                                                    :class="milestone.is_completed ? 'text-gray-400 line-through' : 'text-gray-900 dark:text-white'"
                                                >
                                                    {{ milestone.title }}
                                                </span>
                                                <span v-if="milestone.is_soft" class="text-xs text-amber-600 dark:text-amber-400">(soft)</span>
                                            </div>
                                            <p v-if="milestone.description" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ milestone.description }}
                                            </p>
                                            <div
                                                v-if="milestone.memo"
                                                class="text-xs text-gray-600 dark:text-gray-400 mt-1 prose prose-xs prose-gray dark:prose-invert max-w-none"
                                                v-html="renderMemo(milestone.memo)"
                                            ></div>
                                            <!-- Milestone Todos -->
                                            <div v-if="milestone.todos?.length" class="mt-2 pl-2 border-l-2 border-gray-200 dark:border-gray-600 space-y-1">
                                                <div
                                                    v-for="todo in milestone.todos"
                                                    :key="todo.id"
                                                    class="flex items-center gap-2 text-xs"
                                                >
                                                    <span :class="todo.is_completed ? 'text-green-500' : 'text-gray-400'">
                                                        {{ todo.is_completed ? '✓' : '○' }}
                                                    </span>
                                                    <span :class="todo.is_completed ? 'text-gray-400 line-through' : 'text-gray-600 dark:text-gray-400'">
                                                        {{ todo.title }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <span v-if="milestone.due_date" class="text-xs text-gray-400 flex-shrink-0">
                                            {{ formatDate(milestone.due_date) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <div class="text-3xl mb-2">🎯</div>
                                <p class="text-sm">No milestones yet</p>
                            </div>
                        </div>

                        <!-- REMINDERS TAB -->
                        <div v-show="activeTab === 'reminders'">
                            <div v-if="goal.reminders?.length" class="space-y-2">
                                <div
                                    v-for="reminder in goal.reminders"
                                    :key="reminder.id"
                                    class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3"
                                    :class="{ 'opacity-50': !reminder.is_active }"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ reminder.title || 'Reminder' }}
                                                </span>
                                                <span
                                                    class="px-1.5 py-0.5 text-xs rounded"
                                                    :class="reminder.is_active
                                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400'
                                                        : 'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-400'"
                                                >
                                                    {{ reminder.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 space-y-0.5">
                                                <div>📅 {{ getFrequencyText(reminder) }}</div>
                                                <div v-if="reminder.reminder_time">⏰ {{ reminder.reminder_time }}</div>
                                                <div v-if="reminder.type">
                                                    📌 {{ reminder.type === 'progress' ? 'Progress Update' : reminder.type === 'deadline' ? 'Deadline' : 'Custom' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <div class="text-3xl mb-2">🔔</div>
                                <p class="text-sm">No reminders set</p>
                            </div>
                        </div>

                        <!-- REFERENCES TAB -->
                        <div v-show="activeTab === 'refs'">
                            <div v-if="goal.references?.length" class="space-y-3">
                                <!-- Links -->
                                <template v-for="ref in goal.references" :key="ref.id">
                                    <!-- Link Type -->
                                    <div
                                        v-if="ref.type === 'link'"
                                        class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="text-lg flex-shrink-0">🔗</span>
                                            <div class="flex-1 min-w-0">
                                                <a
                                                    :href="ref.url"
                                                    target="_blank"
                                                    class="font-medium text-sm text-indigo-600 dark:text-indigo-400 hover:underline block"
                                                >
                                                    {{ ref.title || ref.url }}
                                                </a>
                                                <div v-if="ref.title && ref.url" class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">
                                                    {{ ref.url }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- File Type - Image Preview -->
                                    <div
                                        v-else-if="ref.type === 'file' && isImageFile(ref.file_name)"
                                        class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3"
                                    >
                                        <div v-if="ref.title" class="font-medium text-sm text-gray-900 dark:text-white mb-2">
                                            {{ ref.title }}
                                        </div>
                                        <a :href="`/storage/${ref.file_path}`" target="_blank" class="block">
                                            <img
                                                :src="`/storage/${ref.file_path}`"
                                                :alt="ref.title || ref.file_name"
                                                class="max-w-full max-h-64 rounded-lg object-contain mx-auto hover:opacity-90 transition-opacity"
                                            />
                                        </a>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">
                                            {{ ref.file_name }} ({{ Math.round((ref.file_size || 0) / 1024) }}KB)
                                        </div>
                                    </div>

                                    <!-- File Type - Non-Image -->
                                    <div
                                        v-else-if="ref.type === 'file'"
                                        class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="text-lg flex-shrink-0">📎</span>
                                            <div class="flex-1 min-w-0">
                                                <a
                                                    :href="`/storage/${ref.file_path}`"
                                                    target="_blank"
                                                    class="font-medium text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                                                >
                                                    {{ ref.title || ref.file_name }}
                                                </a>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ ref.file_name }} ({{ Math.round((ref.file_size || 0) / 1024) }}KB)
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Text/Note Type - Markdown -->
                                    <div
                                        v-else-if="ref.type === 'text'"
                                        class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="text-lg flex-shrink-0">📝</span>
                                            <div class="flex-1 min-w-0">
                                                <div v-if="ref.title" class="font-medium text-sm text-gray-900 dark:text-white mb-2">
                                                    {{ ref.title }}
                                                </div>
                                                <div
                                                    v-if="ref.content"
                                                    class="prose prose-sm prose-gray dark:prose-invert max-w-none"
                                                    v-html="renderMemo(ref.content)"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <div class="text-3xl mb-2">📚</div>
                                <p class="text-sm">No references added</p>
                            </div>
                        </div>

                        <!-- HISTORY TAB -->
                        <div v-show="activeTab === 'history'">
                            <!-- Progress Chart -->
                            <div class="mb-4">
                                <GoalProgressChart
                                    :goal="goal"
                                    :progress-logs="goal.progress_logs || []"
                                />
                            </div>

                            <!-- Logs list -->
                            <div v-if="goal.progress_logs?.length" class="space-y-2">
                                <div
                                    v-for="log in goal.progress_logs.slice().sort((a,b) => new Date(b.logged_at||b.created_at) - new Date(a.logged_at||a.created_at)).slice(0, 20)"
                                    :key="log.id"
                                    class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg font-semibold text-indigo-600 dark:text-indigo-400">
                                                {{ formatNumber(log.new_value) }}
                                            </span>
                                            <span v-if="goal.unit" class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ goal.unit }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-400">
                                            {{ formatDateTime(log.logged_at || log.created_at) }}
                                        </span>
                                    </div>
                                    <p v-if="log.note" class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        {{ log.note }}
                                    </p>
                                </div>
                                <div v-if="goal.progress_logs.length > 20" class="text-center text-xs text-gray-500 dark:text-gray-400 py-2">
                                    Showing latest 20 of {{ goal.progress_logs.length }} logs
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <div class="text-3xl mb-2">📈</div>
                                <p class="text-sm">No progress logs yet</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-between gap-3 px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
                        <button
                            @click="deleteGoal"
                            class="px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                        >
                            🗑️ Delete
                        </button>
                        <div class="flex items-center gap-2">
                            <button
                                @click="close"
                                class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            >
                                Close
                            </button>
                            <button
                                @click="openEdit"
                                class="px-4 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors"
                            >
                                ✏️ Edit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
