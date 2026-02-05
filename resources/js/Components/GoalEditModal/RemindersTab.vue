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
    specific_dates: [],
    weekly_days: '',
    monthly_day: 1,
    start_date: '',
    end_date: '',
    remind_time: '09:00',
    message: '',
    is_active: true,
});

// Specific dates management
const newSpecificDate = ref('');

// Days of week
const weekDays = [
    { value: 1, label: 'Mon', fullLabel: 'Monday' },
    { value: 2, label: 'Tue', fullLabel: 'Tuesday' },
    { value: 3, label: 'Wed', fullLabel: 'Wednesday' },
    { value: 4, label: 'Thu', fullLabel: 'Thursday' },
    { value: 5, label: 'Fri', fullLabel: 'Friday' },
    { value: 6, label: 'Sat', fullLabel: 'Saturday' },
    { value: 7, label: 'Sun', fullLabel: 'Sunday' },
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

const addSpecificDate = () => {
    if (newSpecificDate.value && !reminderForm.specific_dates.includes(newSpecificDate.value)) {
        reminderForm.specific_dates.push(newSpecificDate.value);
        reminderForm.specific_dates.sort();
        newSpecificDate.value = '';
    }
};

const removeSpecificDate = (date) => {
    const idx = reminderForm.specific_dates.indexOf(date);
    if (idx > -1) {
        reminderForm.specific_dates.splice(idx, 1);
    }
};

const formatSpecificDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
};

const openAddReminder = () => {
    editingReminder.value = null;
    reminderForm.reset();
    reminderForm.type = 'progress';
    reminderForm.frequency = 'daily';
    reminderForm.remind_time = '09:00';
    reminderForm.weekly_days = '1';
    reminderForm.monthly_day = 1;
    reminderForm.specific_dates = [];
    reminderForm.start_date = '';
    reminderForm.end_date = '';
    selectedWeekDays.value = [1];
    newSpecificDate.value = '';
    showReminderModal.value = true;
};

const openEditReminder = (reminder) => {
    editingReminder.value = reminder;
    reminderForm.type = reminder.type;
    reminderForm.frequency = reminder.frequency;
    reminderForm.specific_dates = reminder.specific_dates ? reminder.specific_dates.split(',') : [];
    reminderForm.weekly_days = reminder.weekly_days || '1';
    reminderForm.monthly_day = reminder.monthly_day || 1;
    reminderForm.start_date = reminder.start_date ? reminder.start_date.slice(0, 10) : '';
    reminderForm.end_date = reminder.end_date ? reminder.end_date.slice(0, 10) : '';
    reminderForm.remind_time = reminder.remind_time?.slice(0, 5) || '09:00';
    reminderForm.message = reminder.message || '';
    reminderForm.is_active = reminder.is_active;
    selectedWeekDays.value = reminder.weekly_days
        ? reminder.weekly_days.split(',').map(Number)
        : [1];
    newSpecificDate.value = '';
    showReminderModal.value = true;
};

const submitReminder = () => {
    // Transform specific_dates array to comma-separated string
    const formData = {
        ...reminderForm.data(),
        specific_dates: reminderForm.specific_dates.length > 0
            ? reminderForm.specific_dates.join(',')
            : null,
    };

    if (editingReminder.value) {
        reminderForm.transform(() => formData).put(
            route('reminders.update', [props.goal.id, editingReminder.value.id]),
            {
                onSuccess: () => {
                    showReminderModal.value = false;
                    reminderForm.reset();
                },
            }
        );
    } else {
        reminderForm.transform(() => formData).post(
            route('reminders.store', props.goal.id),
            {
                onSuccess: () => {
                    showReminderModal.value = false;
                    reminderForm.reset();
                },
            }
        );
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
    daily: 'Daily',
    weekly: 'Weekly',
    monthly: 'Monthly',
    specific: 'Specific dates',
};

const typeLabels = {
    progress: 'Progress',
    deadline: 'Deadline',
    custom: 'Custom',
};

const formatFrequency = (reminder) => {
    if (reminder.frequency === 'weekly' && reminder.weekly_days) {
        const dayNames = { 1: 'Mon', 2: 'Tue', 3: 'Wed', 4: 'Thu', 5: 'Fri', 6: 'Sat', 7: 'Sun' };
        const days = reminder.weekly_days.split(',').map(d => dayNames[d] || d).join(', ');
        return `Weekly (${days})`;
    }
    if (reminder.frequency === 'monthly' && reminder.monthly_day) {
        return `Day ${reminder.monthly_day} monthly`;
    }
    if (reminder.frequency === 'specific' && reminder.specific_dates) {
        const dates = reminder.specific_dates.split(',');
        if (dates.length <= 3) {
            return dates.map(d => {
                const date = new Date(d);
                return date.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
            }).join(', ');
        }
        return `${dates.length} specific dates`;
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
                            {{ formatFrequency(reminder) }} at {{ reminder.remind_time?.slice(0, 5) }}
                        </span>
                    </div>
                    <div
                        v-if="reminder.message"
                        class="text-sm text-gray-500 dark:text-gray-400 truncate mt-1"
                    >
                        "{{ reminder.message }}"
                    </div>
                    <div
                        v-if="reminder.start_date || reminder.end_date"
                        class="text-xs text-gray-400 dark:text-gray-500 mt-1"
                    >
                        📅 {{ reminder.start_date ? formatDate(reminder.start_date) : 'Start' }}
                        → {{ reminder.end_date ? formatDate(reminder.end_date) : 'Ongoing' }}
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
                            <option value="specific">Specific dates</option>
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

                    <!-- Specific dates -->
                    <div v-if="reminderForm.frequency === 'specific'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Select specific dates
                        </label>
                        <div class="flex gap-2 mb-2">
                            <input
                                v-model="newSpecificDate"
                                type="date"
                                class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm"
                            />
                            <button
                                type="button"
                                @click="addSpecificDate"
                                :disabled="!newSpecificDate"
                                class="px-3 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                        <div v-if="reminderForm.specific_dates.length" class="flex flex-wrap gap-2">
                            <span
                                v-for="date in reminderForm.specific_dates"
                                :key="date"
                                class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm"
                            >
                                {{ formatSpecificDate(date) }}
                                <button
                                    type="button"
                                    @click="removeSpecificDate(date)"
                                    class="hover:text-red-500 transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </span>
                        </div>
                        <p v-else class="text-xs text-gray-400 mt-1">
                            Add at least 1 date
                        </p>
                    </div>

                    <!-- Date Range -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Active Period
                            <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">From</label>
                                <input
                                    v-model="reminderForm.start_date"
                                    type="date"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm"
                                />
                            </div>
                            <span class="text-gray-400 mt-5">→</span>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">To</label>
                                <input
                                    v-model="reminderForm.end_date"
                                    type="date"
                                    :min="reminderForm.start_date || undefined"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm"
                                />
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            Leave empty for ongoing reminders
                        </p>
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
