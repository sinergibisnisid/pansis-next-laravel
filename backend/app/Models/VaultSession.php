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

    public function isExpired(): bool
    {
        if (is_null($this->max_duration_seconds) || is_null($this->duration_seconds)) {
            return false;
        }

        return $this->duration_seconds > $this->max_duration_seconds;
    }
}
