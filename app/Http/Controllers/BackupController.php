<?php

namespace App\Http\Controllers;

use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $backupDir = storage_path('app/backups');
        $backups = [];

        if (is_dir($backupDir)) {
            $files = glob($backupDir.'/openbooks-backup-*.sql.gz');
            usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'date' => date('Y-m-d H:i:s', filemtime($file)),
                    'size' => $this->formatBytes(filesize($file)),
                ];
            }
        }

        return view('settings.backups', compact('backups'));
    }

    public function create()
    {
        $exitCode = Artisan::call('backup:run');
        $output = trim(Artisan::output());

        if ($exitCode === 0) {
            $this->logActivity('backup', 'Created database backup via web interface', 'System');

            return redirect()->route('settings.backups')->with('success', $output ?: 'Backup created successfully.');
        }

        return redirect()->route('settings.backups')->with('error', $output ?: 'Backup failed.');
    }

    public function download(string $filename)
    {
        $filename = basename($filename); // prevent path traversal

        if (! $this->isValidBackupFilename($filename)) {
            abort(400, 'Invalid backup filename.');
        }

        $filepath = storage_path('app/backups/'.$filename);

        if (! file_exists($filepath)) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/gzip',
        ]);
    }

    public function destroy(string $filename)
    {
        $filename = basename($filename); // prevent path traversal

        if (! $this->isValidBackupFilename($filename)) {
            return redirect()->route('settings.backups')->with('error', 'Invalid backup filename.');
        }

        $filepath = storage_path('app/backups/'.$filename);

        if (! file_exists($filepath)) {
            return redirect()->route('settings.backups')->with('error', 'Backup file not found.');
        }

        @unlink($filepath);

        $this->logActivity('deleted', "Deleted database backup {$filename}", 'System');

        return redirect()->route('settings.backups')->with('success', "Backup '{$filename}' deleted.");
    }

    private function isValidBackupFilename(string $filename): bool
    {
        return (bool) preg_match('/^openbooks-backup-\d{4}-\d{2}-\d{2}-\d{6}\.sql\.gz$/', $filename);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
