<?php

namespace App\Services;

use App\Enums\DeviceType;
use App\Models\Device;
use App\Models\UpsSpec;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

// Service monitoring UPS: status baterai, deteksi power event, alert kritis
class UpsMonitoringService
{
    // Threshold alert (menit & persen)
    public const CRITICAL_RUNTIME_MINUTES = 30;
    public const WARNING_RUNTIME_MINUTES = 60;
    public const MIN_BATTERY_PERCENT = 20;

    // List semua UPS + filter
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Device::query()
            ->where('type', DeviceType::Ups->value)
            ->with(['upsSpec', 'branch', 'heartbeats' => fn ($q) => $q->latest()->limit(1)]);

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['on_battery'])) {
            $query->whereHas('upsSpec', fn ($q) => $q->where('on_battery', true));
        }

        if (!empty($filters['critical'])) {
            $query->whereHas('upsSpec', fn ($q) => $q->where('on_battery', true)
                ->where('battery_percent', '<=', self::MIN_BATTERY_PERCENT));
        }

        if (!empty($filters['battery_due'])) {
            $query->whereHas('upsSpec', fn ($q) => $q->whereNotNull('battery_replace_due_at')
                ->where('battery_replace_due_at', '<=', now()));
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    // Detail satu UPS
    public function find(string $deviceId): Device
    {
        return Device::where('type', DeviceType::Ups->value)
            ->with(['upsSpec', 'branch', 'heartbeats' => fn ($q) => $q->latest()->limit(10)])
            ->findOrFail($deviceId);
    }

    // Simpan/update spec UPS
    public function upsertSpec(string $deviceId, array $data): UpsSpec
    {
        $device = Device::where('type', DeviceType::Ups->value)->findOrFail($deviceId);

        return UpsSpec::updateOrCreate(
            ['device_id' => $device->id],
            $data,
        );
    }

    // Proses status update dari heartbeat UPS
    public function processStatusUpdate(string $deviceId, array $statusData): void
    {
        $spec = UpsSpec::where('device_id', $deviceId)->first();
        if (!$spec) {
            Log::warning('UPS status update for device without spec', [
                'device_id' => $deviceId,
            ]);
            return;
        }

        $previousOnBattery = $spec->on_battery;
        $updates = [];

        if (isset($statusData['on_battery'])) {
            $updates['on_battery'] = (bool) $statusData['on_battery'];
        }
        if (isset($statusData['battery_percent'])) {
            $updates['battery_percent'] = (int) $statusData['battery_percent'];
        }
        if (isset($statusData['runtime_remaining_minutes'])) {
            $updates['runtime_remaining_minutes'] = (int) $statusData['runtime_remaining_minutes'];
        }

        $updates['last_status_at'] = now();

        // Simpan data runtime ke metadata
        $runtimeKeys = ['input_voltage', 'output_voltage', 'load_percent', 'temperature_c', 'line_frequency_hz'];
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

        $spec->update($updates);

        // Deteksi event power
        $nowOnBattery = $updates['on_battery'] ?? $spec->on_battery;

        if ($previousOnBattery === false && $nowOnBattery === true) {
            $this->handlePowerLossEvent($spec->fresh());
        } elseif ($previousOnBattery === true && $nowOnBattery === false) {
            $this->handlePowerRestoredEvent($spec->fresh());
        }

        // Cek threshold kritis
        if ($nowOnBattery) {
            $this->checkCriticalThresholds($spec->fresh());
        }
    }

    // UPS yang sedang pakai baterai
    public function getOnBattery(): Collection
    {
        return Device::query()
            ->where('type', DeviceType::Ups->value)
            ->whereHas('upsSpec', fn ($q) => $q->where('on_battery', true))
            ->with(['upsSpec', 'branch'])
            ->get();
    }

    // UPS dalam kondisi kritis (baterai + persen rendah)
    public function getCritical(): Collection
    {
        return Device::query()
            ->where('type', DeviceType::Ups->value)
            ->whereHas('upsSpec', fn ($q) => $q->where('on_battery', true)
                ->where('battery_percent', '<=', self::MIN_BATTERY_PERCENT))
            ->with(['upsSpec', 'branch'])
            ->get();
    }

    // UPS yang baterainya sudah waktunya diganti
    public function getBatteryDue(): Collection
    {
        return Device::query()
            ->where('type', DeviceType::Ups->value)
            ->whereHas('upsSpec', fn ($q) => $q->whereNotNull('battery_replace_due_at')
                ->where('battery_replace_due_at', '<=', now()))
            ->with(['upsSpec', 'branch'])
            ->get();
    }

    // Ringkasan statistik semua UPS
    public function getSummary(?string $branchId = null): array
    {
        $query = Device::query()->where('type', DeviceType::Ups->value);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $deviceIds = (clone $query)->pluck('id');

        $total = $deviceIds->count();
        $online = (clone $query)->where('status', 'online')->count();
        $onBattery = UpsSpec::whereIn('device_id', $deviceIds)->where('on_battery', true)->count();
        $critical = UpsSpec::whereIn('device_id', $deviceIds)
            ->where('on_battery', true)
            ->where('battery_percent', '<=', self::MIN_BATTERY_PERCENT)
            ->count();
        $batteryDue = UpsSpec::whereIn('device_id', $deviceIds)
            ->whereNotNull('battery_replace_due_at')
            ->where('battery_replace_due_at', '<=', now())
            ->count();

        $avgBattery = UpsSpec::whereIn('device_id', $deviceIds)
            ->whereNotNull('battery_percent')
            ->avg('battery_percent');

        return [
            'total' => $total,
            'online' => $online,
            'on_battery' => $onBattery,
            'critical' => $critical,
            'battery_due_replacement' => $batteryDue,
            'avg_battery_percent' => round($avgBattery ?? 0, 1),
        ];
    }

    // Handle listrik mati (pindah ke baterai)
    private function handlePowerLossEvent(UpsSpec $spec): void
    {
        Log::warning('UPS switched to battery power', [
            'device_id' => $spec->device_id,
            'battery_percent' => $spec->battery_percent,
            'runtime_remaining_minutes' => $spec->runtime_remaining_minutes,
        ]);

        event('ups.power.lost', [
            'device_id' => $spec->device_id,
            'ups_spec' => $spec,
        ]);
    }

    // Handle listrik kembali normal
    private function handlePowerRestoredEvent(UpsSpec $spec): void
    {
        Log::info('UPS power restored (back to mains)', [
            'device_id' => $spec->device_id,
            'battery_percent' => $spec->battery_percent,
        ]);

        event('ups.power.restored', [
            'device_id' => $spec->device_id,
            'ups_spec' => $spec,
        ]);
    }

    // Cek apakah UPS sudah melewati batas kritis
    private function checkCriticalThresholds(UpsSpec $spec): void
    {
        $runtime = $spec->runtime_remaining_minutes;
        $percent = $spec->battery_percent;

        if ($percent !== null && $percent <= self::MIN_BATTERY_PERCENT) {
            Log::critical('UPS battery critically low', [
                'device_id' => $spec->device_id,
                'battery_percent' => $percent,
                'runtime_remaining_minutes' => $runtime,
            ]);

            event('ups.battery.critical', [
                'device_id' => $spec->device_id,
                'ups_spec' => $spec,
                'severity' => 'critical',
            ]);
        } elseif ($runtime !== null && $runtime <= self::CRITICAL_RUNTIME_MINUTES) {
            Log::critical('UPS runtime critically low', [
                'device_id' => $spec->device_id,
                'runtime_remaining_minutes' => $runtime,
            ]);

            event('ups.runtime.critical', [
                'device_id' => $spec->device_id,
                'ups_spec' => $spec,
                'severity' => 'critical',
            ]);
        } elseif ($runtime !== null && $runtime <= self::WARNING_RUNTIME_MINUTES) {
            Log::warning('UPS runtime low', [
                'device_id' => $spec->device_id,
                'runtime_remaining_minutes' => $runtime,
            ]);

            event('ups.runtime.warning', [
                'device_id' => $spec->device_id,
                'ups_spec' => $spec,
                'severity' => 'warning',
            ]);
        }
    }
}