<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Vault;
use App\Models\Device;
use App\Models\VaultSession;
use App\Models\WorkingTime;
use App\Services\VaultService;
use App\Services\AuditService;
use App\Services\SnapshotService;
use App\Services\NotificationService;
use App\Services\WorkingTimeService;
use App\DTOs\Vault\VaultAccessDTO;
use App\DTOs\Vault\CloseVaultDTO;
use App\Enums\VaultStatus;
use App\Enums\SessionStatus;
use App\Repositories\Contracts\VaultRepositoryInterface;
use App\Repositories\Contracts\VaultSessionRepositoryInterface;
use App\Repositories\Contracts\WorkingTimeRepositoryInterface;
use App\Repositories\Contracts\FingerprintRepositoryInterface;
use App\Repositories\Contracts\DeviceRepositoryInterface;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->vaultRepository = Mockery::mock(VaultRepositoryInterface::class);
    $this->vaultSessionRepository = Mockery::mock(VaultSessionRepositoryInterface::class);
    $this->workingTimeRepository = Mockery::mock(WorkingTimeRepositoryInterface::class);
    $this->fingerprintRepository = Mockery::mock(FingerprintRepositoryInterface::class);
    $this->deviceRepository = Mockery::mock(DeviceRepositoryInterface::class);
    $this->auditService = Mockery::mock(AuditService::class);
    $this->snapshotService = Mockery::mock(SnapshotService::class);
    $this->notificationService = Mockery::mock(NotificationService::class);
    $this->workingTimeService = Mockery::mock(WorkingTimeService::class);

    $this->vaultService = new VaultService(
        $this->vaultRepository,
        $this->vaultSessionRepository,
        $this->workingTimeRepository,
        $this->fingerprintRepository,
        $this->deviceRepository,
        $this->auditService,
        $this->snapshotService,
        $this->notificationService,
        $this->workingTimeService,
    );

    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
    $this->vault = Vault::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => VaultStatus::Locked,
    ]);
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
        'branch_id' => $this->branch->id,
    ]);
    $this->device = Device::factory()->create([
        'vault_id' => $this->vault->id,
        'branch_id' => $this->branch->id,
    ]);
});

describe('openVault', function () {
    test('openVault validates working time', function () {
        $this->vaultRepository
            ->shouldReceive('findOrFail')
            ->with($this->vault->id)
            ->andReturn($this->vault);

        $this->vaultSessionRepository
            ->shouldReceive('getActiveSessionByVault')
            ->with($this->vault->id)
            ->andReturn(null);

        $this->workingTimeService
            ->shouldReceive('isWithinWorkingTime')
            ->with($this->vault->branch_id, $this->vault->id)
            ->andReturn(false);

        $this->notificationService
            ->shouldReceive('sendUnauthorizedAccessAlert')
            ->once();

        $this->auditService
            ->shouldReceive('log')
            ->once();

        $dto = new VaultAccessDTO(
            vaultId: $this->vault->id,
            userId: $this->user->id,
            accessType: 'manual',
            deviceId: $this->device->id,
            ipAddress: '127.0.0.1',
        );

        expect(fn () => $this->vaultService->openVault($dto))
            ->toThrow(ValidationException::class);
    });

    test('openVault validates fingerprint', function () {
        $this->vaultRepository
            ->shouldReceive('findOrFail')
            ->with($this->vault->id)
            ->andReturn($this->vault);

        $this->vaultSessionRepository
            ->shouldReceive('getActiveSessionByVault')
            ->with($this->vault->id)
            ->andReturn(null);

        $this->workingTimeService
            ->shouldReceive('isWithinWorkingTime')
            ->andReturn(true);

        $this->fingerprintRepository
            ->shouldReceive('validateFingerprint')
            ->with('fp-device-001', $this->user->id)
            ->andReturn(false);

        $this->auditService
            ->shouldReceive('log')
            ->once();

        $dto = new VaultAccessDTO(
            vaultId: $this->vault->id,
            userId: $this->user->id,
            accessType: 'fingerprint',
            deviceId: $this->device->id,
            fingerprintDeviceId: 'fp-device-001',
            ipAddress: '127.0.0.1',
        );

        expect(fn () => $this->vaultService->openVault($dto))
            ->toThrow(ValidationException::class);
    });

    test('openVault creates session', function () {
        $session = VaultSession::factory()->make([
            'id' => fake()->uuid(),
            'vault_id' => $this->vault->id,
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
            'status' => SessionStatus::Active,
            'opened_at' => now(),
        ]);

        $this->vaultRepository
            ->shouldReceive('findOrFail')
            ->with($this->vault->id)
            ->andReturn($this->vault);

        $this->vaultSessionRepository
            ->shouldReceive('getActiveSessionByVault')
            ->with($this->vault->id)
            ->andReturn(null);

        $this->workingTimeService
            ->shouldReceive('isWithinWorkingTime')
            ->andReturn(true);

        $this->vaultRepository
            ->shouldReceive('updateStatus')
            ->with($this->vault->id, VaultStatus::Unlocked->value)
            ->once();

        $this->vaultSessionRepository
            ->shouldReceive('create')
            ->andReturn($session);

        $this->snapshotService
            ->shouldReceive('captureSnapshot')
            ->once();

        $this->auditService
            ->shouldReceive('log')
            ->once();

        $dto = new VaultAccessDTO(
            vaultId: $this->vault->id,
            userId: $this->user->id,
            accessType: 'manual',
            deviceId: $this->device->id,
            ipAddress: '127.0.0.1',
        );

        $result = $this->vaultService->openVault($dto);

        expect($result)->toHaveKeys(['vault', 'session']);
    });
});

describe('closeVault', function () {
    test('closeVault calculates duration', function () {
        $openedAt = now()->subMinutes(5);
        $session = VaultSession::factory()->create([
            'vault_id' => $this->vault->id,
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
            'status' => SessionStatus::Active,
            'opened_at' => $openedAt,
        ]);

        $this->vaultRepository
            ->shouldReceive('findOrFail')
            ->with($this->vault->id)
            ->andReturn($this->vault);

        $this->vaultSessionRepository
            ->shouldReceive('findOrFail')
            ->with($session->id)
            ->andReturn($session);

        $this->vaultSessionRepository
            ->shouldReceive('closeSession')
            ->once();

        $this->vaultRepository
            ->shouldReceive('updateStatus')
            ->with($this->vault->id, VaultStatus::Locked->value)
            ->once();

        $this->snapshotService
            ->shouldReceive('captureSnapshot')
            ->once();

        $this->auditService
            ->shouldReceive('log')
            ->once();

        $dto = new CloseVaultDTO(
            vaultId: $this->vault->id,
            sessionId: $session->id,
            userId: $this->user->id,
            closeReason: 'normal',
        );

        $result = $this->vaultService->closeVault($dto);

        expect($result)->toHaveKeys(['vault', 'session', 'duration_seconds']);
        expect($result['duration_seconds'])->toBeGreaterThanOrEqual(300);
    });
});

describe('checkSessionTimeout', function () {
    test('checkSessionTimeout detects expired sessions', function () {
        $expiredSession = VaultSession::factory()->create([
            'vault_id' => $this->vault->id,
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
            'status' => SessionStatus::Active,
            'opened_at' => now()->subMinutes(15),
        ]);

        $this->vaultSessionRepository
            ->shouldReceive('getExpiredSessions')
            ->andReturn(collect([$expiredSession]));

        $this->vaultSessionRepository
            ->shouldReceive('closeSession')
            ->with($expiredSession->id, Mockery::type('array'))
            ->once();

        $this->vaultRepository
            ->shouldReceive('updateStatus')
            ->with($expiredSession->vault_id, VaultStatus::Alarm->value)
            ->once();

        $this->notificationService
            ->shouldReceive('sendUnauthorizedAccessAlert')
            ->once();

        $this->snapshotService
            ->shouldReceive('captureSnapshot')
            ->once();

        $this->vaultService->checkSessionTimeout();
    });
});
