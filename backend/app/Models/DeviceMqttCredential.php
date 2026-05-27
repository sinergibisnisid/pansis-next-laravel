<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-device MQTT broker credentials + topic ACL.
 *
 * Used by the broker (EMQX) auth/ACL hook to authenticate publishing devices
 * and verify they are only allowed on their own topic namespace.
 */
class DeviceMqttCredential extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'device_mqtt_credentials';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'device_id',
        'mqtt_username',
        'mqtt_password_hash',
        'publish_acl',
        'subscribe_acl',
        'is_active',
        'expires_at',
        'last_used_at',
    ];

    protected $hidden = [
        'mqtt_password_hash',
    ];

    protected $casts = [
        'publish_acl' => 'array',
        'subscribe_acl' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return $this->is_active && !$this->isExpired();
    }
}
