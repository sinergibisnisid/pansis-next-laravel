<?php

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Device;
use App\Models\Vault;
use App\Services\DeviceService;
use App\Services\NotificationService;
use App\Services\AuditService;
use App\DTOs\Device\RegisterDeviceDTO;
use App\DTOs\Device\HeartbeatDTO;
use App\Enums\DeviceStatus;
use App\Repositories\Contracts\DeviceRepositoryInterface;

beforeEach(function () {
    $this->deviceRepository = Mockery::mock(DeviceRepositoryInterface::class);
    $this->notificationService = Mockery::mock(NotificationService::class);
    $this->auditService = Mockery::mock(AuditService::class);

    $this->deviceService = new DeviceService(
        $this->deviceRepository,
        $this->notificationService,
        $this->auditService,
    );

    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
    $this->vault = Vault::factory()->create(['branch_id' => $this->branch->id]);
});

describe('registerDevice', function () {
    test('registerDevice creates device with token', function () {
        $device = Device::factory()->make([
            'id' => fake()->uuid(),
            'vault_id' => $this->vault->id,
            'branch_id' => $this->branch->id,
            'name' => 'Test Scanner',
            'serial_number' => 'SN-001',
            'type' => 'fingerprint_scanner',
            'status' => DeviceStatus::Offline,
        ]);

        $this->deviceRepository
            ->shouldReceive('create')
            ->withArgs(function ($data) {
                return $data['name'] === 'Test Scanner'
                    && $data['serial_number'] === 'SN-001'
                    && $data['status'] === DeviceStatus::Offline->value
                    && !empty($data['device_token']);
            })
            ->andReturn($device);

        $dto = new RegisterDeviceDTO(
            vaultId: $this->vault->id,
            branchId: $this->branch->id,
            name: 'Test Scanner',
            serialNumber: 'SN-001',
            type: 'fingerprint_scanner',
            ipAddress: '192.168.1.100',
            macAddress: 'AA:BB:CC:DD:EE:FF',
            firmwareVersion: '1.0.0',
        );

        $result = $this->deviceService->registerDevice($dto);

        expect($result)->toHaveKeys(['device', 'token']);
        expect($result['token'])->toBeString()->toHaveLength(64);
        expect($result['device']->name)->toBe('Test Scanner');
    });
});

describe('processHeartbeat', function () {
    test('processHeartbeat updates device status', function () {
        $device = Device::factory()->create([
            'vault_id' => $this->vault->id,
            'branch_id' => $this->branch->id,
            'status' => DeviceStatus::Offline,
        ]);

        $this->deviceRepository
            ->shouldReceive('findOrFail')
            ->with($device->id)
            ->andReturn($device);

        $this->deviceRepository
            ->shouldReceive('updateHeartbeat')
            ->with($device->id, Mockery::type('array'))
            ->once();

        $dto = new HeartbeatDTO(
            deviceId: $device->id,
            status: DeviceStatus::Online->value,
            cpuUsage: 45.5,
            memoryUsage: 60.2,
            temperature: 42.0,
            signalStrength: -55,
            uptimeSeconds: 86400,
        );

        $result = $this->deviceService->processHeartbeat($dto);

        expect($result)->toBeInstanceOf(Device::class);
    });
});

describe('markOffline', function () {
    test('markOffline changes device status', function () {
        $device = Device::factory()->create([
            'vault_id' => $this->vault->id,
            'branch_id' => $this->branch->id,
            'status' => DeviceStatus::Online,
        ]);

        $this->deviceRepository
            ->shouldReceive('findOrFail')
            ->with($device->id)
            ->andReturn($device);

        $this->notificationService
            ->shouldReceive('send')
            ->once();

        $this->deviceService->markOffline($device->id);

        $device->refresh();
        expect($device->status)->toBe(DeviceStatus::Offline);
    });
});
