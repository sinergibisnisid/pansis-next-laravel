<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupRestoreCommand extends Command
{
    protected $signature = 'backup:restore
        {file : Remote backup path (e.g. backups/pansin-20260527_020000.dump.gpg)}
        {--force : Skip the destructive-action confirmation}';

    protected $description = 'Restore the database from a backup file. DESTRUCTIVE — drops existing data.';

    public function handle(BackupService $backup): int
    {
        $file = (string) $this->argument('file');

        $this->warn('==================================================');
        $this->warn(' RESTORE WILL OVERWRITE THE CURRENT DATABASE');
        $this->warn('==================================================');
        $this->line(' File: ' . $file);
        $this->line(' Disk: ' . config('backup.disk'));
        $this->line(' Database: ' . config('database.connections.' . config('database.default') . '.database'));

        if (!$this->option('force')) {
            if (!$this->confirm('Continue?', false)) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        try {
            $result = $backup->restore($file);
            $this->info('Restore completed at ' . $result['restored_at']);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Restore failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
