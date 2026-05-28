<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupListCommand extends Command
{
    protected $signature = 'backup:list';

    protected $description = 'List all backups currently stored on the configured disk.';

    public function handle(BackupService $backup): int
    {
        $files = $backup->list();

        if (empty($files)) {
            $this->info('No backups found.');
            return self::SUCCESS;
        }

        $rows = array_map(fn ($f) => [
            $f['filename'],
            $this->humanSize($f['size_bytes']),
            $f['last_modified'] ?? '—',
        ], $files);

        $this->table(['Filename', 'Size', 'Modified'], $rows);
        $this->info(sprintf('%d backup(s) on disk %s.', count($files), config('backup.disk')));

        return self::SUCCESS;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }
        return sprintf('%.2f %s', $value, $units[$i]);
    }
}
