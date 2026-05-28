<?php

namespace App\Services;

use App\Enums\BuzzerState;
use App\Enums\CommandType;
use App\Enums\LockState;
use App\Models\HardwareCommand;
use App\Models\User;
use App\Repositories\Contracts\VaultRepositoryInterface;

/**
 * High-level façade for hardware operations on a vault.
 *
 * Delegates the actual MQTT delivery to HardwareCommandService (which provides
 * tracked, retried, ack-aware delivery). Then optimistically updates the
 * vault's lock_state / buzzer_state. The authoritative state still comes from
 * lock/+/state and buzzer/+/state events published by the controller.
 *
 * Per Pansin Access PDF: lock + buzzer commands are safety-critical and must
 * not be silently dropped — that's why we go through the command queue
 * rather than publishing fire-and-forget.
 */
class HardwareControlService
{
    public function __construct(
        private readonly HardwareCommandService $commandService,
        private readonly VaultRepositoryInterface $vaultRepository,
    ) {}

    /**
     * Send the "release magnetic lock" command for the given vault.
     */
    public function releaseLock(
        string $vaultId,
        ?string $userId = null,
        ?string $reason = null,
    ): HardwareCommand {
        $command = $this->commandService->dispatch(
            vaultId: $vaultId,
            type: CommandType::LockRelease,
            issuer: $userId ? User::find($userId) : null,
            reason: $reason,
            extraPayload: ['user_id' => $userId],
        );

        $this->vaultRepository->update($vaultId, [
            'lock_state' => LockState::Released->value,
        ]);

        return $command;
    }

    /**
     * Send the "engage magnetic lock" command for the given vault.
     */
    public function engageLock(string $vaultId, ?string $reason = null): HardwareCommand
    {
        $command = $this->commandService->dispatch(
            vaultId: $vaultId,
            type: CommandType::LockEngage,
            reason: $reason,
        );

        $this->vaultRepository->update($vaultId, [
            'lock_state' => LockState::Engaged->value,
        ]);

        return $command;
    }

    /**
     * Activate the buzzer on the controller's relay.
     */
    public function activateBuzzer(
        string $vaultId,
        ?string $reason = null,
        ?int $durationSeconds = null,
    ): HardwareCommand {
        $command = $this->commandService->dispatch(
            vaultId: $vaultId,
            type: CommandType::BuzzerActivate,
            reason: $reason,
            extraPayload: ['duration_seconds' => $durationSeconds],
        );

        $this->vaultRepository->update($vaultId, [
            'buzzer_state' => BuzzerState::On->value,
        ]);

        return $command;
    }

    /**
     * Deactivate the buzzer.
     */
    public function deactivateBuzzer(string $vaultId, ?string $reason = null): HardwareCommand
    {
        $command = $this->commandService->dispatch(
            vaultId: $vaultId,
            type: CommandType::BuzzerDeactivate,
            reason: $reason,
        );

        $this->vaultRepository->update($vaultId, [
            'buzzer_state' => BuzzerState::Off->value,
        ]);

        return $command;
    }

    /**
     * Sync the authoritative state reported by the controller (lock/+/state).
     */
    public function syncLockState(string $vaultId, LockState $state): void
    {
        $this->vaultRepository->update($vaultId, [
            'lock_state' => $state->value,
        ]);
    }

    public function syncBuzzerState(string $vaultId, BuzzerState $state): void
    {
        $this->vaultRepository->update($vaultId, [
            'buzzer_state' => $state->value,
        ]);
    }
}
