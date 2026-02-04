<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import GoalProgressChart from '@/Components/Charts/GoalProgressChart.vue';
import { formatNumber } from '@/utils/formatNumber';

const props = defineProps({
    goal: Object,
});

// Progress Log management
const showProgressLogModal = ref(false);
const editingProgressLog = ref(null);
const progressLogForm = useForm({
    new_value: '',
    logged_at: '',
    note: '',
});

const showAllProgressLogs = ref(false);

const openAddProgressLog = () => {
    editingProgressLog.value = null;
    progressLogForm.reset();
    progressLogForm.logged_at = new Date().toISOString().split('T')[0];
    progressLogForm.new_value = props.goal.current_value || 0;
    showProgressLogModal.value = true;
};

const openEditProgressLog = (log) => {
    editingProgressLog.value = log;
    progressLogForm.new_value = log.new_value;
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
        router.delete(route('progress-logs.destroy', [props.goal.id, log.id]), {
            preserveScroll: true,
        });
    }
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <div class="p-6">
        <!-- Progress Chart -->
        <div class="mb-6">
            <GoalProgressChart
                :goal="goal"
                :progress-logs="goal.progress_logs || []"
            />
        </div>

        <!-- Progress History -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Progress History
                    <span v-if="goal.progress_logs?.length" class="text-gray-500 dark:text-gray-400 font-normal">
                        ({{ goal.progress_logs.length }} logs)
                    </span>
                </h3>
                <button
                    @click="openAddProgressLog"
                    class="px-3 py-1.5 bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-600 transition-colors flex items-center gap-1"
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
                        <div class="flex items-center gap-2 flex-wrap">
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
                            {{ log.note }}
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
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                            </button>
                            <button
                                @click="deleteProgressLog(log)"
                                class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Show more/less -->
                <button
                    v-if="goal.progress_logs.length > 5"
                    @click="showAllProgressLogs = !showAllProgressLogs"
                    class="w-full py-2 text-sm text-indigo-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                >
                    {{ showAllProgressLogs ? 'Show less' : `Show all ${goal.progress_logs.length} logs` }}
                </button>
            </div>
            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                No progress logs yet. Add your first log!
            </div>
        </div>

        <!-- Progress Log Modal -->
        <div
            v-if="showProgressLogModal"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50"
            @click.self="showProgressLogModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md mx-4">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                    {{ editingProgressLog ? 'Edit Progress Log' : 'Add Progress Log' }}
                </h3>
                <form @submit.prevent="submitProgressLog">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Date *
                        </label>
                        <input
                            v-model="progressLogForm.logged_at"
                            type="date"
                            required
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Can be a past date
                        </p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Value ({{ goal.unit || 'unit' }}) *
                        </label>
                        <input
                            v-model="progressLogForm.new_value"
                            type="number"
                            step="any"
                            required
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Target: {{ formatNumber(goal.target_value) }} {{ goal.unit }}
                        </p>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Note
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
    </div>
</template>
