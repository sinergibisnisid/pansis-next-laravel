<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class UnauthorizedAccessAttempt implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $vaultId,
        public readonly string $branchId,
        public readonly string $deviceId,
        public readonly string $userId,
        public readonly string $reason,
        public readonly Carbon $attemptedAt,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("branch.{$this->branchId}"),
            new PrivateChannel('alarms'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'vault_id' => $this->vaultId,
            'branch_id' => $this->branchId,
            'device_id' => $this->deviceId,
            'user_id' => $this->userId,
            'reason' => $this->reason,
            'attempted_at' => $this->attemptedAt->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'unauthorized.access.attempt';
    }
}
