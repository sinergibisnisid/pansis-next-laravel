<?php

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Vault;
use App\Models\Device;
use App\Models\User;
use App\Enums\DeviceStatus;
use App\Enums\VaultStatus;

uses(Tests\Traits\AuthenticatedUser::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
    $this->vault = Vault::factory()->create(['branch_id' => $this->branch->id]);
    $this->device = Device::factory()->create([
        'vault_id' => $this->vault->id,
        'branch_id' => $this->branch->id,
        'status' => DeviceStatus::Online,
    ]);
});

describe('MQTT Message Processing', function () {
    test('process fingerprint scan message', function () {
        $user = $this->authenticateSuperAdmin();

        $payload = [
            'type' => 'fingerprint_scan',
            'device_id' => $this->device->id,
            'vault_id' => $this->vault->id,
            'user_id' => $user->id,
            'fingerprint_id' => 'fp-001',
            'confidence_score' => 95.5,
            'timestamp' => now()->toIso8601String(),
        ];

        $response = $this->postJson('/api/mqtt/process', [
            'topic' => "vault/{$this->vault->id}/fingerprint",
            'payload' => $payload,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    });

    test('process device heartbeat message', function () {
        $user = $this->authenticateSuperAdmin();

        $payload = [
            'type' => 'heartbeat',
            'device_id' => $this->device->id,
            'status' => 'online',
            'cpu_usage' => 35.2,
            'memory_usage' => 55.8,
            'temperature' => 40.5,
            'signal_strength' => -45,
            'uptime_seconds' => 172800,
            'timestamp' => now()->toIso8601String(),
        ];

        $response = $this->postJson('/api/mqtt/process', [
            'topic' => "device/{$this->device->id}/heartbeat",
            'payload' => $payload,
        ]);

        $response->assertOk();

        $this->device->refresh();
        expect($this->device->last_heartbeat_at)->not->toBeNull();
    });

    test('process vault open message', function () {
        $user = $this->authenticateSuperAdmin();

        $payload = [
            'type' => 'vault_open',
            'device_id' => $this->device->id,
            'vault_id' => $this->vault->id,
            'user_id' => $user->id,
            'access_type' => 'manual',
            'timestamp' => now()->toIso8601String(),
        ];

        $response = $this->postJson('/api/mqtt/process', [
            'topic' => "vault/{$this->vault->id}/open",
            'payload' => $payload,
        ]);

        $response->assertOk();
    });

    test('process vault alarm message', function () {
        $user = $this->authenticateSuperAdmin();

        $payload = [
            'type' => 'alarm',
            'device_id' => $this->device->id,
            'vault_id' => $this->vault->id,
            'alarm_type' => 'session_timeout',
            'severity' => 'critical',
            'description' => 'Vault session exceeded maximum duration',
            'timestamp' => now()->toIso8601String(),
        ];

        $response = $this->postJson('/api/mqtt/process', [
            'topic' => "vault/{$this->vault->id}/alarm",
            'payload' => $payload,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('alarm_logs', [
            'vault_id' => $this->vault->id,
            'device_id' => $this->device->id,
        ]);
    });

    test('invalid MQTT payload handling', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->postJson('/api/mqtt/process', [
            'topic' => 'invalid/topic',
            'payload' => null,
        ]);

        $response->assertStatus(422);
    });
});
