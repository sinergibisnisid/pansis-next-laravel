<?php

namespace App\Models;

use App\Enums\MqttDirection;
use App\Enums\MqttStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MqttLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'mqtt_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'topic',
        'payload',
        'direction',
        'device_id',
        'qos',
        'retained',
        'status',
        'error_message',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'payload' => 'array',
        'direction' => MqttDirection::class,
        'retained' => 'boolean',
        'status' => MqttStatus::class,
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
