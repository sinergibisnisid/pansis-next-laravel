<?php

namespace App\Models;

use App\Enums\AccessStatus;
use App\Enums\AccessType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaultAccessLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'vault_access_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vault_id',
        'user_id',
        'device_id',
        'access_type',
        'status',
        'denial_reason',
        'ip_address',
        'accessed_at',
        'metadata',
    ];

    protected $casts = [
        'access_type' => AccessType::class,
        'status' => AccessStatus::class,
        'accessed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
