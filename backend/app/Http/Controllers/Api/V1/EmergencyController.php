<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\EmergencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Controller emergency / tombol panik
class EmergencyController extends Controller
{
    public function __construct(
        private readonly EmergencyService $emergencyService,
    ) {}

    // List emergency aktif (belum resolved)
    public function active(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'severity', 'acknowledged']);
        $perPage = $request->integer('per_page', 15);

        $emergencies = $this->emergencyService->getActiveEmergencies($filters, $perPage);

        return $this->paginatedResponse($emergencies, 'Active emergencies retrieved');
    }

    // Riwayat emergency
    public function history(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'date_from', 'date_to']);
        $perPage = $request->integer('per_page', 15);

        $history = $this->emergencyService->getHistory($filters, $perPage);

        return $this->paginatedResponse($history, 'Emergency history retrieved');
    }

    // Trigger emergency (dari MQTT atau manual operator)
    public function trigger(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vault_id' => 'required|uuid|exists:vaults,id',
            'device_id' => 'nullable|uuid|exists:devices,id',
            'source' => 'nullable|string|in:panic_button,manual,sensor',
            'notes' => 'nullable|string|max:500',
        ]);

        $alarm = $this->emergencyService->trigger($data['vault_id'], [
            'device_id' => $data['device_id'] ?? null,
            'source' => $data['source'] ?? 'manual',
            'additional' => $data['notes'] ?? null,
        ]);

        return $this->successResponse($alarm, 'Emergency triggered', 201);
    }

    // Acknowledge emergency
    public function acknowledge(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $alarm = $this->emergencyService->acknowledge(
            $id,
            $request->user()->id,
            $data['notes'] ?? null,
        );

        return $this->successResponse($alarm, 'Emergency acknowledged');
    }

    // Resolve emergency
    public function resolve(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'resolution' => 'required|string|in:false_alarm,handled,escalated,police_called',
            'notes' => 'nullable|string|max:1000',
        ]);

        $alarm = $this->emergencyService->resolve(
            $id,
            $request->user()->id,
            $data['resolution'],
            $data['notes'] ?? null,
        );

        return $this->successResponse($alarm, 'Emergency resolved');
    }

    // Statistik emergency
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->emergencyService->getStatistics(
            $request->input('branch_id'),
            $request->input('date_from'),
            $request->input('date_to'),
        );

        return $this->successResponse($stats, 'Emergency statistics retrieved');
    }
}