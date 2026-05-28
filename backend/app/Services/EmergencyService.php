<?php

namespace App\Services;

use App\Enums\AlarmType;
use App\Enums\Severity;
use App\Models\AlarmLog;
use App\Models\Branch;
use App\Models\Vault;
use App\Models\VaultSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Service emergency / tombol panik: trigger, acknowledge, resolve
class EmergencyService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly HardwareCommandService $hardwareCommandService,
    ) {}

    // Ambil emergency yang masih aktif (belum resolved)
    public function getActiveEmergencies(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AlarmLog::query()
            ->where('alarm_type', AlarmType::Emergency->value)
            ->whereNull('resolved_at')
            ->with(['vault', 'vault.branch', 'acknowledgedByUser']);

        if (!empty($filters['branch_id'])) {
            $query->whereHas('vault', fn ($q) => $q->where('branch_id', $filters['branch_id']));
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['acknowledged'])) {
            $query->whereNotNull('acknowledged_at');
        } elseif (isset($filters['acknowledged']) && $filters['acknowledged'] === false) {
            $query->whereNull('acknowledged_at');
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    // Riwayat emergency yang sudah selesai
    public function getHistory(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AlarmLog::query()
            ->where('alarm_type', AlarmType::Emergency->value)
            ->whereNotNull('resolved_at')
            ->with(['vault', 'vault.branch', 'acknowledgedByUser', 'resolvedByUser']);

        if (!empty($filters['branch_id'])) {
            $query->whereHas('vault', fn ($q) => $q->where('branch_id', $filters['branch_id']));
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    // Trigger emergency (dipanggil saat tombol panik ditekan)
    public function trigger(string $vaultId, array $context = []): AlarmLog
    {
        $vault = Vault::with('branch')->findOrFail($vaultId);

        return DB::transaction(function () use ($vault, $context) {
            // Buat record alarm
            $alarm = AlarmLog::create([
                'vault_id' => $vault->id,
                'branch_id' => $vault->branch_id,
                'device_id' => $context['device_id'] ?? null,
                'alarm_type' => AlarmType::Emergency->value,
                'severity' => Severity::Critical->value,
                'title' => 'Emergency Button Pressed',
                'description' => 'Emergency button pressed in vault: ' . $vault->name,
                'triggered_at' => now(),
                'metadata' => [
                    'trigger_source' => $context['source'] ?? 'panic_button',
                    'vault_name' => $vault->name,
                    'branch_name' => $vault->branch?->name,
                    'session_id' => $context['session_id'] ?? null,
                    'additional' => $context['additional'] ?? null,
                ],
            ]);

            // Tandai session aktif dengan timestamp emergency
            $activeSession = VaultSession::where('vault_id', $vault->id)
                ->whereNull('closed_at')
                ->latest('opened_at')
                ->first();

            if ($activeSession) {
                $activeSession->update([
                    'emergency_button_pressed_at' => now(),
                ]);
            }

            // Nyalakan buzzer sebagai respon langsung
            $this->activateEmergencyBuzzer($vault->id, $context['device_id'] ?? null);

            Log::critical('Emergency triggered', [
                'vault_id' => $vault->id,
                'branch_id' => $vault->branch_id,
                'alarm_id' => $alarm->id,
            ]);

            // Dispatch event untuk eskalasi notifikasi
            event('emergency.triggered', [
                'alarm' => $alarm,
                'vault' => $vault,
            ]);

            return $alarm;
        });
    }

    // Acknowledge emergency (operator sudah lihat)
    public function acknowledge(string $alarmId, string $userId, ?string $notes = null): AlarmLog
    {
        $alarm = AlarmLog::where('alarm_type', AlarmType::Emergency->value)
            ->findOrFail($alarmId);

        if ($alarm->acknowledged_at) {
            return $alarm;
        }

        $alarm->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
            'metadata' => array_merge($alarm->metadata ?? [], [
                'acknowledge_notes' => $notes,
            ]),
        ]);

        Log::info('Emergency acknowledged', [
            'alarm_id' => $alarmId,
            'user_id' => $userId,
        ]);

        event('emergency.acknowledged', [
            'alarm' => $alarm->fresh(),
        ]);

        return $alarm->fresh();
    }

    // Resolve emergency (situasi sudah ditangani)
    public function resolve(string $alarmId, string $userId, string $resolution, ?string $notes = null): AlarmLog
    {
        $alarm = AlarmLog::where('alarm_type', AlarmType::Emergency->value)
            ->findOrFail($alarmId);

        if ($alarm->resolved_at) {
            return $alarm;
        }

        $alarm->update([
            'resolved_at' => now(),
            'resolved_by' => $userId,
            'resolution_notes' => $resolution . ($notes ? " — {$notes}" : ''),
            'metadata' => array_merge($alarm->metadata ?? [], [
                'resolution_type' => $resolution,
                'resolution_notes' => $notes,
                'response_time_seconds' => $alarm->created_at
                    ? now()->diffInSeconds($alarm->created_at)
                    : null,
            ]),
        ]);

        // Matikan buzzer
        $this->deactivateEmergencyBuzzer($alarm->vault_id);

        Log::info('Emergency resolved', [
            'alarm_id' => $alarmId,
            'user_id' => $userId,
            'resolution' => $resolution,
        ]);

        event('emergency.resolved', [
            'alarm' => $alarm->fresh(),
        ]);

        return $alarm->fresh();
    }

    // Statistik emergency
    public function getStatistics(?string $branchId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = AlarmLog::query()->where('alarm_type', AlarmType::Emergency->value);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        $total = (clone $query)->count();
        $active = (clone $query)->whereNull('resolved_at')->count();
        $acknowledged = (clone $query)->whereNotNull('acknowledged_at')->whereNull('resolved_at')->count();
        $resolved = (clone $query)->whereNotNull('resolved_at')->count();

        // Rata-rata waktu respon
        $avgResponseSeconds = (clone $query)
            ->whereNotNull('acknowledged_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, acknowledged_at)) as avg_response')
            ->value('avg_response');

        // Rata-rata waktu resolusi
        $avgResolutionSeconds = (clone $query)
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, resolved_at)) as avg_resolution')
            ->value('avg_resolution');

        // Breakdown per cabang
        $byBranch = (clone $query)
            ->select('branch_id', DB::raw('COUNT(*) as count'))
            ->groupBy('branch_id')
            ->with('branch:id,name')
            ->get();

        return [
            'total' => $total,
            'active' => $active,
            'acknowledged_pending_resolution' => $acknowledged,
            'resolved' => $resolved,
            'avg_response_seconds' => round($avgResponseSeconds ?? 0),
            'avg_resolution_seconds' => round($avgResolutionSeconds ?? 0),
            'by_branch' => $byBranch,
        ];
    }

    // Nyalakan buzzer emergency
    private function activateEmergencyBuzzer(string $vaultId, ?string $deviceId = null): void
    {
        try {
            $this->hardwareCommandService->dispatch(
                vaultId: $vaultId,
                type: \App\Enums\CommandType::BuzzerActivate,
                deviceId: $deviceId,
                reason: 'emergency_button_pressed',
            );
        } catch (\Throwable $e) {
            Log::error('Failed to activate emergency buzzer', [
                'vault_id' => $vaultId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // Matikan buzzer emergency
    private function deactivateEmergencyBuzzer(string $vaultId): void
    {
        try {
            $this->hardwareCommandService->dispatch(
                vaultId: $vaultId,
                type: \App\Enums\CommandType::BuzzerDeactivate,
                reason: 'emergency_resolved',
            );
        } catch (\Throwable $e) {
            Log::error('Failed to deactivate emergency buzzer', [
                'vault_id' => $vaultId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}