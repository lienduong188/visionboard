<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GoalProgressChart from '@/Components/Charts/GoalProgressChart.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    goal: Object,
});

const showProgressModal = ref(false);
const progressForm = useForm({
    current_value: props.goal.current_value || 0,
    note: '',
});

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
    target_value: '',
    due_date: '',
});

const openAddMilestone = () => {
    editingMilestone.value = null;
    milestoneForm.reset();
    showMilestoneModal.value = true;
};

const openEditMilestone = (milestone) => {
    editingMilestone.value = milestone;
    milestoneForm.title = milestone.title;
    milestoneForm.description = milestone.description || '';
    milestoneForm.target_value = milestone.target_value || '';
    milestoneForm.due_date = milestone.due_date || '';
    showMilestoneModal.value = true;
};

const submitMilestone = () => {
    if (editingMilestone.value) {
        milestoneForm.put(route('milestones.update', [props.goal.id, editingMilestone.value.id]), {
            onSuccess: () => {
                showMilestoneModal.value = false;
                milestoneForm.reset();
            },
        });
    } else {
        milestoneForm.post(route('milestones.store', props.goal.id), {
            onSuccess: () => {
                showMilestoneModal.value = false;
                milestoneForm.reset();
            },
        });
    }
};

const toggleMilestone = (milestone) => {
    router.patch(route('milestones.toggle', [props.goal.id, milestone.id]));
};

const deleteMilestone = (milestone) => {
    if (confirm('Delete this milestone?')) {
        router.delete(route('milestones.destroy', [props.goal.id, milestone.id]));
    }
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

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <Head :title="goal.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('goals.index')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        ← Back
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
                        :href="route('goals.edit', goal.id)"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                    >
                        Edit
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
                        v-if="goal.cover_image"
                        class="h-64 bg-cover bg-center"
                        :style="{ backgroundImage: `url(${goal.cover_image})` }"
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
                                {{ goal.current_value?.toLocaleString() || 0 }}
                                <span class="text-gray-500 dark:text-gray-400 font-normal">
                                    / {{ goal.target_value?.toLocaleString() }} {{ goal.unit }}
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
                            <div v-if="goal.milestones?.length" class="space-y-3">
                                <div
                                    v-for="milestone in goal.milestones"
                                    :key="milestone.id"
                                    class="flex items-center gap-3 p-3 rounded-lg group"
                                    :class="milestone.is_completed
                                        ? 'bg-green-50 dark:bg-green-900/20'
                                        : 'bg-gray-50 dark:bg-gray-700/50'"
                                >
                                    <button
                                        @click="toggleMilestone(milestone)"
                                        class="w-6 h-6 flex items-center justify-center rounded-full text-sm transition-colors"
                                        :class="milestone.is_completed
                                            ? 'bg-green-500 text-white hover:bg-green-600'
                                            : 'bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-400 hover:bg-indigo-500 hover:text-white'"
                                    >
                                        {{ milestone.is_completed ? '✓' : milestone.sort_order }}
                                    </button>
                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="font-medium"
                                            :class="milestone.is_completed
                                                ? 'text-green-700 dark:text-green-400 line-through'
                                                : 'text-gray-900 dark:text-white'"
                                        >
                                            {{ milestone.title }}
                                        </div>
                                        <div
                                            v-if="milestone.description"
                                            class="text-sm text-gray-500 dark:text-gray-400 truncate"
                                        >
                                            {{ milestone.description }}
                                        </div>
                                        <div
                                            v-if="milestone.due_date"
                                            class="text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            Due: {{ formatDate(milestone.due_date) }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
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
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No milestones yet. Add your first milestone!
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
                        <div v-if="goal.progress_logs?.length" class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                📜 Progress History
                            </h3>
                            <div class="space-y-2">
                                <div
                                    v-for="log in goal.progress_logs.slice(0, 5)"
                                    :key="log.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                                >
                                    <div>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ log.new_value?.toLocaleString() }} {{ goal.unit }}
                                        </span>
                                        <span class="text-gray-500 dark:text-gray-400 mx-2">→</span>
                                        <span
                                            class="font-medium"
                                            :class="log.new_progress >= log.previous_progress ? 'text-green-500' : 'text-red-500'"
                                        >
                                            {{ log.new_progress }}%
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(log.logged_at) }}
                                    </div>
                                </div>
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
                            v-model="progressForm.current_value"
                            type="number"
                            step="any"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
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
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md mx-4">
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
                            placeholder="Optional description..."
                        ></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Target Value
                            </label>
                            <input
                                v-model="milestoneForm.target_value"
                                type="number"
                                step="any"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                placeholder="Optional"
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
    </AuthenticatedLayout>
</template>
