<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    output: Object,
    categories: Object,
    isPublic: Boolean,
    movementTypes: { type: Object, default: () => ({}) },
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

// Gộp image_path (legacy) và images (JSON) thành một mảng
const allImages = computed(() => {
    const list = [];
    if (props.output.image_path) list.push(props.output.image_path);
    for (const p of props.output.images ?? []) list.push(p);
    return list;
});

const formatDuration = (minutes) => {
    if (!minutes) return '0m';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h === 0) return `${m}'`;
    if (m === 0) return `${h}h`;
    return `${h}h${m}'`;
};

const movementPace = computed(() => {
    const dist = parseFloat(props.output.distance_km);
    const hms = props.output.duration_hms;
    if (!dist || dist <= 0 || !hms) return null;
    const parts = hms.split(':');
    if (parts.length !== 3) return null;
    const totalSec = parseInt(parts[0]) * 3600 + parseInt(parts[1]) * 60 + parseInt(parts[2]);
    if (!totalSec) return null;
    const paceSecPerKm = totalSec / dist;
    const paceMin = Math.floor(paceSecPerKm / 60);
    const paceSec = Math.round(paceSecPerKm % 60);
    return `${paceMin}:${String(paceSec).padStart(2, '0')}`;
});

const movementTypeInfo = computed(() => {
    if (!props.output.movement_type) return null;
    return props.movementTypes[props.output.movement_type] || null;
});
</script>

<template>
    <div
        class="flex items-start gap-3 py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 group transition-colors"
        :class="{
            'opacity-50': output.status === 'skipped',
        }"
    >
        <!-- Status toggle -->
        <button
            @click="!isPublic && toggleStatus()"
            class="text-lg flex-shrink-0 transition-transform mt-0.5"
            :class="!isPublic ? 'hover:scale-110 cursor-pointer' : 'cursor-default'"
            :title="isPublic ? output.status : (output.status === 'done' ? 'Mark as planned' : 'Mark as done')"
        >
            {{ statusIcon[output.status] || '⬜' }}
        </button>

        <!-- Category badge -->
        <span class="text-sm flex-shrink-0 mt-0.5" :title="category.label">
            {{ category.icon }}
        </span>

        <!-- Right: Title + meta row -->
        <div class="flex-1 min-w-0">
            <!-- Title -->
            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate"
                :class="{ 'line-through': output.status === 'skipped' }">
                {{ output.title }}
            </div>
            <!-- Goal link -->
            <div v-if="output.goal" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                → {{ output.goal.title }}
            </div>

            <!-- Movement stats (chỉ hiện khi có data) -->
            <div v-if="output.category === 'movement' && (output.distance_km || output.duration_hms)" class="flex items-center gap-2 mt-1 flex-wrap text-xs text-orange-600 dark:text-orange-400 font-mono">
                <span v-if="movementTypeInfo" class="not-mono text-orange-500">{{ movementTypeInfo.icon }} {{ movementTypeInfo.ja }}</span>
                <span v-if="output.distance_km">📍 {{ parseFloat(output.distance_km) % 1 === 0 ? parseFloat(output.distance_km) : parseFloat(output.distance_km).toFixed(2).replace(/\.?0+$/, '') }} km</span>
                <span v-if="output.duration_hms">⏱ {{ output.duration_hms }}</span>
                <span v-if="movementPace">⚡ {{ movementPace }}/km</span>
                <span v-if="output.heart_rate">❤️ {{ output.heart_rate }}</span>
            </div>

            <!-- Meta row: duration, rating, link, image, actions -->
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                <!-- Duration -->
                <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                    {{ formatDuration(output.duration) }}
                </span>

                <!-- Rating -->
                <span v-if="output.rating" class="text-xs text-yellow-500">
                    {{ '⭐'.repeat(output.rating) }}
                </span>

                <!-- Output link -->
                <a
                    v-if="output.output_link"
                    :href="output.output_link"
                    target="_blank"
                    class="text-blue-500 hover:text-blue-700"
                    title="View output"
                >
                    🔗
                </a>

                <!-- Image thumbnails (multiple) -->
                <template v-if="allImages.length > 0">
                    <a
                        v-for="(img, i) in allImages"
                        :key="i"
                        :href="'/storage/' + img"
                        target="_blank"
                        title="View image"
                    >
                        <img
                            :src="'/storage/' + img"
                            class="h-6 w-6 rounded object-cover border border-gray-200 dark:border-gray-600 hover:opacity-80 transition-opacity"
                            alt=""
                        />
                    </a>
                </template>

                <!-- Actions: ẩn khi public view -->
                <div v-if="!isPublic" class="flex gap-1 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
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
        </div>
    </div>
</template>
