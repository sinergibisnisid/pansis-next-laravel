<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Router-specific specification + state, attached 1:1 to a Device with type=router.
 */
class RouterSpec extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'router_specs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'device_id',
        'lan_ip',
        'wan_ip_primary',
        'wan_ip_secondary',
        'isp_primary',
        'isp_secondary',
        'vpn_enabled',
        'vpn_type',
        'vpn_endpoint',
        'failover_enabled',
        'poe_enabled',
        'poe_ports',
        'lan_ports',
        'metadata',
    ];

    protected $casts = [
        'vpn_enabled' => 'boolean',
        'failover_enabled' => 'boolean',
        'poe_enabled' => 'boolean',
        'poe_ports' => 'integer',
        'lan_ports' => 'integer',
        'metadata' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
