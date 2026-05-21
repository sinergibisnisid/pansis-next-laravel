<?php

namespace App\Providers;

use App\Events\AlarmAcknowledged;
use App\Events\AlarmResolved;
use App\Events\DeviceHeartbeatReceived;
use App\Events\DeviceStatusChanged;
use App\Events\FingerprintScanned;
use App\Events\LivestreamStarted;
use App\Events\LivestreamStopped;
use App\Events\MaintenanceDue;
use App\Events\SessionTimeoutWarning;
use App\Events\UnauthorizedAccessAttempt;
use App\Events\VaultAlarmTriggered;
use App\Events\VaultClosed;
use App\Events\VaultOpened;
use App\Listeners\HandleDeviceHeartbeat;
use App\Listeners\HandleDeviceStatusChange;
use App\Listeners\HandleFingerprintScan;
use App\Listeners\HandleMaintenanceDue;
use App\Listeners\HandleSessionTimeout;
use App\Listeners\HandleUnauthorizedAccess;
use App\Listeners\HandleVaultAlarm;
use App\Listeners\HandleVaultClosed;
use App\Listeners\HandleVaultOpened;
use App\Listeners\SendAlarmNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        VaultOpened::class => [
            HandleVaultOpened::class,
        ],

        VaultClosed::class => [
            HandleVaultClosed::class,
        ],

        VaultAlarmTriggered::class => [
            HandleVaultAlarm::class,
            SendAlarmNotification::class,
        ],

        DeviceStatusChanged::class => [
            HandleDeviceStatusChange::class,
        ],

        DeviceHeartbeatReceived::class => [
            HandleDeviceHeartbeat::class,
        ],

        FingerprintScanned::class => [
            HandleFingerprintScan::class,
        ],

        SessionTimeoutWarning::class => [
            HandleSessionTimeout::class,
        ],

        UnauthorizedAccessAttempt::class => [
            HandleUnauthorizedAccess::class,
        ],

        MaintenanceDue::class => [
            HandleMaintenanceDue::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
