<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    containerWidth: {
        type: Number,
        default: 900,
    },
    containerHeight: {
        type: Number,
        default: 900,
    },
});

// 100 positive words for waterfall effect
const systemPositiveWords = [
    // Success & Victory
    { word: 'Success', color: '#10B981' },
    { word: 'Victory', color: '#059669' },
    { word: 'Glory', color: '#047857' },
    { word: 'Champion', color: '#065F46' },
    { word: 'Excellence', color: '#10B981' },
    { word: 'Complete', color: '#34D399' },
    { word: 'Achieve', color: '#6EE7B7' },
    { word: 'Wonderful', color: '#059669' },
    { word: 'Extraordinary', color: '#047857' },
    { word: 'Greatness', color: '#065F46' },

    // Growth & Progress
    { word: 'Develop', color: '#8B5CF6' },
    { word: 'Progress', color: '#7C3AED' },
    { word: 'Change', color: '#6D28D9' },
    { word: 'Breakthrough', color: '#5B21B6' },
    { word: 'Improve', color: '#8B5CF6' },
    { word: 'Blossom', color: '#A78BFA' },
    { word: 'Flourish', color: '#C4B5FD' },
    { word: 'Expand', color: '#7C3AED' },
    { word: 'Mature', color: '#6D28D9' },
    { word: 'Rise', color: '#5B21B6' },

    // Motivation & Energy
    { word: 'Drive', color: '#F59E0B' },
    { word: 'Energy', color: '#D97706' },
    { word: 'Passion', color: '#B45309' },
    { word: 'Enthusiasm', color: '#92400E' },
    { word: 'Aspiration', color: '#F59E0B' },
    { word: 'Determination', color: '#FBBF24' },
    { word: 'Persistence', color: '#FCD34D' },
    { word: 'Dedication', color: '#D97706' },
    { word: 'Commitment', color: '#B45309' },
    { word: 'Focus', color: '#92400E' },

    // Joy & Happiness
    { word: 'Happiness', color: '#EC4899' },
    { word: 'Joyful', color: '#DB2777' },
    { word: 'Peaceful', color: '#BE185D' },
    { word: 'Delight', color: '#9D174D' },
    { word: 'Radiant', color: '#EC4899' },
    { word: 'Bright', color: '#F472B6' },
    { word: 'Vibrant', color: '#F9A8D4' },
    { word: 'Uplifted', color: '#DB2777' },
    { word: 'Elated', color: '#BE185D' },
    { word: 'Bliss', color: '#9D174D' },

    // Strength & Power
    { word: 'Strong', color: '#EF4444' },
    { word: 'Power', color: '#DC2626' },
    { word: 'Mighty', color: '#B91C1C' },
    { word: 'Fierce', color: '#991B1B' },
    { word: 'Bold', color: '#EF4444' },
    { word: 'Brave', color: '#F87171' },
    { word: 'Fearless', color: '#FCA5A5' },
    { word: 'Courageous', color: '#DC2626' },
    { word: 'Resilient', color: '#B91C1C' },
    { word: 'Unbroken', color: '#991B1B' },

    // Peace & Serenity
    { word: 'Serenity', color: '#06B6D4' },
    { word: 'Tranquil', color: '#0891B2' },
    { word: 'Stillness', color: '#0E7490' },
    { word: 'Harmony', color: '#155E75' },
    { word: 'Balance', color: '#06B6D4' },
    { word: 'Composure', color: '#22D3EE' },
    { word: 'Mindful', color: '#67E8F9' },
    { word: 'Calm', color: '#0891B2' },
    { word: 'Ease', color: '#0E7490' },
    { word: 'Gentle', color: '#155E75' },

    // Love & Connection
    { word: 'Love', color: '#F43F5E' },
    { word: 'Kindness', color: '#E11D48' },
    { word: 'Compassion', color: '#BE123C' },
    { word: 'Care', color: '#9F1239' },
    { word: 'Warmth', color: '#F43F5E' },
    { word: 'Tenderness', color: '#FB7185' },
    { word: 'Goodness', color: '#FDA4AF' },
    { word: 'Empathy', color: '#E11D48' },
    { word: 'Unity', color: '#BE123C' },
    { word: 'Bond', color: '#9F1239' },

    // Creativity & Inspiration
    { word: 'Creative', color: '#6366F1' },
    { word: 'Inspired', color: '#4F46E5' },
    { word: 'Imagine', color: '#4338CA' },
    { word: 'Dream', color: '#3730A3' },
    { word: 'Innovate', color: '#6366F1' },
    { word: 'Brilliant', color: '#818CF8' },
    { word: 'Talented', color: '#A5B4FC' },
    { word: 'Vision', color: '#4F46E5' },
    { word: 'Wonder', color: '#4338CA' },
    { word: 'Magic', color: '#3730A3' },

    // Freedom & Adventure
    { word: 'Freedom', color: '#14B8A6' },
    { word: 'Adventure', color: '#0D9488' },
    { word: 'Explore', color: '#0F766E' },
    { word: 'Discover', color: '#115E59' },
    { word: 'Journey', color: '#14B8A6' },
    { word: 'Wander', color: '#2DD4BF' },
    { word: 'Roam', color: '#5EEAD4' },
    { word: 'Soar', color: '#0D9488' },
    { word: 'Reach', color: '#0F766E' },
    { word: 'Ascend', color: '#115E59' },

    // Wisdom & Knowledge
    { word: 'Wisdom', color: '#84CC16' },
    { word: 'Knowledge', color: '#65A30D' },
    { word: 'Learn', color: '#4D7C0F' },
    { word: 'Grow', color: '#3F6212' },
    { word: 'Illuminate', color: '#84CC16' },
    { word: 'Insight', color: '#A3E635' },
    { word: 'Clarity', color: '#BEF264' },
    { word: 'Truth', color: '#65A30D' },
    { word: 'Understand', color: '#4D7C0F' },
    { word: 'Aware', color: '#3F6212' },
];

const fallingWords = ref([]);
let animationId = null;
let lastSpawnTime = 0;
const spawnInterval = 1200; // Spawn every 1.2 seconds
const maxWords = 20; // Max words on screen

// Spawn a new falling word
const spawnWord = () => {
    if (systemPositiveWords.length === 0) return;
    if (fallingWords.value.length >= maxWords) return;

    const randomWord = systemPositiveWords[Math.floor(Math.random() * systemPositiveWords.length)];
    const word = {
        id: Date.now() + Math.random(),
        ...randomWord,
        x: Math.random() * (props.containerWidth - 100) + 50,
        y: -50,
        speed: 0.3 + Math.random() * 0.7,
        opacity: 0.4 + Math.random() * 0.6,
        scale: 0.7 + Math.random() * 0.5,
        rotation: (Math.random() - 0.5) * 20,
    };

    fallingWords.value.push(word);
};

// Animation loop
const animate = (timestamp) => {
    // Spawn new words periodically
    if (timestamp - lastSpawnTime > spawnInterval) {
        spawnWord();
        lastSpawnTime = timestamp;
    }

    // Update positions
    fallingWords.value = fallingWords.value
        .map(word => ({
            ...word,
            y: word.y + word.speed,
            opacity: word.y > props.containerHeight * 0.7
                ? Math.max(0, word.opacity - 0.02)
                : word.opacity,
        }))
        .filter(word => word.y < props.containerHeight + 50 && word.opacity > 0);

    animationId = requestAnimationFrame(animate);
};

onMounted(() => {
    // Spawn initial words scattered across the screen
    for (let i = 0; i < 8; i++) {
        setTimeout(() => {
            const randomWord = systemPositiveWords[Math.floor(Math.random() * systemPositiveWords.length)];
            fallingWords.value.push({
                id: Date.now() + Math.random(),
                ...randomWord,
                x: Math.random() * (props.containerWidth - 100) + 50,
                y: Math.random() * props.containerHeight * 0.5,
                speed: 0.3 + Math.random() * 0.7,
                opacity: 0.4 + Math.random() * 0.6,
                scale: 0.7 + Math.random() * 0.5,
                rotation: (Math.random() - 0.5) * 20,
            });
        }, i * 150);
    }
    animationId = requestAnimationFrame(animate);
});

onUnmounted(() => {
    if (animationId) {
        cancelAnimationFrame(animationId);
    }
});
</script>

<template>
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-20">
        <div
            v-for="word in fallingWords"
            :key="word.id"
            class="absolute waterfall-word"
            :style="{
                left: word.x + 'px',
                top: word.y + 'px',
                opacity: word.opacity,
                transform: `scale(${word.scale}) rotate(${word.rotation}deg)`,
            }"
        >
            <span
                class="px-4 py-2 rounded-full font-bold text-base whitespace-nowrap shadow-md"
                :style="{
                    backgroundColor: word.color + '40',
                    color: word.color,
                    textShadow: `0 1px 2px rgba(0,0,0,0.3)`,
                    border: `1px solid ${word.color}50`,
                    backdropFilter: 'blur(4px)',
                }"
            >
                {{ word.word }}
            </span>
        </div>
    </div>
</template>

<style scoped>
.waterfall-word {
    transition: opacity 0.3s ease;
}
</style>
