<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    output: { type: Object, default: null },
    categories: Object,
    goals: Array,
    durationPresets: Array,
    defaultDate: { type: String, default: null },
    defaultTitle: { type: String, default: null },
});

const emit = defineEmits(['close']);

const form = ref({
    title: '',
    category: 'coding',
    goal_id: null,
    duration: 60,
    note: '',
    output_link: '',
    rating: null,
    status: 'done',
    output_date: '',
});

const isEditing = computed(() => !!props.output);

// Reset form when modal opens
watch(() => props.show, (val) => {
    if (val) {
        if (props.output) {
            form.value = {
                title: props.output.title,
                category: props.output.category,
                goal_id: props.output.goal_id,
                duration: props.output.duration,
                note: props.output.note || '',
                output_link: props.output.output_link || '',
                rating: props.output.rating,
                status: props.output.status,
                output_date: props.output.output_date?.split('T')[0] || props.output.output_date,
            };
        } else {
            const now = new Date();
            const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
            form.value = {
                title: props.defaultTitle || '',
                category: 'coding',
                goal_id: null,
                duration: 60,
                note: '',
                output_link: '',
                rating: null,
                status: isFutureDate(props.defaultDate || today) ? 'planned' : 'done',
                output_date: props.defaultDate || today,
            };
        }
    }
});

const isFutureDate = (dateStr) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const date = new Date(dateStr + 'T00:00:00');
    return date > today;
};

const setRating = (r) => {
    form.value.rating = form.value.rating === r ? null : r;
};

const submit = () => {
    if (!form.value.title.trim()) return;

    // Clean empty strings to null
    const data = {
        ...form.value,
        output_link: form.value.output_link || null,
        note: form.value.note || null,
        goal_id: form.value.goal_id || null,
        rating: form.value.rating || null,
    };

    if (isEditing.value) {
        router.put(route('tracking-output.update', props.output.id), data, {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    } else {
        router.post(route('tracking-output.store'), data, {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    }
};

const close = () => emit('close');
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50" @click="close"></div>

            <!-- Modal -->
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ isEditing ? 'Edit Output' : 'Add Output' }}
                    </h3>
                    <button @click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Title *
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="What did you accomplish?"
                            required
                            autofocus
                        />
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Category
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="(cat, key) in categories"
                                :key="key"
                                type="button"
                                @click="form.category = key"
                                class="px-3 py-1.5 rounded-full text-sm transition-colors"
                                :class="form.category === key
                                    ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 ring-2 ring-indigo-500'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                            >
                                {{ cat.icon }} {{ cat.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Goal Link -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Linked Goal
                        </label>
                        <select
                            v-model="form.goal_id"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option :value="null">-- None --</option>
                            <option v-for="goal in goals" :key="goal.id" :value="goal.id">
                                {{ goal.category?.icon }} {{ goal.title }}
                            </option>
                        </select>
                    </div>

                    <!-- Duration -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Duration
                        </label>
                        <div class="flex gap-2">
                            <button
                                v-for="preset in durationPresets"
                                :key="preset"
                                type="button"
                                @click="form.duration = preset"
                                class="px-4 py-2 rounded-lg text-sm font-mono transition-colors"
                                :class="form.duration === preset
                                    ? 'bg-indigo-500 text-white'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                            >
                                {{ preset }}'
                            </button>
                        </div>
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Date
                        </label>
                        <input
                            v-model="form.output_date"
                            type="date"
                            min="2026-02-17"
                            max="2027-02-05"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Status
                        </label>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="form.status = 'done'"
                                class="px-4 py-2 rounded-lg text-sm transition-colors"
                                :class="form.status === 'done'
                                    ? 'bg-green-500 text-white'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'"
                            >
                                ✅ Done
                            </button>
                            <button
                                type="button"
                                @click="form.status = 'planned'"
                                class="px-4 py-2 rounded-lg text-sm transition-colors"
                                :class="form.status === 'planned'
                                    ? 'bg-blue-500 text-white'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'"
                            >
                                📋 Planned
                            </button>
                            <button
                                type="button"
                                @click="form.status = 'skipped'"
                                class="px-4 py-2 rounded-lg text-sm transition-colors"
                                :class="form.status === 'skipped'
                                    ? 'bg-gray-500 text-white'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'"
                            >
                                ⏭️ Skipped
                            </button>
                        </div>
                    </div>

                    <!-- Rating -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Rating
                        </label>
                        <div class="flex gap-1">
                            <button
                                v-for="r in 5"
                                :key="r"
                                type="button"
                                @click="setRating(r)"
                                class="text-2xl transition-transform hover:scale-110"
                            >
                                {{ r <= (form.rating || 0) ? '⭐' : '☆' }}
                            </button>
                        </div>
                    </div>

                    <!-- Note -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Note
                        </label>
                        <textarea
                            v-model="form.note"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="What did you learn? How did it go?"
                        ></textarea>
                    </div>

                    <!-- Output Link -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Output Link
                        </label>
                        <input
                            v-model="form.output_link"
                            type="url"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="https://..."
                        />
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            type="button"
                            @click="close"
                            class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors"
                        >
                            {{ isEditing ? 'Update' : 'Add Output' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
