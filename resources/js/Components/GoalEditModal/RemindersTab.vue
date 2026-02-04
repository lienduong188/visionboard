<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    goal: Object,
});

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

// Days of week
const weekDays = [
    { value: 1, label: 'T2', fullLabel: 'Thứ 2' },
    { value: 2, label: 'T3', fullLabel: 'Thứ 3' },
    { value: 3, label: 'T4', fullLabel: 'Thứ 4' },
    { value: 4, label: 'T5', fullLabel: 'Thứ 5' },
    { value: 5, label: 'T6', fullLabel: 'Thứ 6' },
    { value: 6, label: 'T7', fullLabel: 'Thứ 7' },
    { value: 7, label: 'CN', fullLabel: 'Chủ nhật' },
];

const selectedWeekDays = ref([1]);

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
    router.patch(route('reminders.toggle', [props.goal.id, reminder.id]), {}, {
        preserveScroll: true,
    });
};

const deleteReminder = (reminder) => {
    if (confirm('Delete this reminder?')) {
        router.delete(route('reminders.destroy', [props.goal.id, reminder.id]), {
            preserveScroll: true,
        });
    }
};

const frequencyLabels = {
    daily: 'Hàng ngày',
    weekly: 'Hàng tuần',
    monthly: 'Hàng tháng',
    custom: 'Tùy chỉnh',
};

const typeLabels = {
    progress: 'Tiến độ',
    deadline: 'Deadline',
    custom: 'Tùy chỉnh',
};

const formatFrequency = (reminder) => {
    if (reminder.frequency === 'weekly' && reminder.weekly_days) {
        const dayNames = { 1: 'T2', 2: 'T3', 3: 'T4', 4: 'T5', 5: 'T6', 6: 'T7', 7: 'CN' };
        const days = reminder.weekly_days.split(',').map(d => dayNames[d] || d).join(', ');
        return `Hàng tuần (${days})`;
    }
    if (reminder.frequency === 'monthly' && reminder.monthly_day) {
        return `Ngày ${reminder.monthly_day} hàng tháng`;
    }
    return frequencyLabels[reminder.frequency] || reminder.frequency;
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
                Reminders
                <span v-if="goal.reminders?.length" class="text-gray-500 dark:text-gray-400 font-normal">
                    ({{ goal.reminders.filter(r => r.is_active).length }} active)
                </span>
            </h3>
            <button
                @click="openAddReminder"
                class="px-3 py-1.5 bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-600 transition-colors flex items-center gap-1"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Add
            </button>
        </div>

        <!-- Reminders List -->
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
                    {{ reminder.is_active ? 'ON' : 'OFF' }}
                </button>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
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
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                    </button>
                    <button
                        @click="deleteReminder(reminder)"
                        class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors"
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

        <!-- Reminder Modal -->
        <div
            v-if="showReminderModal"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50"
            @click.self="showReminderModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md mx-4 max-h-[80vh] overflow-y-auto">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                    {{ editingReminder ? 'Edit Reminder' : 'Add Reminder' }}
                </h3>
                <form @submit.prevent="submitReminder">
                    <!-- Type -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Type *
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="reminderForm.type = 'progress'"
                                class="p-3 rounded-lg border-2 text-center transition-all"
                                :class="reminderForm.type === 'progress'
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-blue-300'"
                            >
                                <div class="text-xl mb-1">Progress</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Update progress</div>
                            </button>
                            <button
                                type="button"
                                @click="reminderForm.type = 'deadline'"
                                class="p-3 rounded-lg border-2 text-center transition-all"
                                :class="reminderForm.type === 'deadline'
                                    ? 'border-red-500 bg-red-50 dark:bg-red-900/30'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-red-300'"
                            >
                                <div class="text-xl mb-1">Deadline</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Due date reminder</div>
                            </button>
                            <button
                                type="button"
                                @click="reminderForm.type = 'custom'"
                                class="p-3 rounded-lg border-2 text-center transition-all"
                                :class="reminderForm.type === 'custom'
                                    ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-purple-300'"
                            >
                                <div class="text-xl mb-1">Custom</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Custom message</div>
                            </button>
                        </div>
                    </div>

                    <!-- Frequency -->
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

                    <!-- Weekly days -->
                    <div v-if="reminderForm.frequency === 'weekly'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Days of week
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

                    <!-- Monthly day -->
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
                            <span class="text-gray-600 dark:text-gray-400">of each month</span>
                        </div>
                    </div>

                    <!-- Custom days -->
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
                            Time *
                        </label>
                        <input
                            v-model="reminderForm.remind_time"
                            type="time"
                            required
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Message -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Custom message
                        </label>
                        <textarea
                            v-model="reminderForm.message"
                            rows="2"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="Message in reminder email..."
                        ></textarea>
                    </div>

                    <!-- Active toggle -->
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
    </div>
</template>
