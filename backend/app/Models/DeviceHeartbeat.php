<?php

namespace App\Models;

use App\Enums\HeartbeatStatus;
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
        'error_count',
        'last_error',
        'metadata',
        'recorded_at',
    ];

    protected $casts = [
        'status' => HeartbeatStatus::class,
        'recorded_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
