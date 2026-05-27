<?php

namespace App\Services;

use App\DTOs\Vault\CloseVaultDTO;
use App\DTOs\Vault\VaultAccessDTO;
use App\Enums\AuditEvent;
use App\Enums\CloseReason;
use App\Enums\SessionStatus;
use App\Enums\SnapshotTrigger;
use App\Enums\VaultStatus;
use App\Models\VaultSession;
use App\Repositories\Contracts\DeviceRepositoryInterface;
use App\Repositories\Contracts\FingerprintRepositoryInterface;
use App\Repositories\Contracts\VaultRepositoryInterface;
use App\Repositories\Contracts\VaultSessionRepositoryInterface;
use App\Repositories\Contracts\WorkingTimeRepositoryInterface;
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
        private readonly HardwareControlService $hardwareControlService,
    ) {}

    /**
     * Approve vault access (fingerprint validated, working hours OK, etc.) and
     * release the magnetic lock so the user can open the door.
     *
     * NOTE per Pansin Access PDF: this does NOT start the occupancy timer.
     * The occupancy timer starts only when the door sensor reports the door
     * has actually opened — handled by VaultDoorService::handleDoorOpened().
     *
     * The session created here is in "active" state but with door_opened_at = null.
     * The snapshot is also deferred to the door-open event.
     */
    public function openVault(VaultAccessDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $vault = $this->vaultRepository->findOrFail($dto->vaultId);
            $user = \App\Models\User::findOrFail($dto->userId);

            // Reject if vault already has an active session.
            $activeSession = $this->vaultSessionRepository->getActiveSessionByVault($dto->vaultId);
            if ($activeSession) {
                throw ValidationException::withMessages([
                    'vault' => ['Vault is already open with an active session.'],
                ]);
            }

            // Working time check.
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

            // Fingerprint validation.
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

            // Mark vault status as Unlocked (logical state — physical lock release happens via MQTT).
            $this->vaultRepository->updateStatus($dto->vaultId, VaultStatus::Unlocked->value);

            // Create vault session in Active state. door_opened_at remains null until
            // the door sensor actually fires.
            $session = $this->vaultSessionRepository->create([
                'vault_id' => $dto->vaultId,
                'user_id' => $dto->userId,
                'device_id' => $dto->deviceId,
                'status' => SessionStatus::Active->value,
                'opened_at' => now(),
                'max_duration_seconds' => ($vault->max_session_duration_minutes ?? 10) * 60,
            ]);

            // Issue the physical lock-release command via MQTT.
            $this->hardwareControlService->releaseLock(
                vaultId: $dto->vaultId,
                userId: $dto->userId,
                reason: 'access_granted',
            );

            // Audit log.
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

    /**
     * Close the vault session. This is for cases where closure is initiated
     * from the dashboard / API (manual close, timeout). For closure via the
     * physical exit-button + door-closed flow, see VaultDoorService.
     */
    public function closeVault(CloseVaultDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $vault = $this->vaultRepository->findOrFail($dto->vaultId);
            $user = \App\Models\User::findOrFail($dto->userId);
            $session = $this->vaultSessionRepository->findOrFail($dto->sessionId);

            $closedAt = now();
            $startedAt = $session->door_opened_at ?? $session->opened_at;
            $durationSeconds = $closedAt->diffInSeconds($startedAt);

            $this->vaultSessionRepository->closeSession($dto->sessionId, [
                'status' => SessionStatus::Closed->value,
                'closed_at' => $closedAt,
                'close_reason' => $dto->closeReason ?? CloseReason::Manual->value,
                'duration_seconds' => $durationSeconds,
            ]);

            $this->vaultRepository->updateStatus($dto->vaultId, VaultStatus::Locked->value);

            // Re-engage the magnetic lock.
            $this->hardwareControlService->engageLock(
                vaultId: $dto->vaultId,
                reason: 'session_closed',
            );

            // Snapshot on close (uses VaultClose trigger, separate from DoorClose).
            $this->snapshotService->captureSnapshot(
                vaultId: $dto->vaultId,
                deviceId: $session->device_id ?? '',
                userId: $dto->userId,
                trigger: SnapshotTrigger::VaultClose,
            );

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
            'door_state' => $vault->door_state,
            'lock_state' => $vault->lock_state,
            'buzzer_state' => $vault->buzzer_state,
            'active_session' => $activeSession,
            'is_open' => $activeSession !== null,
        ];
    }

    /**
     * Check all active vault sessions for occupancy timeout.
     *
     * Per Pansin Access PDF "Occupancy Timer": when occupancy duration exceeds
     * the configured threshold (default 10 minutes), activate the buzzer relay
     * and raise an alarm. Occupancy is measured from door_opened_at, falling
     * back to opened_at if the door event never fired (still treated as
     * suspicious because the door should have opened by now).
     */
    public function checkSessionTimeout(): void
    {
        $expiredSessions = $this->vaultSessionRepository->getExpiredSessions();

        foreach ($expiredSessions as $session) {
            // Skip sessions that already triggered timeout alarm.
            if ($session->timeout_alarm_triggered) {
                continue;
            }

            $startedAt = $session->door_opened_at ?? $session->opened_at;
            $durationSeconds = now()->diffInSeconds($startedAt);

            $this->vaultSessionRepository->update($session->id, [
                'status' => SessionStatus::Timeout->value,
                'timeout_alarm_triggered' => true,
                'timeout_alarm_at' => now(),
                'duration_seconds' => $durationSeconds,
            ]);

            $this->vaultRepository->updateStatus($session->vault_id, VaultStatus::Alarm->value);

            // Activate the physical buzzer on the controller.
            $this->hardwareControlService->activateBuzzer(
                vaultId: $session->vault_id,
                reason: 'occupancy_timeout',
            );

            // Snapshot for evidence.
            $this->snapshotService->captureSnapshot(
                vaultId: $session->vault_id,
                deviceId: $session->device_id ?? '',
                userId: $session->user_id,
                trigger: SnapshotTrigger::Alarm,
            );

            // Notify configured recipients.
            $this->notificationService->sendUnauthorizedAccessAlert(
                $session->vault_id,
                $session->user_id,
                'Session timeout — vault left open beyond ' . ($session->max_duration_seconds ?? 600) . ' seconds'
            );

            Event::dispatch('vault.alarm.triggered', [
                'vault_id' => $session->vault_id,
                'session' => $session,
                'reason' => 'session_timeout',
                'duration_seconds' => $durationSeconds,
            ]);
        }
    }
}
