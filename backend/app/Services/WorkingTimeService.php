<?php

namespace App\Services;

use App\Models\WorkingTime;
use App\Repositories\Contracts\WorkingTimeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class WorkingTimeService
{
    public function __construct(
        private readonly WorkingTimeRepositoryInterface $workingTimeRepository,
    ) {}

    // Ambil data jadwal kerja dengan filter + pagination
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = WorkingTime::query();

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['vault_id'])) {
            $query->where('vault_id', $filters['vault_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->with(['branch', 'vault'])->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findOrFail(string $id): WorkingTime
    {
        return WorkingTime::findOrFail($id);
    }

    public function create(array $data): WorkingTime
    {
        return WorkingTime::create($data);
    }

    public function update(WorkingTime $workingTime, array $data): WorkingTime
    {
        $workingTime->update($data);
        return $workingTime->fresh();
    }

    public function delete(WorkingTime $workingTime): void
    {
        $workingTime->delete();
    }

    // Cek apakah datetime tertentu masuk jam kerja, return info jadwal + next open/close
    public function isWithinWorkingHours(string $branchId, ?string $vaultId = null, ?Carbon $dateTime = null): array
    {
        $dateTime = $dateTime ?? now();
        $isWorking = $this->isWithinWorkingTime($branchId, $vaultId, $dateTime);

        $schedules = $vaultId
            ? $this->getScheduleForVault($vaultId)
            : $this->getScheduleForBranch($branchId);

        $currentSchedule = null;
        $nextOpening = null;
        $nextClosing = null;

        $currentDayOfWeek = $dateTime->dayOfWeek;
        $currentTime = $dateTime->format('H:i:s');

        foreach ($schedules as $schedule) {
            if (!$schedule->is_active || $schedule->is_holiday) {
                continue;
            }

            // Cocokkan hari
            $matchesDay = false;
            if ($schedule->specific_date) {
                $matchesDay = $dateTime->isSameDay($schedule->specific_date);
            } elseif ($schedule->day_of_week !== null) {
                $matchesDay = $currentDayOfWeek === $schedule->day_of_week;
            } else {
                $matchesDay = true; // tanpa batasan hari = berlaku tiap hari
            }

            if ($matchesDay) {
                if ($currentTime >= $schedule->start_time && $currentTime <= $schedule->end_time) {
                    $currentSchedule = $schedule;
                    $nextClosing = $dateTime->copy()->setTimeFromTimeString($schedule->end_time);
                } elseif ($currentTime < $schedule->start_time && !$nextOpening) {
                    $nextOpening = $dateTime->copy()->setTimeFromTimeString($schedule->start_time);
                }
            }
        }

        // Cari jadwal buka berikutnya kalau hari ini sudah lewat
        if (!$nextOpening && !$isWorking) {
            for ($i = 1; $i <= 7; $i++) {
                $checkDate = $dateTime->copy()->addDays($i);
                $checkDayOfWeek = $checkDate->dayOfWeek;

                foreach ($schedules as $schedule) {
                    if (!$schedule->is_active || $schedule->is_holiday) {
                        continue;
                    }

                    $matchesDay = false;
                    if ($schedule->specific_date) {
                        $matchesDay = $checkDate->isSameDay($schedule->specific_date);
                    } elseif ($schedule->day_of_week !== null) {
                        $matchesDay = $checkDayOfWeek === $schedule->day_of_week;
                    } else {
                        $matchesDay = true;
                    }

                    if ($matchesDay) {
                        $nextOpening = $checkDate->copy()->setTimeFromTimeString($schedule->start_time);
                        break 2;
                    }
                }
            }
        }

        return [
            'is_working_hours' => $isWorking,
            'schedule' => $currentSchedule,
            'next_opening' => $nextOpening?->toIso8601String(),
            'next_closing' => $nextClosing?->toIso8601String(),
        ];
    }

    // Cek sederhana apakah sekarang masuk jam kerja (boolean)
    public function isWithinWorkingTime(string $branchId, ?string $vaultId = null, \DateTimeInterface|null $dateTime = null): bool
    {
        $dateTime = $dateTime ?? now();

        if ($this->isHoliday($branchId, $dateTime)) {
            return false;
        }

        $schedules = $vaultId
            ? $this->getScheduleForVault($vaultId)
            : $this->getScheduleForBranch($branchId);

        if ($schedules->isEmpty()) {
            return true; // belum ada jadwal = default allow
        }

        $currentDay = strtolower($dateTime->format('l'));
        $currentTime = $dateTime->format('H:i:s');

        foreach ($schedules as $schedule) {
            if (!$schedule->is_active) {
                continue;
            }

            $days = is_array($schedule->days) ? $schedule->days : json_decode($schedule->days, true);

            if (!is_array($days) || !in_array($currentDay, $days)) {
                continue;
            }

            if ($currentTime >= $schedule->start_time && $currentTime <= $schedule->end_time) {
                return true;
            }
        }

        return false;
    }

    public function getScheduleForBranch(string $branchId): Collection
    {
        return $this->workingTimeRepository->getByBranch($branchId);
    }

    // Ambil jadwal vault, fallback ke jadwal branch kalau vault belum punya
    public function getScheduleForVault(string $vaultId): Collection
    {
        $vaultSchedules = $this->workingTimeRepository->getByVault($vaultId);

        if ($vaultSchedules->isNotEmpty()) {
            return $vaultSchedules;
        }

        $vault = \App\Models\Vault::find($vaultId);
        if ($vault && $vault->branch_id) {
            return $this->workingTimeRepository->getByBranch($vault->branch_id);
        }

        return new Collection();
    }

    // Cek apakah tanggal tertentu libur (termasuk recurring)
    public function isHoliday(string $branchId, \DateTimeInterface $date): bool
    {
        $dateString = $date->format('Y-m-d');

        $holidays = WorkingTime::where('branch_id', $branchId)
            ->where('type', 'holiday')
            ->where('is_active', true)
            ->get();

        foreach ($holidays as $holiday) {
            if ($holiday->date && $holiday->date->format('Y-m-d') === $dateString) {
                return true;
            }

            // Libur recurring (tanggal+bulan sama tiap tahun)
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
