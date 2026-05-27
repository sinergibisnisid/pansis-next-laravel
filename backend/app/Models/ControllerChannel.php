<?php

namespace App\Models;

use App\Enums\ChannelDirection;
use App\Enums\ChannelFunction;
use App\Enums\NormalState;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One physical I/O point on an Intelligence Controller device.
 *
 * Per Pansin Access PDF: a controller has 4 sensor inputs (S1-S4) and
 * 4 relay outputs (R1-R4). Each row in this table maps a (device, direction,
 * channel_number) triple to a logical function (door_sensor, magnetic_lock, …).
 */
class ControllerChannel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'controller_channels';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'device_id',
        'vault_id',
        'direction',
        'channel_number',
        'function',
        'label',
        'normal_state',
        'is_active',
        'is_auto_discovered',
        'metadata',
    ];

    protected $casts = [
        'direction' => ChannelDirection::class,
        'function' => ChannelFunction::class,
        'normal_state' => NormalState::class,
        'channel_number' => 'integer',
        'is_active' => 'boolean',
        'is_auto_discovered' => 'boolean',
        'metadata' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Whether this is an input channel (sensor, button).
     */
    public function isInput(): bool
    {
        return $this->direction === ChannelDirection::Input;
    }

    /**
     * Whether this is an output channel (relay).
     */
    public function isOutput(): bool
    {
        return $this->direction === ChannelDirection::Output;
    }

    /**
     * Channel position label like "S1" (input #1) or "R3" (output #3).
     */
    public function position(): string
    {
        $prefix = $this->isInput() ? 'S' : 'R';
        return $prefix . $this->channel_number;
    }
}
