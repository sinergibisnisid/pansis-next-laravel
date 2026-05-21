<?php

namespace App\Actions\Maintenance;

use App\Models\MaintenancePlan;
use App\Services\MaintenanceService;

class CompleteMaintenanceAction
{
    public function __construct(
        private readonly MaintenanceService $maintenanceService,
    ) {}

    public function execute(string $planId, string $userId, ?string $notes = null): MaintenancePlan
    {
        return $this->maintenanceService->completePlan($planId, $userId, $notes);
    }
}
