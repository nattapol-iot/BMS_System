<?php
namespace App\Http\Controllers;

use App\Core\AuditLog\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function run(Request $request)
    {
        $disk = Storage::disk('local');
        $dir = 'backups';
        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $filename = 'nexus_bms_' . now()->format('Ymd_His') . '.sql';
        $absolutePath = $disk->path($dir . '/' . $filename);

        $host = config('database.connections.mysql.host');
        $port = (string) config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = (string) config('database.connections.mysql.password');

        $mysqldumpCandidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MariaDB 10.11\\bin\\mysqldump.exe',
            'mysqldump',
        ];
        $mysqldump = null;
        foreach ($mysqldumpCandidates as $candidate) {
            if ($candidate === 'mysqldump' || file_exists($candidate)) {
                $mysqldump = $candidate;
                break;
            }
        }
        if ($mysqldump === null) {
            return back()->with('error', 'mysqldump executable not found.');
        }

        $args = [
            escapeshellarg('--host=' . $host),
            escapeshellarg('--port=' . $port),
            escapeshellarg('--user=' . $username),
        ];
        if ($password !== '') {
            $args[] = escapeshellarg('--password=' . $password);
        }
        $args[] = '--single-transaction';
        $args[] = '--quick';
        $args[] = '--default-character-set=utf8mb4';
        $args[] = escapeshellarg('--result-file=' . $absolutePath);
        $args[] = escapeshellarg($database);

        $command = escapeshellarg($mysqldump) . ' ' . implode(' ', $args) . ' 2>&1';
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        clearstatcache(true, $absolutePath);
        if (!file_exists($absolutePath) || filesize($absolutePath) === 0) {
            $stderr = implode("\n", $output);
            return back()->with('error', 'Backup failed (exit ' . $exitCode . '): ' . substr($stderr, 0, 500));
        }
        $size = filesize($absolutePath);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'create',
            'module' => 'settings',
            'description' => "Database backup created: {$filename} (" . $this->formatBytes($size) . ")",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Backup created: {$filename} (" . $this->formatBytes($size) . ")");
    }

    public function download(string $filename)
    {
        $safe = basename($filename);
        $path = 'backups/' . $safe;
        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }
        return response()->download(Storage::disk('local')->path($path));
    }

    public function destroy(Request $request, string $filename)
    {
        $safe = basename($filename);
        $path = 'backups/' . $safe;
        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }
        Storage::disk('local')->delete($path);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'delete',
            'module' => 'settings',
            'description' => "Database backup deleted: {$safe}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Backup deleted: {$safe}");
    }

    public static function listBackups(): array
    {
        $disk = Storage::disk('local');
        if (!$disk->exists('backups')) {
            return [];
        }
        return collect($disk->files('backups'))
            ->filter(fn($f) => str_ends_with($f, '.sql'))
            ->map(fn($f) => [
                'name' => basename($f),
                'size' => $disk->size($f),
                'time' => $disk->lastModified($f),
            ])
            ->sortByDesc('time')
            ->values()
            ->all();
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}
