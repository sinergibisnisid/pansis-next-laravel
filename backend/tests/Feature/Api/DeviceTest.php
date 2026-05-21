<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Device;
use App\Enums\DeviceStatus;

uses(Tests\Traits\AuthenticatedUser::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
});

describe('Device CRUD', function () {
    test('list devices', function () {
        $user = $this->authenticateSuperAdmin();

        Device::factory()->count(5)->create(['branch_id' => $this->branch->id]);

        $response = $this->getJson('/api/devices');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    });

    test('register device', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->postJson('/api/devices', [
            'branch_id' => $this->branch->id,
            'name' => 'Fingerprint Scanner 01',
            'serial_number' => 'FP-2024-001',
            'type' => 'fingerprint_scanner',
            'ip_address' => '192.168.1.100',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'firmware_version' => '1.0.0',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => ['device', 'token'],
            ]);

        $this->assertDatabaseHas('devices', [
            'serial_number' => 'FP-2024-001',
            'name' => 'Fingerprint Scanner 01',
        ]);
    });
});

describe('Device Heartbeat', function () {
    test('device heartbeat updates status', function () {
        $user = $this->authenticateSuperAdmin();

        $device = Device::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => DeviceStatus::Offline,
        ]);

        $response = $this->postJson("/api/devices/{$device->id}/heartbeat", [
            'status' => 'online',
            'cpu_usage' => 45.5,
            'memory_usage' => 60.2,
            'temperature' => 42.0,
            'signal_strength' => -55,
            'uptime_seconds' => 86400,
        ]);

        $response->assertOk();

        $device->refresh();
        expect($device->status)->toBe(DeviceStatus::Online);
    });

    test('device marked offline after threshold', function () {
        $user = $this->authenticateSuperAdmin();

        $device = Device::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => DeviceStatus::Online,
            'last_heartbeat_at' => now()->subSeconds(120),
        ]);

        // Trigger offline check
        $this->artisan('devices:check-offline');

        $device->refresh();
        expect($device->status)->toBe(DeviceStatus::Offline);
    });
});

describe('Device Authentication', function () {
    test('device authentication with token', function () {
        $user = $this->authenticateSuperAdmin();

        // Register a device first to get a token
        $response = $this->postJson('/api/devices', [
            'branch_id' => $this->branch->id,
            'name' => 'Auth Test Device',
            'serial_number' => 'AUTH-001',
            'type' => 'fingerprint_scanner',
            'ip_address' => '192.168.1.101',
            'mac_address' => 'AA:BB:CC:DD:EE:01',
            'firmware_version' => '1.0.0',
        ]);

        $token = $response->json('data.token');
        $serialNumber = 'AUTH-001';

        // Authenticate with the device token
        $authResponse = $this->postJson('/api/devices/authenticate', [
            'serial_number' => $serialNumber,
            'token' => $token,
        ]);

        $authResponse->assertOk()
            ->assertJsonFragment(['serial_number' => $serialNumber]);
    });
});

describe('Device Filtering', function () {
    test('list online devices', function () {
        $user = $this->authenticateSuperAdmin();

        Device::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'status' => DeviceStatus::Online,
        ]);
        Device::factory()->count(2)->create([
            'branch_id' => $this->branch->id,
            'status' => DeviceStatus::Offline,
        ]);

        $response = $this->getJson('/api/devices?status=online');

        $response->assertOk();

        $devices = collect($response->json('data.data') ?? $response->json('data'));
        $devices->each(function ($device) {
            expect($device['status'])->toBe('online');
        });
    });

    test('list offline devices', function () {
        $user = $this->authenticateSuperAdmin();

        Device::factory()->count(2)->create([
            'branch_id' => $this->branch->id,
            'status' => DeviceStatus::Online,
        ]);
        Device::factory()->count(4)->create([
            'branch_id' => $this->branch->id,
            'status' => DeviceStatus::Offline,
        ]);

        $response = $this->getJson('/api/devices?status=offline');

        $response->assertOk();

        $devices = collect($response->json('data.data') ?? $response->json('data'));
        $devices->each(function ($device) {
            expect($device['status'])->toBe('offline');
        });
    });
});
