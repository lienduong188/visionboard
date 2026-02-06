<script setup>
import { ref, onMounted, watch } from 'vue';

const themes = [
    { id: 'light', name: 'Light', icon: '☀️', title: 'Sáng' },
    { id: 'dark', name: 'Dark', icon: '🌙', title: 'Tối' },
    { id: 'hope', name: 'Hope', icon: '🌿', title: 'Hy vọng (Emerald)' },
];

const currentTheme = ref('dark');
const isOpen = ref(false);

const applyTheme = (theme) => {
    const html = document.documentElement;

    // Remove all theme classes
    html.classList.remove('dark', 'theme-hope');

    // Apply selected theme
    if (theme === 'dark') {
        html.classList.add('dark');
    } else if (theme === 'hope') {
        html.classList.add('theme-hope');
    }
    // 'light' doesn't need any class

    // Save to localStorage
    localStorage.setItem('theme', theme);
    currentTheme.value = theme;
    isOpen.value = false;
};

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = (e) => {
    if (!e.target.closest('.theme-switcher')) {
        isOpen.value = false;
    }
};

onMounted(() => {
    // Load saved theme or default to dark
    const savedTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(savedTheme);

    // Close dropdown when clicking outside
    document.addEventListener('click', closeDropdown);
});

const currentThemeData = ref(themes.find(t => t.id === currentTheme.value) || themes[1]);

watch(currentTheme, (newTheme) => {
    currentThemeData.value = themes.find(t => t.id === newTheme) || themes[1];
});
</script>

<template>
    <div class="theme-switcher relative">
        <button
            @click="toggleDropdown"
            class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg text-sm font-medium transition-colors
                   bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600
                   text-gray-700 dark:text-gray-300"
            :title="'Theme: ' + currentThemeData.title"
        >
            <span class="text-base">{{ currentThemeData.icon }}</span>
            <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-36 rounded-lg shadow-lg py-1 z-50
                       bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
            >
                <button
                    v-for="theme in themes"
                    :key="theme.id"
                    @click="applyTheme(theme.id)"
                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors
                           hover:bg-gray-100 dark:hover:bg-gray-700"
                    :class="{
                        'bg-gray-100 dark:bg-gray-700': currentTheme === theme.id,
                        'text-gray-900 dark:text-white': currentTheme === theme.id,
                        'text-gray-700 dark:text-gray-300': currentTheme !== theme.id,
                    }"
                    :title="theme.title"
                >
                    <span class="text-base">{{ theme.icon }}</span>
                    <span>{{ theme.name }}</span>
                    <svg
                        v-if="currentTheme === theme.id"
                        class="w-4 h-4 ml-auto text-emerald-500"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </Transition>
    </div>
</template>
