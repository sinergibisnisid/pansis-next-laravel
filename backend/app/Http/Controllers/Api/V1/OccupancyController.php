<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OccupancyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Controller tracking occupancy vault
class OccupancyController extends Controller
{
    public function __construct(
        private readonly OccupancyService $occupancyService,
    ) {}

    // Status occupancy vault saat ini
    public function status(string $vaultId): JsonResponse
    {
        $status = $this->occupancyService->getStatus($vaultId);

        return $this->successResponse($status, 'Occupancy status retrieved');
    }

    // Catat orang masuk
    public function entry(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vault_id' => 'required|uuid|exists:vaults,id',
            'user_id' => 'nullable|uuid|exists:users,id',
            'session_id' => 'nullable|uuid|exists:vault_sessions,id',
            'method' => 'nullable|string|in:fingerprint,door_sensor,manual,camera',
            'notes' => 'nullable|string|max:255',
        ]);

        $log = $this->occupancyService->recordEntry(
            vaultId: $data['vault_id'],
            userId: $data['user_id'] ?? null,
            sessionId: $data['session_id'] ?? null,
            method: $data['method'] ?? 'door_sensor',
            notes: $data['notes'] ?? null,
        );

        return $this->successResponse($log, 'Entry recorded', 201);
    }

    // Catat orang keluar
    public function exit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vault_id' => 'required|uuid|exists:vaults,id',
            'user_id' => 'nullable|uuid|exists:users,id',
            'method' => 'nullable|string|in:exit_button,door_sensor,manual,camera',
            'notes' => 'nullable|string|max:255',
        ]);

        $log = $this->occupancyService->recordExit(
            vaultId: $data['vault_id'],
            userId: $data['user_id'] ?? null,
            method: $data['method'] ?? 'door_sensor',
            notes: $data['notes'] ?? null,
        );

        if (!$log) {
            return $this->errorResponse('No matching entry found for exit', 404);
        }

        return $this->successResponse($log, 'Exit recorded');
    }

    // Paksa semua orang keluar dari vault
    public function exitAll(Request $request, string $vaultId): JsonResponse
    {
        $data = $request->validate([
            'method' => 'nullable|string|in:session_closed,manual,emergency',
        ]);

        $count = $this->occupancyService->exitAll(
            $vaultId,
            $data['method'] ?? 'manual',
        );

        return $this->successResponse(
            ['exited_count' => $count],
            "All occupants exited ({$count} people)",
        );
    }

    // Riwayat occupancy vault
    public function history(Request $request, string $vaultId): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'user_id']);
        $perPage = $request->integer('per_page', 15);

        $history = $this->occupancyService->getHistory($vaultId, $filters, $perPage);

        return $this->paginatedResponse($history, 'Occupancy history retrieved');
    }

    // Ringkasan occupancy semua vault
    public function summary(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');
        $summary = $this->occupancyService->getSummary($branchId);

        return $this->successResponse($summary, 'Occupancy summary retrieved');
    }
}