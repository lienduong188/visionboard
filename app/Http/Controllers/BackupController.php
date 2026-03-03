<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class BackupController extends Controller
{
    public function index(Request $request)
    {
        $status  = $this->readStatus();
        $backups = $this->listBackups();

        return Inertia::render('Settings/Backup', [
            'backupStatus' => $status,
            'backups'      => $backups,
        ]);
    }

    public function run(Request $request)
    {
        $exitCode = Artisan::call('backup:run');

        if ($exitCode === 0) {
            return back()->with('success', 'Backup thành công!');
        }

        return back()->with('error', 'Backup thất bại. Kiểm tra log để biết chi tiết.');
    }

    public static function readStatus(): array
    {
        $statusPath = storage_path('app/backup_status.json');

        if (!file_exists($statusPath)) {
            return [
                'last_backup_at' => null,
                'file'           => null,
                'size_bytes'     => 0,
                'status'         => null,
                'error'          => null,
                'is_overdue'     => true,
            ];
        }

        $data = json_decode(file_get_contents($statusPath), true) ?? [];

        $lastAt    = isset($data['last_backup_at']) ? Carbon::parse($data['last_backup_at']) : null;
        $isOverdue = !$lastAt || $lastAt->diffInHours(Carbon::now()) > 25;

        return array_merge($data, ['is_overdue' => $isOverdue]);
    }

    private function listBackups(): array
    {
        $dir   = storage_path('app/backups');
        $files = glob("{$dir}/backup_*.sqlite") ?: [];

        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        return array_map(function ($path) {
            $name = basename($path);
            // backup_20260303_020000.sqlite → 2026-03-03 02:00:00
            preg_match('/backup_(\d{8})_(\d{6})\.sqlite/', $name, $m);
            $dateStr = isset($m[1], $m[2])
                ? substr($m[1], 0, 4) . '-' . substr($m[1], 4, 2) . '-' . substr($m[1], 6, 2)
                  . ' ' . substr($m[2], 0, 2) . ':' . substr($m[2], 2, 2) . ':' . substr($m[2], 4, 2)
                : null;

            return [
                'file'       => $name,
                'size_bytes' => filesize($path),
                'created_at' => $dateStr,
            ];
        }, $files);
    }
}
