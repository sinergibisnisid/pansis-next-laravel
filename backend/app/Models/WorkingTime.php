<?php

namespace App\Models;

use App\Enums\WorkingTimeType;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class WorkingTime extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'working_times';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'branch_id',
        'vault_id',
        'name',
        'type',
        'day_of_week',
        'specific_date',
        'start_time',
        'end_time',
        'timezone',
        'is_active',
        'is_holiday',
        'description',
        'metadata',
    ];

    protected $casts = [
        'type' => WorkingTimeType::class,
        'specific_date' => 'date',
        'is_active' => 'boolean',
        'is_holiday' => 'boolean',
        'metadata' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function isWithinWorkingTime(DateTimeInterface $dateTime): bool
    {
        $carbon = Carbon::instance($dateTime);

        if ($this->timezone) {
            $carbon = $carbon->setTimezone($this->timezone);
        }

        if ($this->is_holiday) {
            return false;
        }

        if ($this->specific_date) {
            if (!$carbon->isSameDay($this->specific_date)) {
                return false;
            }
        } elseif ($this->day_of_week !== null) {
            if ($carbon->dayOfWeek !== $this->day_of_week) {
                return false;
            }
        }

        $currentTime = $carbon->format('H:i:s');

        return $currentTime >= $this->start_time && $currentTime <= $this->end_time;
    }
}
