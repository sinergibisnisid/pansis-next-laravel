<?php

namespace App\Actions\Device;

use App\DTOs\Device\RegisterDeviceDTO;
use App\Models\Device;
use App\Services\DeviceService;

class RegisterDeviceAction
{
    public function __construct(
        private readonly DeviceService $deviceService,
    ) {}

    public function execute(RegisterDeviceDTO $dto): Device
    {
        $result = $this->deviceService->registerDevice($dto);

        return $result['device'];
    }
}
