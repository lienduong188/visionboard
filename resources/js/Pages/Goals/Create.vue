<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    categories: Array,
});

const form = useForm({
    category_id: '',
    title: '',
    description: '',
    cover_image: '',
    target_value: '',
    unit: '',
    start_date: new Date().toISOString().split('T')[0],
    target_date: '',
    priority: 'medium',
});

const submit = () => {
    form.post(route('goals.store'));
};
</script>

<template>
    <Head title="Create Goal" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('goals.index')"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    ← Back
                </Link>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    Create New Goal
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
                                <option value="">Select a category</option>
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
                                placeholder="e.g., Run a full marathon"
                            />
                            <InputError :message="form.errors.title" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Describe your goal..."
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <!-- Cover Image URL -->
                        <div>
                            <InputLabel for="cover_image" value="Cover Image URL (optional)" />
                            <TextInput
                                id="cover_image"
                                v-model="form.cover_image"
                                type="url"
                                class="mt-1 block w-full"
                                placeholder="https://example.com/image.jpg"
                            />
                            <InputError :message="form.errors.cover_image" class="mt-2" />
                            <div
                                v-if="form.cover_image"
                                class="mt-2 h-32 bg-cover bg-center rounded-lg"
                                :style="{ backgroundImage: `url(${form.cover_image})` }"
                            ></div>
                        </div>

                        <!-- Target Value & Unit -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="target_value" value="Target Value (optional)" />
                                <TextInput
                                    id="target_value"
                                    v-model="form.target_value"
                                    type="number"
                                    step="any"
                                    class="mt-1 block w-full"
                                    placeholder="e.g., 2000000"
                                />
                                <InputError :message="form.errors.target_value" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="unit" value="Unit (optional)" />
                                <TextInput
                                    id="unit"
                                    v-model="form.unit"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="e.g., 円, km, books"
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

                        <!-- Submit -->
                        <div class="flex justify-end gap-4">
                            <Link
                                :href="route('goals.index')"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                Create Goal
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
