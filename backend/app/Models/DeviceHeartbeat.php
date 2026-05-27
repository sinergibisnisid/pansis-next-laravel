<?php

namespace App\Models;

use App\Enums\HeartbeatStatus;
use App\Enums\WanStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceHeartbeat extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'device_heartbeats';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'device_id',
        'status',
        'cpu_usage',
        'memory_usage',
        'temperature',
        'signal_strength',
        'uptime_seconds',
        'firmware_version',
        'ip_address',
        'wan_status',
        'isp_provider',
        'vpn_connected',
        'vpn_endpoint',
        'ups_on_battery',
        'ups_battery_percent',
        'ups_runtime_minutes',
        'error_count',
        'last_error',
        'metadata',
        'recorded_at',
    ];

    protected $casts = [
        'status' => HeartbeatStatus::class,
        'wan_status' => WanStatus::class,
        'vpn_connected' => 'boolean',
        'ups_on_battery' => 'boolean',
        'ups_battery_percent' => 'integer',
        'ups_runtime_minutes' => 'integer',
        'recorded_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
