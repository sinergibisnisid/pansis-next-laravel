<?php

namespace App\Services;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Models\HardwareCommand;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reliable dispatcher for hardware commands (lock release/engage, buzzer on/off).
 *
 * Provides at-least-once delivery semantics with ack tracking + retry on top
 * of MQTT QoS 2. The ack flow is:
 *
 *   1. dispatch() inserts a hardware_commands row (status=pending) and
 *      publishes the command via MqttService. command_id (the row id) is
 *      embedded in the payload.
 *   2. Controller executes and publishes ack on:
 *         lock/{vault_id}/ack/{command_id}
 *         buzzer/{vault_id}/ack/{command_id}
 *      with body { "status": "success" | "error", "error": "..." }
 *   3. handleAck() updates the row → status=acknowledged.
 *   4. retryStale() runs every minute and re-publishes commands stuck in
 *      'sent' beyond ack_deadline_at, until max_attempts reached.
 *
 * Safety-critical commands (lock_release, lock_engage, buzzer_activate) are
 * always published at QoS 2 with retry. Non-critical (strobe) at QoS 1.
 */
class HardwareCommandService
{
    /**
     * Default time the controller has to ack a command before we re-send.
     */
    public const DEFAULT_ACK_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly MqttService $mqttService,
    ) {}

    /**
     * Issue a hardware command.
     *
     * Returns the persisted HardwareCommand record. The caller can poll its
     * status or rely on the event 'hardware.command.acknowledged' / 'failed'.
     */
    public function dispatch(
        string $vaultId,
        CommandType $type,
        ?string $deviceId = null,
        ?User $issuer = null,
        ?string $reason = null,
        array $extraPayload = [],
        ?int $ackTimeoutSeconds = null,
        int $maxAttempts = 3,
    ): HardwareCommand {
        $ackTimeoutSeconds ??= self::DEFAULT_ACK_TIMEOUT_SECONDS;
        $qos = $type->isSafetyCritical() ? 2 : 1;
        $topic = str_replace('{vault_id}', $vaultId, $type->topicTemplate());

        return DB::transaction(function () use (
            $vaultId, $type, $deviceId, $issuer, $reason, $extraPayload,
            $topic, $qos, $ackTimeoutSeconds, $maxAttempts
        ) {
            $command = HardwareCommand::create([
                'vault_id' => $vaultId,
                'device_id' => $deviceId,
                'issued_by' => $issuer?->id,
                'command_type' => $type->value,
                'topic' => $topic,
                'payload' => array_merge($extraPayload, [
                    'vault_id' => $vaultId,
                    'reason' => $reason,
                ]),
                'qos' => $qos,
                'reason' => $reason,
                'status' => CommandStatus::Pending->value,
                'attempts' => 0,
                'max_attempts' => $maxAttempts,
            ]);

            $this->publish($command, $ackTimeoutSeconds);

            return $command->fresh();
        });
    }

    /**
     * Publish (or re-publish) a command to MQTT and update its tracking fields.
     */
    public function publish(HardwareCommand $command, int $ackTimeoutSeconds): void
    {
        $payload = array_merge($command->payload ?? [], [
            'command_id' => $command->id,
            'command_type' => $command->command_type->value,
            'attempt' => $command->attempts + 1,
            'requested_at' => now()->toIso8601String(),
        ]);

        $now = now();
        $deadline = $now->copy()->addSeconds($ackTimeoutSeconds);

        $command->update([
            'attempts' => $command->attempts + 1,
            'first_sent_at' => $command->first_sent_at ?? $now,
            'last_sent_at' => $now,
            'ack_deadline_at' => $deadline,
            'status' => CommandStatus::Sent->value,
            'payload' => $payload,
        ]);

        $published = $this->mqttService->publish(
            topic: $command->topic,
            payload: $payload,
            qos: $command->qos,
        );

        if (!$published) {
            Log::error('Hardware command publish failed', [
                'command_id' => $command->id,
                'topic' => $command->topic,
                'attempt' => $command->attempts,
            ]);
        }
    }

    /**
     * Process an ack received from the controller.
     */
    public function handleAck(string $commandId, string $status, ?string $error = null): void
    {
        $command = HardwareCommand::find($commandId);
        if (!$command) {
            Log::warning('Received ack for unknown command', ['command_id' => $commandId]);
            return;
        }

        if ($command->status->isTerminal()) {
            // Late ack on an already-finalized command — ignore but log.
            Log::info('Late ack received on terminal command', [
                'command_id' => $commandId,
                'status' => $command->status->value,
            ]);
            return;
        }

        $now = now();
        $finalStatus = $status === 'success'
            ? CommandStatus::Acknowledged
            : ($command->canRetry() ? CommandStatus::Sent : CommandStatus::Failed);

        $command->update([
            'status' => $finalStatus->value,
            'ack_status' => $status,
            'ack_error' => $error,
            'acknowledged_at' => $finalStatus === CommandStatus::Acknowledged ? $now : null,
            'failed_at' => $finalStatus === CommandStatus::Failed ? $now : null,
        ]);

        \Illuminate\Support\Facades\Event::dispatch('hardware.command.' . $finalStatus->value, [
            'command' => $command->fresh(),
        ]);
    }

    /**
     * Retry commands that are still 'sent' but past their ack deadline.
     * Returns the number of commands retried.
     */
    public function retryStale(int $batchSize = 100): int
    {
        $stale = HardwareCommand::query()
            ->where('status', CommandStatus::Sent->value)
            ->whereNotNull('ack_deadline_at')
            ->where('ack_deadline_at', '<', now())
            ->orderBy('ack_deadline_at')
            ->limit($batchSize)
            ->get();

        $retried = 0;
        foreach ($stale as $command) {
            if (!$command->canRetry()) {
                $command->update([
                    'status' => CommandStatus::Failed->value,
                    'failed_at' => now(),
                    'ack_error' => 'Exceeded max attempts without acknowledgement',
                ]);
                \Illuminate\Support\Facades\Event::dispatch('hardware.command.failed', [
                    'command' => $command->fresh(),
                ]);

                Log::critical('Hardware command failed (no ack after retries)', [
                    'command_id' => $command->id,
                    'vault_id' => $command->vault_id,
                    'command_type' => $command->command_type->value,
                    'attempts' => $command->attempts,
                ]);
                continue;
            }

            $this->publish($command, self::DEFAULT_ACK_TIMEOUT_SECONDS);
            $retried++;
        }

        return $retried;
    }

    /**
     * Cancel a pending/sent command (operator decision or supersession).
     */
    public function cancel(string $commandId, ?string $reason = null): void
    {
        $command = HardwareCommand::findOrFail($commandId);
        if ($command->status->isTerminal()) {
            return;
        }

        $command->update([
            'status' => CommandStatus::Cancelled->value,
            'ack_error' => $reason,
            'failed_at' => now(),
        ]);
    }

    /**
     * Get pending commands for a given vault. Useful for the UI to show
     * "buzzer activate command in flight".
     */
    public function getPendingForVault(string $vaultId): Collection
    {
        return HardwareCommand::query()
            ->where('vault_id', $vaultId)
            ->whereIn('status', [
                CommandStatus::Pending->value,
                CommandStatus::Sent->value,
            ])
            ->orderBy('first_sent_at')
            ->get();
    }
}
