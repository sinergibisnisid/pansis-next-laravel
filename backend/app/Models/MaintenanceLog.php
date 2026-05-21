<?php

namespace App\Models;

use App\Enums\MaintenanceType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'maintenance_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'maintenance_plan_id',
        'vault_id',
        'device_id',
        'branch_id',
        'performed_by',
        'type',
        'title',
        'description',
        'status',
        'started_at',
        'completed_at',
        'duration_minutes',
        'findings',
        'actions_taken',
        'parts_replaced',
        'next_maintenance_date',
        'attachments',
        'metadata',
    ];

    protected $casts = [
        'type' => MaintenanceType::class,
        'status' => 'string',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_maintenance_date' => 'date',
        'parts_replaced' => 'array',
        'attachments' => 'array',
        'metadata' => 'array',
    ];

    public function maintenancePlan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class);
    }

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

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
