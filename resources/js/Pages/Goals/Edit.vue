<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatForInput, parseFromInput } from '@/utils/formatNumber';

const props = defineProps({
    goal: Object,
    categories: Array,
    coreGoalsCount: Number,
});

const form = useForm({
    category_id: props.goal.category_id,
    title: props.goal.title,
    description: props.goal.description || '',
    slogan: props.goal.slogan || '',
    cover_image: null,
    remove_cover_image: false,
    target_value: props.goal.target_value || '',
    current_value: props.goal.current_value || 0,
    unit: props.goal.unit || '',
    start_date: props.goal.start_date || '2026-01-01',
    target_date: props.goal.target_date || '2026-12-31',
    priority: props.goal.priority,
    status: props.goal.status,
    is_pinned: props.goal.is_pinned,
    is_core_goal: props.goal.is_core_goal || false,
});

// Can set core goal if: already a core goal OR has less than 3 core goals
const canSetCoreGoal = computed(() => props.goal.is_core_goal || props.coreGoalsCount < 3);

const currentImage = ref(props.goal.cover_image);
const imagePreview = ref(null);
const fileInput = ref(null);

// Display values for formatted number inputs
const displayTargetValue = ref(formatForInput(props.goal.target_value));
const displayCurrentValue = ref(formatForInput(props.goal.current_value));

const onTargetValueBlur = () => {
    form.target_value = parseFromInput(displayTargetValue.value);
    displayTargetValue.value = formatForInput(form.target_value);
};
const onCurrentValueBlur = () => {
    form.current_value = parseFromInput(displayCurrentValue.value);
    displayCurrentValue.value = formatForInput(form.current_value);
};

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.cover_image = file;
        form.remove_cover_image = false;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const removeImage = () => {
    form.cover_image = null;
    form.remove_cover_image = true;
    imagePreview.value = null;
    currentImage.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = () => {
    router.post(route('goals.update', props.goal.id), {
        _method: 'put',
        ...form.data(),
    }, {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="`Edit: ${goal.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('goals.show', goal.id)"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    ← Back
                </Link>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    Edit Goal
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Category -->
                        <div>
                            <InputLabel for="category_id" value="Category" />
                            <select
                                id="category_id"
                                v-model="form.category_id"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.icon }} {{ category.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.category_id" class="mt-2" />
                        </div>

                        <!-- Title -->
                        <div>
                            <InputLabel for="title" value="Title" />
                            <TextInput
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.title" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Describe using: State + Vision + Action"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" title="Mô tả theo công thức: Trạng thái + Hình ảnh + Hành động">
                                💡 Tip: Use <strong>State + Vision + Action</strong> formula to visualize your goal clearly
                            </p>
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <!-- Slogan -->
                        <div>
                            <InputLabel for="slogan" value="Slogan" />
                            <TextInput
                                id="slogan"
                                v-model="form.slogan"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="e.g., Run to live, live to run!"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" title="Câu dẫn đường truyền cảm hứng">
                                ✨ A short inspiring phrase for this goal
                            </p>
                            <InputError :message="form.errors.slogan" class="mt-2" />
                        </div>

                        <!-- Cover Image Upload -->
                        <div>
                            <InputLabel for="cover_image" value="Cover Image" />
                            <div class="mt-1">
                                <input
                                    ref="fileInput"
                                    id="cover_image"
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                    @change="handleImageChange"
                                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-lg file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-indigo-50 file:text-indigo-700
                                        hover:file:bg-indigo-100
                                        dark:file:bg-indigo-900 dark:file:text-indigo-300
                                        dark:hover:file:bg-indigo-800"
                                />
                            </div>
                            <InputError :message="form.errors.cover_image" class="mt-2" />
                            <!-- Show current image or new preview -->
                            <div v-if="imagePreview || currentImage" class="mt-3 relative">
                                <img
                                    :src="imagePreview || currentImage"
                                    alt="Cover"
                                    class="h-32 w-full object-cover rounded-lg"
                                />
                                <button
                                    type="button"
                                    @click="removeImage"
                                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Max 2MB. Formats: JPEG, PNG, GIF, WebP
                            </p>
                        </div>

                        <!-- Target Value & Current Value & Unit -->
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <InputLabel for="target_value" value="Target Value" />
                                <TextInput
                                    id="target_value"
                                    v-model="displayTargetValue"
                                    type="text"
                                    inputmode="decimal"
                                    class="mt-1 block w-full"
                                    @blur="onTargetValueBlur"
                                />
                                <InputError :message="form.errors.target_value" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="current_value" value="Current Value" />
                                <TextInput
                                    id="current_value"
                                    v-model="displayCurrentValue"
                                    type="text"
                                    inputmode="decimal"
                                    class="mt-1 block w-full"
                                    @blur="onCurrentValueBlur"
                                />
                                <InputError :message="form.errors.current_value" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="unit" value="Unit" />
                                <TextInput
                                    id="unit"
                                    v-model="form.unit"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.unit" class="mt-2" />
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="start_date" value="Start Date" />
                                <TextInput
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.start_date" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="target_date" value="Target Date" />
                                <TextInput
                                    id="target_date"
                                    v-model="form.target_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.target_date" class="mt-2" />
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <InputLabel for="status" value="Status" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="not_started">Not Started</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="paused">Paused</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-2" />
                        </div>

                        <!-- Priority -->
                        <div>
                            <InputLabel value="Priority" />
                            <div class="mt-2 flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        v-model="form.priority"
                                        value="low"
                                        class="text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-gray-700 dark:text-gray-300">📌 Low</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        v-model="form.priority"
                                        value="medium"
                                        class="text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-gray-700 dark:text-gray-300">⭐ Medium</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        v-model="form.priority"
                                        value="high"
                                        class="text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-gray-700 dark:text-gray-300">🔥 High</span>
                                </label>
                            </div>
                            <InputError :message="form.errors.priority" class="mt-2" />
                        </div>

                        <!-- Pinned -->
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.is_pinned"
                                    class="rounded text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="text-gray-700 dark:text-gray-300">📍 Pin this goal</span>
                            </label>
                        </div>

                        <!-- Core Goal Toggle -->
                        <div class="p-4 rounded-lg" :class="form.is_core_goal ? 'bg-indigo-50 dark:bg-indigo-900/30 border-2 border-indigo-500' : 'bg-gray-50 dark:bg-gray-700/50'">
                            <label class="flex items-start gap-3 cursor-pointer" :class="{ 'opacity-50': !canSetCoreGoal && !form.is_core_goal }">
                                <input
                                    type="checkbox"
                                    v-model="form.is_core_goal"
                                    :disabled="!canSetCoreGoal && !form.is_core_goal"
                                    class="mt-1 rounded text-indigo-600 focus:ring-indigo-500"
                                />
                                <div>
                                    <span class="font-semibold text-gray-900 dark:text-white">🎯 Core Goal</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" title="Trục trung tâm của Vision Board">
                                        Mark this as one of 3 core goals that orbit your Vision Board.
                                    </p>
                                    <p v-if="!canSetCoreGoal" class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                                        ⚠️ You already have {{ coreGoalsCount + (goal.is_core_goal ? 1 : 0) }}/3 Core Goals. Uncheck another goal to add a new one.
                                    </p>
                                </div>
                            </label>
                            <InputError :message="form.errors.is_core_goal" class="mt-2" />
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end gap-4">
                            <Link
                                :href="route('goals.show', goal.id)"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                Save Changes
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
