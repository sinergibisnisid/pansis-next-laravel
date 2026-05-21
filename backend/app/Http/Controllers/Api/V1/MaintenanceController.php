<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Maintenance\CompleteMaintenanceAction;
use App\Actions\Maintenance\CreateMaintenancePlanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\CreateMaintenancePlanRequest;
use App\Services\MaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function __construct(
        private readonly MaintenanceService $maintenanceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'branch_id', 'vault_id', 'device_id', 'type',
            'priority', 'status', 'assigned_to', 'date_from', 'date_to',
        ]);
        $perPage = $request->integer('per_page', 15);

        $plans = $this->maintenanceService->paginate($filters, $perPage);

        return $this->paginatedResponse($plans, 'Maintenance plans retrieved successfully');
    }

    public function show(string $id): JsonResponse
    {
        $plan = $this->maintenanceService->findOrFail($id);
        $plan->load(['vault', 'device', 'branch', 'assignedTo', 'logs']);

        return $this->successResponse($plan, 'Maintenance plan retrieved successfully');
    }

    public function store(CreateMaintenancePlanRequest $request, CreateMaintenancePlanAction $action): JsonResponse
    {
        $plan = $action->execute($request->validated(), $request->user());

        return $this->successResponse($plan, 'Maintenance plan created successfully', 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $plan = $this->maintenanceService->findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|string|in:cleaning,lubrication,inspection,repair,calibration,replacement',
            'priority' => 'sometimes|string|in:low,medium,high,urgent',
            'frequency' => 'nullable|string|in:daily,weekly,monthly,quarterly,yearly,once',
            'scheduled_date' => 'sometimes|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'due_date' => 'nullable|date|after_or_equal:scheduled_date',
            'assigned_to' => 'nullable|uuid|exists:users,id',
        ]);

        $plan = $this->maintenanceService->update($plan, $data);

        return $this->successResponse($plan, 'Maintenance plan updated successfully');
    }

    public function complete(Request $request, string $id, CompleteMaintenanceAction $action): JsonResponse
    {
        $plan = $this->maintenanceService->findOrFail($id);

        $data = $request->validate([
            'notes' => 'nullable|string|max:2000',
            'parts_replaced' => 'nullable|array',
            'parts_replaced.*' => 'string|max:255',
        ]);

        $result = $action->execute($plan, $data, $request->user());

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse($result['plan'], 'Maintenance completed successfully');
    }

    public function upcoming(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'days', 'assigned_to']);
        $days = $request->integer('days', 7);

        $plans = $this->maintenanceService->getUpcoming($filters, $days);

        return $this->successResponse($plans, 'Upcoming maintenance plans retrieved');
    }

    public function overdue(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'assigned_to']);
        $plans = $this->maintenanceService->getOverdue($filters);

        return $this->successResponse($plans, 'Overdue maintenance plans retrieved');
    }

    public function logs(string $id): JsonResponse
    {
        $plan = $this->maintenanceService->findOrFail($id);
        $logs = $this->maintenanceService->getLogs($plan);

        return $this->successResponse($logs, 'Maintenance logs retrieved');
    }
}
