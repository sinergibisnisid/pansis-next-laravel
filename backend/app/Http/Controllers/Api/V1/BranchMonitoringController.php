<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BranchMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Controller monitoring cabang (geo/map view)
class BranchMonitoringController extends Controller
{
    public function __construct(
        private readonly BranchMonitoringService $branchMonitoringService,
    ) {}

    // Semua cabang + koordinat + status untuk map
    public function geoStatus(Request $request): JsonResponse
    {
        $organizationId = $request->input('organization_id');
        $data = $this->branchMonitoringService->getGeoStatus($organizationId);

        return $this->successResponse($data, 'Branch geo status retrieved');
    }

    // Detail status cabang (popup map)
    public function detail(string $branchId): JsonResponse
    {
        $data = $this->branchMonitoringService->getBranchDetail($branchId);

        return $this->successResponse($data, 'Branch detail retrieved');
    }

    // Cabang yang punya alarm aktif
    public function withAlarms(): JsonResponse
    {
        $branches = $this->branchMonitoringService->getBranchesWithAlarms();

        return $this->successResponse($branches, 'Branches with alarms retrieved');
    }

    // Ringkasan kesehatan seluruh cabang
    public function overallHealth(Request $request): JsonResponse
    {
        $organizationId = $request->input('organization_id');
        $health = $this->branchMonitoringService->getOverallHealth($organizationId);

        return $this->successResponse($health, 'Overall health retrieved');
    }
}