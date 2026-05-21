<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'devices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vault_id',
        'branch_id',
        'name',
        'serial_number',
        'type',
        'status',
        'ip_address',
        'mac_address',
        'firmware_version',
        'signal_quality',
        'device_token',
        'last_heartbeat_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'type' => DeviceType::class,
        'status' => DeviceStatus::class,
        'is_active' => 'boolean',
        'last_heartbeat_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'device_token',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fingerprintDevices(): HasMany
    {
        return $this->hasMany(FingerprintDevice::class);
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

    public function livestreamSessions(): HasMany
    {
        return $this->hasMany(LivestreamSession::class);
    }

    public function mqttLogs(): HasMany
    {
        return $this->hasMany(MqttLog::class);
    }

    public function heartbeats(): HasMany
    {
        return $this->hasMany(DeviceHeartbeat::class);
    }

    public function maintenancePlans(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class);
    }
}
