<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    words: {
        type: Array,
        default: () => [],
    },
    currentEffect: {
        type: String,
        default: 'orbit',
    },
});

const emit = defineEmits(['close']);

const showForm = ref(false);
const editingWord = ref(null);

const form = useForm({
    word: '',
    color: '#6366F1',
});

const predefinedColors = [
    '#EF4444', // Red
    '#F97316', // Orange
    '#EAB308', // Yellow
    '#22C55E', // Green
    '#06B6D4', // Cyan
    '#3B82F6', // Blue
    '#8B5CF6', // Purple
    '#EC4899', // Pink
    '#6366F1', // Indigo
];

const addWord = () => {
    form.post(route('theme-words.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

const startEdit = (word) => {
    editingWord.value = word;
    form.word = word.word;
    form.color = word.color;
    showForm.value = true;
};

const updateWord = () => {
    form.put(route('theme-words.update', editingWord.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingWord.value = null;
            form.reset();
            showForm.value = false;
        },
    });
};

const deleteWord = (word) => {
    if (confirm('Xoa tu ngu nay?')) {
        router.delete(route('theme-words.destroy', word.id), {
            preserveScroll: true,
        });
    }
};

const cancelEdit = () => {
    showForm.value = false;
    editingWord.value = null;
    form.reset();
    form.color = '#6366F1';
};

const changeEffect = (effect) => {
    router.patch(route('theme-words.effect'), { effect }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 max-w-xs">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-900 dark:text-white text-sm">
                Theme Words
            </h3>
            <button @click="emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Effect Toggle -->
        <div class="flex gap-2 mb-4">
            <button
                @click="changeEffect('orbit')"
                class="flex-1 py-2 px-3 rounded-lg text-xs font-medium transition-colors"
                :class="currentEffect === 'orbit'
                    ? 'bg-indigo-600 text-white'
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
            >
                Orbit
            </button>
            <button
                @click="changeEffect('waterfall')"
                class="flex-1 py-2 px-3 rounded-lg text-xs font-medium transition-colors"
                :class="currentEffect === 'waterfall'
                    ? 'bg-indigo-600 text-white'
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
            >
                Waterfall
            </button>
        </div>

        <!-- Word List -->
        <div class="space-y-2 mb-4 max-h-40 overflow-y-auto">
            <div
                v-for="word in words"
                :key="word.id"
                class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-700"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="w-3 h-3 rounded-full flex-shrink-0"
                        :style="{ backgroundColor: word.color }"
                    ></div>
                    <span class="text-sm text-gray-900 dark:text-white truncate max-w-[120px]">{{ word.word }}</span>
                </div>
                <div class="flex gap-1 flex-shrink-0">
                    <button
                        @click="startEdit(word)"
                        class="p-1 text-gray-400 hover:text-indigo-600 text-xs"
                        title="Edit"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                    <button
                        @click="deleteWord(word)"
                        class="p-1 text-gray-400 hover:text-red-600 text-xs"
                        title="Delete"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div v-if="words.length === 0" class="text-center py-3 text-gray-500 dark:text-gray-400 text-xs">
                Chua co theme words
            </div>
        </div>

        <!-- Add/Edit Form -->
        <div v-if="showForm" class="border-t dark:border-gray-600 pt-3">
            <div class="space-y-3">
                <input
                    v-model="form.word"
                    type="text"
                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="VD: Kien tri, Ben bi..."
                    maxlength="50"
                    @keyup.enter="editingWord ? updateWord() : addWord()"
                />

                <!-- Color Picker -->
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="color in predefinedColors"
                        :key="color"
                        @click="form.color = color"
                        class="w-5 h-5 rounded-full border-2 transition-transform hover:scale-110"
                        :class="form.color === color ? 'scale-110 border-gray-900 dark:border-white' : 'border-transparent'"
                        :style="{ backgroundColor: color }"
                    ></button>
                    <input
                        v-model="form.color"
                        type="color"
                        class="w-5 h-5 rounded cursor-pointer border-0"
                    />
                </div>

                <div class="flex gap-2">
                    <button
                        v-if="editingWord"
                        @click="updateWord"
                        class="flex-1 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                        :disabled="form.processing || !form.word"
                    >
                        Cap nhat
                    </button>
                    <button
                        v-else
                        @click="addWord"
                        class="flex-1 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                        :disabled="form.processing || !form.word"
                    >
                        Them
                    </button>
                    <button
                        @click="cancelEdit"
                        class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        Huy
                    </button>
                </div>
            </div>
        </div>

        <!-- Add Button -->
        <button
            v-if="!showForm"
            @click="showForm = true"
            class="w-full py-2 mt-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 dark:text-gray-400 hover:border-indigo-500 hover:text-indigo-600 transition-colors text-sm"
        >
            + Them tu ngu
        </button>
    </div>
</template>
