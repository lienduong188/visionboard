<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'result']);

const DEFAULT_ITEMS = [
    { label: 'Viết blog', color: '#6366F1' },
    { label: 'Quay vlog', color: '#3B82F6' },
    { label: 'Edit video', color: '#EC4899' },
    { label: 'Học', color: '#F59E0B' },
    { label: 'Đọc', color: '#8B5CF6' },
    { label: 'Vẽ', color: '#F43F5E' },
    { label: 'Dọn dẹp', color: '#14B8A6' },
    { label: 'Design', color: '#E879F9' },
    { label: 'Thêu', color: '#10B981' },
    { label: 'Móc len', color: '#EF4444' },
];

const COLORS_POOL = [
    '#6366F1', '#EC4899', '#10B981', '#F59E0B', '#8B5CF6',
    '#F43F5E', '#3B82F6', '#14B8A6', '#E879F9', '#EF4444',
    '#06B6D4', '#84CC16', '#F97316', '#A855F7', '#22D3EE',
];

// State
const mode = ref('default'); // 'default' or 'custom'
const customInput = ref('');
const customItems = ref([]);
const isSpinning = ref(false);
const result = ref(null);
const rotation = ref(0);
const canvasRef = ref(null);

const MAX_CUSTOM_ITEMS = 10;

const wheelItems = computed(() => {
    if (mode.value === 'custom') {
        return customItems.value.length >= 2 ? customItems.value : null;
    }
    return DEFAULT_ITEMS;
});

const addCustomItem = () => {
    const text = customInput.value.trim();
    if (!text || customItems.value.length >= MAX_CUSTOM_ITEMS) return;
    const colorIdx = customItems.value.length % COLORS_POOL.length;
    customItems.value.push({ label: text, color: COLORS_POOL[colorIdx] });
    customInput.value = '';
};

const removeCustomItem = (idx) => {
    customItems.value.splice(idx, 1);
};

const resetCustom = () => {
    customItems.value = [];
    customInput.value = '';
    result.value = null;
};

const switchMode = (newMode) => {
    mode.value = newMode;
    result.value = null;
};

// Canvas drawing
const drawWheel = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const size = canvas.width;
    const center = size / 2;
    const radius = center - 8;
    const items = wheelItems.value;

    ctx.clearRect(0, 0, size, size);

    if (!items) {
        // Draw empty single-color wheel for custom mode
        ctx.beginPath();
        ctx.arc(center, center, radius, 0, 2 * Math.PI);
        ctx.fillStyle = '#D1D5DB';
        ctx.fill();
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 2;
        ctx.stroke();

        // Hint text
        ctx.fillStyle = '#9CA3AF';
        ctx.font = 'bold 14px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('Add items to spin!', center, center + 5);
    } else {
        const sliceAngle = (2 * Math.PI) / items.length;

        ctx.save();
        ctx.translate(center, center);
        ctx.rotate((rotation.value * Math.PI) / 180);

        items.forEach((item, i) => {
            const startAngle = i * sliceAngle;
            const endAngle = startAngle + sliceAngle;

            // Draw slice
            ctx.beginPath();
            ctx.moveTo(0, 0);
            ctx.arc(0, 0, radius, startAngle, endAngle);
            ctx.closePath();
            ctx.fillStyle = item.color;
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 2;
            ctx.stroke();

            // Draw text
            ctx.save();
            ctx.rotate(startAngle + sliceAngle / 2);
            ctx.textAlign = 'right';
            ctx.fillStyle = '#fff';
            ctx.font = 'bold 13px sans-serif';
            ctx.shadowColor = 'rgba(0,0,0,0.5)';
            ctx.shadowBlur = 3;

            const text = item.label.length > 12 ? item.label.substring(0, 11) + '…' : item.label;
            ctx.fillText(text, radius - 16, 5);
            ctx.restore();
        });

        ctx.restore();
    }

    // Draw center circle
    ctx.beginPath();
    ctx.arc(center, center, 18, 0, 2 * Math.PI);
    ctx.fillStyle = '#1F2937';
    ctx.fill();
    ctx.strokeStyle = '#fff';
    ctx.lineWidth = 3;
    ctx.stroke();

    // Draw pointer (triangle at top)
    ctx.beginPath();
    ctx.moveTo(center - 12, 4);
    ctx.lineTo(center + 12, 4);
    ctx.lineTo(center, 24);
    ctx.closePath();
    ctx.fillStyle = '#EF4444';
    ctx.fill();
    ctx.strokeStyle = '#fff';
    ctx.lineWidth = 2;
    ctx.stroke();
};

// Spin animation
let animationId = null;

const spin = () => {
    if (isSpinning.value || !wheelItems.value) return;
    isSpinning.value = true;
    result.value = null;

    const items = wheelItems.value;
    const totalSlices = items.length;
    const sliceAngle = 360 / totalSlices;

    // Random target: 5-8 full rotations + random slice
    const extraRotations = (5 + Math.random() * 3) * 360;
    const randomOffset = Math.random() * 360;
    const targetRotation = rotation.value + extraRotations + randomOffset;

    const startRotation = rotation.value;
    const totalDelta = targetRotation - startRotation;
    const duration = 4000; // 4 seconds
    const startTime = performance.now();

    const animate = (now) => {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Ease out cubic
        const eased = 1 - Math.pow(1 - progress, 3);
        rotation.value = startRotation + totalDelta * eased;

        drawWheel();

        if (progress < 1) {
            animationId = requestAnimationFrame(animate);
        } else {
            isSpinning.value = false;

            // Determine which slice the pointer landed on
            // Pointer is at top = 270° in canvas coords (0° = right/3 o'clock)
            const normalizedAngle = ((270 - (rotation.value % 360)) % 360 + 360) % 360;
            const sliceIdx = Math.floor(normalizedAngle / sliceAngle) % totalSlices;
            result.value = items[sliceIdx];

            // Result stays visible until user takes action
        }
    };

    animationId = requestAnimationFrame(animate);
};

// Draw wheel when items change or modal opens
watch([wheelItems, () => props.show], () => {
    if (props.show) {
        nextTick(() => drawWheel());
    }
});

onMounted(() => {
    if (props.show) drawWheel();
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/60" @click="emit('close')"></div>

            <!-- Modal -->
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        🎰 Spin Wheel
                    </h3>
                    <button @click="emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-xl">
                        ✕
                    </button>
                </div>

                <div class="p-6">
                    <!-- Mode Toggle -->
                    <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1 mb-4">
                        <button
                            @click="switchMode('default')"
                            class="flex-1 px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                            :class="mode === 'default'
                                ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                                : 'text-gray-500 dark:text-gray-400'"
                        >
                            Default
                        </button>
                        <button
                            @click="switchMode('custom')"
                            class="flex-1 px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                            :class="mode === 'custom'
                                ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                                : 'text-gray-500 dark:text-gray-400'"
                        >
                            Custom
                        </button>
                    </div>

                    <!-- Custom Items Input -->
                    <div v-if="mode === 'custom'" class="mb-4">
                        <div class="flex gap-2 mb-2">
                            <input
                                v-model="customInput"
                                type="text"
                                :disabled="customItems.length >= MAX_CUSTOM_ITEMS"
                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50"
                                placeholder="Nhập hoạt động..."
                                @keyup.enter="addCustomItem"
                            />
                            <button
                                @click="addCustomItem"
                                :disabled="customItems.length >= MAX_CUSTOM_ITEMS"
                                class="px-3 py-2 bg-indigo-500 text-white rounded-lg text-sm hover:bg-indigo-600 disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                +
                            </button>
                        </div>

                        <!-- Custom items list -->
                        <div v-if="customItems.length > 0" class="flex flex-wrap gap-1.5 mb-2">
                            <span
                                v-for="(item, idx) in customItems"
                                :key="idx"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs text-white"
                                :style="{ backgroundColor: item.color }"
                            >
                                {{ item.label }}
                                <button @click="removeCustomItem(idx)" class="hover:text-red-200 ml-0.5">&times;</button>
                            </span>
                        </div>

                        <div class="flex justify-between items-center">
                            <p v-if="customItems.length < 2" class="text-xs text-amber-500">
                                Thêm ít nhất 2 mục để quay
                            </p>
                            <p v-else-if="customItems.length >= MAX_CUSTOM_ITEMS" class="text-xs text-rose-500">
                                Đã đạt tối đa {{ MAX_CUSTOM_ITEMS }} mục
                            </p>
                            <span v-else class="text-xs text-gray-400">{{ customItems.length }}/{{ MAX_CUSTOM_ITEMS }}</span>
                            <button
                                v-if="customItems.length > 0"
                                @click="resetCustom"
                                class="text-xs text-gray-400 hover:text-red-400 ml-auto"
                            >
                                Reset all
                            </button>
                        </div>
                    </div>

                    <!-- Wheel Canvas -->
                    <div class="flex justify-center mb-4">
                        <canvas
                            ref="canvasRef"
                            width="320"
                            height="320"
                            class="max-w-full"
                        />
                    </div>

                    <!-- Result -->
                    <div v-if="result" class="text-center mb-4 animate-bounce">
                        <div class="inline-block px-6 py-3 rounded-xl text-white font-bold text-lg shadow-lg"
                            :style="{ backgroundColor: result.color }">
                            🎉 {{ result.label }}
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 justify-center">
                        <button
                            @click="spin"
                            :disabled="isSpinning || !wheelItems"
                            class="px-8 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl font-bold text-lg shadow-lg hover:from-indigo-600 hover:to-purple-700 transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="{ 'animate-pulse': isSpinning }"
                        >
                            {{ isSpinning ? 'Đang về bờ...' : (result ? 'Về bờ nữa!!' : 'Về bờ!!') }}
                        </button>
                        <button
                            v-if="result && !isSpinning"
                            @click="emit('result', result.label)"
                            class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-bold text-lg shadow-lg hover:from-green-600 hover:to-emerald-700 transition-all"
                        >
                            ✅ Use This
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
