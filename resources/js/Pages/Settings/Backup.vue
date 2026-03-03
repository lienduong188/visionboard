<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    backupStatus: Object,
    backups: Array,
});

const running = ref(false);

const lastBackupAt = computed(() => {
    if (!props.backupStatus?.last_backup_at) return null;
    return new Date(props.backupStatus.last_backup_at);
});

const lastBackupLabel = computed(() => {
    if (!lastBackupAt.value) return 'Chưa có backup nào';
    const diff = Math.floor((Date.now() - lastBackupAt.value.getTime()) / 60000);
    if (diff < 60) return `${diff} phút trước`;
    if (diff < 1440) return `${Math.floor(diff / 60)} giờ trước`;
    return `${Math.floor(diff / 1440)} ngày trước`;
});

const formatBytes = (bytes) => {
    if (!bytes) return '0 KB';
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
};

const formatDate = (str) => {
    if (!str) return '';
    return new Date(str).toLocaleString('vi-VN', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit',
    });
};

const runBackup = () => {
    running.value = true;
    router.post(route('backup.run'), {}, {
        onFinish: () => { running.value = false; },
    });
};
</script>

<template>
    <Head title="Backup Settings" />
    <AuthenticatedLayout>
        <div class="max-w-2xl mx-auto px-4 py-8">
            <!-- Header -->
            <div class="flex items-center gap-3 mb-8">
                <a href="/settings/reviews" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    ← Quay lại
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">💾 Backup Database</h1>
                    <p class="text-sm text-gray-500 mt-1">Tự động backup hàng ngày lúc 2:00 AM (Asia/Tokyo)</p>
                </div>
            </div>

            <!-- Flash messages -->
            <div v-if="$page.props.flash?.success"
                class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-xl text-green-800 dark:text-green-300 flex items-center gap-2">
                ✅ {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error"
                class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl text-red-800 dark:text-red-300 flex items-center gap-2">
                ❌ {{ $page.props.flash.error }}
            </div>

            <!-- Status Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Trạng thái</h2>

                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span v-if="backupStatus?.status === 'success'"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">
                                ✅ Thành công
                            </span>
                            <span v-else-if="backupStatus?.status === 'failed'"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400">
                                ❌ Thất bại
                            </span>
                            <span v-else
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                — Chưa có backup
                            </span>

                            <span v-if="backupStatus?.is_overdue"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400">
                                ⚠️ Quá hạn
                            </span>
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Lần cuối: <span class="font-medium text-gray-900 dark:text-white">{{ lastBackupLabel }}</span>
                        </p>
                        <p v-if="lastBackupAt" class="text-xs text-gray-400 mt-0.5">
                            {{ formatDate(backupStatus.last_backup_at) }}
                        </p>
                        <p v-if="backupStatus?.size_bytes" class="text-xs text-gray-400 mt-0.5">
                            Kích thước: {{ formatBytes(backupStatus.size_bytes) }}
                        </p>
                        <p v-if="backupStatus?.error" class="text-xs text-red-500 mt-1">
                            Lỗi: {{ backupStatus.error }}
                        </p>
                    </div>

                    <button
                        @click="runBackup"
                        :disabled="running"
                        class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors">
                        <span v-if="running">⏳ Đang backup...</span>
                        <span v-else>💾 Backup ngay</span>
                    </button>
                </div>
            </div>

            <!-- Schedule Info -->
            <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-xl p-4 mb-6">
                <p class="text-sm text-indigo-800 dark:text-indigo-300">
                    🕑 <strong>Lịch tự động:</strong> 2:00 AM hàng ngày (Asia/Tokyo)<br>
                    <span class="text-xs mt-1 block text-indigo-600 dark:text-indigo-400">
                        Cần cron: <code class="bg-indigo-100 dark:bg-indigo-900 px-1 rounded">* * * * * php artisan schedule:run</code>
                    </span>
                </p>
            </div>

            <!-- Backup List -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Lịch sử backup
                    <span class="text-sm font-normal text-gray-500">(giữ 7 bản gần nhất)</span>
                </h2>

                <div v-if="backups?.length" class="space-y-2">
                    <div v-for="(backup, i) in backups" :key="backup.file"
                        class="flex items-center justify-between py-2.5 px-3 rounded-lg"
                        :class="i === 0 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-700/40'">
                        <div>
                            <span class="text-xs font-mono text-gray-600 dark:text-gray-300">{{ backup.file }}</span>
                            <span v-if="i === 0" class="ml-2 text-xs text-green-600 dark:text-green-400">← mới nhất</span>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">{{ formatDate(backup.created_at) }}</p>
                            <p class="text-xs text-gray-400">{{ formatBytes(backup.size_bytes) }}</p>
                        </div>
                    </div>
                </div>

                <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                    Chưa có backup nào. Nhấn "Backup ngay" để tạo bản đầu tiên.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
