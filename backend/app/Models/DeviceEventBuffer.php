<?php

namespace App\Models;

use App\Enums\BufferedEventStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One offline event reported by a controller after it reconnects to the cloud.
 *
 * Lifecycle: pending → processing → processed | failed | skipped.
 * Idempotency is enforced by the unique (device_id, source_event_id) index.
 */
class DeviceEventBuffer extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'device_event_buffers';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'device_id',
        'vault_id',
        'source_event_id',
        'topic',
        'event_type',
        'payload',
        'occurred_at',
        'uploaded_at',
        'status',
        'attempts',
        'last_error',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'status' => BufferedEventStatus::class,
        'attempts' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }
}
