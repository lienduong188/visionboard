<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class RunDailyBackup extends Command
{
    protected $signature = 'backup:run';
    protected $description = 'Backup SQLite database to storage/app/backups/';

    public function handle(): int
    {
        $dbPath = database_path('database.sqlite');

        if (!file_exists($dbPath)) {
            $this->writeStatus('failed', null, 0, 'Database file not found');
            $this->error('Database file not found: ' . $dbPath);
            return 1;
        }

        $timestamp = Carbon::now()->format('Ymd_His');
        $filename  = "backup_{$timestamp}.sqlite";
        $backupDir = storage_path('app/backups');

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $dest = "{$backupDir}/{$filename}";

        if (!copy($dbPath, $dest)) {
            $this->writeStatus('failed', null, 0, 'Failed to copy database file');
            $this->error('Backup failed: could not copy database');
            return 1;
        }

        $size = filesize($dest);

        // Giữ 7 bản mới nhất, xóa cũ hơn
        $this->pruneOldBackups($backupDir, 7);

        $this->writeStatus('success', $filename, $size, null);
        $this->info("Backup created: {$filename} (" . round($size / 1024) . " KB)");

        return 0;
    }

    private function pruneOldBackups(string $dir, int $keep): void
    {
        $files = glob("{$dir}/backup_*.sqlite");
        if (!$files) return;

        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }
    }

    private function writeStatus(string $status, ?string $file, int $size, ?string $error): void
    {
        $statusPath = storage_path('app/backup_status.json');

        $data = [
            'last_backup_at' => Carbon::now()->toDateTimeString(),
            'file'           => $file,
            'size_bytes'     => $size,
            'status'         => $status,
            'error'          => $error,
        ];

        file_put_contents($statusPath, json_encode($data, JSON_PRETTY_PRINT));
    }
}
