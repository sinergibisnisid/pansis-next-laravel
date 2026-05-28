<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpMqtt\Client\Facades\MQTT;
use Throwable;

/**
 * Per-service health checks for k8s liveness/readiness probes and load balancers.
 *
 * Each public method returns an array shaped like:
 *   ['status' => 'ok'|'degraded'|'down', 'latency_ms' => int, 'detail' => mixed]
 *
 * Status semantics:
 *   - ok        → fully healthy
 *   - degraded  → reachable but slow, near limits, or partial failure
 *   - down      → unreachable or unrecoverable error
 */
class HealthCheckService
{
    /** Latency above which a check is considered degraded (milliseconds). */
    public const DEGRADED_THRESHOLD_MS = 500;

    /** Hard timeout for any single check (milliseconds). */
    public const TIMEOUT_MS = 3000;

    /**
     * Aggregated health summary for /health/ready.
     * Overall status is the worst of the individual services.
     */
    public function summary(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'mqtt' => $this->checkMqtt(),
            'storage' => $this->checkStorage(),
        ];

        $overall = 'ok';
        foreach ($checks as $check) {
            if ($check['status'] === 'down') {
                $overall = 'down';
                break;
            }
            if ($check['status'] === 'degraded' && $overall === 'ok') {
                $overall = 'degraded';
            }
        }

        return [
            'status' => $overall,
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'services' => $checks,
        ];
    }

    /**
     * Postgres connectivity + connection pool stats.
     */
    public function checkDatabase(): array
    {
        return $this->time(function () {
            try {
                $start = microtime(true);
                DB::connection()->select('SELECT 1');
                $latency = (int) ((microtime(true) - $start) * 1000);

                // Postgres-specific: active connections vs max.
                $stats = null;
                if (DB::connection()->getDriverName() === 'pgsql') {
                    $row = DB::selectOne(
                        'SELECT count(*) AS active, current_setting(\'max_connections\')::int AS max FROM pg_stat_activity'
                    );
                    if ($row) {
                        $stats = [
                            'active_connections' => (int) $row->active,
                            'max_connections' => (int) $row->max,
                            'utilization_percent' => round(($row->active / max($row->max, 1)) * 100, 1),
                        ];
                    }
                }

                return [
                    'status' => $latency > self::DEGRADED_THRESHOLD_MS ? 'degraded' : 'ok',
                    'detail' => $stats,
                ];
            } catch (Throwable $e) {
                return [
                    'status' => 'down',
                    'detail' => ['error' => $e->getMessage()],
                ];
            }
        });
    }

    /**
     * Redis ping + memory + connected clients.
     */
    public function checkRedis(): array
    {
        return $this->time(function () {
            try {
                $pong = Redis::connection()->ping();
                $info = Redis::connection()->info();

                $usedMemoryMb = isset($info['used_memory'])
                    ? round(((int) $info['used_memory']) / 1024 / 1024, 2)
                    : null;

                return [
                    'status' => $pong ? 'ok' : 'down',
                    'detail' => [
                        'ping' => $pong ? 'PONG' : null,
                        'used_memory_mb' => $usedMemoryMb,
                        'connected_clients' => isset($info['connected_clients'])
                            ? (int) $info['connected_clients'] : null,
                        'uptime_seconds' => isset($info['uptime_in_seconds'])
                            ? (int) $info['uptime_in_seconds'] : null,
                    ],
                ];
            } catch (Throwable $e) {
                return [
                    'status' => 'down',
                    'detail' => ['error' => $e->getMessage()],
                ];
            }
        });
    }

    /**
     * Queue health: pending + failed counts. Degraded if too many pending or
     * any failed jobs exist.
     */
    public function checkQueue(): array
    {
        return $this->time(function () {
            try {
                $failedCount = DB::table('failed_jobs')->count();

                // Sum of all pending jobs across known queues.
                $pendingByQueue = [];
                $totalPending = 0;
                $queues = [
                    'default', 'monitoring', 'notifications', 'snapshots',
                    'reports', 'alarms', 'mqtt', 'broadcasting', 'heartbeat',
                ];
                foreach ($queues as $queue) {
                    try {
                        $size = (int) Redis::connection()->llen("queues:{$queue}");
                    } catch (Throwable) {
                        $size = 0;
                    }
                    if ($size > 0) {
                        $pendingByQueue[$queue] = $size;
                    }
                    $totalPending += $size;
                }

                $status = match (true) {
                    $failedCount > 100 => 'down',
                    $failedCount > 0 || $totalPending > 1000 => 'degraded',
                    default => 'ok',
                };

                return [
                    'status' => $status,
                    'detail' => [
                        'pending_total' => $totalPending,
                        'pending_by_queue' => $pendingByQueue,
                        'failed' => $failedCount,
                    ],
                ];
            } catch (Throwable $e) {
                return [
                    'status' => 'down',
                    'detail' => ['error' => $e->getMessage()],
                ];
            }
        });
    }

    /**
     * MQTT broker reachability via php-mqtt connection check.
     */
    public function checkMqtt(): array
    {
        return $this->time(function () {
            try {
                $client = MQTT::connection();
                $connected = $client->isConnected();

                return [
                    'status' => $connected ? 'ok' : 'down',
                    'detail' => [
                        'host' => config('mqtt.host'),
                        'port' => (int) config('mqtt.port'),
                        'connected' => $connected,
                    ],
                ];
            } catch (Throwable $e) {
                return [
                    'status' => 'down',
                    'detail' => [
                        'host' => config('mqtt.host'),
                        'port' => (int) config('mqtt.port'),
                        'error' => $e->getMessage(),
                    ],
                ];
            }
        });
    }

    /**
     * Storage disk write/read/delete probe.
     */
    public function checkStorage(): array
    {
        return $this->time(function () {
            try {
                $disk = Storage::disk(config('filesystems.default', 'local'));
                $probeFile = '_health_check/' . Str::random(16) . '.txt';
                $payload = 'probe-' . now()->timestamp;

                $disk->put($probeFile, $payload);
                $read = $disk->get($probeFile);
                $disk->delete($probeFile);

                return [
                    'status' => $read === $payload ? 'ok' : 'degraded',
                    'detail' => [
                        'driver' => config('filesystems.default', 'local'),
                        'writable' => true,
                        'readable' => $read === $payload,
                    ],
                ];
            } catch (Throwable $e) {
                return [
                    'status' => 'down',
                    'detail' => ['error' => $e->getMessage()],
                ];
            }
        });
    }

    /**
     * Wraps a check with timing + safety. Always returns the standard shape.
     */
    private function time(callable $fn): array
    {
        $start = microtime(true);
        try {
            $result = $fn();
        } catch (Throwable $e) {
            Log::error('Health check threw', ['error' => $e->getMessage()]);
            $result = ['status' => 'down', 'detail' => ['error' => $e->getMessage()]];
        }
        $result['latency_ms'] = (int) ((microtime(true) - $start) * 1000);
        return $result;
    }
}
