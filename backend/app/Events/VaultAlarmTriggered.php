<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class VaultAlarmTriggered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $vaultId,
        public readonly string $branchId,
        public readonly string $alarmType,
        public readonly string $severity,
        public readonly string $alarmLogId,
        public readonly Carbon $triggeredAt,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("branch.{$this->branchId}"),
            new PrivateChannel("vault.{$this->vaultId}"),
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
            'alarm_type' => $this->alarmType,
            'severity' => $this->severity,
            'alarm_log_id' => $this->alarmLogId,
            'triggered_at' => $this->triggeredAt->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'vault.alarm.triggered';
    }
}
