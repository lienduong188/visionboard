<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    goal: Object,
    categories: Array,
    coreGoalsCount: Number,
    show: Boolean,
});

const emit = defineEmits(['close', 'saved', 'deleted']);

const form = useForm({
    category_id: '',
    title: '',
    description: '',
    slogan: '',
    cover_image: null,
    remove_cover_image: false,
    target_value: '',
    current_value: 0,
    start_value: '',
    unit: '',
    start_date: '2026-01-01',
    target_date: '2026-12-31',
    priority: 'medium',
    status: 'not_started',
    is_pinned: false,
    is_core_goal: false,
    progress_mode: 'value',
});

// Watch for goal changes to update form
watch(() => props.goal, (newGoal) => {
    if (newGoal) {
        form.category_id = newGoal.category_id;
        form.title = newGoal.title;
        form.description = newGoal.description || '';
        form.slogan = newGoal.slogan || '';
        form.cover_image = null;
        form.remove_cover_image = false;
        form.target_value = newGoal.target_value || '';
        form.current_value = newGoal.current_value || 0;
        form.start_value = newGoal.start_value || '';
        form.unit = newGoal.unit || '';
        form.start_date = formatDateForInput(newGoal.start_date) || '2026-01-01';
        form.target_date = formatDateForInput(newGoal.target_date) || '2026-12-31';
        form.priority = newGoal.priority;
        form.status = newGoal.status;
        form.is_pinned = newGoal.is_pinned;
        form.is_core_goal = newGoal.is_core_goal || false;
        form.progress_mode = newGoal.progress_mode || 'value';
        currentImage.value = newGoal.cover_image;
        imagePreview.value = null;
    }
}, { immediate: true });

// Can set core goal if: already a core goal OR has less than 3 core goals
const canSetCoreGoal = computed(() => {
    if (!props.goal) return props.coreGoalsCount < 3;
    return props.goal.is_core_goal || props.coreGoalsCount < 3;
});

const currentImage = ref(null);
const imagePreview = ref(null);
const fileInput = ref(null);
const showDeleteConfirm = ref(false);
const processing = ref(false);
const newChecklistItem = ref('');
const editingChecklistId = ref(null);
const editingChecklistTitle = ref('');

// Helper function to format date for input type="date" (YYYY-MM-DD)
const formatDateForInput = (dateValue) => {
    if (!dateValue) return '';
    // Handle ISO string like "2026-01-01T00:00:00.000000Z" or "2026-01-01"
    const dateStr = String(dateValue);
    if (dateStr.includes('T')) {
        return dateStr.split('T')[0];
    }
    // Handle date string that might have time
    if (dateStr.includes(' ')) {
        return dateStr.split(' ')[0];
    }
    return dateStr;
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
    if (!props.goal || processing.value) return;

    processing.value = true;
    router.post(route('goals.update', props.goal.id), {
        _method: 'put',
        ...form.data(),
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            processing.value = false;
            emit('saved');
            emit('close');
        },
        onError: (errors) => {
            processing.value = false;
            form.errors = errors;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const confirmDelete = () => {
    showDeleteConfirm.value = true;
};

const deleteGoal = () => {
    if (!props.goal || processing.value) return;

    processing.value = true;
    router.delete(route('goals.destroy', props.goal.id), {
        preserveScroll: true,
        onSuccess: () => {
            processing.value = false;
            emit('deleted');
            emit('close');
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const close = () => {
    showDeleteConfirm.value = false;
    emit('close');
};

// Checklist methods
const addChecklist = () => {
    if (!newChecklistItem.value.trim() || !props.goal) return;

    router.post(route('checklists.store', props.goal.id), {
        title: newChecklistItem.value.trim(),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newChecklistItem.value = '';
        },
    });
};

const toggleChecklist = (checklist) => {
    if (!props.goal) return;

    router.patch(route('checklists.toggle', [props.goal.id, checklist.id]), {}, {
        preserveScroll: true,
    });
};

const startEditChecklist = (checklist) => {
    editingChecklistId.value = checklist.id;
    editingChecklistTitle.value = checklist.title;
};

const saveEditChecklist = (checklist) => {
    if (!editingChecklistTitle.value.trim() || !props.goal) return;

    router.put(route('checklists.update', [props.goal.id, checklist.id]), {
        title: editingChecklistTitle.value.trim(),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editingChecklistId.value = null;
            editingChecklistTitle.value = '';
        },
    });
};

const cancelEditChecklist = () => {
    editingChecklistId.value = null;
    editingChecklistTitle.value = '';
};

const deleteChecklist = (checklist) => {
    if (!props.goal) return;

    router.delete(route('checklists.destroy', [props.goal.id, checklist.id]), {
        preserveScroll: true,
    });
};

// Handle ESC key
const handleKeydown = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});

const progressColor = computed(() => {
    const progress = props.goal?.progress || 0;
    if (progress >= 100) return 'bg-green-500';
    if (progress >= 75) return 'bg-emerald-500';
    if (progress >= 50) return 'bg-yellow-500';
    if (progress >= 25) return 'bg-orange-500';
    return 'bg-red-500';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click.self="close"
            >
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>

                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="show && goal"
                            class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-h-[90vh] overflow-hidden"
                            @click.stop
                        >
                            <!-- Header -->
                            <div
                                class="sticky top-0 z-10 flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800"
                                :style="{ borderTopColor: goal.category?.color }"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">{{ goal.category?.icon }}</span>
                                    <div>
                                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ goal.title }}
                                        </h2>
                                        <p v-if="goal.slogan" class="text-sm text-indigo-600 dark:text-indigo-400 italic">
                                            "{{ goal.slogan }}"
                                        </p>
                                    </div>
                                </div>
                                <button
                                    @click="close"
                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Progress Bar (at top) -->
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Tiến độ</span>
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

                            <!-- Form Content -->
                            <div class="overflow-y-auto max-h-[calc(90vh-200px)] p-6">
                                <form @submit.prevent="submit" class="space-y-5">
                                    <!-- Category -->
                                    <div>
                                        <InputLabel for="modal_category_id" value="Category" />
                                        <select
                                            id="modal_category_id"
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
                                        <InputLabel for="modal_title" value="Title" />
                                        <TextInput
                                            id="modal_title"
                                            v-model="form.title"
                                            type="text"
                                            class="mt-1 block w-full"
                                            required
                                        />
                                        <InputError :message="form.errors.title" class="mt-2" />
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <InputLabel for="modal_description" value="Thuyết minh mục tiêu" />
                                        <textarea
                                            id="modal_description"
                                            v-model="form.description"
                                            rows="3"
                                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Mô tả theo công thức: Trạng thái + Hình ảnh + Hành động"
                                        ></textarea>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            💡 Gợi ý: Mô tả theo công thức <strong>Trạng thái + Hình ảnh + Hành động</strong>
                                        </p>
                                        <InputError :message="form.errors.description" class="mt-2" />
                                    </div>

                                    <!-- Slogan -->
                                    <div>
                                        <InputLabel for="modal_slogan" value="Câu dẫn đường (Slogan)" />
                                        <TextInput
                                            id="modal_slogan"
                                            v-model="form.slogan"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="Ví dụ: Chạy để sống, sống để chạy!"
                                        />
                                        <InputError :message="form.errors.slogan" class="mt-2" />
                                    </div>

                                    <!-- Cover Image -->
                                    <div>
                                        <InputLabel for="modal_cover_image" value="Cover Image" />
                                        <div class="mt-1">
                                            <input
                                                ref="fileInput"
                                                id="modal_cover_image"
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
                                    </div>

                                    <!-- Start/Target/Current Value & Unit -->
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                        <div>
                                            <InputLabel for="modal_start_value" value="Start Value" />
                                            <TextInput
                                                id="modal_start_value"
                                                v-model="form.start_value"
                                                type="number"
                                                step="any"
                                                class="mt-1 block w-full"
                                                placeholder="VD: 27"
                                            />
                                            <InputError :message="form.errors.start_value" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="modal_target_value" value="Target Value" />
                                            <TextInput
                                                id="modal_target_value"
                                                v-model="form.target_value"
                                                type="number"
                                                step="any"
                                                class="mt-1 block w-full"
                                                placeholder="VD: 20"
                                            />
                                            <InputError :message="form.errors.target_value" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="modal_current_value" value="Current Value" />
                                            <TextInput
                                                id="modal_current_value"
                                                v-model="form.current_value"
                                                type="number"
                                                step="any"
                                                class="mt-1 block w-full"
                                                placeholder="VD: 25"
                                            />
                                            <InputError :message="form.errors.current_value" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="modal_unit" value="Unit" />
                                            <TextInput
                                                id="modal_unit"
                                                v-model="form.unit"
                                                type="text"
                                                class="mt-1 block w-full"
                                                placeholder="VD: %"
                                            />
                                            <InputError :message="form.errors.unit" class="mt-2" />
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        💡 <strong>Decrease goal</strong>: Nhập Start > Target (VD: giảm mỡ từ 27% xuống 20%). <strong>Increase goal</strong>: Nhập Start &lt; Target hoặc để trống Start.
                                    </p>

                                    <!-- Dates -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel for="modal_start_date" value="Start Date" />
                                            <TextInput
                                                id="modal_start_date"
                                                v-model="form.start_date"
                                                type="date"
                                                class="mt-1 block w-full"
                                            />
                                            <InputError :message="form.errors.start_date" class="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel for="modal_target_date" value="Target Date" />
                                            <TextInput
                                                id="modal_target_date"
                                                v-model="form.target_date"
                                                type="date"
                                                class="mt-1 block w-full"
                                            />
                                            <InputError :message="form.errors.target_date" class="mt-2" />
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div>
                                        <InputLabel for="modal_status" value="Status" />
                                        <select
                                            id="modal_status"
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

                                    <!-- Progress Mode -->
                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <InputLabel value="📊 Cách tính tiến độ" />
                                        <div class="mt-3 space-y-3">
                                            <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg transition-colors"
                                                :class="form.progress_mode === 'value' ? 'bg-blue-100 dark:bg-blue-800/50' : 'hover:bg-blue-100/50 dark:hover:bg-blue-800/20'">
                                                <input
                                                    type="radio"
                                                    v-model="form.progress_mode"
                                                    value="value"
                                                    class="mt-1 text-blue-600 focus:ring-blue-500"
                                                />
                                                <div>
                                                    <span class="font-semibold text-gray-900 dark:text-white">📈 Theo giá trị (Value)</span>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                        Progress = Current Value / Target Value. Thích hợp cho mục tiêu đo lường được (km, kg, %).
                                                    </p>
                                                </div>
                                            </label>
                                            <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg transition-colors"
                                                :class="form.progress_mode === 'milestone' ? 'bg-blue-100 dark:bg-blue-800/50' : 'hover:bg-blue-100/50 dark:hover:bg-blue-800/20'">
                                                <input
                                                    type="radio"
                                                    v-model="form.progress_mode"
                                                    value="milestone"
                                                    class="mt-1 text-blue-600 focus:ring-blue-500"
                                                />
                                                <div>
                                                    <span class="font-semibold text-gray-900 dark:text-white">🏁 Theo milestones</span>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                        Progress = Milestones hoàn thành / Tổng milestones. Thích hợp cho mục tiêu có nhiều bước.
                                                    </p>
                                                </div>
                                            </label>
                                        </div>
                                        <InputError :message="form.errors.progress_mode" class="mt-2" />
                                    </div>

                                    <!-- Checklist -->
                                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                        <InputLabel value="✅ Checklist" />
                                        <div class="mt-3 space-y-2">
                                            <!-- Existing items -->
                                            <div
                                                v-for="item in goal.checklists"
                                                :key="item.id"
                                                class="flex items-center gap-2 group"
                                            >
                                                <button
                                                    type="button"
                                                    @click="toggleChecklist(item)"
                                                    class="w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                                                    :class="item.is_completed
                                                        ? 'bg-green-500 border-green-500 text-white'
                                                        : 'border-gray-300 dark:border-gray-500 hover:border-green-400'"
                                                >
                                                    <svg v-if="item.is_completed" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                                <!-- Edit mode -->
                                                <div v-if="editingChecklistId === item.id" class="flex-1 flex items-center gap-2">
                                                    <input
                                                        v-model="editingChecklistTitle"
                                                        type="text"
                                                        class="flex-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white rounded focus:ring-indigo-500 focus:border-indigo-500 py-1"
                                                        @keyup.enter="saveEditChecklist(item)"
                                                        @keyup.escape="cancelEditChecklist"
                                                    />
                                                    <button
                                                        type="button"
                                                        @click="saveEditChecklist(item)"
                                                        class="text-green-500 hover:text-green-600 text-sm"
                                                    >✓</button>
                                                    <button
                                                        type="button"
                                                        @click="cancelEditChecklist"
                                                        class="text-gray-400 hover:text-gray-600 text-sm"
                                                    >✕</button>
                                                </div>
                                                <!-- Display mode -->
                                                <span
                                                    v-else
                                                    @dblclick="startEditChecklist(item)"
                                                    class="flex-1 text-sm cursor-pointer"
                                                    :class="item.is_completed
                                                        ? 'line-through text-gray-400 dark:text-gray-500'
                                                        : 'text-gray-700 dark:text-gray-300'"
                                                >
                                                    {{ item.title }}
                                                </span>
                                                <button
                                                    v-if="editingChecklistId !== item.id"
                                                    type="button"
                                                    @click="deleteChecklist(item)"
                                                    class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity text-xs"
                                                >
                                                    ✕
                                                </button>
                                            </div>

                                            <!-- Add new item -->
                                            <div class="flex items-center gap-2 pt-2">
                                                <div class="w-5 h-5 flex-shrink-0"></div>
                                                <input
                                                    v-model="newChecklistItem"
                                                    type="text"
                                                    placeholder="Thêm checklist item..."
                                                    class="flex-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white rounded focus:ring-indigo-500 focus:border-indigo-500 py-1.5"
                                                    @keyup.enter="addChecklist"
                                                />
                                                <button
                                                    type="button"
                                                    @click="addChecklist"
                                                    :disabled="!newChecklistItem.trim()"
                                                    class="text-indigo-500 hover:text-indigo-600 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    + Add
                                                </button>
                                            </div>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            💡 Double-click để edit. Checklist không ảnh hưởng tiến độ goal.
                                        </p>
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
                                                <span class="font-semibold text-gray-900 dark:text-white">🎯 Core Goal - Trục Trung Tâm</span>
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                    Đánh dấu goal này là 1 trong 3 trục trung tâm của Vision Board.
                                                </p>
                                                <p v-if="!canSetCoreGoal" class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                                                    ⚠️ Bạn đã có 3/3 Core Goals. Hãy bỏ chọn một goal khác để thêm mới.
                                                </p>
                                            </div>
                                        </label>
                                        <InputError :message="form.errors.is_core_goal" class="mt-2" />
                                    </div>
                                </form>
                            </div>

                            <!-- Footer Actions -->
                            <div class="sticky bottom-0 flex items-center justify-between p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                                <!-- Delete Button -->
                                <div>
                                    <button
                                        v-if="!showDeleteConfirm"
                                        type="button"
                                        @click="confirmDelete"
                                        class="px-4 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                    >
                                        🗑️ Delete
                                    </button>
                                    <div v-else class="flex items-center gap-2">
                                        <span class="text-sm text-red-600 dark:text-red-400">Xác nhận xóa?</span>
                                        <button
                                            type="button"
                                            @click="deleteGoal"
                                            class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm"
                                            :disabled="processing"
                                        >
                                            Xóa
                                        </button>
                                        <button
                                            type="button"
                                            @click="showDeleteConfirm = false"
                                            class="px-3 py-1.5 text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 rounded-lg transition-colors text-sm"
                                        >
                                            Hủy
                                        </button>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-3">
                                    <!-- View Details Link -->
                                    <Link
                                        :href="route('goals.show', goal.id)"
                                        class="px-4 py-2 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors text-sm font-medium"
                                    >
                                        📋 Milestones & Details
                                    </Link>

                                    <!-- Save Button -->
                                    <PrimaryButton
                                        @click="submit"
                                        :disabled="processing"
                                    >
                                        💾 Save
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
