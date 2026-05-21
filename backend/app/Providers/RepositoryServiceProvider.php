<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\DeviceRepositoryInterface;
use App\Repositories\Contracts\VaultRepositoryInterface;
use App\Repositories\Contracts\VaultSessionRepositoryInterface;
use App\Repositories\Contracts\AlarmLogRepositoryInterface;
use App\Repositories\Contracts\FingerprintRepositoryInterface;
use App\Repositories\Contracts\MaintenancePlanRepositoryInterface;
use App\Repositories\Contracts\NotificationLogRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Repositories\Contracts\LivestreamSessionRepositoryInterface;
use App\Repositories\Contracts\WorkingTimeRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\DeviceRepository;
use App\Repositories\VaultRepository;
use App\Repositories\VaultSessionRepository;
use App\Repositories\AlarmLogRepository;
use App\Repositories\FingerprintRepository;
use App\Repositories\MaintenancePlanRepository;
use App\Repositories\NotificationLogRepository;
use App\Repositories\ReportRepository;
use App\Repositories\LivestreamSessionRepository;
use App\Repositories\WorkingTimeRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array<string, string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        DeviceRepositoryInterface::class => DeviceRepository::class,
        VaultRepositoryInterface::class => VaultRepository::class,
        VaultSessionRepositoryInterface::class => VaultSessionRepository::class,
        AlarmLogRepositoryInterface::class => AlarmLogRepository::class,
        FingerprintRepositoryInterface::class => FingerprintRepository::class,
        MaintenancePlanRepositoryInterface::class => MaintenancePlanRepository::class,
        NotificationLogRepositoryInterface::class => NotificationLogRepository::class,
        ReportRepositoryInterface::class => ReportRepository::class,
        LivestreamSessionRepositoryInterface::class => LivestreamSessionRepository::class,
        WorkingTimeRepositoryInterface::class => WorkingTimeRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        foreach ($this->bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
