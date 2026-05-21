<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'user_id', 'event', 'entity_type', 'entity_id',
            'branch_id', 'date_from', 'date_to', 'ip_address',
        ]);
        $perPage = $request->integer('per_page', 15);

        $logs = $this->auditService->paginate($filters, $perPage);

        return $this->paginatedResponse($logs, 'Audit logs retrieved successfully');
    }

    public function show(string $id): JsonResponse
    {
        $log = $this->auditService->findOrFail($id);
        $log->load(['user', 'branch']);

        return $this->successResponse($log, 'Audit log retrieved successfully');
    }

    public function userActivity(Request $request, string $userId): JsonResponse
    {
        $filters = $request->only(['event', 'date_from', 'date_to']);
        $perPage = $request->integer('per_page', 15);

        $logs = $this->auditService->getUserActivity($userId, $filters, $perPage);

        return $this->paginatedResponse($logs, 'User activity retrieved successfully');
    }

    public function entityHistory(Request $request, string $entityType, string $entityId): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);

        $logs = $this->auditService->getEntityHistory($entityType, $entityId, $perPage);

        return $this->paginatedResponse($logs, 'Entity history retrieved successfully');
    }
}
