<script setup>
import OutputItemCard from './OutputItemCard.vue';
import { computed } from 'vue';

const props = defineProps({
    date: String,
    outputs: Array,
    categories: Object,
    isToday: Boolean,
    isTomorrow: Boolean,
    isRestDay: Boolean,
    isMissed: Boolean,
    dayNumber: Number,
    isPublic: Boolean,
});

const emit = defineEmits(['add', 'edit', 'delete', 'toggle-rest']);

const dayLabel = computed(() => {
    if (props.isToday) return 'Today';
    if (props.isTomorrow) return 'Tomorrow';
    const d = new Date(props.date + 'T00:00:00');
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    return days[d.getDay()];
});

const formattedDate = computed(() => {
    const d = new Date(props.date + 'T00:00:00');
    return `${d.getDate()}/${d.getMonth() + 1}`;
});

const totalDuration = computed(() => {
    const total = props.outputs
        .filter(o => o.status === 'done')
        .reduce((sum, o) => sum + (o.duration || 0), 0);
    if (!total) return '';
    const h = Math.floor(total / 60);
    const m = total % 60;
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h${m}m`;
});

const doneCount = computed(() => props.outputs.filter(o => o.status === 'done').length);
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <!-- Day Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <span
                    class="text-sm font-bold"
                    :class="{
                        'text-indigo-600 dark:text-indigo-400': isToday,
                        'text-purple-600 dark:text-purple-400': isTomorrow,
                        'text-gray-700 dark:text-gray-300': !isToday && !isTomorrow,
                    }"
                >
                    {{ dayLabel }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formattedDate }}
                </span>
                <span v-if="dayNumber" class="text-xs text-gray-400 dark:text-gray-500">
                    Day #{{ dayNumber }}
                </span>
                <span v-if="isRestDay" class="text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-1.5 py-0.5 rounded">
                    😴 Rest
                </span>
                <span v-if="isMissed" class="text-xs bg-red-100 dark:bg-red-900/30 text-red-500 dark:text-red-400 px-1.5 py-0.5 rounded">
                    ✗ Missed
                </span>
            </div>
            <div class="flex items-center gap-3">
                <span v-if="totalDuration" class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                    ⏱️ {{ totalDuration }}
                </span>
                <span v-if="doneCount" class="text-xs text-green-500">
                    {{ doneCount }} done
                </span>
                <button
                    v-if="!isPublic"
                    @click="emit('add', date)"
                    class="text-xs text-indigo-500 hover:text-indigo-700 dark:text-indigo-400 font-medium"
                >
                    + Add
                </button>
            </div>
        </div>

        <!-- Outputs -->
        <div v-if="outputs.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700/50">
            <OutputItemCard
                v-for="output in outputs"
                :key="output.id"
                :output="output"
                :categories="categories"
                :is-public="isPublic"
                @edit="emit('edit', { ...$event, output_date: date })"
                @delete="emit('delete', $event)"
            />
        </div>

        <!-- Empty state -->
        <div v-else class="px-4 py-6 text-center">
            <p class="text-sm" :class="isMissed ? 'text-red-400 dark:text-red-500' : 'text-gray-400 dark:text-gray-500'">
                {{ isRestDay ? '😴 Rest day - recharging!' : isMissed ? '✗ Bỏ lỡ ngày này' : 'No outputs for this day' }}
            </p>
            <button
                v-if="!isRestDay && !isPublic"
                @click="emit('add', date)"
                class="mt-2 text-sm text-indigo-500 hover:text-indigo-700"
            >
                + Add output
            </button>
        </div>
    </div>
</template>
