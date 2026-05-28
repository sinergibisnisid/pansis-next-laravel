<?php

namespace App\Models;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One hardware command issued to a controller relay (lock, buzzer, strobe).
 *
 * Lifecycle: pending → sent → (acknowledged | failed | cancelled).
 *
 * The id of this row doubles as the command_id embedded in the MQTT payload,
 * so the controller can echo it back in the ack message for correlation.
 */
class HardwareCommand extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hardware_commands';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vault_id',
        'device_id',
        'issued_by',
        'command_type',
        'topic',
        'payload',
        'qos',
        'reason',
        'status',
        'attempts',
        'max_attempts',
        'first_sent_at',
        'last_sent_at',
        'ack_deadline_at',
        'acknowledged_at',
        'ack_status',
        'ack_error',
        'failed_at',
    ];

    protected $casts = [
        'command_type' => CommandType::class,
        'status' => CommandStatus::class,
        'payload' => 'array',
        'qos' => 'integer',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'first_sent_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'ack_deadline_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isOverdue(): bool
    {
        return $this->status === CommandStatus::Sent
            && $this->ack_deadline_at
            && $this->ack_deadline_at->isPast();
    }

    public function canRetry(): bool
    {
        return $this->attempts < $this->max_attempts && !$this->status->isTerminal();
    }
}
