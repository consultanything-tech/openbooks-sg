<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Create a compressed MySQL database backup';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $filename = 'openbooks-backup-'.now()->format('Y-m-d-His').'.sql.gz';
        $filepath = $backupDir.'/'.$filename;

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s 2>/dev/null | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || ! file_exists($filepath) || filesize($filepath) === 0) {
            @unlink($filepath);
            $this->error('Database backup failed. Check mysqldump credentials and availability.');

            return Command::FAILURE;
        }

        $this->pruneOldBackups($backupDir, 10);

        $size = $this->formatBytes(filesize($filepath));
        $this->info("Backup created successfully: {$filename} ({$size})");
        $this->line("Path: {$filepath}");

        // Log activity
        $user = auth()->user();
        if ($user && class_exists(ActivityLog::class)) {
            try {
                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => 'backup',
                    'description' => "Created database backup {$filename} ({$size})",
                    'model_type' => 'System',
                ]);
            } catch (\Throwable $e) {
                // Silently ignore logging failures
            }
        }

        return Command::SUCCESS;
    }

    private function pruneOldBackups(string $dir, int $keep): void
    {
        $files = glob($dir.'/openbooks-backup-*.sql.gz');
        if (count($files) <= $keep) {
            return;
        }

        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }
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
