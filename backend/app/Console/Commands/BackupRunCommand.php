<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupRunCommand extends Command
{
    protected $signature = 'backup:run {--cleanup : Also run retention cleanup after backup}';

    protected $description = 'Run a fresh Postgres backup (encrypted if BACKUP_ENCRYPTION_PASSPHRASE is set).';

    public function handle(BackupService $backup): int
    {
        $this->info('Starting backup...');
        try {
            $result = $backup->run();
            $this->info(sprintf(
                'Backup written: %s (%.2f MB, encrypted=%s, %.2fs)',
                $result['filename'],
                $result['size_bytes'] / 1024 / 1024,
                $result['encrypted'] ? 'yes' : 'no',
                $result['duration_seconds'] ?? 0,
            ));

            if ($this->option('cleanup')) {
                $removed = $backup->cleanup();
                $this->info(sprintf('Cleanup removed %d old backup(s).', count($removed)));
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
