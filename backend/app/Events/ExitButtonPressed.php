<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when the physical exit push button is pressed inside the vault.
 * Per PDF: this is the user's intent to leave the vault.
 *
 * Use ShouldBroadcastNow because security personnel must see this immediately.
 */
class ExitButtonPressed implements ShouldBroadcastNow
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
        return 'button.exit_pressed';
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
