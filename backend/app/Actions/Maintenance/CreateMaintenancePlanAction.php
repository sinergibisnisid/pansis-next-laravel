<?php

namespace App\Actions\Maintenance;

use App\DTOs\Maintenance\CreateMaintenancePlanDTO;
use App\Models\MaintenancePlan;
use App\Services\MaintenanceService;

class CreateMaintenancePlanAction
{
    public function __construct(
        private readonly MaintenanceService $maintenanceService,
    ) {}

    public function execute(CreateMaintenancePlanDTO $dto): MaintenancePlan
    {
        return $this->maintenanceService->createPlan($dto);
    }
}
