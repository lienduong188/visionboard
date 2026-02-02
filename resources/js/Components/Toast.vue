<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const toasts = ref([]);
let toastId = 0;

const addToast = (message, type = 'success') => {
    const id = ++toastId;
    toasts.value.push({ id, message, type, visible: true });

    // Auto remove after 5 seconds
    setTimeout(() => {
        removeToast(id);
    }, 5000);
};

const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index !== -1) {
        toasts.value[index].visible = false;
        setTimeout(() => {
            toasts.value = toasts.value.filter(t => t.id !== id);
        }, 300);
    }
};

// Watch for flash messages from Laravel
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        addToast(flash.success, 'success');
    }
    if (flash?.error) {
        addToast(flash.error, 'error');
    }
    if (flash?.warning) {
        addToast(flash.warning, 'warning');
    }
    if (flash?.info) {
        addToast(flash.info, 'info');
    }
}, { immediate: true, deep: true });

// Expose addToast for manual usage
defineExpose({ addToast });
</script>

<template>
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2">
        <transition-group name="toast">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg max-w-sm transform transition-all duration-300"
                :class="{
                    'bg-green-500 text-white': toast.type === 'success',
                    'bg-red-500 text-white': toast.type === 'error',
                    'bg-yellow-500 text-white': toast.type === 'warning',
                    'bg-blue-500 text-white': toast.type === 'info',
                    'opacity-0 translate-x-full': !toast.visible,
                    'opacity-100 translate-x-0': toast.visible,
                }"
            >
                <!-- Icon -->
                <span class="flex-shrink-0">
                    <svg v-if="toast.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <svg v-else-if="toast.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <svg v-else-if="toast.type === 'warning'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </span>

                <!-- Message -->
                <span class="flex-1 text-sm font-medium">{{ toast.message }}</span>

                <!-- Close button -->
                <button
                    @click="removeToast(toast.id)"
                    class="flex-shrink-0 hover:opacity-75 transition-opacity"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
