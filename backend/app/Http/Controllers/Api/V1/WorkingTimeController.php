<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkingTime\CreateWorkingTimeRequest;
use App\Services\WorkingTimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkingTimeController extends Controller
{
    public function __construct(
        private readonly WorkingTimeService $workingTimeService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'vault_id', 'type']);
        $perPage = $request->integer('per_page', 15);

        $workingTimes = $this->workingTimeService->paginate($filters, $perPage);

        return $this->paginatedResponse($workingTimes, 'Working times retrieved successfully');
    }

    public function show(string $id): JsonResponse
    {
        $workingTime = $this->workingTimeService->findOrFail($id);
        $workingTime->load(['branch', 'vault']);

        return $this->successResponse($workingTime, 'Working time retrieved successfully');
    }

    public function store(CreateWorkingTimeRequest $request): JsonResponse
    {
        $workingTime = $this->workingTimeService->create($request->validated());

        return $this->successResponse($workingTime, 'Working time created successfully', 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $workingTime = $this->workingTimeService->findOrFail($id);

        $data = $request->validate([
            'branch_id' => 'sometimes|uuid|exists:branches,id',
            'vault_id' => 'nullable|uuid|exists:vaults,id',
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:recurring,specific_date,holiday',
            'day_of_week' => 'nullable|integer|between:0,6',
            'specific_date' => 'nullable|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'timezone' => 'nullable|string|timezone',
            'is_holiday' => 'nullable|boolean',
        ]);

        $workingTime = $this->workingTimeService->update($workingTime, $data);

        return $this->successResponse($workingTime, 'Working time updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $workingTime = $this->workingTimeService->findOrFail($id);
        $this->workingTimeService->delete($workingTime);

        return $this->successResponse(message: 'Working time deleted successfully');
    }

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => 'required|uuid|exists:branches,id',
            'vault_id' => 'nullable|uuid|exists:vaults,id',
            'datetime' => 'nullable|date',
        ]);

        $result = $this->workingTimeService->isWithinWorkingHours(
            $data['branch_id'],
            $data['vault_id'] ?? null,
            isset($data['datetime']) ? \Carbon\Carbon::parse($data['datetime']) : now(),
        );

        return $this->successResponse([
            'is_working_hours' => $result['is_working_hours'],
            'current_schedule' => $result['schedule'] ?? null,
            'next_opening' => $result['next_opening'] ?? null,
            'next_closing' => $result['next_closing'] ?? null,
        ], 'Working time check completed');
    }
}
