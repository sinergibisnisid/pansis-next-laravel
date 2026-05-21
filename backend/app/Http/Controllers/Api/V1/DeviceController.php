<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Device\ProcessHeartbeatAction;
use App\Actions\Device\RegisterDeviceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Device\HeartbeatRequest;
use App\Http\Requests\Device\RegisterDeviceRequest;
use App\Services\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(
        private readonly DeviceService $deviceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'vault_id', 'type', 'status', 'search']);
        $perPage = $request->integer('per_page', 15);

        $devices = $this->deviceService->paginate($filters, $perPage);

        return $this->paginatedResponse($devices, 'Devices retrieved successfully');
    }

    public function show(string $id): JsonResponse
    {
        $device = $this->deviceService->findOrFail($id);
        $device->load(['vault', 'branch', 'latestHeartbeat']);

        return $this->successResponse($device, 'Device retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $device = $this->deviceService->create($request->validated());

        return $this->successResponse($device, 'Device created successfully', 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $device = $this->deviceService->findOrFail($id);
        $device = $this->deviceService->update($device, $request->validated());

        return $this->successResponse($device, 'Device updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $device = $this->deviceService->findOrFail($id);
        $this->deviceService->delete($device);

        return $this->successResponse(message: 'Device deleted successfully');
    }

    public function register(RegisterDeviceRequest $request, RegisterDeviceAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse($result['device'], 'Device registered successfully', 201);
    }

    public function heartbeat(HeartbeatRequest $request, ProcessHeartbeatAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse($result['data'], 'Heartbeat processed');
    }

    public function status(string $id): JsonResponse
    {
        $device = $this->deviceService->findOrFail($id);
        $device->load(['latestHeartbeat']);

        return $this->successResponse([
            'device' => $device,
            'status' => $device->status,
            'is_online' => $device->status === 'online',
            'last_heartbeat' => $device->latestHeartbeat,
            'uptime_seconds' => $device->latestHeartbeat?->uptime_seconds,
        ], 'Device status retrieved');
    }

    public function online(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'vault_id', 'type']);
        $devices = $this->deviceService->getByStatus('online', $filters);

        return $this->successResponse($devices, 'Online devices retrieved');
    }

    public function offline(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'vault_id', 'type']);
        $devices = $this->deviceService->getByStatus('offline', $filters);

        return $this->successResponse($devices, 'Offline devices retrieved');
    }
}
