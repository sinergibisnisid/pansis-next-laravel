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

    /**
     * I/O channels of this device. Relevant only for Controller-type devices.
     */
    public function channels(): HasMany
    {
        return $this->hasMany(ControllerChannel::class);
    }

    public function inputChannels(): HasMany
    {
        return $this->channels()->where('direction', \App\Enums\ChannelDirection::Input->value);
    }

    public function outputChannels(): HasMany
    {
        return $this->channels()->where('direction', \App\Enums\ChannelDirection::Output->value);
    }

    // Kredensial MQTT aktif
    public function mqttCredential(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DeviceMqttCredential::class)->where('is_active', true)->latest();
    }

    /**
     * Router-specific spec (only populated for type=router devices).
     */
    public function routerSpec(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RouterSpec::class);
    }

    /**
     * UPS-specific spec (only populated for type=ups devices).
     */
    public function upsSpec(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UpsSpec::class);
    }
}
