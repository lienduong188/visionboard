<script setup>
import { computed } from 'vue';

const props = defineProps({
    item: Object,
    variant: {
        type: String,
        default: 'today', // 'overdue', 'today', 'upcoming'
    },
});

const emit = defineEmits(['toggle', 'toggle-todo', 'click-goal', 'dismiss-reminder']);

const typeIcon = computed(() => {
    switch (props.item.type) {
        case 'milestone': return props.item.is_soft ? '!' : '#';
        case 'todo': return '>';
        case 'reminder': return '@';
        default: return '*';
    }
});

const typeLabel = computed(() => {
    switch (props.item.type) {
        case 'milestone': return props.item.is_soft ? 'Soft Milestone' : 'Milestone';
        case 'todo': return 'Todo';
        case 'reminder': return 'Reminder';
        default: return 'Task';
    }
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'overdue':
            return 'border-l-4 border-l-red-500 bg-red-50 dark:bg-red-900/20 hope:bg-red-50';
        case 'today':
            return 'border-l-4 border-l-amber-500 bg-amber-50 dark:bg-amber-900/20 hope:bg-amber-50';
        case 'upcoming':
            return 'border-l-4 border-l-blue-500 bg-white dark:bg-gray-800 hope:bg-white';
        default:
            return 'bg-white dark:bg-gray-800 hope:bg-white';
    }
});

const canToggle = computed(() => {
    return props.item.type !== 'reminder';
});

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
    });
};

// Calculate days until/since due date
const daysUntil = computed(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const due = new Date(props.item.due_date);
    due.setHours(0, 0, 0, 0);
    const diff = Math.ceil((due - today) / (1000 * 60 * 60 * 24));

    if (diff < -1) return `${Math.abs(diff)} days overdue`;
    if (diff === -1) return '1 day overdue';
    if (diff === 0) return 'Today';
    if (diff === 1) return 'Tomorrow';
    return `In ${diff} days`;
});

// Format weekly days (e.g., "1,3,5" -> "Mon, Wed, Fri")
const formatWeeklyDays = (daysStr) => {
    if (!daysStr) return '';
    const dayNames = ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    return daysStr.split(',').map(d => dayNames[parseInt(d)] || d).join(', ');
};
</script>

<template>
    <div
        class="rounded-lg shadow-sm p-4 transition-all hover:shadow-md"
        :class="variantClasses"
    >
        <div class="flex items-start gap-3">
            <!-- Toggle Checkbox -->
            <button
                v-if="canToggle"
                @click="emit('toggle', item)"
                class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors flex-shrink-0 mt-0.5"
                :class="item.is_completed
                    ? 'bg-green-500 border-green-500 text-white'
                    : 'border-gray-300 dark:border-gray-600 hover:border-indigo-500 dark:hover:border-indigo-400'"
            >
                <svg v-if="item.is_completed" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </button>
            <div v-else class="w-5 h-5 flex items-center justify-center flex-shrink-0 text-amber-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h3
                            class="font-medium truncate"
                            :class="item.is_completed
                                ? 'text-gray-400 line-through'
                                : 'text-gray-900 dark:text-white hope:text-gray-900'"
                        >
                            {{ item.title }}
                        </h3>
                        <div class="flex items-center gap-2 mt-1 text-sm text-gray-500 dark:text-gray-400 hope:text-gray-600 flex-wrap">
                            <span class="inline-flex items-center gap-1">
                                <span>{{ item.goal.category?.icon }}</span>
                                <button
                                    @click="emit('click-goal', item.goal.id)"
                                    class="hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline truncate max-w-[150px]"
                                >
                                    {{ item.goal.title }}
                                </button>
                            </span>
                            <span v-if="item.type === 'todo'" class="text-gray-400 truncate">
                                <span class="mx-1">→</span>
                                <span class="truncate max-w-[100px] inline-block align-bottom">{{ item.milestone.title }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Date & Type Badge -->
                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        <span
                            class="text-xs px-2 py-0.5 rounded-full whitespace-nowrap"
                            :class="{
                                'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300': variant === 'overdue',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300': variant === 'today',
                                'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300': variant === 'upcoming',
                            }"
                            :title="formatDate(item.due_date)"
                        >
                            {{ daysUntil }}
                        </span>
                        <span class="text-xs text-gray-400 whitespace-nowrap">
                            {{ typeLabel }}
                        </span>
                    </div>
                </div>

                <!-- Description -->
                <p
                    v-if="item.description"
                    class="mt-2 text-sm text-gray-600 dark:text-gray-400 hope:text-gray-600 line-clamp-2"
                >
                    {{ item.description }}
                </p>

                <!-- Milestone Todos List (for milestones) -->
                <div
                    v-if="item.type === 'milestone' && item.todos && item.todos.length > 0"
                    class="mt-3 pl-1 border-l-2 border-gray-200 dark:border-gray-600"
                >
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 pl-2">
                        {{ item.todos_completed }}/{{ item.todos_count }} todos
                    </div>
                    <div class="space-y-1.5">
                        <div
                            v-for="todo in item.todos"
                            :key="todo.id"
                            class="flex items-center gap-2 pl-2"
                        >
                            <button
                                @click.stop="emit('toggle-todo', { goalId: item.goal.id, milestoneId: item.id, todoId: todo.id })"
                                class="w-4 h-4 rounded border flex items-center justify-center transition-colors flex-shrink-0"
                                :class="todo.is_completed
                                    ? 'bg-green-500 border-green-500 text-white'
                                    : 'border-gray-300 dark:border-gray-500 hover:border-indigo-500'"
                            >
                                <svg v-if="todo.is_completed" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <span
                                class="text-sm"
                                :class="todo.is_completed
                                    ? 'text-gray-400 line-through'
                                    : 'text-gray-700 dark:text-gray-300'"
                            >
                                {{ todo.title }}
                            </span>
                            <span
                                v-if="todo.end_date"
                                class="text-xs text-gray-400 ml-auto"
                            >
                                {{ formatDate(todo.end_date) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Reminder info -->
                <div
                    v-if="item.type === 'reminder'"
                    class="mt-3 text-xs text-gray-500 dark:text-gray-400 space-y-1.5"
                >
                    <!-- Type & Frequency -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full capitalize"
                            :class="{
                                'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300': item.reminder_type === 'progress',
                                'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300': item.reminder_type === 'deadline',
                                'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300': item.reminder_type === 'custom',
                            }"
                        >
                            {{ item.reminder_type }}
                        </span>
                        <span class="inline-flex items-center gap-1 capitalize">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ item.frequency }}
                        </span>
                    </div>

                    <!-- Time -->
                    <div v-if="item.remind_time" class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ item.remind_time }}</span>
                    </div>

                    <!-- Weekly days -->
                    <div v-if="item.frequency === 'weekly' && item.weekly_days" class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ formatWeeklyDays(item.weekly_days) }}</span>
                    </div>

                    <!-- Monthly day -->
                    <div v-if="item.frequency === 'monthly' && item.monthly_day" class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Day {{ item.monthly_day }} of month</span>
                    </div>

                    <!-- Active period -->
                    <div v-if="item.start_date || item.end_date" class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        <span>
                            {{ item.start_date ? formatDate(item.start_date) : 'Start' }}
                            →
                            {{ item.end_date ? formatDate(item.end_date) : 'No end' }}
                        </span>
                    </div>

                    <!-- Dismiss button -->
                    <div class="pt-2">
                        <button
                            @click="emit('dismiss-reminder', item)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/50 dark:text-green-300 dark:hover:bg-green-900 hope:bg-green-100 hope:text-green-700 hope:hover:bg-green-200"
                            title="Mark as done for today"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Done for today
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
