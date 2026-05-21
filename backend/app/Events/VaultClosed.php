<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class VaultClosed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $vaultId,
        public readonly string $userId,
        public readonly string $sessionId,
        public readonly string $branchId,
        public readonly Carbon $closedAt,
        public readonly int $durationSeconds,
        public readonly string $closeReason,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("branch.{$this->branchId}"),
            new PrivateChannel("vault.{$this->vaultId}"),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'vault_id' => $this->vaultId,
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'branch_id' => $this->branchId,
            'status' => 'locked',
            'closed_at' => $this->closedAt->toIso8601String(),
            'duration_seconds' => $this->durationSeconds,
            'close_reason' => $this->closeReason,
        ];
    }

    public function broadcastAs(): string
    {
        return 'vault.closed';
    }
}
