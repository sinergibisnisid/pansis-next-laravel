<?php

namespace App\Models;

use App\Enums\StreamQuality;
use App\Enums\StreamStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LivestreamSession extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'livestream_sessions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'device_id',
        'vault_id',
        'branch_id',
        'user_id',
        'stream_path',
        'stream_url',
        'webrtc_url',
        'status',
        'started_at',
        'stopped_at',
        'duration_seconds',
        'stream_token',
        'quality',
        'metadata',
    ];

    protected $casts = [
        'status' => StreamStatus::class,
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'quality' => StreamQuality::class,
        'metadata' => 'array',
    ];

    protected $hidden = [
        'stream_token',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
