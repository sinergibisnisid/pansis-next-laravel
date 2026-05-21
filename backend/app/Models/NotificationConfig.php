<?php

namespace App\Models;

use App\Enums\EventType;
use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationConfig extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'notification_configs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'branch_id',
        'event_type',
        'channel',
        'is_enabled',
        'recipients',
        'schedule',
        'template',
        'metadata',
    ];

    protected $casts = [
        'event_type' => EventType::class,
        'channel' => NotificationChannel::class,
        'is_enabled' => 'boolean',
        'recipients' => 'array',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
