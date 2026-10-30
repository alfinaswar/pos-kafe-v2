<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--output= : Destination SQL file path}';
    protected $description = 'Create a MySQL database backup using mysqldump';

    public function handle(): int
    {
        $output = $this->option('output') ?: storage_path('backups/backup-' . now()->format('Ymd-His') . '.sql');
        $directory = dirname($output);
        if (!is_dir($directory)) mkdir($directory, 0750, true);
        $config = config('database.connections.mysql');
        $command = sprintf('mysqldump --host=%s --port=%s --user=%s %s > %s', escapeshellarg($config['host']), escapeshellarg($config['port']), escapeshellarg($config['username']), escapeshellarg($config['database']), escapeshellarg($output));
        $result = Process::run($command);
        if ($result->failed() || !is_file($output) || filesize($output) === 0) {
            $this->error('Database backup failed.');
            return self::FAILURE;
        }
        $this->info("Backup created: {$output}");
        return self::SUCCESS;
    }
}
