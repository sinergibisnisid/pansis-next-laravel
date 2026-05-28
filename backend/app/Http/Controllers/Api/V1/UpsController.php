<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UpsMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Controller monitoring UPS
class UpsController extends Controller
{
    public function __construct(
        private readonly UpsMonitoringService $upsService,
    ) {}

    // List semua UPS
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'status', 'on_battery', 'critical', 'battery_due']);
        $perPage = $request->integer('per_page', 15);

        $devices = $this->upsService->list($filters, $perPage);

        return $this->paginatedResponse($devices, 'UPS devices retrieved successfully');
    }

    // Detail UPS
    public function show(string $id): JsonResponse
    {
        $device = $this->upsService->find($id);

        return $this->successResponse($device, 'UPS device retrieved successfully');
    }

    // Simpan/update spec UPS
    public function upsertSpec(Request $request, string $deviceId): JsonResponse
    {
        $data = $request->validate([
            'powers_device_id' => 'nullable|uuid|exists:devices,id',
            'manufacturer' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'capacity_va' => 'nullable|integer|min:0',
            'capacity_w' => 'nullable|integer|min:0',
            'battery_runtime_minutes' => 'nullable|integer|min:0',
            'battery_installed_at' => 'nullable|date',
            'battery_replace_due_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        $spec = $this->upsService->upsertSpec($deviceId, $data);

        return $this->successResponse($spec, 'UPS spec saved successfully');
    }

    // UPS yang sedang pakai baterai
    public function onBattery(): JsonResponse
    {
        $devices = $this->upsService->getOnBattery();

        return $this->successResponse($devices, 'UPS devices on battery retrieved');
    }

    // UPS dalam kondisi kritis
    public function critical(): JsonResponse
    {
        $devices = $this->upsService->getCritical();

        return $this->successResponse($devices, 'Critical UPS devices retrieved');
    }

    // UPS yang baterainya perlu diganti
    public function batteryDue(): JsonResponse
    {
        $devices = $this->upsService->getBatteryDue();

        return $this->successResponse($devices, 'UPS devices with battery due retrieved');
    }

    // Ringkasan monitoring UPS
    public function summary(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');
        $summary = $this->upsService->getSummary($branchId);

        return $this->successResponse($summary, 'UPS summary retrieved');
    }
}