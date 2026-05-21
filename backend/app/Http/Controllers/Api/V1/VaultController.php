<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Vault\CloseVaultAction;
use App\Actions\Vault\OpenVaultAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vault\CloseVaultRequest;
use App\Http\Requests\Vault\CreateVaultRequest;
use App\Http\Requests\Vault\OpenVaultRequest;
use App\Http\Requests\Vault\UpdateVaultRequest;
use App\Repositories\VaultRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VaultController extends Controller
{
    public function __construct(
        private readonly VaultRepository $vaultRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'status', 'type', 'search', 'organization_id']);
        $perPage = $request->integer('per_page', 15);

        $vaults = $this->vaultRepository->paginate($filters, $perPage);

        return $this->paginatedResponse($vaults, 'Vaults retrieved successfully');
    }

    public function show(string $id): JsonResponse
    {
        $vault = $this->vaultRepository->findOrFail($id);
        $vault->load(['branch', 'devices', 'currentSession']);

        return $this->successResponse($vault, 'Vault retrieved successfully');
    }

    public function store(CreateVaultRequest $request): JsonResponse
    {
        $vault = $this->vaultRepository->create($request->validated());
        $vault->load(['branch']);

        return $this->successResponse($vault, 'Vault created successfully', 201);
    }

    public function update(UpdateVaultRequest $request, string $id): JsonResponse
    {
        $vault = $this->vaultRepository->findOrFail($id);
        $vault = $this->vaultRepository->update($vault, $request->validated());
        $vault->load(['branch']);

        return $this->successResponse($vault, 'Vault updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $vault = $this->vaultRepository->findOrFail($id);
        $this->vaultRepository->delete($vault);

        return $this->successResponse(message: 'Vault deleted successfully');
    }

    public function status(string $id): JsonResponse
    {
        $vault = $this->vaultRepository->findOrFail($id);
        $vault->load(['currentSession', 'devices']);

        return $this->successResponse([
            'vault' => $vault,
            'status' => $vault->status,
            'is_open' => $vault->status === 'open',
            'current_session' => $vault->currentSession,
            'devices_online' => $vault->devices->where('status', 'online')->count(),
            'devices_total' => $vault->devices->count(),
        ], 'Vault status retrieved');
    }

    public function openVault(OpenVaultRequest $request, OpenVaultAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse($result['data'], 'Vault opened successfully');
    }

    public function closeVault(CloseVaultRequest $request, CloseVaultAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse($result['data'], 'Vault closed successfully');
    }

    public function activeSessions(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'vault_id']);
        $sessions = $this->vaultRepository->getActiveSessions($filters);

        return $this->successResponse($sessions, 'Active sessions retrieved');
    }
}
