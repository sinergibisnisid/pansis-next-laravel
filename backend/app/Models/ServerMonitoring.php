<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerMonitoring extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'server_monitorings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'hostname',
        'cpu_usage',
        'memory_usage',
        'memory_total_mb',
        'memory_used_mb',
        'disk_usage',
        'disk_total_gb',
        'disk_used_gb',
        'queue_size',
        'queue_failed',
        'websocket_connections',
        'mqtt_connected',
        'mqtt_messages_in',
        'mqtt_messages_out',
        'active_streams',
        'uptime_seconds',
        'load_average',
        'recorded_at',
        'metadata',
    ];

    protected $casts = [
        'mqtt_connected' => 'boolean',
        'load_average' => 'array',
        'recorded_at' => 'datetime',
        'metadata' => 'array',
    ];
}
