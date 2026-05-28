<?php

use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\OpenApiController;
use Illuminate\Support\Facades\Route;

// ─── Health checks (P3-28) ───────────────────────────────────────────────
// No auth — used by k8s probes and load balancers.
Route::get('/health', [HealthController::class, 'liveness']);
Route::prefix('health')->group(function () {
    Route::get('/ready', [HealthController::class, 'readiness']);
    Route::get('/db', [HealthController::class, 'database']);
    Route::get('/redis', [HealthController::class, 'redis']);
    Route::get('/queue', [HealthController::class, 'queue']);
    Route::get('/mqtt', [HealthController::class, 'mqtt']);
    Route::get('/storage', [HealthController::class, 'storage']);
});

// ─── API documentation (P3-32) ───────────────────────────────────────────
// Public spec + Swagger UI. Disable in production by removing these routes
// or wrapping in an auth middleware if you want internal-only docs.
Route::get('/openapi.json', [OpenApiController::class, 'spec']);
Route::get('/docs', [OpenApiController::class, 'docs']);

// Include route modules
require __DIR__ . '/api/auth.php';
require __DIR__ . '/api/users.php';
require __DIR__ . '/api/monitoring.php';
require __DIR__ . '/api/devices.php';
require __DIR__ . '/api/reports.php';
require __DIR__ . '/api/mqtt.php';
require __DIR__ . '/api/settings.php';
require __DIR__ . '/api/p2-monitoring.php';
