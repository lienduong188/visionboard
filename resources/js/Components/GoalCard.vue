<script setup>
import { computed } from 'vue';
import { formatNumber } from '@/utils/formatNumber';

const props = defineProps({
    goal: {
        type: Object,
        required: true,
    },
    showCategory: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['click']);

const progressColor = computed(() => {
    if (props.goal.progress >= 100) return 'bg-green-500';
    if (props.goal.progress >= 75) return 'bg-emerald-500';
    if (props.goal.progress >= 50) return 'bg-yellow-500';
    if (props.goal.progress >= 25) return 'bg-orange-500';
    return 'bg-red-500';
});

const statusBadge = computed(() => {
    const badges = {
        'not_started': { text: 'Not Started', class: 'bg-gray-500' },
        'in_progress': { text: 'In Progress', class: 'bg-blue-500' },
        'completed': { text: 'Completed', class: 'bg-green-500' },
        'paused': { text: 'Paused', class: 'bg-yellow-500' },
        'cancelled': { text: 'Cancelled', class: 'bg-red-500' },
    };
    return badges[props.goal.status] || badges['not_started'];
});

const priorityIcon = computed(() => {
    const icons = {
        'high': '🔥',
        'medium': '⭐',
        'low': '📌',
    };
    return icons[props.goal.priority] || '';
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const daysRemaining = computed(() => {
    if (!props.goal.target_date) return null;
    const target = new Date(props.goal.target_date);
    const today = new Date();
    const diff = Math.ceil((target - today) / (1000 * 60 * 60 * 24));
    return diff;
});
</script>

<template>
    <div
        @click="emit('click', goal)"
        class="block group cursor-pointer"
    >
        <div
            class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"
            :style="{ borderTop: `4px solid ${goal.category?.color || '#6366F1'}` }"
        >
            <!-- Cover Image -->
            <div
                v-if="goal.cover_image"
                class="h-40 bg-cover bg-center"
                :style="{ backgroundImage: `url(${goal.cover_image})` }"
            >
                <div class="h-full w-full bg-gradient-to-t from-black/50 to-transparent"></div>
            </div>
            <div
                v-else
                class="h-32 flex items-center justify-center text-6xl"
                :style="{ backgroundColor: `${goal.category?.color}20` }"
            >
                {{ goal.category?.icon || '🎯' }}
            </div>

            <!-- Pin Badge -->
            <div
                v-if="goal.is_pinned"
                class="absolute top-2 right-2 bg-yellow-400 text-yellow-900 rounded-full p-1"
            >
                📍
            </div>

            <!-- Content -->
            <div class="p-4">
                <!-- Category & Priority -->
                <div class="flex items-center justify-between mb-2">
                    <span
                        v-if="showCategory && goal.category"
                        class="text-xs font-medium px-2 py-1 rounded-full"
                        :style="{
                            backgroundColor: `${goal.category.color}20`,
                            color: goal.category.color,
                        }"
                    >
                        {{ goal.category.icon }} {{ goal.category.name }}
                    </span>
                    <span class="text-lg">{{ priorityIcon }}</span>
                </div>

                <!-- Title -->
                <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                    {{ goal.title }}
                </h3>

                <!-- Description -->
                <p
                    v-if="goal.description"
                    class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2"
                >
                    {{ goal.description }}
                </p>

                <!-- Progress Bar -->
                <div class="mb-3">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600 dark:text-gray-400">Progress</span>
                        <span class="font-bold" :class="goal.progress >= 100 ? 'text-green-500' : 'text-gray-900 dark:text-white'">
                            {{ goal.progress }}%
                        </span>
                    </div>
                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="progressColor"
                            :style="{ width: `${goal.progress}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Value Progress (if has target) -->
                <div
                    v-if="goal.target_value && goal.unit"
                    class="text-sm text-gray-600 dark:text-gray-400 mb-3"
                >
                    <span class="font-semibold text-gray-900 dark:text-white">
                        {{ formatNumber(goal.current_value) }}
                    </span>
                    /
                    <span>{{ formatNumber(goal.target_value) }} {{ goal.unit }}</span>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                    <!-- Status Badge -->
                    <span
                        class="text-xs font-medium px-2 py-1 rounded-full text-white"
                        :class="statusBadge.class"
                    >
                        {{ statusBadge.text }}
                    </span>

                    <!-- Target Date -->
                    <div
                        v-if="goal.target_date"
                        class="text-xs text-gray-500 dark:text-gray-400"
                    >
                        <span v-if="daysRemaining !== null">
                            <span
                                v-if="daysRemaining < 0"
                                class="text-red-500 font-medium"
                            >
                                {{ Math.abs(daysRemaining) }} days overdue
                            </span>
                            <span
                                v-else-if="daysRemaining === 0"
                                class="text-yellow-500 font-medium"
                            >
                                Due today!
                            </span>
                            <span
                                v-else-if="daysRemaining <= 7"
                                class="text-orange-500 font-medium"
                            >
                                {{ daysRemaining }} days left
                            </span>
                            <span v-else>
                                {{ formatDate(goal.target_date) }}
                            </span>
                        </span>
                    </div>
                </div>

                <!-- Milestones Preview -->
                <div
                    v-if="goal.milestones?.length"
                    class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700"
                >
                    <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                        <span>📋</span>
                        <span>
                            {{ goal.milestones.filter(m => m.is_completed).length }}/{{ goal.milestones.length }} milestones
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
