<?php

namespace App\Models;

use App\Enums\DeviceType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One-time provisioning code that lets a device claim itself into the platform.
 * The plaintext code is shown only once at creation; storage is hashed.
 */
class DeviceClaimCode extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'device_claim_codes';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'branch_id',
        'vault_id',
        'created_by',
        'code_hash',
        'code_suffix',
        'expected_device_type',
        'expected_device_name',
        'expires_at',
        'used_at',
        'used_by_device_id',
        'notes',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected $casts = [
        'expected_device_type' => DeviceType::class,
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usedByDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'used_by_device_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    public function isUsable(): bool
    {
        return !$this->isExpired() && !$this->isUsed();
    }
}
