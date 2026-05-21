<?php

namespace App\Models;

use App\Enums\ScanResult;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FingerprintLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'fingerprint_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'fingerprint_device_id',
        'device_id',
        'user_id',
        'vault_id',
        'scan_result',
        'confidence_score',
        'rejection_reason',
        'ip_address',
        'scanned_at',
        'metadata',
    ];

    protected $casts = [
        'scan_result' => ScanResult::class,
        'scanned_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function fingerprintDevice(): BelongsTo
    {
        return $this->belongsTo(FingerprintDevice::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }
}
