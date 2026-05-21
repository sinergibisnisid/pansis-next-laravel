<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Vault;
use App\Models\VaultSession;
use App\Models\Device;
use App\Models\WorkingTime;
use App\Enums\VaultStatus;
use App\Enums\SessionStatus;

uses(Tests\Traits\AuthenticatedUser::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
    $this->vault = Vault::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => VaultStatus::Locked,
        'is_active' => true,
    ]);
    $this->device = Device::factory()->create([
        'vault_id' => $this->vault->id,
        'branch_id' => $this->branch->id,
    ]);
});

describe('Vault CRUD', function () {
    test('list vaults paginated', function () {
        $user = $this->authenticateSuperAdmin();

        Vault::factory()->count(15)->create(['branch_id' => $this->branch->id]);

        $response = $this->getJson('/api/vaults?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'meta',
                ],
            ]);

        expect($response->json('data.data'))->toHaveCount(10);
    });

    test('create vault', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->postJson('/api/vaults', [
            'branch_id' => $this->branch->id,
            'name' => 'Vault Utama',
            'code' => 'VLT-001',
            'type' => 'main',
            'floor' => '1',
            'room' => 'Room A',
            'max_session_duration_minutes' => 10,
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Vault Utama']);
    });

    test('update vault', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->putJson("/api/vaults/{$this->vault->id}", [
            'name' => 'Vault Updated',
            'max_session_duration_minutes' => 15,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Vault Updated']);
    });

    test('delete vault soft deletes', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->deleteJson("/api/vaults/{$this->vault->id}");

        $response->assertOk();

        $this->assertSoftDeleted('vaults', ['id' => $this->vault->id]);
    });

    test('get vault status', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->getJson("/api/vaults/{$this->vault->id}/status");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['vault', 'status', 'active_session', 'is_open'],
            ]);
    });
});

describe('Vault Access', function () {
    test('open vault with valid fingerprint', function () {
        $user = $this->authenticateSuperAdmin();

        // Setup working time to allow access
        WorkingTime::factory()->create([
            'branch_id' => $this->branch->id,
            'vault_id' => $this->vault->id,
            'days' => [strtolower(now()->format('l'))],
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'is_active' => true,
            'type' => 'regular',
        ]);

        $response = $this->postJson("/api/vaults/{$this->vault->id}/open", [
            'user_id' => $user->id,
            'access_type' => 'manual',
            'device_id' => $this->device->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['vault', 'session'],
            ]);
    });

    test('open vault denied outside working hours', function () {
        $user = $this->authenticateSuperAdmin();

        // Setup working time that doesn't include current time
        WorkingTime::factory()->create([
            'branch_id' => $this->branch->id,
            'vault_id' => $this->vault->id,
            'days' => [strtolower(now()->format('l'))],
            'start_time' => '03:00:00',
            'end_time' => '03:01:00',
            'is_active' => true,
            'type' => 'regular',
        ]);

        // Travel to a time outside working hours
        $this->travelTo(now()->setTime(12, 0, 0));

        $response = $this->postJson("/api/vaults/{$this->vault->id}/open", [
            'user_id' => $user->id,
            'access_type' => 'fingerprint',
            'device_id' => $this->device->id,
            'fingerprint_device_id' => 'fp-device-001',
        ]);

        $response->assertStatus(422);
    });

    test('open vault denied with invalid fingerprint', function () {
        $user = $this->authenticateSuperAdmin();

        // Setup working time to allow access
        WorkingTime::factory()->create([
            'branch_id' => $this->branch->id,
            'vault_id' => $this->vault->id,
            'days' => [strtolower(now()->format('l'))],
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'is_active' => true,
            'type' => 'regular',
        ]);

        $response = $this->postJson("/api/vaults/{$this->vault->id}/open", [
            'user_id' => $user->id,
            'access_type' => 'fingerprint',
            'device_id' => $this->device->id,
            'fingerprint_device_id' => 'invalid-device',
        ]);

        $response->assertStatus(422);
    });

    test('close vault', function () {
        $user = $this->authenticateSuperAdmin();

        // Create an active session
        $session = VaultSession::factory()->create([
            'vault_id' => $this->vault->id,
            'user_id' => $user->id,
            'device_id' => $this->device->id,
            'status' => SessionStatus::Active,
            'opened_at' => now()->subMinutes(5),
        ]);

        $this->vault->update(['status' => VaultStatus::Unlocked]);

        $response = $this->postJson("/api/vaults/{$this->vault->id}/close", [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'close_reason' => 'normal',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['vault', 'session', 'duration_seconds'],
            ]);
    });

    test('session timeout triggers alarm', function () {
        $user = $this->authenticateSuperAdmin();

        // Create an expired session
        VaultSession::factory()->create([
            'vault_id' => $this->vault->id,
            'user_id' => $user->id,
            'device_id' => $this->device->id,
            'status' => SessionStatus::Active,
            'opened_at' => now()->subMinutes(15),
        ]);

        $this->vault->update(['status' => VaultStatus::Unlocked]);

        // Trigger session timeout check via artisan or service
        $this->artisan('vault:check-timeouts');

        $this->vault->refresh();
        expect($this->vault->status)->toBe(VaultStatus::Alarm);
    });

    test('list active sessions', function () {
        $user = $this->authenticateSuperAdmin();

        VaultSession::factory()->count(3)->create([
            'vault_id' => $this->vault->id,
            'user_id' => $user->id,
            'status' => SessionStatus::Active,
            'opened_at' => now(),
        ]);

        $response = $this->getJson('/api/vaults/sessions/active');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    });
});
