<?php

namespace App\Services;

use App\Models\Vault;
use App\Models\VaultOccupancyLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Service tracking jumlah orang di dalam vault
class OccupancyService
{
    // Catat orang masuk vault
    public function recordEntry(
        string $vaultId,
        ?string $userId = null,
        ?string $sessionId = null,
        string $method = 'door_sensor',
        ?string $notes = null,
    ): VaultOccupancyLog {
        $log = VaultOccupancyLog::create([
            'vault_id' => $vaultId,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'entered_at' => now(),
            'entry_method' => $method,
            'notes' => $notes,
        ]);

        // Cek apakah melebihi batas
        $currentCount = $this->getCurrentCount($vaultId);
        $vault = Vault::find($vaultId);

        if ($vault && $currentCount > $vault->max_occupancy) {
            $this->handleOverOccupancy($vault, $currentCount);
        }

        return $log;
    }

    // Catat orang keluar vault (FIFO kalau user tidak diketahui)
    public function recordExit(
        string $vaultId,
        ?string $userId = null,
        string $method = 'door_sensor',
        ?string $notes = null,
    ): ?VaultOccupancyLog {
        $query = VaultOccupancyLog::where('vault_id', $vaultId)
            ->whereNull('exited_at');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $log = $query->oldest('entered_at')->first();

        if (!$log) {
            Log::warning('Exit recorded but no matching entry found', [
                'vault_id' => $vaultId,
                'user_id' => $userId,
            ]);
            return null;
        }

        $now = now();
        $log->update([
            'exited_at' => $now,
            'exit_method' => $method,
            'duration_seconds' => (int) $log->entered_at->diffInSeconds($now),
            'notes' => $notes ?? $log->notes,
        ]);

        return $log->fresh();
    }

    // Paksa semua orang keluar (misal saat session ditutup)
    public function exitAll(string $vaultId, string $method = 'session_closed'): int
    {
        $now = now();

        $logs = VaultOccupancyLog::where('vault_id', $vaultId)
            ->whereNull('exited_at')
            ->get();

        foreach ($logs as $log) {
            $log->update([
                'exited_at' => $now,
                'exit_method' => $method,
                'duration_seconds' => (int) $log->entered_at->diffInSeconds($now),
            ]);
        }

        return $logs->count();
    }

    // Jumlah orang di dalam vault saat ini
    public function getCurrentCount(string $vaultId): int
    {
        return VaultOccupancyLog::where('vault_id', $vaultId)
            ->whereNull('exited_at')
            ->count();
    }

    // List orang yang sedang di dalam vault
    public function getCurrentOccupants(string $vaultId): Collection
    {
        return VaultOccupancyLog::where('vault_id', $vaultId)
            ->whereNull('exited_at')
            ->with('user')
            ->orderBy('entered_at')
            ->get();
    }

    // Status occupancy vault (jumlah + info threshold)
    public function getStatus(string $vaultId): array
    {
        $vault = Vault::findOrFail($vaultId);
        $currentCount = $this->getCurrentCount($vaultId);
        $occupants = $this->getCurrentOccupants($vaultId);

        return [
            'vault_id' => $vaultId,
            'current_count' => $currentCount,
            'max_occupancy' => $vault->max_occupancy,
            'is_over_limit' => $currentCount > $vault->max_occupancy,
            'occupants' => $occupants,
            'oldest_entry_at' => $occupants->first()?->entered_at,
            'longest_duration_seconds' => $occupants->first()?->currentDuration(),
        ];
    }

    // Riwayat occupancy vault
    public function getHistory(string $vaultId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = VaultOccupancyLog::where('vault_id', $vaultId)
            ->with('user');

        if (!empty($filters['date_from'])) {
            $query->where('entered_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('entered_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderByDesc('entered_at')->paginate($perPage);
    }

    // Ringkasan occupancy semua vault
    public function getSummary(?string $branchId = null): array
    {
        $vaultQuery = Vault::query();
        if ($branchId) {
            $vaultQuery->where('branch_id', $branchId);
        }
        $vaultIds = $vaultQuery->pluck('id');

        $totalOccupied = VaultOccupancyLog::whereIn('vault_id', $vaultIds)
            ->whereNull('exited_at')
            ->distinct('vault_id')
            ->count('vault_id');

        $totalPeopleInside = VaultOccupancyLog::whereIn('vault_id', $vaultIds)
            ->whereNull('exited_at')
            ->count();

        $overLimit = 0;
        $vaults = Vault::whereIn('id', $vaultIds)->get();
        foreach ($vaults as $vault) {
            $count = VaultOccupancyLog::where('vault_id', $vault->id)
                ->whereNull('exited_at')
                ->count();
            if ($count > $vault->max_occupancy) {
                $overLimit++;
            }
        }

        // Average occupancy duration today
        $avgDuration = VaultOccupancyLog::whereIn('vault_id', $vaultIds)
            ->whereNotNull('exited_at')
            ->whereDate('entered_at', today())
            ->avg('duration_seconds');

        return [
            'total_vaults' => $vaultIds->count(),
            'vaults_occupied' => $totalOccupied,
            'total_people_inside' => $totalPeopleInside,
            'vaults_over_limit' => $overLimit,
            'avg_duration_seconds_today' => round($avgDuration ?? 0),
        ];
    }

    // Handle vault melebihi batas occupancy
    private function handleOverOccupancy(Vault $vault, int $currentCount): void
    {
        Log::warning('Vault over occupancy limit', [
            'vault_id' => $vault->id,
            'current_count' => $currentCount,
            'max_occupancy' => $vault->max_occupancy,
        ]);

        event('vault.occupancy.exceeded', [
            'vault' => $vault,
            'current_count' => $currentCount,
            'max_occupancy' => $vault->max_occupancy,
        ]);
    }
}