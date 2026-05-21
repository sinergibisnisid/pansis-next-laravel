<?php

namespace App\Services;

use App\Models\WorkingTime;
use App\Repositories\Contracts\WorkingTimeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class WorkingTimeService
{
    public function __construct(
        private readonly WorkingTimeRepositoryInterface $workingTimeRepository,
    ) {}

    public function isWithinWorkingTime(string $branchId, ?string $vaultId = null, ?\DateTimeInterface $dateTime = null): bool
    {
        $dateTime = $dateTime ?? now();

        // Check if today is a holiday
        if ($this->isHoliday($branchId, $dateTime)) {
            return false;
        }

        // Get applicable schedules
        $schedules = $vaultId
            ? $this->getScheduleForVault($vaultId)
            : $this->getScheduleForBranch($branchId);

        if ($schedules->isEmpty()) {
            // No schedules configured, default to allow access
            return true;
        }

        $currentDay = strtolower($dateTime->format('l'));
        $currentTime = $dateTime->format('H:i:s');

        foreach ($schedules as $schedule) {
            if (!$schedule->is_active) {
                continue;
            }

            // Check if the schedule applies to the current day
            $days = is_array($schedule->days) ? $schedule->days : json_decode($schedule->days, true);

            if (!is_array($days) || !in_array($currentDay, $days)) {
                continue;
            }

            // Check if current time is within the schedule's time range
            $startTime = $schedule->start_time;
            $endTime = $schedule->end_time;

            if ($currentTime >= $startTime && $currentTime <= $endTime) {
                return true;
            }
        }

        return false;
    }

    public function getScheduleForBranch(string $branchId): Collection
    {
        return $this->workingTimeRepository->getByBranch($branchId);
    }

    public function getScheduleForVault(string $vaultId): Collection
    {
        $vaultSchedules = $this->workingTimeRepository->getByVault($vaultId);

        // If vault has specific schedules, use those
        if ($vaultSchedules->isNotEmpty()) {
            return $vaultSchedules;
        }

        // Fall back to branch schedules
        $vault = \App\Models\Vault::find($vaultId);
        if ($vault && $vault->branch_id) {
            return $this->workingTimeRepository->getByBranch($vault->branch_id);
        }

        return new Collection();
    }

    public function isHoliday(string $branchId, \DateTimeInterface $date): bool
    {
        $dateString = $date->format('Y-m-d');

        // Check holidays from working_times table with type 'holiday'
        $holidays = WorkingTime::where('branch_id', $branchId)
            ->where('type', 'holiday')
            ->where('is_active', true)
            ->get();

        foreach ($holidays as $holiday) {
            if ($holiday->date && $holiday->date->format('Y-m-d') === $dateString) {
                return true;
            }

            // Check recurring holidays (same month and day every year)
            if ($holiday->is_recurring) {
                $holidayMonthDay = $holiday->date?->format('m-d');
                $checkMonthDay = $date->format('m-d');

                if ($holidayMonthDay === $checkMonthDay) {
                    return true;
                }
            }
        }

        return false;
    }
}
