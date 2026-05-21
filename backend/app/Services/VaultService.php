<?php

namespace App\Services;

use App\DTOs\Vault\VaultAccessDTO;
use App\DTOs\Vault\CloseVaultDTO;
use App\Enums\AuditEvent;
use App\Enums\SessionStatus;
use App\Enums\SnapshotTrigger;
use App\Enums\VaultStatus;
use App\Enums\AlarmType;
use App\Enums\Severity;
use App\Models\Vault;
use App\Models\VaultSession;
use App\Repositories\Contracts\VaultRepositoryInterface;
use App\Repositories\Contracts\VaultSessionRepositoryInterface;
use App\Repositories\Contracts\WorkingTimeRepositoryInterface;
use App\Repositories\Contracts\FingerprintRepositoryInterface;
use App\Repositories\Contracts\DeviceRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

class VaultService
{
    public function __construct(
        private readonly VaultRepositoryInterface $vaultRepository,
        private readonly VaultSessionRepositoryInterface $vaultSessionRepository,
        private readonly WorkingTimeRepositoryInterface $workingTimeRepository,
        private readonly FingerprintRepositoryInterface $fingerprintRepository,
        private readonly DeviceRepositoryInterface $deviceRepository,
        private readonly AuditService $auditService,
        private readonly SnapshotService $snapshotService,
        private readonly NotificationService $notificationService,
        private readonly WorkingTimeService $workingTimeService,
    ) {}

    public function openVault(VaultAccessDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $vault = $this->vaultRepository->findOrFail($dto->vaultId);
            $user = \App\Models\User::findOrFail($dto->userId);

            // Check if vault is already open
            $activeSession = $this->vaultSessionRepository->getActiveSessionByVault($dto->vaultId);
            if ($activeSession) {
                throw ValidationException::withMessages([
                    'vault' => ['Vault is already open with an active session.'],
                ]);
            }

            // Validate working time
            if (!$this->workingTimeService->isWithinWorkingTime($vault->branch_id, $dto->vaultId)) {
                $this->notificationService->sendUnauthorizedAccessAlert(
                    $dto->vaultId,
                    $dto->userId,
                    'Access attempted outside working hours'
                );

                $this->auditService->log(
                    user: $user,
                    event: AuditEvent::AccessDenied,
                    auditable: $vault,
                    metadata: ['reason' => 'outside_working_hours']
                );

                throw ValidationException::withMessages([
                    'vault' => ['Access denied. Outside of working hours.'],
                ]);
            }

            // Validate fingerprint if access type is fingerprint
            if ($dto->accessType === 'fingerprint') {
                $isValid = $this->fingerprintRepository->validateFingerprint(
                    $dto->fingerprintDeviceId ?? '',
                    $dto->userId
                );

                if (!$isValid) {
                    $this->auditService->log(
                        user: $user,
                        event: AuditEvent::AccessDenied,
                        auditable: $vault,
                        metadata: ['reason' => 'fingerprint_validation_failed']
                    );

                    throw ValidationException::withMessages([
                        'vault' => ['Fingerprint validation failed.'],
                    ]);
                }
            }

            // Open the vault
            $this->vaultRepository->updateStatus($dto->vaultId, VaultStatus::Unlocked->value);

            // Create vault session
            $session = $this->vaultSessionRepository->create([
                'vault_id' => $dto->vaultId,
                'user_id' => $dto->userId,
                'device_id' => $dto->deviceId,
                'access_type' => $dto->accessType,
                'status' => SessionStatus::Active->value,
                'opened_at' => now(),
                'confidence_score' => $dto->confidenceScore,
            ]);

            // Trigger snapshot
            $this->snapshotService->captureSnapshot(
                $dto->vaultId,
                $dto->deviceId ?? '',
                $dto->userId,
                SnapshotTrigger::VaultOpen
            );

            // Audit log
            $this->auditService->log(
                user: $user,
                event: AuditEvent::AccessGranted,
                auditable: $vault,
                metadata: [
                    'session_id' => $session->id,
                    'access_type' => $dto->accessType,
                    'ip_address' => $dto->ipAddress,
                ]
            );

            // Dispatch event
            Event::dispatch('vault.opened', [
                'vault' => $vault,
                'session' => $session,
                'user' => $user,
            ]);

            return [
                'vault' => $vault->fresh(),
                'session' => $session,
            ];
        });
    }

    public function closeVault(CloseVaultDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $vault = $this->vaultRepository->findOrFail($dto->vaultId);
            $user = \App\Models\User::findOrFail($dto->userId);
            $session = $this->vaultSessionRepository->findOrFail($dto->sessionId);

            // Calculate duration
            $openedAt = $session->opened_at;
            $closedAt = now();
            $durationSeconds = $closedAt->diffInSeconds($openedAt);

            // Close the session
            $this->vaultSessionRepository->closeSession($dto->sessionId, [
                'status' => SessionStatus::Closed->value,
                'closed_at' => $closedAt,
                'close_reason' => $dto->closeReason,
                'duration_seconds' => $durationSeconds,
            ]);

            // Update vault status
            $this->vaultRepository->updateStatus($dto->vaultId, VaultStatus::Locked->value);

            // Trigger snapshot on close
            $this->snapshotService->captureSnapshot(
                $dto->vaultId,
                $session->device_id ?? '',
                $dto->userId,
                SnapshotTrigger::VaultClose
            );

            // Audit log
            $this->auditService->log(
                user: $user,
                event: AuditEvent::AccessGranted,
                auditable: $vault,
                metadata: [
                    'action' => 'vault_closed',
                    'session_id' => $dto->sessionId,
                    'duration_seconds' => $durationSeconds,
                    'close_reason' => $dto->closeReason,
                ]
            );

            // Dispatch event
            Event::dispatch('vault.closed', [
                'vault' => $vault,
                'session' => $session->fresh(),
                'user' => $user,
                'duration_seconds' => $durationSeconds,
            ]);

            return [
                'vault' => $vault->fresh(),
                'session' => $session->fresh(),
                'duration_seconds' => $durationSeconds,
            ];
        });
    }

    public function getVaultStatus(string $vaultId): array
    {
        $vault = $this->vaultRepository->findOrFail($vaultId);
        $activeSession = $this->vaultSessionRepository->getActiveSessionByVault($vaultId);

        return [
            'vault' => $vault,
            'status' => $vault->status,
            'active_session' => $activeSession,
            'is_open' => $activeSession !== null,
        ];
    }

    public function checkSessionTimeout(): void
    {
        $expiredSessions = $this->vaultSessionRepository->getExpiredSessions();

        foreach ($expiredSessions as $session) {
            $this->vaultSessionRepository->closeSession($session->id, [
                'status' => SessionStatus::Timeout->value,
                'closed_at' => now(),
                'close_reason' => 'session_timeout',
                'duration_seconds' => now()->diffInSeconds($session->opened_at),
            ]);

            // Update vault status to alarm
            $this->vaultRepository->updateStatus($session->vault_id, VaultStatus::Alarm->value);

            // Send alarm notification
            $this->notificationService->sendUnauthorizedAccessAlert(
                $session->vault_id,
                $session->user_id,
                'Session timeout - vault left open'
            );

            // Trigger snapshot
            $this->snapshotService->captureSnapshot(
                $session->vault_id,
                $session->device_id ?? '',
                $session->user_id,
                SnapshotTrigger::Alarm
            );

            // Dispatch alarm event
            Event::dispatch('vault.alarm.triggered', [
                'vault_id' => $session->vault_id,
                'session' => $session,
                'reason' => 'session_timeout',
            ]);
        }
    }
}
