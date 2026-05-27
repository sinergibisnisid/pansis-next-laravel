<?php

namespace App\Services;

use App\DTOs\Hardware\ButtonEventDTO;
use App\DTOs\Hardware\DoorSensorEventDTO;
use App\Enums\AlarmStatus;
use App\Enums\AlarmType;
use App\Enums\AuditEvent;
use App\Enums\BuzzerState;
use App\Enums\CloseReason;
use App\Enums\DoorState;
use App\Enums\LockState;
use App\Enums\SessionStatus;
use App\Enums\Severity;
use App\Enums\SnapshotTrigger;
use App\Enums\VaultStatus;
use App\Events\DoorClosed;
use App\Events\DoorOpened;
use App\Events\EmergencyButtonPressed;
use App\Events\ExitButtonPressed;
use App\Models\AlarmLog;
use App\Models\VaultSession;
use App\Repositories\Contracts\VaultRepositoryInterface;
use App\Repositories\Contracts\VaultSessionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Handles physical door sensor and button events from the IoT controller.
 *
 * Per Pansin Access PDF "Vault Access Workflow":
 *   - DoorOpened: starts the occupancy timer (true source of truth, not fingerprint).
 *   - DoorClosed: stops the timer if user is exiting normally.
 *   - ExitButtonPressed: user intent to leave; backend releases lock so door can open.
 *   - EmergencyButtonPressed: panic; raise critical alarm + activate buzzer + notify.
 */
class VaultDoorService
{
    public function __construct(
        private readonly VaultRepositoryInterface $vaultRepository,
        private readonly VaultSessionRepositoryInterface $vaultSessionRepository,
        private readonly HardwareControlService $hardwareControl,
        private readonly SnapshotService $snapshotService,
        private readonly NotificationService $notificationService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Handle door/{vault_id}/opened event from controller.
     *
     * If there is an active session: start the occupancy timer for that session
     * and trigger the camera snapshot.
     *
     * If there is NO active session: this is a "door forced open" — raise critical alarm.
     */
    public function handleDoorOpened(DoorSensorEventDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            $vault = $this->vaultRepository->findOrFail($dto->vaultId);
            $occurredAt = $dto->occurredAt
                ? \Carbon\Carbon::instance(\DateTime::createFromImmutable($dto->occurredAt))
                : now();

            $this->vaultRepository->update($dto->vaultId, [
                'door_state' => DoorState::Opened->value,
                'door_state_changed_at' => $occurredAt,
            ]);

            $activeSession = $this->vaultSessionRepository->getActiveSessionByVault($dto->vaultId);

            if (!$activeSession) {
                $this->handleForcedDoorOpen($vault, $dto, $occurredAt);
                return;
            }

            // Stamp the actual physical door open time. This is when the
            // occupancy timer logically begins per the PDF workflow.
            if (is_null($activeSession->door_opened_at)) {
                $activeSession->update(['door_opened_at' => $occurredAt]);
            }

            // Snapshot at the moment the door physically opens (PDF PHASE 2 step 7).
            $this->snapshotService->captureSnapshot(
                vaultId: $dto->vaultId,
                deviceId: $dto->deviceId ?? '',
                userId: $activeSession->user_id,
                trigger: SnapshotTrigger::DoorOpen,
            );

            Event::dispatch(new DoorOpened(
                vaultId: $dto->vaultId,
                branchId: $vault->branch_id,
                sessionId: $activeSession->id,
                deviceId: $dto->deviceId,
                occurredAt: $occurredAt,
            ));

            Log::info('Door opened — occupancy timer started', [
                'vault_id' => $dto->vaultId,
                'session_id' => $activeSession->id,
                'occurred_at' => $occurredAt->toIso8601String(),
            ]);
        });
    }

    /**
     * Handle door/{vault_id}/closed event from controller.
     *
     * If active session is awaiting exit (exit button was pressed): close the session.
     * Otherwise just record the door state change.
     */
    public function handleDoorClosed(DoorSensorEventDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            $vault = $this->vaultRepository->findOrFail($dto->vaultId);
            $occurredAt = $dto->occurredAt
                ? \Carbon\Carbon::instance(\DateTime::createFromImmutable($dto->occurredAt))
                : now();

            $this->vaultRepository->update($dto->vaultId, [
                'door_state' => DoorState::Closed->value,
                'door_state_changed_at' => $occurredAt,
            ]);

            $activeSession = $this->vaultSessionRepository->getActiveSessionByVault($dto->vaultId);

            if ($activeSession) {
                $activeSession->update(['door_closed_at' => $occurredAt]);

                $this->snapshotService->captureSnapshot(
                    vaultId: $dto->vaultId,
                    deviceId: $dto->deviceId ?? '',
                    userId: $activeSession->user_id,
                    trigger: SnapshotTrigger::DoorClose,
                );

                // If user signaled exit before the door closed, finalize the session
                // and re-engage the magnetic lock.
                if (!is_null($activeSession->exit_button_pressed_at)) {
                    $this->finalizeExitFlow($activeSession, $occurredAt);
                }
            }

            Event::dispatch(new DoorClosed(
                vaultId: $dto->vaultId,
                branchId: $vault->branch_id,
                sessionId: $activeSession?->id,
                deviceId: $dto->deviceId,
                occurredAt: $occurredAt,
            ));
        });
    }

    /**
     * Handle button/{vault_id}/exit_pressed event from controller.
     *
     * Per PDF Exit Push Button: opens the pintu dari dalam.
     * Backend releases the magnetic lock so the user can leave.
     */
    public function handleExitButtonPressed(ButtonEventDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            $vault = $this->vaultRepository->findOrFail($dto->vaultId);
            $occurredAt = $dto->occurredAt
                ? \Carbon\Carbon::instance(\DateTime::createFromImmutable($dto->occurredAt))
                : now();

            $activeSession = $this->vaultSessionRepository->getActiveSessionByVault($dto->vaultId);

            if ($activeSession) {
                $activeSession->update(['exit_button_pressed_at' => $occurredAt]);
            }

            // Release the magnetic lock so the user can physically exit.
            $this->hardwareControl->releaseLock(
                vaultId: $dto->vaultId,
                userId: $activeSession?->user_id,
                reason: 'exit_button_pressed',
            );

            Event::dispatch(new ExitButtonPressed(
                vaultId: $dto->vaultId,
                branchId: $vault->branch_id,
                sessionId: $activeSession?->id,
                deviceId: $dto->deviceId,
                occurredAt: $occurredAt,
            ));

            Log::info('Exit button pressed — lock released', [
                'vault_id' => $dto->vaultId,
                'session_id' => $activeSession?->id,
            ]);
        });
    }

    /**
     * Handle button/{vault_id}/emergency event from controller.
     *
     * Per PDF Emergency Button (NC): panic condition. We:
     *   1. Release the magnetic lock immediately (people first, security second).
     *   2. Activate the buzzer locally + raise a critical alarm.
     *   3. Capture a snapshot.
     *   4. Notify HQ + branch admin via configured channels.
     */
    public function handleEmergencyButtonPressed(ButtonEventDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            $vault = $this->vaultRepository->findOrFail($dto->vaultId);
            $occurredAt = $dto->occurredAt
                ? \Carbon\Carbon::instance(\DateTime::createFromImmutable($dto->occurredAt))
                : now();

            $activeSession = $this->vaultSessionRepository->getActiveSessionByVault($dto->vaultId);

            if ($activeSession) {
                $activeSession->update(['emergency_button_pressed_at' => $occurredAt]);
            }

            // Safety first: free the door.
            $this->hardwareControl->releaseLock(
                vaultId: $dto->vaultId,
                userId: $activeSession?->user_id,
                reason: 'emergency_button_pressed',
            );

            // Activate local buzzer to draw attention.
            $this->hardwareControl->activateBuzzer(
                vaultId: $dto->vaultId,
                reason: 'emergency_button_pressed',
            );

            $this->vaultRepository->update($dto->vaultId, [
                'status' => VaultStatus::Alarm->value,
            ]);

            // Persist the alarm.
            $alarm = AlarmLog::create([
                'vault_id' => $dto->vaultId,
                'device_id' => $dto->deviceId,
                'branch_id' => $vault->branch_id,
                'user_id' => $activeSession?->user_id,
                'alarm_type' => AlarmType::Emergency,
                'severity' => Severity::Critical,
                'status' => AlarmStatus::Active,
                'title' => 'Emergency button pressed',
                'description' => "Tombol darurat ditekan di vault {$vault->name}.",
                'triggered_at' => $occurredAt,
                'metadata' => [
                    'session_id' => $activeSession?->id,
                    'device_id' => $dto->deviceId,
                ],
            ]);

            // Capture a snapshot for evidence.
            $this->snapshotService->captureSnapshot(
                vaultId: $dto->vaultId,
                deviceId: $dto->deviceId ?? '',
                userId: $activeSession?->user_id,
                trigger: SnapshotTrigger::Emergency,
            );

            // Audit log.
            $this->auditService->log(
                user: $activeSession?->user,
                event: AuditEvent::AlarmTriggered,
                auditable: $vault,
                metadata: [
                    'reason' => 'emergency_button_pressed',
                    'alarm_id' => $alarm->id,
                ],
            );

            // Notify configured recipients.
            $this->notificationService->sendAlarmNotification($alarm);

            Event::dispatch(new EmergencyButtonPressed(
                vaultId: $dto->vaultId,
                branchId: $vault->branch_id,
                sessionId: $activeSession?->id,
                deviceId: $dto->deviceId,
                occurredAt: $occurredAt,
            ));

            Log::critical('Emergency button pressed', [
                'vault_id' => $dto->vaultId,
                'branch_id' => $vault->branch_id,
                'alarm_id' => $alarm->id,
            ]);
        });
    }

    /**
     * Door opened without an active session: forced entry / tampering.
     */
    private function handleForcedDoorOpen(
        \App\Models\Vault $vault,
        DoorSensorEventDTO $dto,
        \Carbon\Carbon $occurredAt,
    ): void {
        $this->vaultRepository->update($dto->vaultId, [
            'status' => VaultStatus::Alarm->value,
        ]);

        // Activate buzzer locally.
        $this->hardwareControl->activateBuzzer(
            vaultId: $dto->vaultId,
            reason: 'door_forced_open',
        );

        $alarm = AlarmLog::create([
            'vault_id' => $dto->vaultId,
            'device_id' => $dto->deviceId,
            'branch_id' => $vault->branch_id,
            'alarm_type' => AlarmType::DoorForcedOpen,
            'severity' => Severity::Critical,
            'status' => AlarmStatus::Active,
            'title' => 'Door forced open',
            'description' => "Pintu vault {$vault->name} terbuka tanpa sesi akses yang aktif.",
            'triggered_at' => $occurredAt,
        ]);

        $this->snapshotService->captureSnapshot(
            vaultId: $dto->vaultId,
            deviceId: $dto->deviceId ?? '',
            userId: null,
            trigger: SnapshotTrigger::Alarm,
        );

        $this->notificationService->sendAlarmNotification($alarm);

        Log::critical('Door forced open detected', [
            'vault_id' => $dto->vaultId,
            'branch_id' => $vault->branch_id,
            'alarm_id' => $alarm->id,
        ]);
    }

    /**
     * Final step of normal exit: door has been closed after exit button was pressed.
     * Close the session, re-engage the lock.
     */
    private function finalizeExitFlow(VaultSession $session, \Carbon\Carbon $occurredAt): void
    {
        $startedAt = $session->door_opened_at ?? $session->opened_at ?? $occurredAt;
        $durationSeconds = $occurredAt->diffInSeconds($startedAt);

        $session->update([
            'status' => SessionStatus::Closed->value,
            'closed_at' => $occurredAt,
            'close_reason' => CloseReason::PushButton->value,
            'duration_seconds' => $durationSeconds,
        ]);

        $this->vaultRepository->update($session->vault_id, [
            'status' => VaultStatus::Locked->value,
        ]);

        // Re-engage magnetic lock.
        $this->hardwareControl->engageLock(
            vaultId: $session->vault_id,
            reason: 'exit_complete',
        );

        // Make sure buzzer is off after a clean exit.
        $this->hardwareControl->deactivateBuzzer(
            vaultId: $session->vault_id,
            reason: 'exit_complete',
        );
    }
}
