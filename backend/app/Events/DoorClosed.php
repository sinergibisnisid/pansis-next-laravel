<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when door sensor detects the vault door has been physically closed.
 */
class DoorClosed implements ShouldBroadcast
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
        ];
    }

    public function broadcastAs(): string
    {
        return 'door.closed';
    }

    public function broadcastWith(): array
    {
        return [
            'vault_id' => $this->vaultId,
            'session_id' => $this->sessionId,
            'device_id' => $this->deviceId,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
