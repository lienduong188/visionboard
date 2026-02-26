<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GoalProgressChart from '@/Components/Charts/GoalProgressChart.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import draggable from 'vuedraggable';
import { marked } from 'marked';
import { formatNumber, formatForInput, parseFromInput, todayLocalStr, formatLocalDate } from '@/utils/formatNumber';

const props = defineProps({
    goal: Object,
});

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

// Sortable milestones
const sortableMilestones = ref([...(props.goal.milestones || [])]);

// Watch for goal changes
watch(() => props.goal.milestones, (newMilestones) => {
    sortableMilestones.value = [...(newMilestones || [])];
}, { deep: true });

// Handle milestone reorder
const onMilestoneReorder = () => {
    const order = sortableMilestones.value.map(m => m.id);
    router.post(route('milestones.reorder', props.goal.id), { order }, {
        preserveScroll: true,
    });
};

const showProgressModal = ref(false);
const progressForm = useForm({
    current_value: props.goal.current_value || 0,
    note: '',
});

// Display value for progress form
const displayProgressCurrentValue = ref(formatForInput(props.goal.current_value || 0));
const onProgressCurrentValueBlur = () => {
    progressForm.current_value = parseFromInput(displayProgressCurrentValue.value);
    displayProgressCurrentValue.value = formatForInput(progressForm.current_value);
};

const submitProgress = () => {
    progressForm.patch(route('goals.progress', props.goal.id), {
        onSuccess: () => {
            showProgressModal.value = false;
            progressForm.reset('note');
        },
    });
};

const togglePin = () => {
    router.patch(route('goals.pin', props.goal.id));
};

const deleteGoal = () => {
    if (confirm('Are you sure you want to delete this goal?')) {
        router.delete(route('goals.destroy', props.goal.id));
    }
};

// Milestone management
const showMilestoneModal = ref(false);
const editingMilestone = ref(null);
const milestoneForm = useForm({
    title: '',
    description: '',
    memo: '',
    target_value: '',
    due_date: '',
    is_soft: false,
    image: null,
    remove_image: false,
});
const milestoneImagePreview = ref(null);
const milestoneFileInput = ref(null);

// Display value for milestone form
const displayMilestoneTargetValue = ref('');
const onMilestoneTargetValueBlur = () => {
    milestoneForm.target_value = parseFromInput(displayMilestoneTargetValue.value);
    displayMilestoneTargetValue.value = formatForInput(milestoneForm.target_value);
};

// Expanded todos state
const expandedMilestoneTodos = ref({});

const openAddMilestone = () => {
    editingMilestone.value = null;
    milestoneForm.reset();
    milestoneImagePreview.value = null;
    displayMilestoneTargetValue.value = '';
    showMilestoneModal.value = true;
};

const openEditMilestone = (milestone) => {
    editingMilestone.value = milestone;
    milestoneForm.title = milestone.title;
    milestoneForm.description = milestone.description || '';
    milestoneForm.memo = milestone.memo || '';
    milestoneForm.target_value = milestone.target_value || '';
    displayMilestoneTargetValue.value = formatForInput(milestone.target_value);
    milestoneForm.due_date = milestone.due_date || '';
    milestoneForm.is_soft = milestone.is_soft || false;
    milestoneForm.image = null;
    milestoneForm.remove_image = false;
    milestoneImagePreview.value = milestone.image_url || null;
    showMilestoneModal.value = true;
};

const handleMilestoneImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        milestoneForm.image = file;
        milestoneForm.remove_image = false;
        milestoneImagePreview.value = URL.createObjectURL(file);
    }
};

const removeMilestoneImage = () => {
    milestoneForm.image = null;
    milestoneForm.remove_image = true;
    milestoneImagePreview.value = null;
    if (milestoneFileInput.value) {
        milestoneFileInput.value.value = '';
    }
};

const submitMilestone = () => {
    if (editingMilestone.value) {
        router.post(route('milestones.update', [props.goal.id, editingMilestone.value.id]), {
            _method: 'put',
            ...milestoneForm.data(),
        }, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                showMilestoneModal.value = false;
                milestoneForm.reset();
                milestoneImagePreview.value = null;
            },
        });
    } else {
        milestoneForm.post(route('milestones.store', props.goal.id), {
            onSuccess: () => {
                showMilestoneModal.value = false;
                milestoneForm.reset();
                milestoneImagePreview.value = null;
            },
        });
    }
};

// Toggle milestone soft status
const toggleMilestoneSoft = (milestone) => {
    router.patch(route('milestones.toggle-soft', [props.goal.id, milestone.id]), {}, {
        preserveScroll: true,
    });
};

const toggleMilestone = (milestone) => {
    router.patch(route('milestones.toggle', [props.goal.id, milestone.id]));
};

const deleteMilestone = (milestone) => {
    if (confirm('Delete this milestone?')) {
        router.delete(route('milestones.destroy', [props.goal.id, milestone.id]));
    }
};

// Milestone Todo management
const newTodoTitle = ref({});
const showAddTodo = ref({});

const addMilestoneTodo = (milestone) => {
    const title = newTodoTitle.value[milestone.id];
    if (!title?.trim()) return;

    router.post(route('milestone-todos.store', [props.goal.id, milestone.id]), {
        title: title.trim(),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newTodoTitle.value[milestone.id] = '';
            showAddTodo.value[milestone.id] = false;
        },
    });
};

const toggleMilestoneTodo = (milestone, todo) => {
    router.patch(route('milestone-todos.toggle', [props.goal.id, milestone.id, todo.id]), {}, {
        preserveScroll: true,
    });
};

const deleteMilestoneTodo = (milestone, todo) => {
    router.delete(route('milestone-todos.destroy', [props.goal.id, milestone.id, todo.id]), {
        preserveScroll: true,
    });
};

const progressColor = computed(() => {
    if (props.goal.progress >= 100) return 'bg-green-500';
    if (props.goal.progress >= 75) return 'bg-emerald-500';
    if (props.goal.progress >= 50) return 'bg-yellow-500';
    if (props.goal.progress >= 25) return 'bg-orange-500';
    return 'bg-red-500';
});

const statusBadge = computed(() => {
    const badges = {
        'not_started': { text: 'Not Started', class: 'bg-gray-500' },
        'in_progress': { text: 'In Progress', class: 'bg-blue-500' },
        'completed': { text: 'Completed', class: 'bg-green-500' },
        'paused': { text: 'Paused', class: 'bg-yellow-500' },
        'cancelled': { text: 'Cancelled', class: 'bg-red-500' },
    };
    return badges[props.goal.status] || badges['not_started'];
});

const formatDate = (date) => formatLocalDate(date, 'ja-JP', { year: 'numeric', month: 'long', day: 'numeric' });

// Expanded milestones state (toggle details)
const expandedMilestones = ref({});

const toggleMilestoneExpand = (milestoneId) => {
    expandedMilestones.value[milestoneId] = !expandedMilestones.value[milestoneId];
};

// Reminder management
const showReminderModal = ref(false);
const editingReminder = ref(null);
const reminderForm = useForm({
    type: 'progress',
    frequency: 'daily',
    custom_days: '',
    weekly_days: '',
    monthly_day: 1,
    remind_time: '09:00',
    message: '',
    is_active: true,
});

// Days of week for weekly frequency
const weekDays = [
    { value: 1, label: 'Mon', fullLabel: 'Monday' },
    { value: 2, label: 'Tue', fullLabel: 'Tuesday' },
    { value: 3, label: 'Wed', fullLabel: 'Wednesday' },
    { value: 4, label: 'Thu', fullLabel: 'Thursday' },
    { value: 5, label: 'Fri', fullLabel: 'Friday' },
    { value: 6, label: 'Sat', fullLabel: 'Saturday' },
    { value: 7, label: 'Sun', fullLabel: 'Sunday' },
];

// Selected week days (array of numbers)
const selectedWeekDays = ref([1]); // Default: Monday

// Toggle week day selection
const toggleWeekDay = (day) => {
    const idx = selectedWeekDays.value.indexOf(day);
    if (idx > -1) {
        if (selectedWeekDays.value.length > 1) {
            selectedWeekDays.value.splice(idx, 1);
        }
    } else {
        selectedWeekDays.value.push(day);
    }
    selectedWeekDays.value.sort((a, b) => a - b);
    reminderForm.weekly_days = selectedWeekDays.value.join(',');
};

const openAddReminder = () => {
    editingReminder.value = null;
    reminderForm.reset();
    reminderForm.type = 'progress';
    reminderForm.frequency = 'daily';
    reminderForm.remind_time = '09:00';
    reminderForm.weekly_days = '1';
    reminderForm.monthly_day = 1;
    selectedWeekDays.value = [1];
    showReminderModal.value = true;
};

const openEditReminder = (reminder) => {
    editingReminder.value = reminder;
    reminderForm.type = reminder.type;
    reminderForm.frequency = reminder.frequency;
    reminderForm.custom_days = reminder.custom_days || '';
    reminderForm.weekly_days = reminder.weekly_days || '1';
    reminderForm.monthly_day = reminder.monthly_day || 1;
    reminderForm.remind_time = reminder.remind_time?.slice(0, 5) || '09:00';
    reminderForm.message = reminder.message || '';
    reminderForm.is_active = reminder.is_active;
    // Parse weekly_days to array
    selectedWeekDays.value = reminder.weekly_days
        ? reminder.weekly_days.split(',').map(Number)
        : [1];
    showReminderModal.value = true;
};

const submitReminder = () => {
    if (editingReminder.value) {
        reminderForm.put(route('reminders.update', [props.goal.id, editingReminder.value.id]), {
            onSuccess: () => {
                showReminderModal.value = false;
                reminderForm.reset();
            },
        });
    } else {
        reminderForm.post(route('reminders.store', props.goal.id), {
            onSuccess: () => {
                showReminderModal.value = false;
                reminderForm.reset();
            },
        });
    }
};

const toggleReminder = (reminder) => {
    router.patch(route('reminders.toggle', [props.goal.id, reminder.id]));
};

const deleteReminder = (reminder) => {
    if (confirm('Delete this reminder?')) {
        router.delete(route('reminders.destroy', [props.goal.id, reminder.id]));
    }
};

const frequencyLabels = {
    daily: 'Daily',
    weekly: 'Weekly',
    monthly: 'Monthly',
    custom: 'Custom',
};

const typeLabels = {
    progress: 'Progress',
    deadline: 'Deadline',
    custom: 'Custom',
};

// Progress Log management
const showProgressLogModal = ref(false);
const editingProgressLog = ref(null);
const progressLogForm = useForm({
    new_value: '',
    logged_at: '',
    note: '',
});

// Display value for progress log form
const displayProgressLogNewValue = ref('');
const onProgressLogNewValueBlur = () => {
    progressLogForm.new_value = parseFromInput(displayProgressLogNewValue.value);
    displayProgressLogNewValue.value = formatForInput(progressLogForm.new_value);
};

const openAddProgressLog = () => {
    editingProgressLog.value = null;
    progressLogForm.reset();
    // Default to today
    progressLogForm.logged_at = todayLocalStr();
    progressLogForm.new_value = props.goal.current_value || 0;
    displayProgressLogNewValue.value = formatForInput(props.goal.current_value || 0);
    showProgressLogModal.value = true;
};

const openEditProgressLog = (log) => {
    editingProgressLog.value = log;
    progressLogForm.new_value = log.new_value;
    displayProgressLogNewValue.value = formatForInput(log.new_value);
    progressLogForm.logged_at = log.logged_at?.split('T')[0] || log.logged_at?.split(' ')[0] || '';
    progressLogForm.note = log.note || '';
    showProgressLogModal.value = true;
};

const submitProgressLog = () => {
    if (editingProgressLog.value) {
        progressLogForm.put(route('progress-logs.update', [props.goal.id, editingProgressLog.value.id]), {
            onSuccess: () => {
                showProgressLogModal.value = false;
                progressLogForm.reset();
            },
        });
    } else {
        progressLogForm.post(route('progress-logs.store', props.goal.id), {
            onSuccess: () => {
                showProgressLogModal.value = false;
                progressLogForm.reset();
            },
        });
    }
};

const deleteProgressLog = (log) => {
    if (confirm('Delete this progress log?')) {
        router.delete(route('progress-logs.destroy', [props.goal.id, log.id]));
    }
};

// Show all progress logs toggle
const showAllProgressLogs = ref(false);

// Format frequency display with details
const formatFrequency = (reminder) => {
    if (reminder.frequency === 'weekly' && reminder.weekly_days) {
        const dayNames = { 1: 'Mon', 2: 'Tue', 3: 'Wed', 4: 'Thu', 5: 'Fri', 6: 'Sat', 7: 'Sun' };
        const days = reminder.weekly_days.split(',').map(d => dayNames[d] || d).join(', ');
        return `Weekly (${days})`;
    }
    if (reminder.frequency === 'monthly' && reminder.monthly_day) {
        return `Day ${reminder.monthly_day} monthly`;
    }
    return frequencyLabels[reminder.frequency] || reminder.frequency;
};
</script>

<template>
    <Head :title="goal.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('goals.index', { view: 'plan' })"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        ← Back to Plan
                    </Link>
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ goal.title }}
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="togglePin"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        :title="goal.is_pinned ? 'Unpin' : 'Pin'"
                    >
                        {{ goal.is_pinned ? '📍' : '📌' }}
                    </button>
                    <Link
                        :href="route('goals.index', { view: 'plan', editGoal: goal.id })"
                        class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors"
                    >
                        Edit in Modal
                    </Link>
                    <button
                        @click="deleteGoal"
                        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Main Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                    <!-- Cover Image -->
                    <div
                        v-if="goal.cover_image_url"
                        class="h-64 bg-cover bg-center"
                        :style="{ backgroundImage: `url(${goal.cover_image_url})` }"
                    >
                        <div class="h-full w-full bg-gradient-to-t from-black/60 to-transparent flex items-end p-6">
                            <div>
                                <span
                                    class="px-3 py-1 rounded-full text-sm font-medium"
                                    :style="{
                                        backgroundColor: goal.category?.color,
                                        color: 'white',
                                    }"
                                >
                                    {{ goal.category?.icon }} {{ goal.category?.name }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="h-32 flex items-center justify-center text-8xl"
                        :style="{ backgroundColor: `${goal.category?.color}20` }"
                    >
                        {{ goal.category?.icon || '🎯' }}
                    </div>

                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-6">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <span
                                        v-if="!goal.cover_image"
                                        class="px-3 py-1 rounded-full text-sm font-medium"
                                        :style="{
                                            backgroundColor: `${goal.category?.color}20`,
                                            color: goal.category?.color,
                                        }"
                                    >
                                        {{ goal.category?.icon }} {{ goal.category?.name }}
                                    </span>
                                    <span
                                        class="px-3 py-1 rounded-full text-sm font-medium text-white"
                                        :class="statusBadge.class"
                                    >
                                        {{ statusBadge.text }}
                                    </span>
                                </div>
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                                    {{ goal.title }}
                                </h1>
                            </div>
                            <div class="text-right">
                                <div class="text-4xl font-bold" :class="goal.progress >= 100 ? 'text-green-500' : 'text-indigo-500'">
                                    {{ goal.progress }}%
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Complete</div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-6">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="progressColor"
                                    :style="{ width: `${goal.progress}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Value Progress -->
                        <div
                            v-if="goal.target_value && goal.unit"
                            class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-600 dark:text-gray-400">Progress</span>
                                <button
                                    @click="showProgressModal = true"
                                    class="px-3 py-1 bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-600 transition-colors"
                                >
                                    Update Progress
                                </button>
                            </div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatNumber(goal.current_value) }}
                                <span class="text-gray-500 dark:text-gray-400 font-normal">
                                    / {{ formatNumber(goal.target_value) }} {{ goal.unit }}
                                </span>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="goal.description" class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Description</h3>
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ goal.description }}</p>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div v-if="goal.start_date" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Start Date</div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ formatDate(goal.start_date) }}</div>
                            </div>
                            <div v-if="goal.target_date" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Target Date</div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ formatDate(goal.target_date) }}</div>
                            </div>
                        </div>

                        <!-- Milestones -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    📋 Milestones
                                    <span v-if="goal.milestones?.length" class="text-gray-500 dark:text-gray-400 font-normal">
                                        ({{ goal.milestones.filter(m => m.is_completed).length }}/{{ goal.milestones.length }})
                                    </span>
                                    <span class="text-sm font-normal text-gray-400 dark:text-gray-500 ml-2">(drag to reorder)</span>
                                </h3>
                                <button
                                    @click="openAddMilestone"
                                    class="px-3 py-1 bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-600 transition-colors flex items-center gap-1"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    Add
                                </button>
                            </div>
                            <draggable
                                v-if="sortableMilestones.length"
                                v-model="sortableMilestones"
                                item-key="id"
                                class="space-y-3"
                                handle=".drag-handle"
                                @end="onMilestoneReorder"
                            >
                                <template #item="{ element: milestone }">
                                <div
                                    class="rounded-lg group overflow-hidden"
                                    :class="milestone.is_soft
                                        ? 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800'
                                        : milestone.is_completed
                                            ? 'bg-green-50 dark:bg-green-900/20'
                                            : 'bg-gray-50 dark:bg-gray-700/50'"
                                >
                                    <!-- Milestone Header -->
                                    <div class="flex items-start gap-3 p-3">
                                        <!-- Drag Handle -->
                                        <div class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex-shrink-0 mt-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"></path>
                                            </svg>
                                        </div>
                                        <button
                                            @click="toggleMilestone(milestone)"
                                            class="w-6 h-6 flex items-center justify-center rounded-full text-sm transition-colors flex-shrink-0 mt-0.5"
                                            :class="milestone.is_completed
                                                ? 'bg-green-500 text-white hover:bg-green-600'
                                                : milestone.is_soft
                                                    ? 'bg-amber-400 text-white hover:bg-amber-500'
                                                    : 'bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-400 hover:bg-indigo-500 hover:text-white'"
                                        >
                                            {{ milestone.is_completed ? '✓' : milestone.is_soft ? '🔔' : milestone.sort_order }}
                                        </button>
                                        <div class="flex-1 min-w-0">
                                            <!-- Title row (always visible) - click to toggle -->
                                            <div
                                                class="flex items-center gap-2 cursor-pointer select-none"
                                                @click="toggleMilestoneExpand(milestone.id)"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-gray-400 transition-transform flex-shrink-0"
                                                    :class="{ 'rotate-90': expandedMilestones[milestone.id] }"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                </svg>
                                                <span
                                                    class="font-medium"
                                                    :class="milestone.is_completed
                                                        ? 'text-green-700 dark:text-green-400 line-through'
                                                        : milestone.is_soft
                                                            ? 'text-amber-700 dark:text-amber-300'
                                                            : 'text-gray-900 dark:text-white'"
                                                >
                                                    {{ milestone.title }}
                                                </span>
                                                <span v-if="milestone.is_soft" class="text-xs text-amber-600 dark:text-amber-400" title="Soft milestone - doesn't count toward progress">
                                                    (soft)
                                                </span>
                                                <!-- Show indicators for hidden content -->
                                                <span v-if="!expandedMilestones[milestone.id] && (milestone.description || milestone.memo || milestone.image_url || milestone.due_date)" class="text-xs text-gray-400">
                                                    <span v-if="milestone.memo">📝</span>
                                                    <span v-if="milestone.image_url">🖼️</span>
                                                    <span v-if="milestone.due_date">📅</span>
                                                </span>
                                            </div>
                                            <!-- Expandable details -->
                                            <div v-show="expandedMilestones[milestone.id]" class="mt-2 pl-6 space-y-1">
                                                <div v-if="milestone.description" class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ milestone.description }}
                                                </div>
                                                <div v-if="milestone.memo" class="text-sm text-indigo-600 dark:text-indigo-400">
                                                    <span class="mr-1">📝</span>
                                                    <span class="memo-content prose prose-sm prose-indigo dark:prose-invert max-w-none inline" v-html="renderMemo(milestone.memo)"></span>
                                                </div>
                                                <div v-if="milestone.due_date" class="text-sm text-gray-500 dark:text-gray-400">
                                                    📅 Due: {{ formatDate(milestone.due_date) }}
                                                </div>
                                                <!-- Milestone Image -->
                                                <img
                                                    v-if="milestone.image_url"
                                                    :src="milestone.image_url"
                                                    alt="Milestone image"
                                                    class="mt-2 rounded-lg max-h-32 object-cover"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button
                                                @click="toggleMilestoneSoft(milestone)"
                                                class="p-1.5 rounded transition-colors"
                                                :class="milestone.is_soft
                                                    ? 'text-amber-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30'
                                                    : 'text-gray-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/30'"
                                                :title="milestone.is_soft ? 'Make normal milestone' : 'Make soft milestone'"
                                            >
                                                🔔
                                            </button>
                                            <button
                                                @click="openEditMilestone(milestone)"
                                                class="p-1.5 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded transition-colors"
                                                title="Edit"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="deleteMilestone(milestone)"
                                                class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors"
                                                title="Delete"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Milestone Todos Section (only show when expanded) -->
                                    <div v-show="expandedMilestones[milestone.id]" class="border-t border-gray-200 dark:border-gray-700 ml-9 mr-3 mb-2">
                                        <!-- Todos Header -->
                                        <div class="flex items-center justify-between py-2">
                                            <button
                                                @click="expandedMilestoneTodos[milestone.id] = !expandedMilestoneTodos[milestone.id]"
                                                class="text-xs text-gray-500 dark:text-gray-400 hover:text-indigo-500 flex items-center gap-1"
                                            >
                                                <svg
                                                    class="w-3 h-3 transition-transform"
                                                    :class="{ 'rotate-90': expandedMilestoneTodos[milestone.id] }"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                </svg>
                                                Todos ({{ milestone.todos?.filter(t => t.is_completed).length || 0 }}/{{ milestone.todos?.length || 0 }})
                                            </button>
                                            <button
                                                @click="showAddTodo[milestone.id] = !showAddTodo[milestone.id]"
                                                class="text-xs text-indigo-500 hover:text-indigo-600"
                                            >
                                                + Add
                                            </button>
                                        </div>

                                        <!-- Add Todo Input -->
                                        <div v-if="showAddTodo[milestone.id]" class="flex items-center gap-2 pb-2">
                                            <input
                                                v-model="newTodoTitle[milestone.id]"
                                                type="text"
                                                class="flex-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded py-1 px-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Todo title..."
                                                @keyup.enter="addMilestoneTodo(milestone)"
                                            />
                                            <button
                                                @click="addMilestoneTodo(milestone)"
                                                class="text-xs px-2 py-1 bg-indigo-500 text-white rounded hover:bg-indigo-600"
                                            >
                                                Add
                                            </button>
                                        </div>

                                        <!-- Todos List -->
                                        <div v-if="expandedMilestoneTodos[milestone.id] && milestone.todos?.length" class="space-y-1 pb-2">
                                            <div
                                                v-for="todo in milestone.todos"
                                                :key="todo.id"
                                                class="flex items-center gap-2 text-sm py-1 px-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700/50 group/todo"
                                            >
                                                <button
                                                    @click="toggleMilestoneTodo(milestone, todo)"
                                                    class="w-4 h-4 rounded border flex items-center justify-center transition-colors flex-shrink-0"
                                                    :class="todo.is_completed
                                                        ? 'bg-green-500 border-green-500 text-white'
                                                        : 'border-gray-300 dark:border-gray-500 hover:border-green-400'"
                                                >
                                                    <svg v-if="todo.is_completed" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                                <span
                                                    class="flex-1"
                                                    :class="todo.is_completed ? 'text-gray-400 line-through' : 'text-gray-700 dark:text-gray-300'"
                                                >
                                                    {{ todo.title }}
                                                </span>
                                                <span v-if="todo.start_date || todo.end_date" class="text-xs text-gray-400">
                                                    {{ todo.start_date ? formatDate(todo.start_date).slice(5) : '' }}
                                                    {{ todo.start_date && todo.end_date ? '→' : '' }}
                                                    {{ todo.end_date ? formatDate(todo.end_date).slice(5) : '' }}
                                                </span>
                                                <button
                                                    @click="deleteMilestoneTodo(milestone, todo)"
                                                    class="text-red-400 hover:text-red-600 opacity-0 group-hover/todo:opacity-100 transition-opacity text-xs"
                                                >
                                                    ✕
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                </template>
                            </draggable>
                            <div v-if="!sortableMilestones.length" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No milestones yet. Add your first milestone!
                            </div>
                        </div>

                        <!-- Reminders -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    🔔 Reminders
                                    <span v-if="goal.reminders?.length" class="text-gray-500 dark:text-gray-400 font-normal">
                                        ({{ goal.reminders.filter(r => r.is_active).length }} active)
                                    </span>
                                </h3>
                                <button
                                    @click="openAddReminder"
                                    class="px-3 py-1 bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-600 transition-colors flex items-center gap-1"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    Add
                                </button>
                            </div>
                            <div v-if="goal.reminders?.length" class="space-y-3">
                                <div
                                    v-for="reminder in goal.reminders"
                                    :key="reminder.id"
                                    class="flex items-center gap-3 p-3 rounded-lg group"
                                    :class="reminder.is_active
                                        ? 'bg-indigo-50 dark:bg-indigo-900/20'
                                        : 'bg-gray-50 dark:bg-gray-700/50 opacity-60'"
                                >
                                    <button
                                        @click="toggleReminder(reminder)"
                                        class="w-8 h-8 flex items-center justify-center rounded-full text-lg transition-colors"
                                        :class="reminder.is_active
                                            ? 'bg-indigo-500 text-white hover:bg-indigo-600'
                                            : 'bg-gray-300 dark:bg-gray-600 text-gray-500 hover:bg-indigo-500 hover:text-white'"
                                        :title="reminder.is_active ? 'Disable' : 'Enable'"
                                    >
                                        {{ reminder.is_active ? '🔔' : '🔕' }}
                                    </button>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="px-2 py-0.5 text-xs rounded-full font-medium"
                                                :class="{
                                                    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': reminder.type === 'progress',
                                                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': reminder.type === 'deadline',
                                                    'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400': reminder.type === 'custom',
                                                }"
                                            >
                                                {{ typeLabels[reminder.type] }}
                                            </span>
                                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ formatFrequency(reminder) }} lúc {{ reminder.remind_time?.slice(0, 5) }}
                                            </span>
                                        </div>
                                        <div
                                            v-if="reminder.message"
                                            class="text-sm text-gray-500 dark:text-gray-400 truncate mt-1"
                                        >
                                            "{{ reminder.message }}"
                                        </div>
                                        <div
                                            v-if="reminder.next_send_at"
                                            class="text-xs text-gray-400 dark:text-gray-500 mt-1"
                                        >
                                            Next: {{ formatDate(reminder.next_send_at) }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button
                                            @click="openEditReminder(reminder)"
                                            class="p-1.5 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded transition-colors"
                                            title="Edit"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteReminder(reminder)"
                                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors"
                                            title="Delete"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No reminders yet. Add one to stay on track!
                            </div>
                        </div>

                        <!-- Progress Chart -->
                        <div class="mb-6">
                            <GoalProgressChart
                                :goal="goal"
                                :progress-logs="goal.progress_logs || []"
                            />
                        </div>

                        <!-- Progress History -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    📜 Progress History
                                    <span v-if="goal.progress_logs?.length" class="text-gray-500 dark:text-gray-400 font-normal">
                                        ({{ goal.progress_logs.length }} logs)
                                    </span>
                                </h3>
                                <button
                                    @click="openAddProgressLog"
                                    class="px-3 py-1 bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-600 transition-colors flex items-center gap-1"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    Add Log
                                </button>
                            </div>
                            <div v-if="goal.progress_logs?.length" class="space-y-2">
                                <div
                                    v-for="log in (showAllProgressLogs ? goal.progress_logs : goal.progress_logs.slice(0, 5))"
                                    :key="log.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg group"
                                >
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-white">
                                                {{ formatNumber(log.new_value) }} {{ goal.unit }}
                                            </span>
                                            <span class="text-gray-500 dark:text-gray-400">→</span>
                                            <span
                                                class="font-medium"
                                                :class="log.new_progress >= log.previous_progress ? 'text-green-500' : 'text-red-500'"
                                            >
                                                {{ log.new_progress }}%
                                            </span>
                                            <span
                                                v-if="log.new_progress > log.previous_progress"
                                                class="text-xs text-green-500"
                                            >
                                                (+{{ log.new_progress - log.previous_progress }}%)
                                            </span>
                                            <span
                                                v-else-if="log.new_progress < log.previous_progress"
                                                class="text-xs text-red-500"
                                            >
                                                ({{ log.new_progress - log.previous_progress }}%)
                                            </span>
                                        </div>
                                        <div v-if="log.note" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            📝 {{ log.note }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDate(log.logged_at) }}
                                        </div>
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button
                                                @click="openEditProgressLog(log)"
                                                class="p-1.5 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded transition-colors"
                                                title="Edit"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="deleteProgressLog(log)"
                                                class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors"
                                                title="Delete"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Show more/less button -->
                                <button
                                    v-if="goal.progress_logs.length > 5"
                                    @click="showAllProgressLogs = !showAllProgressLogs"
                                    class="w-full py-2 text-sm text-indigo-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                                >
                                    {{ showAllProgressLogs ? 'Show less' : `View all ${goal.progress_logs.length} logs` }}
                                </button>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No progress logs yet. Add your first log!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Update Modal -->
        <div
            v-if="showProgressModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showProgressModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md mx-4">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                    Update Progress
                </h3>
                <form @submit.prevent="submitProgress">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Current Value ({{ goal.unit }})
                        </label>
                        <input
                            v-model="displayProgressCurrentValue"
                            type="text"
                            inputmode="decimal"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            @blur="onProgressCurrentValueBlur"
                        />
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Note (optional)
                        </label>
                        <textarea
                            v-model="progressForm.note"
                            rows="3"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="Add a note about this update..."
                        ></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="showProgressModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="progressForm.processing"
                            class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors disabled:opacity-50"
                        >
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Milestone Modal -->
        <div
            v-if="showMilestoneModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showMilestoneModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                    {{ editingMilestone ? 'Edit Milestone' : 'Add Milestone' }}
                </h3>
                <form @submit.prevent="submitMilestone">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Title *
                        </label>
                        <input
                            v-model="milestoneForm.title"
                            type="text"
                            required
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="e.g., Complete first chapter"
                        />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Description
                        </label>
                        <textarea
                            v-model="milestoneForm.description"
                            rows="2"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="Short description..."
                        ></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            📝 Memo / Notes
                        </label>
                        <textarea
                            v-model="milestoneForm.memo"
                            rows="3"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="Detailed notes, ideas..."
                        ></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Target Value
                            </label>
                            <input
                                v-model="displayMilestoneTargetValue"
                                type="text"
                                inputmode="decimal"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                placeholder="Optional"
                                @blur="onMilestoneTargetValueBlur"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Due Date
                            </label>
                            <input
                                v-model="milestoneForm.due_date"
                                type="date"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>
                    <!-- Image Upload -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            🖼️ Image
                        </label>
                        <input
                            ref="milestoneFileInput"
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                            @change="handleMilestoneImageChange"
                            class="block w-full text-sm text-gray-500 dark:text-gray-400
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-lg file:border-0
                                file:text-sm file:font-semibold
                                file:bg-indigo-50 file:text-indigo-700
                                hover:file:bg-indigo-100
                                dark:file:bg-indigo-900 dark:file:text-indigo-300"
                        />
                        <div v-if="milestoneImagePreview" class="mt-2 relative">
                            <img
                                :src="milestoneImagePreview"
                                alt="Preview"
                                class="h-24 rounded-lg object-cover"
                            />
                            <button
                                type="button"
                                @click="removeMilestoneImage"
                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-0.5 hover:bg-red-600 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <!-- Soft Milestone Toggle -->
                    <div class="mb-6 p-3 rounded-lg" :class="milestoneForm.is_soft ? 'bg-amber-50 dark:bg-amber-900/20' : 'bg-gray-50 dark:bg-gray-700/50'">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                v-model="milestoneForm.is_soft"
                                type="checkbox"
                                class="mt-1 rounded text-amber-500 focus:ring-amber-500"
                            />
                            <div>
                                <span class="font-medium text-gray-900 dark:text-white">🔔 Soft Milestone</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5" title="Nhắc nhở nhẹ, không tính vào progress">
                                    This milestone won't count toward goal progress, just a daily reminder.
                                </p>
                            </div>
                        </label>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="showMilestoneModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="milestoneForm.processing"
                            class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors disabled:opacity-50"
                        >
                            {{ editingMilestone ? 'Update' : 'Add' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Progress Log Modal -->
        <div
            v-if="showProgressLogModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showProgressLogModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md mx-4">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                    {{ editingProgressLog ? 'Edit Progress Log' : 'Add Progress Log' }}
                </h3>
                <form @submit.prevent="submitProgressLog">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            📅 Date *
                        </label>
                        <input
                            v-model="progressLogForm.logged_at"
                            type="date"
                            required
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" title="Chọn ngày bạn muốn ghi nhận tiến độ">
                            Select the date for this progress entry (can be past date)
                        </p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            📊 Value ({{ goal.unit || 'unit' }}) *
                        </label>
                        <input
                            v-model="displayProgressLogNewValue"
                            type="text"
                            inputmode="decimal"
                            required
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            @blur="onProgressLogNewValueBlur"
                        />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Target: {{ formatNumber(goal.target_value) }} {{ goal.unit }}
                        </p>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            📝 Note
                        </label>
                        <textarea
                            v-model="progressLogForm.note"
                            rows="2"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="e.g., Ran 5km this morning..."
                        ></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="showProgressLogModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="progressLogForm.processing"
                            class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors disabled:opacity-50"
                        >
                            {{ editingProgressLog ? 'Update' : 'Add' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reminder Modal -->
        <div
            v-if="showReminderModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showReminderModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md mx-4">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                    {{ editingReminder ? 'Edit Reminder' : 'Add Reminder' }}
                </h3>
                <form @submit.prevent="submitReminder">
                    <!-- Reminder Type with tooltips -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Reminder Type *
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="reminderForm.type = 'progress'"
                                class="p-3 rounded-lg border-2 text-center transition-all"
                                :class="reminderForm.type === 'progress'
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-blue-300'"
                                title="Remind to update progress"
                            >
                                <div class="text-xl mb-1">📊</div>
                                <div class="text-xs font-medium text-gray-700 dark:text-gray-300">Progress</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Update progress</div>
                            </button>
                            <button
                                type="button"
                                @click="reminderForm.type = 'deadline'"
                                class="p-3 rounded-lg border-2 text-center transition-all"
                                :class="reminderForm.type === 'deadline'
                                    ? 'border-red-500 bg-red-50 dark:bg-red-900/30'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-red-300'"
                                title="Remind about upcoming deadline"
                            >
                                <div class="text-xl mb-1">⏰</div>
                                <div class="text-xs font-medium text-gray-700 dark:text-gray-300">Deadline</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Due date alert</div>
                            </button>
                            <button
                                type="button"
                                @click="reminderForm.type = 'custom'"
                                class="p-3 rounded-lg border-2 text-center transition-all"
                                :class="reminderForm.type === 'custom'
                                    ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-purple-300'"
                                title="Custom message"
                            >
                                <div class="text-xl mb-1">💬</div>
                                <div class="text-xs font-medium text-gray-700 dark:text-gray-300">Custom</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Custom message</div>
                            </button>
                        </div>
                    </div>

                    <!-- Frequency selection (like Google Calendar) -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Frequency *
                        </label>
                        <select
                            v-model="reminderForm.frequency"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>

                    <!-- Weekly: Select days of week -->
                    <div v-if="reminderForm.frequency === 'weekly'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Select days of week
                        </label>
                        <div class="flex gap-1">
                            <button
                                v-for="day in weekDays"
                                :key="day.value"
                                type="button"
                                @click="toggleWeekDay(day.value)"
                                class="w-10 h-10 rounded-full text-sm font-medium transition-all"
                                :class="selectedWeekDays.includes(day.value)
                                    ? 'bg-indigo-500 text-white'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/30'"
                                :title="day.fullLabel"
                            >
                                {{ day.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Monthly: Select day of month -->
                    <div v-if="reminderForm.frequency === 'monthly'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Day of month
                        </label>
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600 dark:text-gray-400">Day</span>
                            <select
                                v-model="reminderForm.monthly_day"
                                class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            >
                                <option v-for="d in 31" :key="d" :value="d">{{ d }}</option>
                            </select>
                            <span class="text-gray-600 dark:text-gray-400">monthly</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" title="Nếu tháng không có ngày này, sẽ nhắc vào ngày cuối tháng">
                            If the month doesn't have this day, will remind on the last day.
                        </p>
                    </div>

                    <!-- Custom days (legacy) -->
                    <div v-if="reminderForm.frequency === 'custom'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Custom days (1=Mon, 7=Sun)
                        </label>
                        <input
                            v-model="reminderForm.custom_days"
                            type="text"
                            placeholder="e.g., 1,3,5 for Mon, Wed, Fri"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Time -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Remind Time *
                        </label>
                        <input
                            v-model="reminderForm.remind_time"
                            type="time"
                            required
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Custom Message -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Custom Message
                        </label>
                        <textarea
                            v-model="reminderForm.message"
                            rows="2"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="Message to show in reminder email..."
                        ></textarea>
                    </div>

                    <!-- Active toggle (when editing) -->
                    <div v-if="editingReminder" class="mb-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="reminderForm.is_active"
                                type="checkbox"
                                class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="showReminderModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="reminderForm.processing"
                            class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors disabled:opacity-50"
                        >
                            {{ editingReminder ? 'Update' : 'Add' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
