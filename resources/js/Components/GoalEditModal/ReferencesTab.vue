<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    goal: Object,
});

// Modal state
const showModal = ref(false);
const editingReference = ref(null);
const expandedTextId = ref(null);

// Form
const form = useForm({
    type: 'link',
    title: '',
    content: '',
    file: null,
});

// File input ref
const fileInput = ref(null);
const selectedFileName = ref('');

// Grouped references
const groupedReferences = computed(() => {
    const refs = props.goal?.references || [];
    return {
        links: refs.filter(r => r.type === 'link'),
        files: refs.filter(r => r.type === 'file'),
        texts: refs.filter(r => r.type === 'text'),
    };
});

const totalCount = computed(() => props.goal?.references?.length || 0);

// Open modal for adding
const openAddModal = (type = 'link') => {
    editingReference.value = null;
    form.reset();
    form.type = type;
    selectedFileName.value = '';
    showModal.value = true;
};

// Open modal for editing
const openEditModal = (reference) => {
    editingReference.value = reference;
    form.type = reference.type;
    form.title = reference.title;
    form.content = reference.content || '';
    form.file = null;
    selectedFileName.value = reference.file_name || '';
    showModal.value = true;
};

// Handle file selection
const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.file = file;
        selectedFileName.value = file.name;
        if (!form.title) {
            form.title = file.name;
        }
    }
};

// Submit form
const submitForm = () => {
    if (editingReference.value) {
        router.post(route('references.update', [props.goal.id, editingReference.value.id]), {
            _method: 'put',
            title: form.title,
            content: form.content,
            file: form.file,
        }, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        router.post(route('references.store', props.goal.id), {
            type: form.type,
            title: form.title,
            content: form.content,
            file: form.file,
        }, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

// Delete reference
const deleteReference = (reference) => {
    if (confirm('Delete this reference?')) {
        router.delete(route('references.destroy', [props.goal.id, reference.id]), {
            preserveScroll: true,
        });
    }
};

// Copy text to clipboard
const copyToClipboard = async (text, e) => {
    try {
        await navigator.clipboard.writeText(text);
        const btn = e.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
        setTimeout(() => {
            btn.innerHTML = originalText;
        }, 1500);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

// Toggle text expansion
const toggleTextExpand = (id) => {
    expandedTextId.value = expandedTextId.value === id ? null : id;
};

// Format file size
const formatFileSize = (bytes) => {
    if (!bytes) return '';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    return `${size.toFixed(1)} ${units[unitIndex]}`;
};

// Type icons
const typeIcons = {
    link: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>',
    file: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>',
    text: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
};
</script>

<template>
    <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                References
                <span v-if="totalCount" class="text-gray-500 dark:text-gray-400 font-normal">
                    ({{ totalCount }})
                </span>
            </h3>
            <div class="flex gap-2">
                <button
                    @click="openAddModal('link')"
                    class="px-3 py-1.5 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600 transition-colors flex items-center gap-1"
                    title="Add link"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                    Link
                </button>
                <button
                    @click="openAddModal('file')"
                    class="px-3 py-1.5 bg-green-500 text-white text-sm rounded-lg hover:bg-green-600 transition-colors flex items-center gap-1"
                    title="Add file"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    File
                </button>
                <button
                    @click="openAddModal('text')"
                    class="px-3 py-1.5 bg-purple-500 text-white text-sm rounded-lg hover:bg-purple-600 transition-colors flex items-center gap-1"
                    title="Add note"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Note
                </button>
            </div>
        </div>

        <!-- References List -->
        <div v-if="totalCount" class="space-y-6">
            <!-- Links Section -->
            <div v-if="groupedReferences.links.length">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-2">
                    <span v-html="typeIcons.link" class="text-blue-500"></span>
                    Links ({{ groupedReferences.links.length }})
                </h4>
                <div class="space-y-2">
                    <div
                        v-for="ref in groupedReferences.links"
                        :key="ref.id"
                        class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg group"
                    >
                        <a
                            :href="ref.content"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex-1 min-w-0"
                        >
                            <div class="font-medium text-blue-600 dark:text-blue-400 hover:underline truncate">
                                {{ ref.title }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ ref.content }}
                            </div>
                        </a>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button
                                @click="copyToClipboard(ref.content, $event)"
                                class="p-1.5 text-gray-400 hover:text-blue-500 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded transition-colors"
                                title="Copy link"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                            <button
                                @click="openEditModal(ref)"
                                class="p-1.5 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded transition-colors"
                                title="Edit"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                            </button>
                            <button
                                @click="deleteReference(ref)"
                                class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors"
                                title="Delete"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Files Section -->
            <div v-if="groupedReferences.files.length">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-2">
                    <span v-html="typeIcons.file" class="text-green-500"></span>
                    Files ({{ groupedReferences.files.length }})
                </h4>
                <div class="space-y-2">
                    <div
                        v-for="ref in groupedReferences.files"
                        :key="ref.id"
                        class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg group"
                    >
                        <a
                            :href="ref.file_url"
                            target="_blank"
                            class="flex-1 min-w-0"
                        >
                            <div class="font-medium text-green-600 dark:text-green-400 hover:underline truncate">
                                {{ ref.title }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                <span>{{ ref.file_name }}</span>
                                <span v-if="ref.file_size">{{ formatFileSize(ref.file_size) }}</span>
                            </div>
                        </a>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a
                                :href="ref.file_url"
                                download
                                class="p-1.5 text-gray-400 hover:text-green-500 hover:bg-green-100 dark:hover:bg-green-900/30 rounded transition-colors"
                                title="Download"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                            </a>
                            <button
                                @click="openEditModal(ref)"
                                class="p-1.5 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded transition-colors"
                                title="Edit"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                            </button>
                            <button
                                @click="deleteReference(ref)"
                                class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors"
                                title="Delete"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes/Text Section -->
            <div v-if="groupedReferences.texts.length">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-2">
                    <span v-html="typeIcons.text" class="text-purple-500"></span>
                    Notes ({{ groupedReferences.texts.length }})
                </h4>
                <div class="space-y-2">
                    <div
                        v-for="ref in groupedReferences.texts"
                        :key="ref.id"
                        class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg group"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-purple-600 dark:text-purple-400 mb-1">
                                    {{ ref.title }}
                                </div>
                                <div
                                    class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap"
                                    :class="{ 'line-clamp-3': expandedTextId !== ref.id }"
                                >
                                    {{ ref.content }}
                                </div>
                                <button
                                    v-if="ref.content && ref.content.length > 150"
                                    @click="toggleTextExpand(ref.id)"
                                    class="text-xs text-purple-500 hover:text-purple-700 mt-1"
                                >
                                    {{ expandedTextId === ref.id ? 'Show less' : 'Show more' }}
                                </button>
                            </div>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                                <button
                                    @click="copyToClipboard(ref.content, $event)"
                                    class="p-1.5 text-gray-400 hover:text-purple-500 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded transition-colors"
                                    title="Copy text"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                                <button
                                    @click="openEditModal(ref)"
                                    class="p-1.5 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded transition-colors"
                                    title="Edit"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteReference(ref)"
                                    class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors"
                                    title="Delete"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            No references yet. Add links, files, or notes to keep track of resources!
        </div>

        <!-- Add/Edit Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50"
            @click.self="showModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md mx-4 max-h-[80vh] overflow-y-auto">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                    {{ editingReference ? 'Edit Reference' : 'Add Reference' }}
                </h3>

                <form @submit.prevent="submitForm">
                    <!-- Type selector (only for new) -->
                    <div v-if="!editingReference" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Type
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="form.type = 'link'"
                                class="p-3 rounded-lg border-2 text-center transition-all"
                                :class="form.type === 'link'
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-blue-300'"
                            >
                                <div class="text-2xl mb-1">Link</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">URL</div>
                            </button>
                            <button
                                type="button"
                                @click="form.type = 'file'"
                                class="p-3 rounded-lg border-2 text-center transition-all"
                                :class="form.type === 'file'
                                    ? 'border-green-500 bg-green-50 dark:bg-green-900/30'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-green-300'"
                            >
                                <div class="text-2xl mb-1">File</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Upload</div>
                            </button>
                            <button
                                type="button"
                                @click="form.type = 'text'"
                                class="p-3 rounded-lg border-2 text-center transition-all"
                                :class="form.type === 'text'
                                    ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-purple-300'"
                            >
                                <div class="text-2xl mb-1">Note</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Text</div>
                            </button>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Title *
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            required
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="Reference title..."
                        />
                    </div>

                    <!-- URL (for link type) -->
                    <div v-if="form.type === 'link'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            URL *
                        </label>
                        <input
                            v-model="form.content"
                            type="url"
                            required
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="https://..."
                        />
                    </div>

                    <!-- File (for file type) -->
                    <div v-if="form.type === 'file'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            File {{ editingReference ? '(leave empty to keep current)' : '*' }}
                        </label>
                        <div class="relative">
                            <input
                                ref="fileInput"
                                type="file"
                                @change="handleFileChange"
                                class="hidden"
                                :required="!editingReference"
                            />
                            <button
                                type="button"
                                @click="fileInput?.click()"
                                class="w-full px-4 py-3 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-green-500 dark:hover:border-green-500 transition-colors text-left"
                            >
                                <div v-if="selectedFileName" class="text-green-600 dark:text-green-400">
                                    {{ selectedFileName }}
                                </div>
                                <div v-else class="text-gray-400">
                                    Click to select file (max 10MB)
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Text content (for text type) -->
                    <div v-if="form.type === 'text'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Content *
                        </label>
                        <textarea
                            v-model="form.content"
                            rows="6"
                            required
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="Your notes..."
                        ></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors disabled:opacity-50"
                        >
                            {{ editingReference ? 'Update' : 'Add' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
