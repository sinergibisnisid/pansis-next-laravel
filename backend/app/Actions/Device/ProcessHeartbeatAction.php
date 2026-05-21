<?php

namespace App\Actions\Device;

use App\DTOs\Device\HeartbeatDTO;
use App\Services\DeviceService;

class ProcessHeartbeatAction
{
    public function __construct(
        private readonly DeviceService $deviceService,
    ) {}

    public function execute(HeartbeatDTO $dto): void
    {
        $this->deviceService->processHeartbeat($dto);
    }
}
