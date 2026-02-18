<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';

const props = defineProps({
    goals: {
        type: Array,
        default: () => [],
    },
    words: {
        type: Array,
        default: () => [],
    },
    containerWidth: {
        type: Number,
        default: 900,
    },
    containerHeight: {
        type: Number,
        default: 900,
    },
    isMobile: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['goalClick']);

// All floating objects (both goals and words)
const floatingObjects = ref([]);
let animationId = null;

// Center zone to avoid (where VISION BOARD 2026 text is)
const centerAvoidRadius = 180;

// Seeded random for deterministic positioning
const seededRandom = (seed) => {
    const x = Math.sin(seed * 9301 + 49297) * 233280;
    return x - Math.floor(x);
};

// Get random position avoiding center
const getRandomPosition = (size, index, total) => {
    const padding = size / 2 + 30;
    const centerX = props.containerWidth / 2;
    const centerY = props.containerHeight / 2;

    // Divide container into zones (excluding center)
    // Use grid-based distribution for better spread
    const cols = Math.ceil(Math.sqrt(total));
    const rows = Math.ceil(total / cols);
    const col = index % cols;
    const row = Math.floor(index / cols);

    // Calculate zone boundaries
    const zoneWidth = (props.containerWidth - padding * 2) / cols;
    const zoneHeight = (props.containerHeight - padding * 2) / rows;

    // Deterministic position within zone (seeded by index)
    let x = padding + col * zoneWidth + seededRandom(index * 7 + 1) * zoneWidth;
    let y = padding + row * zoneHeight + seededRandom(index * 7 + 3) * zoneHeight;

    // If too close to center, push outward
    const dx = x - centerX;
    const dy = y - centerY;
    const dist = Math.sqrt(dx * dx + dy * dy);
    if (dist < centerAvoidRadius + size) {
        const angle = Math.atan2(dy, dx);
        const pushDist = centerAvoidRadius + size + 50;
        x = centerX + Math.cos(angle) * pushDist;
        y = centerY + Math.sin(angle) * pushDist;
    }

    // Clamp to bounds
    x = Math.max(padding, Math.min(props.containerWidth - padding, x));
    y = Math.max(padding, Math.min(props.containerHeight - padding, y));

    return { x, y };
};

// Initialize all floating objects
const initializeObjects = () => {
    const allObjects = [];
    const totalCount = props.goals.length + props.words.length;

    // Add goals (place in outer areas) - fixed size for all core goals
    const goalSize = props.isMobile ? 80 : 160;
    props.goals.forEach((goal, index) => {
        const cardSize = goalSize;
        const pos = getRandomPosition(cardSize, index, totalCount);

        allObjects.push({
            ...goal,
            type: 'goal',
            objectId: `goal-${goal.id}`,
            x: pos.x,
            y: pos.y,
            vx: (seededRandom(index * 13 + 5) - 0.5) * 0.8,
            vy: (seededRandom(index * 13 + 7) - 0.5) * 0.8,
            size: cardSize,
        });
    });

    // Add words
    props.words.forEach((word, index) => {
        const pos = getRandomPosition(60, props.goals.length + index, totalCount);

        allObjects.push({
            ...word,
            type: 'word',
            objectId: `word-${word.id}`,
            x: pos.x,
            y: pos.y,
            vx: (seededRandom((props.goals.length + index) * 13 + 5) - 0.5) * 1.0,
            vy: (seededRandom((props.goals.length + index) * 13 + 7) - 0.5) * 1.0,
            size: 60, // Word collision size
        });
    });

    floatingObjects.value = allObjects;
};

// Check if position is in center zone
const isInCenterZone = (x, y, size) => {
    const centerX = props.containerWidth / 2;
    const centerY = props.containerHeight / 2;
    const avoidRadius = centerAvoidRadius + size / 2;

    const dx = x - centerX;
    const dy = y - centerY;
    const distance = Math.sqrt(dx * dx + dy * dy);

    return distance < avoidRadius;
};

// Animation loop
const animate = () => {
    const maxSpeed = 1.5;
    const minSpeed = 0.1;

    // No collision detection - words and goals can overlap freely

    floatingObjects.value = floatingObjects.value.map(obj => {
        // Skip physics for dragged object
        if (obj.objectId === draggingId.value) {
            return obj;
        }

        let { x, y, vx, vy, size } = obj;
        const padding = size / 2 + 40; // Larger padding to keep objects inside

        // Update position
        x += vx;
        y += vy;

        // Bounce off walls
        if (x < padding) {
            x = padding;
            vx = Math.abs(vx) * 0.9;
        }
        if (x > props.containerWidth - padding) {
            x = props.containerWidth - padding;
            vx = -Math.abs(vx) * 0.9;
        }
        if (y < padding) {
            y = padding;
            vy = Math.abs(vy) * 0.9;
        }
        if (y > props.containerHeight - padding) {
            y = props.containerHeight - padding;
            vy = -Math.abs(vy) * 0.9;
        }

        // Check center zone and push away if needed
        if (isInCenterZone(x, y, size)) {
            const centerX = props.containerWidth / 2;
            const centerY = props.containerHeight / 2;
            const dx = x - centerX;
            const dy = y - centerY;
            const distance = Math.sqrt(dx * dx + dy * dy);

            if (distance > 0) {
                const force = 0.4;
                vx += (dx / distance) * force;
                vy += (dy / distance) * force;
            }
        }

        // Clamp speed
        const speed = Math.sqrt(vx * vx + vy * vy);
        if (speed > maxSpeed) {
            vx = (vx / speed) * maxSpeed;
            vy = (vy / speed) * maxSpeed;
        }
        if (speed < minSpeed && speed > 0) {
            vx = (vx / speed) * minSpeed;
            vy = (vy / speed) * minSpeed;
        }

        // Add slight random movement
        vx += (Math.random() - 0.5) * 0.03;
        vy += (Math.random() - 0.5) * 0.03;

        return { ...obj, x, y, vx, vy };
    });

    animationId = requestAnimationFrame(animate);
};

// Watch for changes
watch([() => props.goals, () => props.words], () => {
    initializeObjects();
}, { deep: true });

onMounted(() => {
    initializeObjects();
    animationId = requestAnimationFrame(animate);
});

onUnmounted(() => {
    if (animationId) {
        cancelAnimationFrame(animationId);
    }
});

// Hover state for goals
const hoveredGoalId = ref(null);

// Drag state
const draggingId = ref(null);
const dragOffset = ref({ x: 0, y: 0 });
const lastDragPos = ref({ x: 0, y: 0 });
const dragVelocity = ref({ vx: 0, vy: 0 });
const wasDragged = ref(false); // Track if object was actually moved

const startDrag = (objectId, event) => {
    event.preventDefault();
    draggingId.value = objectId;

    const obj = floatingObjects.value.find(o => o.objectId === objectId);
    if (!obj) return;

    const clientX = event.touches ? event.touches[0].clientX : event.clientX;
    const clientY = event.touches ? event.touches[0].clientY : event.clientY;

    const rect = event.currentTarget.parentElement.getBoundingClientRect();
    dragOffset.value = {
        x: clientX - rect.left - obj.x,
        y: clientY - rect.top - obj.y,
    };
    lastDragPos.value = { x: clientX, y: clientY };
    dragVelocity.value = { vx: 0, vy: 0 };
    wasDragged.value = false; // Reset drag flag

    // Add global listeners
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchmove', onDrag, { passive: false });
    document.addEventListener('touchend', endDrag);
};

const onDrag = (event) => {
    if (!draggingId.value) return;
    event.preventDefault();

    const clientX = event.touches ? event.touches[0].clientX : event.clientX;
    const clientY = event.touches ? event.touches[0].clientY : event.clientY;

    // Check if moved significantly (threshold: 5px)
    const dx = clientX - lastDragPos.value.x;
    const dy = clientY - lastDragPos.value.y;
    if (Math.abs(dx) > 5 || Math.abs(dy) > 5) {
        wasDragged.value = true;
    }

    // Calculate velocity from movement
    dragVelocity.value = {
        vx: dx * 0.3,
        vy: dy * 0.3,
    };
    lastDragPos.value = { x: clientX, y: clientY };

    // Update object position
    floatingObjects.value = floatingObjects.value.map(obj => {
        if (obj.objectId !== draggingId.value) return obj;

        const container = document.querySelector('.floating-container');
        if (!container) return obj;

        const rect = container.getBoundingClientRect();
        let newX = clientX - rect.left - dragOffset.value.x;
        let newY = clientY - rect.top - dragOffset.value.y;

        // Clamp to bounds
        const padding = obj.size / 2 + 20;
        newX = Math.max(padding, Math.min(props.containerWidth - padding, newX));
        newY = Math.max(padding, Math.min(props.containerHeight - padding, newY));

        return { ...obj, x: newX, y: newY, vx: 0, vy: 0 };
    });
};

const endDrag = () => {
    if (!draggingId.value) return;

    const draggedId = draggingId.value;

    // Give the object velocity based on drag direction
    floatingObjects.value = floatingObjects.value.map(obj => {
        if (obj.objectId !== draggedId) return obj;
        return {
            ...obj,
            vx: Math.max(-2, Math.min(2, dragVelocity.value.vx)),
            vy: Math.max(-2, Math.min(2, dragVelocity.value.vy)),
        };
    });

    draggingId.value = null;

    // Remove global listeners
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', endDrag);
    document.removeEventListener('touchmove', onDrag);
    document.removeEventListener('touchend', endDrag);

    // Handle tap (click without drag) - especially for touch devices
    // where preventDefault on touchstart blocks the native click event
    if (!wasDragged.value) {
        const obj = floatingObjects.value.find(o => o.objectId === draggedId);
        if (obj && obj.type === 'goal') {
            emit('goalClick', obj);
        }
    }
    wasDragged.value = false;
};

const progressColor = (progress) => {
    if (progress >= 100) return 'bg-green-500';
    if (progress >= 75) return 'bg-emerald-500';
    if (progress >= 50) return 'bg-yellow-500';
    if (progress >= 25) return 'bg-orange-500';
    return 'bg-red-500';
};

// Computed for filtering
const floatingGoals = computed(() => floatingObjects.value.filter(obj => obj.type === 'goal'));
const floatingWords = computed(() => floatingObjects.value.filter(obj => obj.type === 'word'));
</script>

<template>
    <div class="absolute inset-0 overflow-hidden floating-container">
        <!-- Theme Words (draggable) -->
        <div
            v-for="word in floatingWords"
            :key="word.objectId"
            class="absolute floating-word cursor-grab select-none"
            :class="{ 'cursor-grabbing': draggingId === word.objectId }"
            :style="{
                left: word.x + 'px',
                top: word.y + 'px',
                transform: 'translate(-50%, -50%)',
                zIndex: draggingId === word.objectId ? 100 : 15,
            }"
            @mousedown="startDrag(word.objectId, $event)"
            @touchstart="startDrag(word.objectId, $event)"
        >
            <span
                class="px-3 py-1.5 rounded-full font-bold text-sm whitespace-nowrap shadow-lg transition-transform"
                :class="{ 'scale-110 shadow-xl': draggingId === word.objectId }"
                :style="{
                    backgroundColor: word.color + '25',
                    color: word.color,
                    border: `2px solid ${word.color}`,
                }"
            >
                {{ word.word }}
            </span>
        </div>

        <!-- Core Goals (draggable) -->
        <div
            v-for="goal in floatingGoals"
            :key="goal.objectId"
            class="absolute floating-goal cursor-grab select-none"
            :class="{ 'cursor-grabbing': draggingId === goal.objectId }"
            :style="{
                left: goal.x + 'px',
                top: goal.y + 'px',
                transform: 'translate(-50%, -50%)',
                width: goal.size + 'px',
                zIndex: draggingId === goal.objectId ? 100 : (hoveredGoalId === goal.id ? 50 : 10),
            }"
            @mouseenter="hoveredGoalId = goal.id"
            @mouseleave="hoveredGoalId = null"
            @mousedown="startDrag(goal.objectId, $event)"
            @touchstart="startDrag(goal.objectId, $event)"
        >
            <div
                class="block cursor-pointer"
            >
                <div
                    class="relative overflow-hidden rounded-xl shadow-lg transition-all duration-300"
                    :class="{ 'shadow-2xl scale-110': hoveredGoalId === goal.id }"
                    :style="{ height: goal.size + 'px' }"
                >
                    <!-- Full Cover Image or Icon -->
                    <div
                        v-if="goal.cover_image_url"
                        class="w-full h-full bg-cover bg-center"
                        :style="{ backgroundImage: `url(${goal.cover_image_url})` }"
                    >
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
                    </div>
                    <div
                        v-else
                        class="w-full h-full flex items-center justify-center"
                        :style="{ backgroundColor: `${goal.category?.color}30` }"
                    >
                        <span :style="{ fontSize: `${goal.size * 0.45}px` }">
                            {{ goal.category?.icon || '🎯' }}
                        </span>
                    </div>

                    <!-- Progress Badge (top right) -->
                    <div
                        class="absolute top-1.5 right-1.5 px-2 py-0.5 rounded-full text-white text-xs font-bold shadow-md"
                        :class="progressColor(goal.progress)"
                    >
                        {{ goal.progress }}%
                    </div>

                    <!-- Pin Badge -->
                    <div
                        v-if="goal.is_pinned"
                        class="absolute top-1.5 left-1.5 text-sm drop-shadow-md"
                    >
                        📍
                    </div>

                    <!-- Progress Bar (bottom overlay) -->
                    <div class="absolute bottom-0 left-0 right-0 h-1.5 bg-black/30">
                        <div
                            class="h-full transition-all duration-500"
                            :class="progressColor(goal.progress)"
                            :style="{ width: `${goal.progress}%` }"
                        ></div>
                    </div>

                    <!-- Category color border -->
                    <div
                        class="absolute inset-0 rounded-xl pointer-events-none"
                        :style="{ border: `3px solid ${goal.category?.color || '#6366F1'}` }"
                    ></div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.floating-goal,
.floating-word {
    will-change: transform, left, top;
}
</style>
