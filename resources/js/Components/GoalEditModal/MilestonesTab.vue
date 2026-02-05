<script setup>
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import { marked } from 'marked';
import { formatForInput, parseFromInput } from '@/utils/formatNumber';

const props = defineProps({
    goal: Object,
});

// Markdown parser configuration
marked.setOptions({
    breaks: true,
    gfm: true,
});

const renderMemo = (memo) => {
    if (!memo) return '';
    return marked.parse(memo);
};

// Sortable milestones
const sortableMilestones = ref([...(props.goal?.milestones || [])]);

// Watch for goal changes
watch(() => props.goal?.milestones, (newMilestones) => {
    sortableMilestones.value = [...(newMilestones || [])];
}, { deep: true });

// Handle milestone reorder
const onMilestoneReorder = () => {
    const order = sortableMilestones.value.map(m => m.id);
    router.post(route('milestones.reorder', props.goal.id), { order }, {
        preserveScroll: true,
    });
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

// Display value for formatted number input
const displayTargetValue = ref('');
const onTargetValueBlur = () => {
    milestoneForm.target_value = parseFromInput(displayTargetValue.value);
    displayTargetValue.value = formatForInput(milestoneForm.target_value);
};

// Expanded states
const expandedMilestones = ref({});
const expandedMilestoneTodos = ref({});

const toggleMilestoneExpand = (milestoneId) => {
    expandedMilestones.value[milestoneId] = !expandedMilestones.value[milestoneId];
};

const openAddMilestone = () => {
    editingMilestone.value = null;
    milestoneForm.reset();
    milestoneImagePreview.value = null;
    displayTargetValue.value = '';
    showMilestoneModal.value = true;
};

const openEditMilestone = (milestone) => {
    editingMilestone.value = milestone;
    milestoneForm.title = milestone.title;
    milestoneForm.description = milestone.description || '';
    milestoneForm.memo = milestone.memo || '';
    milestoneForm.target_value = milestone.target_value || '';
    displayTargetValue.value = formatForInput(milestone.target_value);
    // Format due_date to YYYY-MM-DD for input type="date"
    milestoneForm.due_date = milestone.due_date ? milestone.due_date.substring(0, 10) : '';
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

const toggleMilestone = (milestone) => {
    router.patch(route('milestones.toggle', [props.goal.id, milestone.id]), {}, {
        preserveScroll: true,
    });
};

const toggleMilestoneSoft = (milestone) => {
    router.patch(route('milestones.toggle-soft', [props.goal.id, milestone.id]), {}, {
        preserveScroll: true,
    });
};

const deleteMilestone = (milestone) => {
    if (confirm('Delete this milestone?')) {
        router.delete(route('milestones.destroy', [props.goal.id, milestone.id]), {
            preserveScroll: true,
        });
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
    <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Milestones
                <span v-if="goal.milestones?.length" class="text-gray-500 dark:text-gray-400 font-normal">
                    ({{ goal.milestones.filter(m => m.is_completed).length }}/{{ goal.milestones.length }})
                </span>
                <span class="text-sm font-normal text-gray-400 dark:text-gray-500 ml-2">(drag to reorder)</span>
            </h3>
            <button
                @click="openAddMilestone"
                class="px-3 py-1.5 bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-600 transition-colors flex items-center gap-1"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Add
            </button>
        </div>

        <!-- Milestones List -->
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
                            {{ milestone.is_completed ? '✓' : milestone.is_soft ? '!' : milestone.sort_order }}
                        </button>
                        <div class="flex-1 min-w-0">
                            <!-- Title row -->
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
                                <span v-if="milestone.is_soft" class="text-xs text-amber-600 dark:text-amber-400">
                                    (soft)
                                </span>
                                <!-- Indicators -->
                                <span v-if="!expandedMilestones[milestone.id] && milestone.image_url" class="text-xs text-gray-400">
                                    img
                                </span>
                            </div>
                            <!-- Expandable details -->
                            <div v-show="expandedMilestones[milestone.id]" class="mt-2 pl-6 space-y-1">
                                <div v-if="milestone.description" class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ milestone.description }}
                                </div>
                                <div v-if="milestone.memo" class="text-sm text-indigo-600 dark:text-indigo-400">
                                    <span class="mr-1">Memo:</span>
                                    <span class="memo-content prose prose-sm prose-indigo dark:prose-invert max-w-none inline" v-html="renderMemo(milestone.memo)"></span>
                                </div>
                                <div v-if="milestone.due_date" class="text-sm text-gray-500 dark:text-gray-400">
                                    Due: {{ formatDate(milestone.due_date) }}
                                </div>
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
                                :title="milestone.is_soft ? 'Make normal' : 'Make soft'"
                            >
                                !
                            </button>
                            <button
                                @click="openEditMilestone(milestone)"
                                class="p-1.5 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                            </button>
                            <button
                                @click="deleteMilestone(milestone)"
                                class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Todos Section -->
                    <div v-show="expandedMilestones[milestone.id]" class="border-t border-gray-200 dark:border-gray-700 ml-9 mr-3 mb-2">
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

                        <!-- Add Todo -->
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
                                <button
                                    @click="deleteMilestoneTodo(milestone, todo)"
                                    class="text-red-400 hover:text-red-600 opacity-0 group-hover/todo:opacity-100 transition-opacity text-xs"
                                >
                                    X
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

        <!-- Milestone Modal -->
        <div
            v-if="showMilestoneModal"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50"
            @click.self="showMilestoneModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-lg mx-4 max-h-[80vh] overflow-y-auto">
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
                            Memo / Notes
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
                                v-model="displayTargetValue"
                                type="text"
                                inputmode="decimal"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                placeholder="Optional"
                                @blur="onTargetValueBlur"
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
                            Image
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
                        <div v-if="milestoneImagePreview" class="mt-2 relative inline-block">
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
                    <!-- Soft Milestone -->
                    <div class="mb-6 p-3 rounded-lg" :class="milestoneForm.is_soft ? 'bg-amber-50 dark:bg-amber-900/20' : 'bg-gray-50 dark:bg-gray-700/50'">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                v-model="milestoneForm.is_soft"
                                type="checkbox"
                                class="mt-1 rounded text-amber-500 focus:ring-amber-500"
                            />
                            <div>
                                <span class="font-medium text-gray-900 dark:text-white">Soft Milestone</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                    Won't count toward goal progress.
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
    </div>
</template>
