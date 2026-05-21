<?php

namespace App\Services;

use App\DTOs\Maintenance\CreateMaintenancePlanDTO;
use App\Enums\MaintenanceStatus;
use App\Models\MaintenancePlan;
use App\Repositories\Contracts\MaintenancePlanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    public function __construct(
        private readonly MaintenancePlanRepositoryInterface $maintenancePlanRepository,
        private readonly NotificationService $notificationService,
    ) {}

    public function createPlan(CreateMaintenancePlanDTO $dto): MaintenancePlan
    {
        return DB::transaction(function () use ($dto) {
            $plan = $this->maintenancePlanRepository->create([
                'vault_id' => $dto->vaultId,
                'device_id' => $dto->deviceId,
                'branch_id' => $dto->branchId,
                'assigned_to' => $dto->assignedTo,
                'title' => $dto->title,
                'description' => $dto->description,
                'type' => $dto->type,
                'priority' => $dto->priority,
                'frequency' => $dto->frequency,
                'scheduled_date' => $dto->scheduledDate,
                'scheduled_time' => $dto->scheduledTime,
                'due_date' => $dto->dueDate,
                'status' => MaintenanceStatus::Scheduled->value,
                'notes' => $dto->notes,
            ]);

            return $plan;
        });
    }

    public function completePlan(string $planId, string $userId, ?string $notes = null): MaintenancePlan
    {
        return DB::transaction(function () use ($planId, $userId, $notes) {
            $plan = $this->maintenancePlanRepository->findOrFail($planId);

            $this->maintenancePlanRepository->markCompleted($planId, $notes);

            $plan->update([
                'status' => MaintenanceStatus::Completed->value,
                'completed_by' => $userId,
                'completed_at' => now(),
                'completion_notes' => $notes,
            ]);

            return $plan->fresh();
        });
    }

    public function getUpcomingMaintenance(?string $branchId = null, int $days = 7): Collection
    {
        $upcoming = $this->maintenancePlanRepository->getUpcoming($days);

        if ($branchId) {
            return $upcoming->where('branch_id', $branchId);
        }

        return $upcoming;
    }

    public function getOverdueMaintenance(?string $branchId = null): Collection
    {
        $overdue = $this->maintenancePlanRepository->getOverdue();

        if ($branchId) {
            return $overdue->where('branch_id', $branchId);
        }

        return $overdue;
    }

    public function checkAndSendReminders(): void
    {
        // Get plans scheduled within the next 24 hours
        $upcomingPlans = MaintenancePlan::where('status', MaintenanceStatus::Scheduled->value)
            ->where('scheduled_date', '>=', now())
            ->where('scheduled_date', '<=', now()->addHours(24))
            ->where(function ($query) {
                $query->whereNull('reminder_sent_at')
                    ->orWhere('reminder_sent_at', '<', now()->subHours(12));
            })
            ->get();

        foreach ($upcomingPlans as $plan) {
            $this->notificationService->sendMaintenanceReminder($plan);

            $plan->update(['reminder_sent_at' => now()]);
        }

        // Check for overdue plans and send alerts
        $overduePlans = MaintenancePlan::where('status', MaintenanceStatus::Scheduled->value)
            ->where('due_date', '<', now())
            ->where(function ($query) {
                $query->whereNull('overdue_notified_at')
                    ->orWhere('overdue_notified_at', '<', now()->subHours(24));
            })
            ->get();

        foreach ($overduePlans as $plan) {
            $plan->update([
                'status' => MaintenanceStatus::Overdue->value,
                'overdue_notified_at' => now(),
            ]);

            $this->notificationService->sendMaintenanceReminder($plan);
        }
    }
}
