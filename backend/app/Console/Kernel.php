<?php

namespace App\Console;

use App\Jobs\CheckDeviceHealthJob;
use App\Jobs\CheckSessionTimeoutsJob;
use App\Jobs\CleanupOldDataJob;
use App\Jobs\CollectServerMetricsJob;
use App\Jobs\MaintenanceReminderJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\MqttSubscribeCommand::class,
        Commands\MqttPublishCommand::class,
        Commands\CheckDeviceHealthCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new CheckSessionTimeoutsJob())->everyMinute();

        $schedule->job(new CheckDeviceHealthJob())->everyMinute();

        $schedule->job(new MaintenanceReminderJob())->dailyAt('07:00');

        $schedule->job(new CollectServerMetricsJob())->everyFiveMinutes();

        $schedule->job(new CleanupOldDataJob())->dailyAt('02:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
