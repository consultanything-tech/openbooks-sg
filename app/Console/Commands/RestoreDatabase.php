<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RestoreDatabase extends Command
{
    protected $signature = 'backup:restore {file : The backup filename in storage/app/backups/} {--force : Skip confirmation prompt}';

    protected $description = 'Restore the database from a compressed backup file';

    public function handle(): int
    {
        $filename = basename($this->argument('file')); // prevent path traversal
        $filepath = storage_path('app/backups/'.$filename);

        if (! file_exists($filepath)) {
            $this->error("Backup file not found: {$filename}");
            $this->line('Use <fg=cyan>php artisan backup:list</> to see available backups.');

            return Command::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->confirm("This will OVERWRITE the current database with backup [{$filename}]. Continue?")) {
                $this->info('Restore cancelled.');

                return Command::SUCCESS;
            }
        }

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $command = sprintf(
            'gunzip < %s | mysql --host=%s --port=%s --user=%s --password=%s %s 2>&1',
            escapeshellarg($filepath),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Database restore failed.');
            if (! empty($output)) {
                $this->line(implode("\n", $output));
            }

            return Command::FAILURE;
        }

        $this->info("Database restored successfully from: {$filename}");

        return Command::SUCCESS;
    }
}
