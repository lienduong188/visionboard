<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    output: Object,
    categories: Object,
});

const emit = defineEmits(['edit', 'delete']);

const category = props.categories[props.output.category] || { icon: '🔧', label: 'Other' };

const statusIcon = {
    done: '✅',
    planned: '⬜',
    skipped: '⏭️',
};

const toggleStatus = () => {
    const nextStatus = props.output.status === 'done' ? 'planned' : 'done';
    router.patch(route('tracking-output.toggle-status', props.output.id), {
        status: nextStatus,
    }, { preserveScroll: true });
};

const formatDuration = (minutes) => {
    if (!minutes) return '0m';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h === 0) return `${m}'`;
    if (m === 0) return `${h}h`;
    return `${h}h${m}'`;
};
</script>

<template>
    <div
        class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 group transition-colors"
        :class="{
            'opacity-50': output.status === 'skipped',
        }"
    >
        <!-- Status toggle -->
        <button
            @click="toggleStatus"
            class="text-lg flex-shrink-0 hover:scale-110 transition-transform"
            :title="output.status === 'done' ? 'Mark as planned' : 'Mark as done'"
        >
            {{ statusIcon[output.status] || '⬜' }}
        </button>

        <!-- Category badge -->
        <span class="text-sm flex-shrink-0" :title="category.label">
            {{ category.icon }}
        </span>

        <!-- Title + Goal link -->
        <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate"
                :class="{ 'line-through': output.status === 'skipped' }">
                {{ output.title }}
            </div>
            <div v-if="output.goal" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                → {{ output.goal.title }}
            </div>
        </div>

        <!-- Duration -->
        <span class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0 font-mono">
            {{ formatDuration(output.duration) }}
        </span>

        <!-- Rating -->
        <div v-if="output.rating" class="flex-shrink-0 text-xs text-yellow-500">
            {{ '⭐'.repeat(output.rating) }}
        </div>

        <!-- Output link -->
        <a
            v-if="output.output_link"
            :href="output.output_link"
            target="_blank"
            class="text-blue-500 hover:text-blue-700 flex-shrink-0"
            title="View output"
        >
            🔗
        </a>

        <!-- Image thumbnail -->
        <a
            v-if="output.image_path"
            :href="'/storage/' + output.image_path"
            target="_blank"
            class="flex-shrink-0"
            title="View image"
        >
            <img
                :src="'/storage/' + output.image_path"
                class="h-7 w-7 rounded object-cover border border-gray-200 dark:border-gray-600 hover:opacity-80 transition-opacity"
                alt=""
            />
        </a>

        <!-- Actions (show on hover) -->
        <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 flex gap-1 transition-opacity">
            <button
                @click="emit('edit', output)"
                class="text-gray-400 hover:text-blue-500 text-sm"
                title="Edit"
            >
                ✏️
            </button>
            <button
                @click="emit('delete', output.id)"
                class="text-gray-400 hover:text-red-500 text-sm"
                title="Delete"
            >
                🗑️
            </button>
        </div>
    </div>
</template>
