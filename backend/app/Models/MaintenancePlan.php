<?php

namespace App\Models;

use App\Enums\MaintenanceFrequency;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenancePlan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'maintenance_plans';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vault_id',
        'device_id',
        'branch_id',
        'assigned_to',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'frequency',
        'scheduled_date',
        'scheduled_time',
        'due_date',
        'completed_at',
        'completed_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'type' => MaintenanceType::class,
        'priority' => MaintenancePriority::class,
        'status' => MaintenanceStatus::class,
        'frequency' => MaintenanceFrequency::class,
        'scheduled_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }
}
