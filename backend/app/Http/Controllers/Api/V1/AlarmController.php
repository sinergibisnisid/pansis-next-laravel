<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alarm\AcknowledgeAlarmRequest;
use App\Http\Requests\Alarm\ResolveAlarmRequest;
use App\Repositories\AlarmLogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlarmController extends Controller
{
    public function __construct(
        private readonly AlarmLogRepository $alarmLogRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'branch_id', 'type', 'severity', 'status',
            'date_from', 'date_to', 'vault_id', 'device_id',
        ]);
        $perPage = $request->integer('per_page', 15);

        $alarms = $this->alarmLogRepository->paginate($filters, $perPage);

        return $this->paginatedResponse($alarms, 'Alarms retrieved successfully');
    }

    public function show(string $id): JsonResponse
    {
        $alarm = $this->alarmLogRepository->findOrFail($id);
        $alarm->load(['vault', 'device', 'branch', 'acknowledgedBy', 'resolvedBy']);

        return $this->successResponse($alarm, 'Alarm retrieved successfully');
    }

    public function active(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'severity', 'type']);
        $alarms = $this->alarmLogRepository->getActive($filters);

        return $this->successResponse($alarms, 'Active alarms retrieved');
    }

    public function acknowledge(AcknowledgeAlarmRequest $request, string $id): JsonResponse
    {
        $alarm = $this->alarmLogRepository->findOrFail($id);

        if ($alarm->status !== 'active') {
            return $this->errorResponse('Alarm is not in active status', 422);
        }

        $alarm = $this->alarmLogRepository->acknowledge($alarm, [
            'acknowledged_by' => $request->user()->id,
            'acknowledged_at' => now(),
            'acknowledge_notes' => $request->validated('notes'),
        ]);

        return $this->successResponse($alarm, 'Alarm acknowledged successfully');
    }

    public function resolve(ResolveAlarmRequest $request, string $id): JsonResponse
    {
        $alarm = $this->alarmLogRepository->findOrFail($id);

        if ($alarm->status === 'resolved') {
            return $this->errorResponse('Alarm is already resolved', 422);
        }

        $alarm = $this->alarmLogRepository->resolve($alarm, [
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
            'resolution_notes' => $request->validated('resolution_notes'),
        ]);

        return $this->successResponse($alarm, 'Alarm resolved successfully');
    }

    public function statistics(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'date_from', 'date_to']);

        $statistics = [
            'by_type' => $this->alarmLogRepository->countByType($filters),
            'by_severity' => $this->alarmLogRepository->countBySeverity($filters),
            'by_branch' => $this->alarmLogRepository->countByBranch($filters),
            'total_active' => $this->alarmLogRepository->getActiveCount($filters['branch_id'] ?? null),
            'total_acknowledged' => $this->alarmLogRepository->countByStatus('acknowledged', $filters),
            'total_resolved' => $this->alarmLogRepository->countByStatus('resolved', $filters),
        ];

        return $this->successResponse($statistics, 'Alarm statistics retrieved');
    }
}
