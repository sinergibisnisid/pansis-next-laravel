<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;

/**
 * Health check endpoints for k8s liveness/readiness probes and load balancers.
 *
 *   GET /health           — basic liveness (returns 200 if app boots)
 *   GET /health/ready     — readiness aggregate (200 ok, 503 if down)
 *   GET /health/db        — Postgres specific
 *   GET /health/redis     — Redis specific
 *   GET /health/queue     — Queue specific
 *   GET /health/mqtt      — MQTT broker specific
 *   GET /health/storage   — Filesystem specific
 *
 * Each endpoint returns a uniform { status, latency_ms, detail, ... } payload.
 * HTTP status code reflects the health: 200 ok/degraded, 503 down.
 */
class HealthController extends Controller
{
    public function __construct(
        private readonly HealthCheckService $healthCheck,
    ) {}

    /**
     * Basic liveness — app process is up.
     */
    public function liveness(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'pansin-access',
            'version' => config('app.version', '1.0.0'),
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }

    /**
     * Readiness — all dependencies must be reachable.
     */
    public function readiness(): JsonResponse
    {
        $summary = $this->healthCheck->summary();
        $code = $summary['status'] === 'down' ? 503 : 200;
        return response()->json($summary, $code);
    }

    public function database(): JsonResponse
    {
        $result = $this->healthCheck->checkDatabase();
        return $this->respond('database', $result);
    }

    public function redis(): JsonResponse
    {
        $result = $this->healthCheck->checkRedis();
        return $this->respond('redis', $result);
    }

    public function queue(): JsonResponse
    {
        $result = $this->healthCheck->checkQueue();
        return $this->respond('queue', $result);
    }

    public function mqtt(): JsonResponse
    {
        $result = $this->healthCheck->checkMqtt();
        return $this->respond('mqtt', $result);
    }

    public function storage(): JsonResponse
    {
        $result = $this->healthCheck->checkStorage();
        return $this->respond('storage', $result);
    }

    private function respond(string $service, array $result): JsonResponse
    {
        $code = $result['status'] === 'down' ? 503 : 200;
        return response()->json([
            'service' => $service,
            'status' => $result['status'],
            'latency_ms' => $result['latency_ms'] ?? null,
            'detail' => $result['detail'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ], $code);
    }
}
