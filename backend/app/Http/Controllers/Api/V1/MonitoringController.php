<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\AlarmLogRepository;
use App\Repositories\DeviceRepository;
use App\Repositories\VaultRepository;
use App\Services\ServerMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MonitoringController extends Controller
{
    public function __construct(
        private readonly VaultRepository $vaultRepository,
        private readonly DeviceRepository $deviceRepository,
        private readonly AlarmLogRepository $alarmLogRepository,
        private readonly ServerMonitoringService $serverMonitoringService,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');

        $vaultStats = $this->vaultRepository->getStatusCounts($branchId);
        $deviceStats = $this->deviceRepository->getStatusCounts($branchId);
        $activeAlarms = $this->alarmLogRepository->getActiveCount($branchId);
        $activeSessions = $this->vaultRepository->getActiveSessionCount($branchId);

        return $this->successResponse([
            'vaults' => $vaultStats,
            'devices' => $deviceStats,
            'active_alarms' => $activeAlarms,
            'active_sessions' => $activeSessions,
        ], 'Dashboard data retrieved');
    }

    public function vaultStatus(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'organization_id']);
        $vaults = $this->vaultRepository->getAllWithStatus($filters);

        return $this->successResponse($vaults, 'Vault status retrieved');
    }

    public function deviceStatus(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'organization_id']);
        $devices = $this->deviceRepository->getAllWithStatus($filters);

        return $this->successResponse($devices, 'Device status retrieved');
    }

    public function alarmStatus(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'severity', 'type']);
        $alarms = $this->alarmLogRepository->getActive($filters);

        return $this->successResponse($alarms, 'Active alarms retrieved');
    }

    public function serverHealth(): JsonResponse
    {
        $health = $this->serverMonitoringService->getHealthCheck();

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return $this->successResponse($health, 'Server health retrieved', $statusCode);
    }

    public function metrics(): JsonResponse
    {
        $metrics = $this->serverMonitoringService->getLatestMetrics();

        return $this->successResponse($metrics, 'Server metrics retrieved');
    }

    public function prometheusMetrics(): Response
    {
        $metrics = $this->serverMonitoringService->getPrometheusMetrics();

        return response($metrics, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
