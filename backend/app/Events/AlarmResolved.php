<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class AlarmResolved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $alarmLogId,
        public readonly string $resolvedBy,
        public readonly Carbon $resolvedAt,
        public readonly string $branchId = '',
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('alarms'),
            new PrivateChannel("branch.{$this->branchId}"),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'alarm_log_id' => $this->alarmLogId,
            'resolved_by' => $this->resolvedBy,
            'resolved_at' => $this->resolvedAt->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'alarm.resolved';
    }
}
