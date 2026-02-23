<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const formErrors = ref({});

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
    category: 'writing',
    goal_id: null,
    duration: 60,
    note: '',
    output_link: '',
    rating: null,
    status: 'done',
    output_date: '',
});

const imageFile = ref(null);
const imagePreview = ref(null);
const removeImage = ref(false);

const isEditing = computed(() => !!props.output);

// Parse date string thành YYYY-MM-DD theo local timezone (tránh UTC shift)
const toLocalDateStr = (dateStr) => {
    if (!dateStr) return '';
    // Nếu đã là YYYY-MM-DD thì dùng luôn
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;
    // Có timezone info → parse và lấy theo local time
    const d = new Date(dateStr);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
};

// Reset form when modal opens
watch(() => props.show, (val) => {
    if (val) {
        formErrors.value = {};
        imageFile.value = null;
        imagePreview.value = null;
        removeImage.value = false;

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
                output_date: toLocalDateStr(props.output.output_date),
            };
        } else {
            const now = new Date();
            const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
            form.value = {
                title: props.defaultTitle || '',
                category: 'writing',
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

const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    imageFile.value = file;
    removeImage.value = false;
    const reader = new FileReader();
    reader.onload = (ev) => { imagePreview.value = ev.target.result; };
    reader.readAsDataURL(file);
};

const handleRemoveImage = () => {
    imageFile.value = null;
    imagePreview.value = null;
    removeImage.value = true;
};

const currentImageUrl = computed(() => {
    if (imagePreview.value) return imagePreview.value;
    if (!removeImage.value && props.output?.image_path) {
        return '/storage/' + props.output.image_path;
    }
    return null;
});

const submit = () => {
    if (!form.value.title.trim()) return;

    const data = new FormData();
    data.append('title', form.value.title);
    data.append('category', form.value.category);
    data.append('duration', form.value.duration);
    data.append('output_date', form.value.output_date);
    data.append('status', form.value.status);
    if (form.value.goal_id) data.append('goal_id', form.value.goal_id);
    if (form.value.note) data.append('note', form.value.note);
    if (form.value.output_link) data.append('output_link', form.value.output_link);
    if (form.value.rating) data.append('rating', form.value.rating);
    if (imageFile.value) data.append('image', imageFile.value);
    if (removeImage.value) data.append('remove_image', '1');

    if (isEditing.value) {
        // Inertia PUT không hỗ trợ FormData → dùng POST + _method spoofing
        data.append('_method', 'PUT');
        router.post(route('tracking-output.update', props.output.id), data, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => emit('close'),
            onError: (errors) => { formErrors.value = errors; },
        });
    } else {
        router.post(route('tracking-output.store'), data, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => emit('close'),
            onError: (errors) => { formErrors.value = errors; },
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
                                :title="cat.tooltip"
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

                    <!-- Image Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Image
                        </label>

                        <!-- Current / Preview image -->
                        <div v-if="currentImageUrl" class="mb-2 relative inline-block">
                            <img
                                :src="currentImageUrl"
                                class="h-32 w-auto rounded-lg object-cover border border-gray-200 dark:border-gray-600"
                                alt="Output image"
                            />
                            <button
                                type="button"
                                @click="handleRemoveImage"
                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600"
                                title="Remove image"
                            >
                                ✕
                            </button>
                        </div>

                        <input
                            type="file"
                            accept="image/*"
                            @change="onImageChange"
                            class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/30 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 cursor-pointer"
                        />
                        <p class="text-xs text-gray-400 mt-1">Max 5MB. JPG, PNG, WebP, GIF...</p>
                    </div>

                    <!-- Validation errors -->
                    <div v-if="Object.keys(formErrors).length > 0" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                        <p v-for="(msg, field) in formErrors" :key="field" class="text-sm text-red-600 dark:text-red-400">
                            {{ msg }}
                        </p>
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
