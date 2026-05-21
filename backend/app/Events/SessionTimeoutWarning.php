<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionTimeoutWarning implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $sessionId,
        public readonly string $vaultId,
        public readonly string $branchId,
        public readonly int $elapsedSeconds,
        public readonly int $maxSeconds,
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
            'session_id' => $this->sessionId,
            'vault_id' => $this->vaultId,
            'branch_id' => $this->branchId,
            'elapsed_seconds' => $this->elapsedSeconds,
            'max_seconds' => $this->maxSeconds,
            'remaining_seconds' => $this->maxSeconds - $this->elapsedSeconds,
        ];
    }

    public function broadcastAs(): string
    {
        return 'session.timeout.warning';
    }
}
