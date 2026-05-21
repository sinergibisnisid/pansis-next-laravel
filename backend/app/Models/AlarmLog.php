<?php

namespace App\Models;

use App\Enums\AlarmStatus;
use App\Enums\AlarmType;
use App\Enums\Severity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlarmLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'alarm_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vault_id',
        'device_id',
        'branch_id',
        'user_id',
        'alarm_type',
        'severity',
        'status',
        'title',
        'description',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'triggered_at',
        'metadata',
    ];

    protected $casts = [
        'alarm_type' => AlarmType::class,
        'severity' => Severity::class,
        'status' => AlarmStatus::class,
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'triggered_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
