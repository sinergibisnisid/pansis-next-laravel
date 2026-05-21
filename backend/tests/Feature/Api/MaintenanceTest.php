<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Vault;
use App\Models\Device;
use App\Models\MaintenancePlan;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceFrequency;

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

describe('Maintenance Plans', function () {
    test('create maintenance plan', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->postJson('/api/maintenance', [
            'vault_id' => $this->vault->id,
            'device_id' => $this->device->id,
            'branch_id' => $this->branch->id,
            'title' => 'Monthly Device Inspection',
            'description' => 'Regular monthly inspection of fingerprint scanner',
            'type' => 'preventive',
            'priority' => 'medium',
            'frequency' => 'monthly',
            'scheduled_date' => now()->addDays(7)->toDateString(),
            'assigned_to' => $user->id,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'title', 'status', 'scheduled_date'],
            ]);

        $this->assertDatabaseHas('maintenance_plans', [
            'title' => 'Monthly Device Inspection',
            'status' => MaintenanceStatus::Scheduled->value,
        ]);
    });

    test('complete maintenance plan', function () {
        $user = $this->authenticateSuperAdmin();

        $plan = MaintenancePlan::factory()->create([
            'vault_id' => $this->vault->id,
            'device_id' => $this->device->id,
            'branch_id' => $this->branch->id,
            'status' => MaintenanceStatus::InProgress,
            'scheduled_date' => now()->subDay(),
        ]);

        $response = $this->postJson("/api/maintenance/{$plan->id}/complete", [
            'notes' => 'All checks passed. Device functioning normally.',
            'completed_at' => now()->toDateTimeString(),
        ]);

        $response->assertOk();

        $plan->refresh();
        expect($plan->status)->toBe(MaintenanceStatus::Completed);
    });

    test('list upcoming maintenance', function () {
        $user = $this->authenticateSuperAdmin();

        MaintenancePlan::factory()->count(3)->create([
            'vault_id' => $this->vault->id,
            'device_id' => $this->device->id,
            'branch_id' => $this->branch->id,
            'status' => MaintenanceStatus::Scheduled,
            'scheduled_date' => now()->addDays(5),
        ]);

        MaintenancePlan::factory()->count(2)->create([
            'vault_id' => $this->vault->id,
            'device_id' => $this->device->id,
            'branch_id' => $this->branch->id,
            'status' => MaintenanceStatus::Completed,
            'scheduled_date' => now()->subDays(10),
        ]);

        $response = $this->getJson('/api/maintenance/upcoming');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    });

    test('list overdue maintenance', function () {
        $user = $this->authenticateSuperAdmin();

        MaintenancePlan::factory()->count(4)->create([
            'vault_id' => $this->vault->id,
            'device_id' => $this->device->id,
            'branch_id' => $this->branch->id,
            'status' => MaintenanceStatus::Scheduled,
            'scheduled_date' => now()->subDays(3),
        ]);

        $response = $this->getJson('/api/maintenance/overdue');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    });
});
