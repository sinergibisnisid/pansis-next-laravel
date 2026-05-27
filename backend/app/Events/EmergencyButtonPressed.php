<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when the emergency button is pressed (Normally Closed circuit broken).
 * Per PDF: panic condition / emergency override.
 *
 * Always broadcasts immediately to alarms channel and branch operators.
 */
class EmergencyButtonPressed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $vaultId,
        public readonly string $branchId,
        public readonly ?string $sessionId,
        public readonly ?string $deviceId,
        public readonly \DateTimeInterface $occurredAt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("branch.{$this->branchId}"),
            new PrivateChannel("vault.{$this->vaultId}"),
            new PrivateChannel('alarms'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'button.emergency';
    }

    public function broadcastWith(): array
    {
        return [
            'vault_id' => $this->vaultId,
            'branch_id' => $this->branchId,
            'session_id' => $this->sessionId,
            'device_id' => $this->deviceId,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
