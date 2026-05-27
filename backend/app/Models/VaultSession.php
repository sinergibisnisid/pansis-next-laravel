<?php

namespace App\Models;

use App\Enums\CloseReason;
use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaultSession extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'vault_sessions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vault_id',
        'user_id',
        'device_id',
        'opened_at',
        'door_opened_at',
        'door_closed_at',
        'exit_button_pressed_at',
        'emergency_button_pressed_at',
        'closed_at',
        'duration_seconds',
        'max_duration_seconds',
        'status',
        'timeout_alarm_triggered',
        'timeout_alarm_at',
        'close_reason',
        'metadata',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'door_opened_at' => 'datetime',
        'door_closed_at' => 'datetime',
        'exit_button_pressed_at' => 'datetime',
        'emergency_button_pressed_at' => 'datetime',
        'closed_at' => 'datetime',
        'status' => SessionStatus::class,
        'timeout_alarm_triggered' => 'boolean',
        'timeout_alarm_at' => 'datetime',
        'close_reason' => CloseReason::class,
        'metadata' => 'array',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Whether the session has exceeded the maximum allowed occupancy duration.
     * Computed against door_opened_at when available (true occupancy time),
     * falling back to opened_at (session creation, fingerprint approval).
     */
    public function isExpired(): bool
    {
        if (is_null($this->max_duration_seconds)) {
            return false;
        }

        $startedAt = $this->door_opened_at ?? $this->opened_at;
        if (is_null($startedAt)) {
            return false;
        }

        $elapsedSeconds = now()->diffInSeconds($startedAt);

        return $elapsedSeconds > $this->max_duration_seconds;
    }

    /**
     * Seconds elapsed since the door physically opened (true occupancy duration).
     * Falls back to opened_at (fingerprint approval time) if door event hasn't fired.
     */
    public function elapsedSeconds(): int
    {
        $startedAt = $this->door_opened_at ?? $this->opened_at;
        if (is_null($startedAt)) {
            return 0;
        }

        return (int) now()->diffInSeconds($startedAt);
    }

    /**
     * Whether the door has actually been opened (door sensor confirmed).
     */
    public function isDoorOpen(): bool
    {
        return !is_null($this->door_opened_at) && is_null($this->door_closed_at);
    }
}
