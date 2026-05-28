<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Log masuk/keluar vault untuk tracking occupancy
class VaultOccupancyLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'vault_occupancy_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vault_id',
        'session_id',
        'user_id',
        'entered_at',
        'exited_at',
        'duration_seconds',
        'entry_method',
        'exit_method',
        'notes',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'exited_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(VaultSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Apakah orang ini masih di dalam vault
    public function isInside(): bool
    {
        return is_null($this->exited_at);
    }

    // Durasi dalam detik (realtime kalau masih di dalam)
    public function currentDuration(): int
    {
        if ($this->exited_at) {
            return $this->duration_seconds ?? (int) $this->entered_at->diffInSeconds($this->exited_at);
        }

        return (int) $this->entered_at->diffInSeconds(now());
    }
}