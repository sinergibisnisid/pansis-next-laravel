<?php

namespace App\Services;

use App\Enums\DeviceType;
use App\Models\Device;
use App\Models\RouterSpec;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

// Service monitoring router PoE: VPN, failover, WAN, PoE port
class RouterMonitoringService
{
    // List semua router + filter
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Device::query()
            ->where('type', DeviceType::Router->value)
            ->with(['routerSpec', 'branch', 'heartbeats' => fn ($q) => $q->latest()->limit(1)]);

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['vpn_enabled'])) {
            $query->whereHas('routerSpec', fn ($q) => $q->where('vpn_enabled', true));
        }

        if (!empty($filters['failover_enabled'])) {
            $query->whereHas('routerSpec', fn ($q) => $q->where('failover_enabled', true));
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    // Detail satu router
    public function find(string $deviceId): Device
    {
        return Device::where('type', DeviceType::Router->value)
            ->with(['routerSpec', 'branch', 'heartbeats' => fn ($q) => $q->latest()->limit(10)])
            ->findOrFail($deviceId);
    }

    // Simpan/update spec router
    public function upsertSpec(string $deviceId, array $data): RouterSpec
    {
        $device = Device::where('type', DeviceType::Router->value)->findOrFail($deviceId);

        return RouterSpec::updateOrCreate(
            ['device_id' => $device->id],
            $data,
        );
    }

    // Proses status update dari heartbeat router
    public function processStatusUpdate(string $deviceId, array $statusData): void
    {
        $spec = RouterSpec::where('device_id', $deviceId)->first();
        if (!$spec) {
            Log::warning('Router status update for device without spec', [
                'device_id' => $deviceId,
            ]);
            return;
        }

        $updates = [];

        if (isset($statusData['wan_ip_primary'])) {
            $updates['wan_ip_primary'] = $statusData['wan_ip_primary'];
        }
        if (isset($statusData['wan_ip_secondary'])) {
            $updates['wan_ip_secondary'] = $statusData['wan_ip_secondary'];
        }
        if (isset($statusData['vpn_enabled'])) {
            $updates['vpn_enabled'] = $statusData['vpn_enabled'];
        }

        // Simpan data runtime ke metadata
        $runtimeKeys = ['wan_status', 'vpn_status', 'failover_active', 'poe_power_draw_w', 'uptime_seconds', 'cpu_percent', 'memory_percent'];
        $runtimeData = array_intersect_key($statusData, array_flip($runtimeKeys));

        if (!empty($runtimeData)) {
            $metadata = $spec->metadata ?? [];
            $metadata['last_runtime'] = array_merge(
                $metadata['last_runtime'] ?? [],
                $runtimeData,
                ['updated_at' => now()->toIso8601String()],
            );
            $updates['metadata'] = $metadata;
        }

        if (!empty($updates)) {
            $spec->update($updates);
        }

        // Deteksi failover
        if (isset($statusData['failover_active']) && $statusData['failover_active'] === true) {
            $this->handleFailoverEvent($spec, $statusData);
        }
    }

    // Router yang sedang failover (WAN sekunder aktif)
    public function getFailoverActive(): Collection
    {
        return Device::query()
            ->where('type', DeviceType::Router->value)
            ->whereHas('routerSpec', function ($q) {
                $q->where('failover_enabled', true)
                    ->whereJsonContains('metadata->last_runtime->failover_active', true);
            })
            ->with(['routerSpec', 'branch'])
            ->get();
    }

    // Router dengan masalah koneksi VPN
    public function getVpnIssues(): Collection
    {
        return Device::query()
            ->where('type', DeviceType::Router->value)
            ->whereHas('routerSpec', function ($q) {
                $q->where('vpn_enabled', true)
                    ->where(function ($inner) {
                        $inner->whereJsonContains('metadata->last_runtime->vpn_status', 'disconnected')
                            ->orWhereJsonContains('metadata->last_runtime->vpn_status', 'error');
                    });
            })
            ->with(['routerSpec', 'branch'])
            ->get();
    }

    // Ringkasan statistik semua router
    public function getSummary(?string $branchId = null): array
    {
        $query = Device::query()->where('type', DeviceType::Router->value);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $total = $query->count();
        $online = (clone $query)->where('status', 'online')->count();
        $offline = (clone $query)->where('status', 'offline')->count();

        $vpnEnabled = RouterSpec::query()
            ->whereIn('device_id', (clone $query)->select('id'))
            ->where('vpn_enabled', true)
            ->count();

        $failoverActive = $this->getFailoverActive()->count();

        return [
            'total' => $total,
            'online' => $online,
            'offline' => $offline,
            'vpn_enabled' => $vpnEnabled,
            'failover_active' => $failoverActive,
        ];
    }

    // Handle event failover (WAN primer down, pindah ke sekunder)
    private function handleFailoverEvent(RouterSpec $spec, array $statusData): void
    {
        Log::warning('Router failover activated', [
            'device_id' => $spec->device_id,
            'isp_primary' => $spec->isp_primary,
            'isp_secondary' => $spec->isp_secondary,
            'wan_status' => $statusData['wan_status'] ?? 'unknown',
        ]);

        // Dispatch event for notification system to pick up
        event('router.failover.activated', [
            'device_id' => $spec->device_id,
            'router_spec' => $spec,
            'status_data' => $statusData,
        ]);
    }
}