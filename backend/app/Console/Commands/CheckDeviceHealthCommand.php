<?php

namespace App\Console\Commands;

use App\Jobs\CheckDeviceHealthJob;
use Illuminate\Console\Command;

class CheckDeviceHealthCommand extends Command
{
    protected $signature = 'devices:check-health';

    protected $description = 'Check device health and mark offline devices';

    public function handle(): int
    {
        $this->info('Dispatching device health check job...');

        CheckDeviceHealthJob::dispatch();

        $this->info('Device health check job dispatched successfully.');

        return self::SUCCESS;
    }
}
