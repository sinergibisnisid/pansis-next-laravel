<?php

namespace App\Services;

use App\Enums\AlarmType;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Vault;
use App\Models\VaultOccupancyLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

// Service monitoring cabang: data geo + status agregat untuk map view
class BranchMonitoringService
{
    // Semua cabang + koordinat + status (untuk map view)
    public function getGeoStatus(?string $organizationId = null): array
    {
        $query = Branch::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('is_active', true);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $branches = $query->get();

        return $branches->map(function (Branch $branch) {
            return $this->buildBranchGeoData($branch);
        })->toArray();
    }

    // Detail status satu cabang (popup map)
    public function getBranchDetail(string $branchId): array
    {
        $branch = Branch::with(['vaults', 'devices'])->findOrFail($branchId);

        $geoData = $this->buildBranchGeoData($branch);

        // Breakdown device per tipe
        $geoData['devices'] = $branch->devices->groupBy('type')->map(function ($devices, $type) {
            return [
                'type' => $type,
                'total' => $devices->count(),
                'online' => $devices->where('status', 'online')->count(),
                'offline' => $devices->where('status', 'offline')->count(),
            ];
        })->values();

        // Detail vault
        $geoData['vaults'] = $branch->vaults->map(function (Vault $vault) {
            $occupancy = VaultOccupancyLog::where('vault_id', $vault->id)
                ->whereNull('exited_at')
                ->count();

            return [
                'id' => $vault->id,
                'name' => $vault->name,
                'status' => $vault->status,
                'current_occupancy' => $occupancy,
                'max_occupancy' => $vault->max_occupancy ?? 1,
            ];
        });

        return $geoData;
    }

    // Cabang yang punya alarm aktif (untuk highlight di map)
    public function getBranchesWithAlarms(): Collection
    {
        return Branch::query()
            ->whereHas('alarmLogs', function ($q) {
                $q->whereNull('resolved_at');
            })
            ->with(['alarmLogs' => function ($q) {
                $q->whereNull('resolved_at')->latest()->limit(5);
            }])
            ->get();
    }

    // Ringkasan kesehatan seluruh cabang
    public function getOverallHealth(?string $organizationId = null): array
    {
        $branchQuery = Branch::query()->where('is_active', true);
        if ($organizationId) {
            $branchQuery->where('organization_id', $organizationId);
        }

        $branchIds = $branchQuery->pluck('id');

        $totalBranches = $branchIds->count();
        $totalDevices = Device::whereIn('branch_id', $branchIds)->count();
        $onlineDevices = Device::whereIn('branch_id', $branchIds)->where('status', 'online')->count();
        $offlineDevices = Device::whereIn('branch_id', $branchIds)->where('status', 'offline')->count();

        $totalVaults = Vault::whereIn('branch_id', $branchIds)->count();
        $openVaults = Vault::whereIn('branch_id', $branchIds)->where('status', 'open')->count();

        $activeAlarms = DB::table('alarm_logs')
            ->whereIn('branch_id', $branchIds)
            ->whereNull('resolved_at')
            ->count();

        $criticalAlarms = DB::table('alarm_logs')
            ->whereIn('branch_id', $branchIds)
            ->whereNull('resolved_at')
            ->where('severity', 'critical')
            ->count();

        // Cabang bermasalah (ada device offline atau alarm aktif)
        $branchesWithIssues = Branch::whereIn('id', $branchIds)
            ->where(function ($q) {
                $q->whereHas('devices', fn ($d) => $d->where('status', 'offline'))
                    ->orWhereHas('alarmLogs', fn ($a) => $a->whereNull('resolved_at'));
            })
            ->count();

        return [
            'total_branches' => $totalBranches,
            'branches_healthy' => $totalBranches - $branchesWithIssues,
            'branches_with_issues' => $branchesWithIssues,
            'total_devices' => $totalDevices,
            'devices_online' => $onlineDevices,
            'devices_offline' => $offlineDevices,
            'device_health_percent' => $totalDevices > 0 ? round(($onlineDevices / $totalDevices) * 100, 1) : 100,
            'total_vaults' => $totalVaults,
            'vaults_open' => $openVaults,
            'active_alarms' => $activeAlarms,
            'critical_alarms' => $criticalAlarms,
        ];
    }

    // Bangun data geo untuk satu cabang
    private function buildBranchGeoData(Branch $branch): array
    {
        $deviceCount = Device::where('branch_id', $branch->id)->count();
        $onlineCount = Device::where('branch_id', $branch->id)->where('status', 'online')->count();
        $offlineCount = $deviceCount - $onlineCount;

        $vaultCount = Vault::where('branch_id', $branch->id)->count();
        $openVaults = Vault::where('branch_id', $branch->id)->where('status', 'open')->count();

        $activeAlarms = DB::table('alarm_logs')
            ->where('branch_id', $branch->id)
            ->whereNull('resolved_at')
            ->count();

        $criticalAlarms = DB::table('alarm_logs')
            ->where('branch_id', $branch->id)
            ->whereNull('resolved_at')
            ->where('severity', 'critical')
            ->count();

        // Tentukan status kesehatan cabang
        $healthStatus = 'healthy';
        if ($criticalAlarms > 0 || $offlineCount > ($deviceCount * 0.5)) {
            $healthStatus = 'critical';
        } elseif ($activeAlarms > 0 || $offlineCount > 0) {
            $healthStatus = 'warning';
        }

        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'code' => $branch->code,
            'address' => $branch->address,
            'city' => $branch->city,
            'province' => $branch->province,
            'latitude' => (float) $branch->latitude,
            'longitude' => (float) $branch->longitude,
            'timezone' => $branch->timezone,
            'health_status' => $healthStatus,
            'summary' => [
                'devices_total' => $deviceCount,
                'devices_online' => $onlineCount,
                'devices_offline' => $offlineCount,
                'vaults_total' => $vaultCount,
                'vaults_open' => $openVaults,
                'active_alarms' => $activeAlarms,
                'critical_alarms' => $criticalAlarms,
            ],
        ];
    }
}