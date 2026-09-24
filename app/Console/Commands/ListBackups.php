<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ListBackups extends Command
{
    protected $signature = 'backup:list';

    protected $description = 'List all available database backups';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            $this->warn('No backups directory found. Run "php artisan backup:run" to create one.');

            return Command::SUCCESS;
        }

        $files = glob($backupDir.'/openbooks-backup-*.sql.gz');

        if (empty($files)) {
            $this->warn('No backups found. Run "php artisan backup:run" to create one.');

            return Command::SUCCESS;
        }

        // Sort newest first
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        $rows = [];
        foreach ($files as $file) {
            $rows[] = [
                basename($file),
                date('Y-m-d H:i:s', filemtime($file)),
                $this->formatBytes(filesize($file)),
            ];
        }

        $this->info(count($files).' backup(s) found:');
        $this->table(['Filename', 'Date', 'Size'], $rows);

        return Command::SUCCESS;
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
