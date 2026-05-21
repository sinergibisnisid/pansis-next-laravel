<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Vault;
use App\Models\Device;
use App\Models\AlarmLog;
use App\Enums\AlarmStatus;
use App\Enums\AlarmType;
use App\Enums\Severity;

uses(Tests\Traits\AuthenticatedUser::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
    $this->vault = Vault::factory()->create(['branch_id' => $this->branch->id]);
    $this->device = Device::factory()->create([
        'vault_id' => $this->vault->id,
        'branch_id' => $this->branch->id,
    ]);
});

describe('Alarm Listing', function () {
    test('list alarms with filtering', function () {
        $user = $this->authenticateSuperAdmin();

        AlarmLog::factory()->count(5)->create([
            'vault_id' => $this->vault->id,
            'device_id' => $this->device->id,
            'branch_id' => $this->branch->id,
            'status' => AlarmStatus::Active,
        ]);

        AlarmLog::factory()->count(3)->create([
            'vault_id' => $this->vault->id,
            'device_id' => $this->device->id,
            'branch_id' => $this->branch->id,
            'status' => AlarmStatus::Resolved,
        ]);

        $response = $this->getJson('/api/alarms?status=active');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    });

    test('get active alarms', function () {
        $user = $this->authenticateSuperAdmin();

        AlarmLog::factory()->count(3)->create([
            'vault_id' => $this->vault->id,
            'device_id' => $this->device->id,
            'branch_id' => $this->branch->id,
            'status' => AlarmStatus::Active,
            'severity' => Severity::Critical,
        ]);

        $response = $this->getJson('/api/alarms/active');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    });
});

describe('Alarm Actions', function () {
    test('acknowledge alarm', function () {
        $user = $this->authenticateSuperAdmin();

        $alarm = AlarmLog::factory()->create([
            'vault_id' => $this->vault->id,
            'device_id' => $this->device->id,
            'branch_id' => $this->branch->id,
            'status' => AlarmStatus::Active,
        ]);

        $response = $this->postJson("/api/alarms/{$alarm->id}/acknowledge");

        $response->assertOk();

        $alarm->refresh();
        expect($alarm->status)->toBe(AlarmStatus::Acknowledged);
        expect($alarm->acknowledged_by)->toBe($user->id);
        expect($alarm->acknowledged_at)->not->toBeNull();
    });

    test('resolve alarm', function () {
        $user = $this->authenticateSuperAdmin();

        $alarm = AlarmLog::factory()->create([
            'vault_id' => $this->vault->id,
            'device_id' => $this->device->id,
            'branch_id' => $this->branch->id,
            'status' => AlarmStatus::Acknowledged,
            'acknowledged_by' => $user->id,
            'acknowledged_at' => now(),
        ]);

        $response = $this->postJson("/api/alarms/{$alarm->id}/resolve", [
            'resolution_notes' => 'False alarm - maintenance activity',
        ]);

        $response->assertOk();

        $alarm->refresh();
        expect($alarm->status)->toBe(AlarmStatus::Resolved);
        expect($alarm->resolved_by)->toBe($user->id);
        expect($alarm->resolved_at)->not->toBeNull();
        expect($alarm->resolution_notes)->toBe('False alarm - maintenance activity');
    });
});

describe('Alarm Statistics', function () {
    test('alarm statistics', function () {
        $user = $this->authenticateSuperAdmin();

        AlarmLog::factory()->count(5)->create([
            'vault_id' => $this->vault->id,
            'device_id' => $this->device->id,
            'branch_id' => $this->branch->id,
            'status' => AlarmStatus::Active,
            'severity' => Severity::Critical,
        ]);

        AlarmLog::factory()->count(3)->create([
            'vault_id' => $this->vault->id,
            'device_id' => $this->device->id,
            'branch_id' => $this->branch->id,
            'status' => AlarmStatus::Resolved,
            'severity' => Severity::Warning,
        ]);

        $response = $this->getJson('/api/alarms/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'active',
                    'acknowledged',
                    'resolved',
                ],
            ]);
    });
});
