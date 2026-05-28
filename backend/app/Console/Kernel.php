<?php

namespace App\Console;

use App\Jobs\CheckDeviceHealthJob;
use App\Jobs\CheckSessionTimeoutsJob;
use App\Jobs\CleanupOldDataJob;
use App\Jobs\CollectServerMetricsJob;
use App\Jobs\MaintenanceReminderJob;
use App\Jobs\RetryStaleCommandsJob;
use App\Jobs\SyncOfflineEventsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\MqttSubscribeCommand::class,
        Commands\MqttPublishCommand::class,
        Commands\CheckDeviceHealthCommand::class,
        Commands\BackupRunCommand::class,
        Commands\BackupCleanupCommand::class,
        Commands\BackupRestoreCommand::class,
        Commands\BackupListCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new CheckSessionTimeoutsJob())->everyMinute();

        $schedule->job(new CheckDeviceHealthJob())->everyMinute();

        $schedule->job(new MaintenanceReminderJob())->dailyAt('07:00');

        $schedule->job(new CollectServerMetricsJob())->everyFiveMinutes();

        $schedule->job(new CleanupOldDataJob())->dailyAt('02:00');

        // P1-11: Drain the offline event buffer every minute. New batches uploaded
        // by reconnecting controllers also dispatch this job inline.
        $schedule->job(new SyncOfflineEventsJob())->everyMinute()->withoutOverlapping();

        // P2-23: Retry stale hardware commands (stuck in 'sent' past ack deadline).
        $schedule->job(new RetryStaleCommandsJob())->everyMinute()->withoutOverlapping();

        // P3-30: Postgres backup + retention cleanup.
        if (config('backup.schedule.enabled', true)) {
            $schedule->command('backup:run')
                ->cron(config('backup.schedule.cron', '0 2 * * *'))
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground()
                ->name('backup-run');

            $schedule->command('backup:cleanup')
                ->cron(config('backup.schedule.cleanup_cron', '0 3 * * *'))
                ->withoutOverlapping()
                ->onOneServer()
                ->name('backup-cleanup');
        }
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
