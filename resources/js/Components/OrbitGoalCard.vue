<script setup>
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    goal: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    },
    total: {
        type: Number,
        required: true,
    },
    radius: {
        type: Number,
        default: 280,
    },
    isMobile: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['click']);

const isHovered = ref(false);

// Calculate position on orbit
const angle = computed(() => {
    return (props.index / props.total) * 360 - 90; // Start from top
});

// Dynamic radius based on scale - higher scale = further out
const dynamicRadius = computed(() => {
    const scale = props.goal.orbit_scale || 3;
    // Adjust base radius based on prop radius (for responsive)
    const radiusMultiplier = props.radius / 280;
    const baseRadius = 200 * radiusMultiplier;
    const increment = 40 * radiusMultiplier;
    return baseRadius + (scale - 1) * increment;
});

const position = computed(() => {
    const rad = (angle.value * Math.PI) / 180;
    return {
        x: Math.cos(rad) * dynamicRadius.value,
        y: Math.sin(rad) * dynamicRadius.value,
    };
});

// Scale size based on orbit_scale (1-5) - more dramatic difference
const cardSize = computed(() => {
    const scale = props.goal.orbit_scale || 3;
    // Desktop sizes: Scale 1: 70px, Scale 2: 100px, Scale 3: 130px, Scale 4: 170px, Scale 5: 220px
    // Mobile sizes: Scale 1: 45px, Scale 2: 55px, Scale 3: 65px, Scale 4: 80px, Scale 5: 95px
    const desktopSizes = [70, 100, 130, 170, 220];
    const mobileSizes = [45, 55, 65, 80, 95];
    const sizes = props.isMobile ? mobileSizes : desktopSizes;
    return sizes[scale - 1] || (props.isMobile ? 65 : 130);
});

const progressColor = computed(() => {
    if (props.goal.progress >= 100) return 'bg-green-500';
    if (props.goal.progress >= 75) return 'bg-emerald-500';
    if (props.goal.progress >= 50) return 'bg-yellow-500';
    if (props.goal.progress >= 25) return 'bg-orange-500';
    return 'bg-red-500';
});

const updateScale = (newScale) => {
    router.patch(route('goals.orbit-scale', props.goal.id), {
        orbit_scale: newScale,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <div
        class="orbit-card absolute transition-all duration-300 cursor-pointer"
        :style="{
            transform: `translate(${position.x}px, ${position.y}px) translate(-50%, -50%)`,
            width: `${cardSize}px`,
            zIndex: isHovered ? 50 : 10,
            paddingBottom: isHovered ? '50px' : '0',
        }"
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
    >
        <div class="card-inner">
            <div
                @click="emit('click', goal)"
                class="block cursor-pointer"
            >
                <div
                    class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-lg transition-all duration-300"
                    :class="{ 'shadow-2xl scale-110': isHovered }"
                    :style="{ borderBottom: `3px solid ${goal.category?.color || '#6366F1'}` }"
                >
                <!-- Cover Image or Icon -->
                <div
                    class="relative overflow-hidden"
                    :style="{ height: `${cardSize * 0.6}px` }"
                >
                    <div
                        v-if="goal.cover_image_url"
                        class="w-full h-full bg-cover bg-center"
                        :style="{ backgroundImage: `url(${goal.cover_image_url})` }"
                    >
                        <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                    </div>
                    <div
                        v-else
                        class="w-full h-full flex items-center justify-center"
                        :style="{ backgroundColor: `${goal.category?.color}20` }"
                    >
                        <span :style="{ fontSize: `${cardSize * 0.35}px` }">
                            {{ goal.category?.icon || '🎯' }}
                        </span>
                    </div>

                    <!-- Progress Badge -->
                    <div
                        class="absolute top-1 right-1 px-1.5 py-0.5 rounded-full text-white text-xs font-bold"
                        :class="progressColor"
                    >
                        {{ goal.progress }}%
                    </div>

                    <!-- Pin Badge -->
                    <div
                        v-if="goal.is_pinned"
                        class="absolute top-1 left-1 text-sm"
                    >
                        📍
                    </div>
                </div>

                <!-- Content -->
                <div class="p-2">
                    <!-- Title -->
                    <h4
                        class="font-semibold text-gray-900 dark:text-white leading-tight line-clamp-2"
                        :style="{ fontSize: cardSize > 140 ? '0.875rem' : '0.75rem' }"
                    >
                        {{ goal.title }}
                    </h4>

                    <!-- Progress Bar -->
                    <div class="mt-1.5 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="progressColor"
                            :style="{ width: `${goal.progress}%` }"
                        ></div>
                    </div>
                </div>
                </div>
            </div>

            <!-- Scale Slider (shows on hover) - hidden on mobile -->
            <div
                v-if="isHovered && !isMobile"
                class="absolute -bottom-12 left-1/2 transform -translate-x-1/2 bg-white dark:bg-gray-800 rounded-lg shadow-xl p-2 z-50"
                @click.prevent.stop
            >
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Size:</span>
                    <input
                        type="range"
                        min="1"
                        max="5"
                        :value="goal.orbit_scale || 3"
                        class="w-20 h-1 accent-indigo-600 cursor-pointer"
                        @input="updateScale(parseInt($event.target.value))"
                        @click.stop
                    />
                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                        {{ goal.orbit_scale || 3 }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.orbit-card {
    will-change: transform;
    cursor: pointer;
}

.orbit-card:hover {
    cursor: pointer;
}

/* Counter-rotate to keep card upright while orbiting */
.orbit-card .card-inner {
    animation: counter-rotate 60s linear infinite;
    cursor: pointer;
}

.orbit-card:hover .card-inner {
    animation-play-state: paused;
}

@keyframes counter-rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(-360deg);
    }
}

/* Ensure slider has correct cursor */
input[type="range"] {
    cursor: pointer;
}
</style>
