<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Vault;
use App\Models\Device;
use App\Enums\VaultStatus;
use App\Enums\DeviceStatus;

uses(Tests\Traits\AuthenticatedUser::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
});

describe('Dashboard', function () {
    test('dashboard returns aggregated data', function () {
        $user = $this->authenticateSuperAdmin();

        Vault::factory()->count(5)->create(['branch_id' => $this->branch->id]);
        Device::factory()->count(10)->create(['branch_id' => $this->branch->id]);

        $response = $this->getJson('/api/monitoring/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'vaults',
                    'devices',
                    'alarms',
                ],
            ]);
    });
});

describe('Status Endpoints', function () {
    test('vault status endpoint', function () {
        $user = $this->authenticateSuperAdmin();

        Vault::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'status' => VaultStatus::Locked,
        ]);
        Vault::factory()->count(2)->create([
            'branch_id' => $this->branch->id,
            'status' => VaultStatus::Unlocked,
        ]);

        $response = $this->getJson('/api/monitoring/vaults');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    });

    test('device status endpoint', function () {
        $user = $this->authenticateSuperAdmin();

        Device::factory()->count(5)->create([
            'branch_id' => $this->branch->id,
            'status' => DeviceStatus::Online,
        ]);
        Device::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'status' => DeviceStatus::Offline,
        ]);

        $response = $this->getJson('/api/monitoring/devices');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    });
});

describe('Health & Metrics', function () {
    test('server health endpoint', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->getJson('/api/monitoring/health');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'status',
                    'timestamp',
                ],
            ]);
    });

    test('metrics endpoint', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->getJson('/api/monitoring/metrics');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    });

    test('prometheus metrics format', function () {
        $response = $this->getJson('/api/monitoring/metrics/prometheus');

        $response->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8');
    });
});
