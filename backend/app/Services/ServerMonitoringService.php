<?php

namespace App\Services;

use App\DTOs\Monitoring\ServerMetricsDTO;
use App\Models\ServerMonitoring;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class ServerMonitoringService
{
    public function collectMetrics(): ServerMetricsDTO
    {
        $cpuUsage = $this->getCpuUsage();
        $memoryInfo = $this->getMemoryInfo();
        $diskInfo = $this->getDiskInfo();
        $queueInfo = $this->getQueueInfo();
        $mqttConnected = $this->checkMqttHealth();
        $websocketConnections = $this->getWebsocketConnections();
        $activeStreams = $this->getActiveStreamCount();

        return new ServerMetricsDTO(
            hostname: gethostname() ?: 'unknown',
            cpuUsage: $cpuUsage,
            memoryUsage: $memoryInfo['usage_percent'],
            memoryTotalMb: $memoryInfo['total_mb'],
            memoryUsedMb: $memoryInfo['used_mb'],
            diskUsage: $diskInfo['usage_percent'],
            diskTotalGb: $diskInfo['total_gb'],
            diskUsedGb: $diskInfo['used_gb'],
            queueSize: $queueInfo['size'],
            queueFailed: $queueInfo['failed'],
            websocketConnections: $websocketConnections,
            mqttConnected: $mqttConnected,
            activeStreams: $activeStreams,
            uptimeSeconds: $this->getUptimeSeconds(),
            loadAverage: $this->getLoadAverage(),
        );
    }

    public function saveMetrics(ServerMetricsDTO $dto): ServerMonitoring
    {
        return ServerMonitoring::create($dto->toArray());
    }

    public function getLatestMetrics(): ?ServerMonitoring
    {
        return ServerMonitoring::latest()->first();
    }

    public function getHealthStatus(): array
    {
        $metrics = $this->getLatestMetrics();

        if (!$metrics) {
            return [
                'status' => 'unknown',
                'message' => 'No metrics available',
                'checks' => [],
            ];
        }

        $checks = [];
        $overallStatus = 'healthy';

        // CPU check
        $cpuStatus = $this->evaluateThreshold($metrics->cpu_usage, 70, 90);
        $checks['cpu'] = [
            'status' => $cpuStatus,
            'value' => $metrics->cpu_usage,
            'unit' => '%',
        ];

        // Memory check
        $memoryStatus = $this->evaluateThreshold($metrics->memory_usage, 75, 90);
        $checks['memory'] = [
            'status' => $memoryStatus,
            'value' => $metrics->memory_usage,
            'unit' => '%',
        ];

        // Disk check
        $diskStatus = $this->evaluateThreshold($metrics->disk_usage ?? 0, 80, 95);
        $checks['disk'] = [
            'status' => $diskStatus,
            'value' => $metrics->disk_usage,
            'unit' => '%',
        ];

        // Queue check
        $queueStatus = $this->evaluateThreshold($metrics->queue_size ?? 0, 100, 500);
        $checks['queue'] = [
            'status' => $queueStatus,
            'value' => $metrics->queue_size,
            'failed' => $metrics->queue_failed,
        ];

        // MQTT check
        $checks['mqtt'] = [
            'status' => $metrics->mqtt_connected ? 'healthy' : 'critical',
            'connected' => $metrics->mqtt_connected,
        ];

        // Websocket check
        $checks['websocket'] = [
            'status' => 'healthy',
            'connections' => $metrics->websocket_connections,
        ];

        // Determine overall status
        foreach ($checks as $check) {
            if ($check['status'] === 'critical') {
                $overallStatus = 'critical';
                break;
            }
            if ($check['status'] === 'degraded') {
                $overallStatus = 'degraded';
            }
        }

        return [
            'status' => $overallStatus,
            'message' => $this->getStatusMessage($overallStatus),
            'checks' => $checks,
            'collected_at' => $metrics->created_at?->toDateTimeString(),
        ];
    }

    public function checkQueueHealth(): array
    {
        try {
            $queueSize = 0;
            $failedCount = 0;

            // Check Redis queue size
            try {
                $queueSize = Redis::llen('queues:default') ?? 0;
            } catch (\Throwable $e) {
                // Redis might not be available
            }

            // Check failed jobs count
            $failedCount = DB::table('failed_jobs')->count();

            return [
                'status' => $queueSize < 500 && $failedCount < 50 ? 'healthy' : 'degraded',
                'queue_size' => $queueSize,
                'failed_jobs' => $failedCount,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function checkMqttHealth(): bool
    {
        try {
            $mqtt = \PhpMqtt\Client\Facades\MQTT::connection();
            $mqtt->publish('health/ping', json_encode(['timestamp' => now()->timestamp]), 0);
            return true;
        } catch (\Throwable $e) {
            Log::warning("MQTT health check failed: {$e->getMessage()}");
            return false;
        }
    }

    public function checkWebsocketHealth(): array
    {
        try {
            $reverbHost = config('reverb.servers.reverb.host', '127.0.0.1');
            $reverbPort = config('reverb.servers.reverb.port', 8080);

            $response = Http::timeout(5)->get("http://{$reverbHost}:{$reverbPort}/apps");

            return [
                'status' => $response->successful() ? 'healthy' : 'degraded',
                'connections' => $this->getWebsocketConnections(),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getPrometheusMetrics(): string
    {
        $metrics = $this->getLatestMetrics();

        if (!$metrics) {
            return '';
        }

        $lines = [];

        $lines[] = '# HELP pansin_cpu_usage_percent CPU usage percentage';
        $lines[] = '# TYPE pansin_cpu_usage_percent gauge';
        $lines[] = "pansin_cpu_usage_percent {$metrics->cpu_usage}";

        $lines[] = '# HELP pansin_memory_usage_percent Memory usage percentage';
        $lines[] = '# TYPE pansin_memory_usage_percent gauge';
        $lines[] = "pansin_memory_usage_percent {$metrics->memory_usage}";

        $lines[] = '# HELP pansin_memory_used_mb Memory used in MB';
        $lines[] = '# TYPE pansin_memory_used_mb gauge';
        $lines[] = "pansin_memory_used_mb " . ($metrics->memory_used_mb ?? 0);

        $lines[] = '# HELP pansin_disk_usage_percent Disk usage percentage';
        $lines[] = '# TYPE pansin_disk_usage_percent gauge';
        $lines[] = "pansin_disk_usage_percent " . ($metrics->disk_usage ?? 0);

        $lines[] = '# HELP pansin_queue_size Current queue size';
        $lines[] = '# TYPE pansin_queue_size gauge';
        $lines[] = "pansin_queue_size " . ($metrics->queue_size ?? 0);

        $lines[] = '# HELP pansin_queue_failed Failed jobs count';
        $lines[] = '# TYPE pansin_queue_failed gauge';
        $lines[] = "pansin_queue_failed " . ($metrics->queue_failed ?? 0);

        $lines[] = '# HELP pansin_websocket_connections Active websocket connections';
        $lines[] = '# TYPE pansin_websocket_connections gauge';
        $lines[] = "pansin_websocket_connections " . ($metrics->websocket_connections ?? 0);

        $lines[] = '# HELP pansin_mqtt_connected MQTT broker connection status';
        $lines[] = '# TYPE pansin_mqtt_connected gauge';
        $lines[] = "pansin_mqtt_connected " . ($metrics->mqtt_connected ? 1 : 0);

        $lines[] = '# HELP pansin_active_streams Active livestream count';
        $lines[] = '# TYPE pansin_active_streams gauge';
        $lines[] = "pansin_active_streams " . ($metrics->active_streams ?? 0);

        $lines[] = '# HELP pansin_uptime_seconds Server uptime in seconds';
        $lines[] = '# TYPE pansin_uptime_seconds gauge';
        $lines[] = "pansin_uptime_seconds " . ($metrics->uptime_seconds ?? 0);

        return implode("\n", $lines) . "\n";
    }

    private function getCpuUsage(): float
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $load = sys_getloadavg();
            $cpuCores = (int) shell_exec('nproc') ?: 1;
            return round(($load[0] / $cpuCores) * 100, 2);
        }

        // Fallback for other OS
        return 0.0;
    }

    private function getMemoryInfo(): array
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $memInfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatch);
            preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availableMatch);

            $totalKb = (int) ($totalMatch[1] ?? 0);
            $availableKb = (int) ($availableMatch[1] ?? 0);
            $usedKb = $totalKb - $availableKb;

            $totalMb = (int) round($totalKb / 1024);
            $usedMb = (int) round($usedKb / 1024);
            $usagePercent = $totalKb > 0 ? round(($usedKb / $totalKb) * 100, 2) : 0;

            return [
                'total_mb' => $totalMb,
                'used_mb' => $usedMb,
                'usage_percent' => $usagePercent,
            ];
        }

        return [
            'total_mb' => 0,
            'used_mb' => 0,
            'usage_percent' => 0,
        ];
    }

    private function getDiskInfo(): array
    {
        $totalBytes = disk_total_space('/');
        $freeBytes = disk_free_space('/');
        $usedBytes = $totalBytes - $freeBytes;

        $totalGb = (int) round($totalBytes / (1024 * 1024 * 1024));
        $usedGb = (int) round($usedBytes / (1024 * 1024 * 1024));
        $usagePercent = $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100, 2) : 0;

        return [
            'total_gb' => $totalGb,
            'used_gb' => $usedGb,
            'usage_percent' => $usagePercent,
        ];
    }

    private function getQueueInfo(): array
    {
        try {
            $size = 0;
            try {
                $size = Redis::llen('queues:default') ?? 0;
            } catch (\Throwable $e) {
                // Redis not available
            }

            $failed = DB::table('failed_jobs')->count();

            return [
                'size' => $size,
                'failed' => $failed,
            ];
        } catch (\Throwable $e) {
            return [
                'size' => 0,
                'failed' => 0,
            ];
        }
    }

    private function getWebsocketConnections(): int
    {
        try {
            $reverbHost = config('reverb.servers.reverb.host', '127.0.0.1');
            $reverbPort = config('reverb.servers.reverb.port', 8080);

            $response = Http::timeout(3)->get("http://{$reverbHost}:{$reverbPort}/stats");

            if ($response->successful()) {
                return $response->json('connections', 0);
            }
        } catch (\Throwable $e) {
            // Reverb not available
        }

        return 0;
    }

    private function getActiveStreamCount(): int
    {
        try {
            return \App\Models\LivestreamSession::where('status', 'active')->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getUptimeSeconds(): int
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = file_get_contents('/proc/uptime');
            return (int) explode(' ', $uptime)[0];
        }

        return 0;
    }

    private function getLoadAverage(): ?array
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $load = sys_getloadavg();
            return $load ?: null;
        }

        return null;
    }

    private function evaluateThreshold(float $value, float $warningThreshold, float $criticalThreshold): string
    {
        if ($value >= $criticalThreshold) {
            return 'critical';
        }

        if ($value >= $warningThreshold) {
            return 'degraded';
        }

        return 'healthy';
    }

    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            'healthy' => 'All systems operational',
            'degraded' => 'Some systems are experiencing issues',
            'critical' => 'Critical issues detected',
            default => 'Status unknown',
        };
    }
}
