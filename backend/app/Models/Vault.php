<?php

namespace App\Models;

use App\Enums\BuzzerState;
use App\Enums\DoorState;
use App\Enums\LockState;
use App\Enums\VaultStatus;
use App\Enums\VaultType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vault extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'vaults';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'type',
        'status',
        'door_state',
        'lock_state',
        'buzzer_state',
        'door_state_changed_at',
        'floor',
        'room',
        'max_session_duration_minutes',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'type' => VaultType::class,
        'status' => VaultStatus::class,
        'door_state' => DoorState::class,
        'lock_state' => LockState::class,
        'buzzer_state' => BuzzerState::class,
        'door_state_changed_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function fingerprintLogs(): HasMany
    {
        return $this->hasMany(FingerprintLog::class);
    }

    public function vaultAccessLogs(): HasMany
    {
        return $this->hasMany(VaultAccessLog::class);
    }

    public function vaultSessions(): HasMany
    {
        return $this->hasMany(VaultSession::class);
    }

    public function alarmLogs(): HasMany
    {
        return $this->hasMany(AlarmLog::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(Snapshot::class);
    }

    public function maintenancePlans(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class);
    }

    public function workingTimes(): HasMany
    {
        return $this->hasMany(WorkingTime::class);
    }

    public function livestreamSessions(): HasMany
    {
        return $this->hasMany(LivestreamSession::class);
    }
}
