<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Models\HardwareCommand;
use App\Services\HardwareCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Controller manajemen antrian hardware command
class HardwareCommandController extends Controller
{
    public function __construct(
        private readonly HardwareCommandService $commandService,
    ) {}

    // List hardware command + filter
    public function index(Request $request): JsonResponse
    {
        $query = HardwareCommand::query()
            ->with(['vault', 'device', 'issuer']);

        if ($request->has('vault_id')) {
            $query->where('vault_id', $request->input('vault_id'));
        }

        if ($request->has('status')) {
            $statuses = is_array($request->input('status'))
                ? $request->input('status')
                : [$request->input('status')];
            $query->whereIn('status', $statuses);
        }

        if ($request->has('command_type')) {
            $query->where('command_type', $request->input('command_type'));
        }

        if ($request->has('device_id')) {
            $query->where('device_id', $request->input('device_id'));
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        $perPage = $request->integer('per_page', 15);
        $commands = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->paginatedResponse($commands, 'Hardware commands retrieved');
    }

    // Detail satu command
    public function show(string $id): JsonResponse
    {
        $command = HardwareCommand::with(['vault', 'device', 'issuer'])
            ->findOrFail($id);

        return $this->successResponse($command, 'Hardware command retrieved');
    }

    // Command yang masih pending untuk vault tertentu
    public function pending(Request $request): JsonResponse
    {
        $request->validate([
            'vault_id' => 'required|uuid|exists:vaults,id',
        ]);

        $commands = $this->commandService->getPendingForVault($request->input('vault_id'));

        return $this->successResponse($commands, 'Pending commands retrieved');
    }

    // Kirim hardware command manual
    public function dispatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vault_id' => 'required|uuid|exists:vaults,id',
            'command_type' => 'required|string|in:' . implode(',', CommandType::values()),
            'device_id' => 'nullable|uuid|exists:devices,id',
            'reason' => 'nullable|string|max:64',
            'max_attempts' => 'nullable|integer|min:1|max:10',
        ]);

        $command = $this->commandService->dispatch(
            vaultId: $data['vault_id'],
            type: CommandType::from($data['command_type']),
            deviceId: $data['device_id'] ?? null,
            issuer: $request->user(),
            reason: $data['reason'] ?? 'manual_dispatch',
            maxAttempts: $data['max_attempts'] ?? 3,
        );

        return $this->successResponse($command, 'Command dispatched', 201);
    }

    // Batalkan command yang pending/sent
    public function cancel(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $this->commandService->cancel($id, $data['reason'] ?? 'Cancelled by operator');

        $command = HardwareCommand::findOrFail($id);

        return $this->successResponse($command, 'Command cancelled');
    }

    // Retry command yang gagal
    public function retry(string $id): JsonResponse
    {
        $command = HardwareCommand::findOrFail($id);

        if ($command->status !== CommandStatus::Failed) {
            return $this->errorResponse('Only failed commands can be retried', 422);
        }

        // Reset for retry
        $command->update([
            'status' => CommandStatus::Pending->value,
            'attempts' => 0,
            'failed_at' => null,
            'ack_error' => null,
        ]);

        $this->commandService->publish($command->fresh(), HardwareCommandService::DEFAULT_ACK_TIMEOUT_SECONDS);

        return $this->successResponse($command->fresh(), 'Command retried');
    }

    // Statistik antrian command
    public function statistics(Request $request): JsonResponse
    {
        $branchFilter = $request->input('branch_id');

        $query = HardwareCommand::query();

        if ($branchFilter) {
            $query->whereHas('vault', fn ($q) => $q->where('branch_id', $branchFilter));
        }

        $total = (clone $query)->count();
        $pending = (clone $query)->where('status', CommandStatus::Pending->value)->count();
        $sent = (clone $query)->where('status', CommandStatus::Sent->value)->count();
        $acknowledged = (clone $query)->where('status', CommandStatus::Acknowledged->value)->count();
        $failed = (clone $query)->where('status', CommandStatus::Failed->value)->count();
        $cancelled = (clone $query)->where('status', CommandStatus::Cancelled->value)->count();

        // Average ack time for successful commands (last 24h)
        $avgAckSeconds = (clone $query)
            ->where('status', CommandStatus::Acknowledged->value)
            ->where('acknowledged_at', '>=', now()->subDay())
            ->whereNotNull('first_sent_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, first_sent_at, acknowledged_at)) as avg_ack')
            ->value('avg_ack');

        // Failed in last 24h
        $failedRecent = (clone $query)
            ->where('status', CommandStatus::Failed->value)
            ->where('failed_at', '>=', now()->subDay())
            ->count();

        // Commands by type
        $byType = (clone $query)
            ->select('command_type', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('command_type')
            ->pluck('count', 'command_type');

        return $this->successResponse([
            'total' => $total,
            'pending' => $pending,
            'sent_awaiting_ack' => $sent,
            'acknowledged' => $acknowledged,
            'failed' => $failed,
            'cancelled' => $cancelled,
            'avg_ack_seconds_24h' => round($avgAckSeconds ?? 0, 2),
            'failed_last_24h' => $failedRecent,
            'by_type' => $byType,
        ], 'Command queue statistics retrieved');
    }
}