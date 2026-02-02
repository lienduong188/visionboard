<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    setting: Object,
    days: Array,
});

const form = useForm({
    weekly_enabled: props.setting.weekly_enabled || false,
    monthly_enabled: props.setting.monthly_enabled || false,
    weekly_day: props.setting.weekly_day || 1,
    monthly_day: props.setting.monthly_day || 1,
    send_time: props.setting.send_time ? props.setting.send_time.substring(0, 5) : '09:00',
});

const monthDays = Array.from({ length: 28 }, (_, i) => ({
    value: i + 1,
    label: `${i + 1}${getOrdinalSuffix(i + 1)}`,
}));

function getOrdinalSuffix(n) {
    const s = ['th', 'st', 'nd', 'rd'];
    const v = n % 100;
    return s[(v - 20) % 10] || s[v] || s[0];
}

const submit = () => {
    form.put(route('settings.reviews.update'));
};
</script>

<template>
    <Head title="Review Settings" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 sm:mb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                                Review Settings
                            </h1>
                            <p class="mt-1 text-gray-600 dark:text-gray-400">
                                Configure your weekly and monthly email reviews
                            </p>
                        </div>
                        <Link
                            :href="route('goals.index')"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-transparent rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Back to Goals
                        </Link>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Weekly Review Section -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                    <span class="text-2xl mr-2">📅</span>
                                    Weekly Review
                                </h2>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Get a summary of your progress every week
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.weekly_enabled"
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div v-if="form.weekly_enabled" class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Send on
                                </label>
                                <select
                                    v-model="form.weekly_day"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option v-for="day in days" :key="day.value" :value="day.value">
                                        {{ day.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg p-4">
                            <h4 class="font-medium text-indigo-900 dark:text-indigo-200 mb-2">Weekly Review Includes:</h4>
                            <ul class="text-sm text-indigo-800 dark:text-indigo-300 space-y-1">
                                <li>• Goals completed this week</li>
                                <li>• Goals with progress updates</li>
                                <li>• Goals needing attention</li>
                                <li>• Upcoming deadlines</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Monthly Review Section -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                    <span class="text-2xl mr-2">📊</span>
                                    Monthly Review
                                </h2>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Get a comprehensive monthly summary
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.monthly_enabled"
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div v-if="form.monthly_enabled" class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Send on day
                                </label>
                                <select
                                    v-model="form.monthly_day"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option v-for="day in monthDays" :key="day.value" :value="day.value">
                                        {{ day.label }} of each month
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 bg-green-50 dark:bg-green-900/30 rounded-lg p-4">
                            <h4 class="font-medium text-green-900 dark:text-green-200 mb-2">Monthly Review Includes:</h4>
                            <ul class="text-sm text-green-800 dark:text-green-300 space-y-1">
                                <li>• Goals completed this month</li>
                                <li>• Progress comparison with last month</li>
                                <li>• Best performing category</li>
                                <li>• Goals needing attention</li>
                                <li>• Overall statistics</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Send Time Section -->
                    <div v-if="form.weekly_enabled || form.monthly_enabled" class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center mb-4">
                            <span class="text-2xl mr-2">⏰</span>
                            Send Time
                        </h2>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                What time would you like to receive reviews?
                            </label>
                            <input
                                type="time"
                                v-model="form.send_time"
                                class="w-full sm:w-auto px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Reviews will be sent at this time on the scheduled day (server timezone)
                            </p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>Save Settings</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
