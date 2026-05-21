<?php

namespace App\Models;

use App\Enums\SnapshotTrigger;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Snapshot extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'snapshots';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vault_id',
        'device_id',
        'user_id',
        'branch_id',
        'trigger_type',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'disk',
        'captured_at',
        'metadata',
    ];

    protected $casts = [
        'trigger_type' => SnapshotTrigger::class,
        'captured_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getUrlAttribute(): ?string
    {
        return Storage::disk($this->disk)->url($this->file_path);
    }
}
