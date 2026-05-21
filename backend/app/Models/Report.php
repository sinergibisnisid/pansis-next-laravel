<?php

namespace App\Models;

use App\Enums\ReportFormat;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'reports';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'branch_id',
        'title',
        'type',
        'format',
        'status',
        'file_path',
        'file_name',
        'file_size',
        'parameters',
        'period_start',
        'period_end',
        'generated_at',
        'error_message',
        'is_scheduled',
        'schedule_frequency',
        'metadata',
    ];

    protected $casts = [
        'type' => ReportType::class,
        'format' => ReportFormat::class,
        'status' => ReportStatus::class,
        'parameters' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
        'is_scheduled' => 'boolean',
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
