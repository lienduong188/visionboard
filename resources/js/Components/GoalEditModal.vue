<script setup>
import InfoTab from './GoalEditModal/InfoTab.vue';
import MilestonesTab from './GoalEditModal/MilestonesTab.vue';
import RemindersTab from './GoalEditModal/RemindersTab.vue';
import ReferencesTab from './GoalEditModal/ReferencesTab.vue';
import HistoryTab from './GoalEditModal/HistoryTab.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    goal: Object,
    categories: Array,
    coreGoalsCount: Number,
    show: Boolean,
});

const emit = defineEmits(['close', 'saved', 'deleted']);

const activeTab = ref('info');
const infoTabRef = ref(null);
const showExitConfirm = ref(false);

// Reset to info tab when modal opens with new goal
watch(() => props.goal?.id, () => {
    activeTab.value = 'info';
    showExitConfirm.value = false;
});

// Tab class helper
const tabClass = (tab) => {
    const base = 'shrink-0 px-4 sm:px-5 py-3 min-h-[52px] text-sm font-semibold border-b-3 transition-colors whitespace-nowrap text-center flex items-center justify-center gap-1.5';
    if (activeTab.value === tab) {
        return `${base} border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-white dark:bg-gray-800`;
    }
    return `${base} border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800`;
};

// Count active reminders
const activeRemindersCount = computed(() => {
    return props.goal?.reminders?.filter(r => r.is_active).length || 0;
});

// Progress color
const progressColor = computed(() => {
    const progress = props.goal?.progress || 0;
    if (progress >= 100) return 'bg-green-500';
    if (progress >= 75) return 'bg-emerald-500';
    if (progress >= 50) return 'bg-yellow-500';
    if (progress >= 25) return 'bg-orange-500';
    return 'bg-red-500';
});

// Close modal - only confirm if InfoTab has unsaved changes
const close = () => {
    if (infoTabRef.value?.isDirty) {
        showExitConfirm.value = true;
    } else {
        confirmClose();
    }
};

// Actually close the modal
const confirmClose = () => {
    showExitConfirm.value = false;
    emit('close');
};

// Cancel close
const cancelClose = () => {
    showExitConfirm.value = false;
};

// Handle save from InfoTab
const onSaved = () => {
    emit('saved');
    emit('close');
};

// Handle delete from InfoTab
const onDeleted = () => {
    emit('deleted');
    emit('close');
};

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

// Expose InfoTab methods for footer actions
const handleSave = () => {
    if (infoTabRef.value) {
        infoTabRef.value.submit();
    }
};

const handleDelete = () => {
    if (infoTabRef.value) {
        infoTabRef.value.confirmDelete();
    }
};

const handleDeleteConfirm = () => {
    if (infoTabRef.value) {
        infoTabRef.value.deleteGoal();
    }
};

const handleDeleteCancel = () => {
    if (infoTabRef.value) {
        infoTabRef.value.cancelDelete();
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
                v-if="show"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click.self="close"
            >
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>

                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-2 sm:p-4">
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="show && goal"
                            class="relative w-full max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-h-[90vh] overflow-hidden flex flex-col"
                            @click.stop
                        >
                            <!-- Header -->
                            <div
                                class="sticky top-0 z-10 flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800"
                                :style="{ borderTopColor: goal.category?.color }"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">{{ goal.category?.icon }}</span>
                                    <div>
                                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ goal.title }}
                                        </h2>
                                        <p v-if="goal.slogan" class="text-sm text-indigo-600 dark:text-indigo-400 italic">
                                            "{{ goal.slogan }}"
                                        </p>
                                    </div>
                                </div>
                                <button
                                    @click="close"
                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Progress Bar -->
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Progress</span>
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
                            <div class="flex overflow-x-auto border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 scrollbar-hide">
                                <button @click="activeTab = 'info'" :class="tabClass('info')">
                                    📋 Info
                                </button>
                                <button @click="activeTab = 'milestones'" :class="tabClass('milestones')">
                                    🎯 Miles.
                                    <span v-if="goal.milestones?.length" class="px-1.5 py-0.5 text-xs bg-gray-200 dark:bg-gray-700 rounded-full">
                                        {{ goal.milestones.filter(m => m.is_completed).length }}/{{ goal.milestones.length }}
                                    </span>
                                </button>
                                <button @click="activeTab = 'reminders'" :class="tabClass('reminders')">
                                    🔔 Remind
                                    <span v-if="activeRemindersCount" class="px-1.5 py-0.5 text-xs bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 rounded-full">
                                        {{ activeRemindersCount }}
                                    </span>
                                </button>
                                <button @click="activeTab = 'references'" :class="tabClass('references')">
                                    📎 Refs
                                    <span v-if="goal.references?.length" class="px-1.5 py-0.5 text-xs bg-gray-200 dark:bg-gray-700 rounded-full">
                                        {{ goal.references.length }}
                                    </span>
                                </button>
                                <button @click="activeTab = 'history'" :class="tabClass('history')">
                                    📈 Hist.
                                    <span v-if="goal.progress_logs?.length" class="px-1.5 py-0.5 text-xs bg-gray-200 dark:bg-gray-700 rounded-full">
                                        {{ goal.progress_logs.length }}
                                    </span>
                                </button>
                            </div>

                            <!-- Tab Content -->
                            <div class="overflow-y-auto flex-1">
                                <InfoTab
                                    v-show="activeTab === 'info'"
                                    ref="infoTabRef"
                                    :goal="goal"
                                    :categories="categories"
                                    :core-goals-count="coreGoalsCount"
                                    @saved="onSaved"
                                    @deleted="onDeleted"
                                />
                                <MilestonesTab
                                    v-show="activeTab === 'milestones'"
                                    :goal="goal"
                                />
                                <RemindersTab
                                    v-show="activeTab === 'reminders'"
                                    :goal="goal"
                                />
                                <ReferencesTab
                                    v-show="activeTab === 'references'"
                                    :goal="goal"
                                />
                                <HistoryTab
                                    v-show="activeTab === 'history'"
                                    :goal="goal"
                                />
                            </div>

                            <!-- Footer Actions (only for Info tab) -->
                            <div
                                v-if="activeTab === 'info'"
                                class="sticky bottom-0 flex items-center justify-between p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800"
                            >
                                <!-- Delete Button -->
                                <div>
                                    <button
                                        v-if="!infoTabRef?.showDeleteConfirm"
                                        type="button"
                                        @click="handleDelete"
                                        class="px-4 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                    >
                                        Delete
                                    </button>
                                    <div v-else class="flex items-center gap-2">
                                        <span class="text-sm text-red-600 dark:text-red-400">Confirm delete?</span>
                                        <button
                                            type="button"
                                            @click="handleDeleteConfirm"
                                            class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm"
                                            :disabled="infoTabRef?.processing"
                                        >
                                            Delete
                                        </button>
                                        <button
                                            type="button"
                                            @click="handleDeleteCancel"
                                            class="px-3 py-1.5 text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 rounded-lg transition-colors text-sm"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>

                                <!-- Save Button -->
                                <PrimaryButton
                                    @click="handleSave"
                                    :disabled="infoTabRef?.processing"
                                >
                                    Save
                                </PrimaryButton>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- Exit Confirmation Modal -->
                <Transition
                    enter-active-class="transition ease-out duration-150"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="showExitConfirm"
                        class="fixed inset-0 z-[60] flex items-center justify-center p-4"
                    >
                        <div class="absolute inset-0 bg-black/30" @click="cancelClose"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-md w-full">
                            <div class="text-center">
                                <div class="text-4xl mb-4">⚠️</div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                    Unsaved Changes
                                </h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-6">
                                    Are you sure you want to leave? You will lose any unsaved changes.
                                </p>
                                <div class="flex items-center justify-center gap-3">
                                    <button
                                        @click="cancelClose"
                                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                    >
                                        Stay
                                    </button>
                                    <button
                                        @click="confirmClose"
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
                                    >
                                        Leave
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
