<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCleanupCommand extends Command
{
    protected $signature = 'backup:cleanup';

    protected $description = 'Apply retention policy to existing backups (daily / weekly / monthly).';

    public function handle(BackupService $backup): int
    {
        $this->info('Applying backup retention policy...');
        $removed = $backup->cleanup();
        $this->info(sprintf('Removed %d backup(s).', count($removed)));

        foreach ($removed as $path) {
            $this->line(' - ' . $path);
        }

        return self::SUCCESS;
    }
}
