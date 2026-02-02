<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CategoryBarChart from '@/Components/Charts/CategoryBarChart.vue';
import CategoryRadarChart from '@/Components/Charts/CategoryRadarChart.vue';
import CompletionTrendChart from '@/Components/Charts/CompletionTrendChart.vue';
import MonthlyStatsChart from '@/Components/Charts/MonthlyStatsChart.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    completionTrend: Object,
    categoryComparison: Array,
    monthlyCompletion: Array,
    detailedStats: Object,
    categories: Array,
});

const categoryChartType = ref('bar'); // 'bar' or 'radar'
</script>

<template>
    <Head title="Phân tích" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 sm:mb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                                Phân tích
                            </h1>
                            <p class="mt-1 text-gray-600 dark:text-gray-400">
                                Theo dõi tiến độ và hiệu suất mục tiêu của bạn
                            </p>
                        </div>
                        <Link
                            :href="route('goals.index')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Quay lại Goals
                        </Link>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 shadow">
                        <div class="flex items-center">
                            <div class="p-2 sm:p-3 rounded-full bg-indigo-100 dark:bg-indigo-900">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div class="ml-3 sm:ml-4">
                                <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">Tổng mục tiêu</p>
                                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ detailedStats.totalGoals }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 shadow">
                        <div class="flex items-center">
                            <div class="p-2 sm:p-3 rounded-full bg-green-100 dark:bg-green-900">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3 sm:ml-4">
                                <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">Hoàn thành</p>
                                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ detailedStats.completedGoals }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 shadow">
                        <div class="flex items-center">
                            <div class="p-2 sm:p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="ml-3 sm:ml-4">
                                <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">Tỷ lệ hoàn thành</p>
                                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ detailedStats.completionRate }}%</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 shadow">
                        <div class="flex items-center">
                            <div class="p-2 sm:p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div class="ml-3 sm:ml-4">
                                <p class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">Tiến độ TB</p>
                                <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ detailedStats.avgProgress }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alert Cards Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div v-if="detailedStats.upcomingDeadlines > 0" class="bg-amber-50 dark:bg-amber-900/30 rounded-xl p-4 border border-amber-200 dark:border-amber-800">
                        <div class="flex items-center">
                            <span class="text-2xl mr-3">&#9203;</span>
                            <div>
                                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Sắp đến hạn</p>
                                <p class="text-lg font-bold text-amber-900 dark:text-amber-100">{{ detailedStats.upcomingDeadlines }} mục tiêu</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="detailedStats.overdueGoals > 0" class="bg-red-50 dark:bg-red-900/30 rounded-xl p-4 border border-red-200 dark:border-red-800">
                        <div class="flex items-center">
                            <span class="text-2xl mr-3">&#9888;</span>
                            <div>
                                <p class="text-sm font-medium text-red-800 dark:text-red-200">Quá hạn</p>
                                <p class="text-lg font-bold text-red-900 dark:text-red-100">{{ detailedStats.overdueGoals }} mục tiêu</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="detailedStats.bestCategory" class="bg-green-50 dark:bg-green-900/30 rounded-xl p-4 border border-green-200 dark:border-green-800">
                        <div class="flex items-center">
                            <span class="text-2xl mr-3">{{ detailedStats.bestCategory.icon }}</span>
                            <div>
                                <p class="text-sm font-medium text-green-800 dark:text-green-200">Danh mục tốt nhất</p>
                                <p class="text-lg font-bold text-green-900 dark:text-green-100">{{ detailedStats.bestCategory.avgProgress }}%</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="detailedStats.worstCategory && detailedStats.worstCategory.avgProgress < detailedStats.avgProgress" class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center">
                            <span class="text-2xl mr-3">{{ detailedStats.worstCategory.icon }}</span>
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Cần chú ý</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ detailedStats.worstCategory.avgProgress }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Completion Trend Chart -->
                    <CompletionTrendChart :data="completionTrend" />

                    <!-- Monthly Stats Chart -->
                    <MonthlyStatsChart :data="monthlyCompletion" />
                </div>

                <!-- Category Comparison -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            So sánh danh mục
                        </h3>
                        <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-0.5">
                            <button
                                @click="categoryChartType = 'bar'"
                                class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                                :class="categoryChartType === 'bar'
                                    ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                            >
                                Bar
                            </button>
                            <button
                                @click="categoryChartType = 'radar'"
                                class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                                :class="categoryChartType === 'radar'
                                    ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                            >
                                Radar
                            </button>
                        </div>
                    </div>

                    <CategoryBarChart
                        v-if="categoryChartType === 'bar'"
                        :data="categoryComparison"
                        :height="350"
                    />
                    <CategoryRadarChart
                        v-else
                        :data="categoryComparison"
                        :height="350"
                    />
                </div>

                <!-- Category Details Table -->
                <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Chi tiết danh mục
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Danh mục</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Mục tiêu</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hoàn thành</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tiến độ TB</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Thanh tiến độ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="category in categoryComparison"
                                    :key="category.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="text-xl mr-2">{{ category.icon }}</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ category.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-white">
                                        {{ category.goalCount }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-white">
                                        {{ category.completedCount }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="category.avgProgress >= 75
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                : category.avgProgress >= 50
                                                    ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                                                    : category.avgProgress >= 25
                                                        ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
                                                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                                        >
                                            {{ category.avgProgress }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                            <div
                                                class="h-2.5 rounded-full transition-all duration-300"
                                                :style="{ width: `${category.avgProgress}%`, backgroundColor: category.color }"
                                            ></div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
