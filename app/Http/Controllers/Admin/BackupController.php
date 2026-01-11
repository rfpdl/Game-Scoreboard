<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class BackupController extends Controller
{
    public function index(): Response
    {
        $backups = $this->getBackups();

        return Inertia::render('Admin/Backups/Index', [
            'backups' => $backups,
            'backupFrequency' => Setting::get('backup_frequency', 'daily'),
            'lastBackup' => Setting::get('last_backup_at'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            // Run backup command
            Artisan::call('backup:run', ['--only-db' => true]);

            Setting::set('last_backup_at', now()->toISOString());

            ActivityLog::log(
                $request->user()->id,
                'created',
                'backup',
                null,
                'Manual database backup',
                null,
                $request->ip()
            );

            return redirect()->back()->with('success', 'Backup created successfully.');
        } catch (Exception $exception) {
            return redirect()->back()->with('error', 'Backup failed: '.$exception->getMessage());
        }
    }

    public function download(string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        $path = $this->getBackupPath($filename);

        if (! Storage::disk('local')->exists($path)) {
            return redirect()->back()->with('error', 'Backup file not found.');
        }

        return response()->download(Storage::disk('local')->path($path));
    }

    public function destroy(Request $request, string $filename): RedirectResponse
    {
        $path = $this->getBackupPath($filename);

        if (! Storage::disk('local')->exists($path)) {
            return redirect()->back()->with('error', 'Backup file not found.');
        }

        Storage::disk('local')->delete($path);

        ActivityLog::log(
            $request->user()->id,
            'deleted',
            'backup',
            null,
            $filename,
            null,
            $request->ip()
        );

        return redirect()->back()->with('success', 'Backup deleted successfully.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup_frequency' => 'required|in:hourly,daily,weekly,disabled',
        ]);

        Setting::set('backup_frequency', $validated['backup_frequency']);

        ActivityLog::log(
            $request->user()->id,
            'updated',
            'setting',
            null,
            'Backup frequency',
            ['frequency' => $validated['backup_frequency']],
            $request->ip()
        );

        return redirect()->back()->with('success', 'Backup settings updated.');
    }

    private function getBackups(): array
    {
        $backupPath = config('app.name', 'laravel-backup');
        $path = $backupPath;

        if (! Storage::disk('local')->exists($path)) {
            return [];
        }

        $files = Storage::disk('local')->files($path);
        $backups = [];

        foreach ($files as $file) {
            if (str_ends_with($file, '.zip')) {
                $backups[] = [
                    'filename' => basename($file),
                    'size' => $this->formatBytes(Storage::disk('local')->size($file)),
                    'created_at' => date('Y-m-d H:i:s', Storage::disk('local')->lastModified($file)),
                ];
            }
        }

        // Sort by date descending
        usort($backups, fn (array $a, array $b): int => strtotime((string) $b['created_at']) - strtotime((string) $a['created_at']));

        return $backups;
    }

    private function getBackupPath(string $filename): string
    {
        $backupPath = config('app.name', 'laravel-backup');

        return $backupPath.'/'.$filename;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= 1024 ** $pow;

        return round($bytes, $precision).' '.$units[$pow];
    }
}
