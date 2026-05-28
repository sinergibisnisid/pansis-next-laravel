<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UPS-specific specification + latest state, attached 1:1 to a Device with type=ups.
 *
 * Per Pansin Access PDF "UPS Backup": minimum 2 jam runtime. We track battery
 * health (on_battery, battery_percent, runtime_remaining_minutes) plus
 * manufacturer specs and battery replacement schedule.
 */
class UpsSpec extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ups_specs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'device_id',
        'powers_device_id',
        'manufacturer',
        'model',
        'capacity_va',
        'capacity_w',
        'battery_runtime_minutes',
        'battery_installed_at',
        'battery_replace_due_at',
        'on_battery',
        'battery_percent',
        'runtime_remaining_minutes',
        'last_status_at',
        'metadata',
    ];

    protected $casts = [
        'capacity_va' => 'integer',
        'capacity_w' => 'integer',
        'battery_runtime_minutes' => 'integer',
        'battery_installed_at' => 'date',
        'battery_replace_due_at' => 'date',
        'on_battery' => 'boolean',
        'battery_percent' => 'integer',
        'runtime_remaining_minutes' => 'integer',
        'last_status_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function powersDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'powers_device_id');
    }

    /**
     * Whether the battery is due for replacement.
     */
    public function isBatteryDue(): bool
    {
        return $this->battery_replace_due_at && $this->battery_replace_due_at->isPast();
    }

    /**
     * Whether the UPS is currently in a critical state (on battery + low percent).
     */
    public function isCritical(): bool
    {
        return $this->on_battery === true && ($this->battery_percent ?? 100) <= 20;
    }
}
