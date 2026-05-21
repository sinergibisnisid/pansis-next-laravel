<?php

namespace App\Models;

use App\Enums\FingerPosition;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FingerprintDevice extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'fingerprint_devices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'device_id',
        'user_id',
        'fingerprint_id',
        'finger_position',
        'template_data',
        'quality_score',
        'registered_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'finger_position' => FingerPosition::class,
        'is_active' => 'boolean',
        'registered_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'template_data',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fingerprintLogs(): HasMany
    {
        return $this->hasMany(FingerprintLog::class);
    }
}
