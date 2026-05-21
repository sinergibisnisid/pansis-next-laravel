<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceDue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $planId,
        public readonly string $branchId,
        public readonly string $vaultId,
        public readonly string $deviceId,
        public readonly string $scheduledDate,
        public readonly string $type,
    ) {}
}
