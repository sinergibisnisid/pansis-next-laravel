<?php

use App\Http\Controllers\Api\V1\MonitoringController;
use App\Http\Controllers\Api\V1\AlarmController;
use App\Http\Controllers\Api\V1\LivestreamController;
use App\Http\Controllers\Api\V1\VaultController;
use App\Http\Controllers\Api\V1\WorkingTimeController;
use App\Http\Controllers\Api\V1\MaintenanceController;
use App\Http\Controllers\Api\V1\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'ensure.active'])->group(function () {

    // Dashboard & Monitoring
    Route::prefix('monitoring')->group(function () {
        Route::get('/dashboard', [MonitoringController::class, 'dashboard']);
        Route::get('/vaults', [MonitoringController::class, 'vaults']);
        Route::get('/devices', [MonitoringController::class, 'devices']);
        Route::get('/server-health', [MonitoringController::class, 'serverHealth']);
        Route::get('/metrics', [MonitoringController::class, 'metrics']);
        Route::get('/prometheus', [MonitoringController::class, 'prometheus']);

        // Alarms
        Route::get('/alarms', [AlarmController::class, 'index']);
        Route::get('/alarms/statistics', [AlarmController::class, 'statistics']);
        Route::get('/alarms/{alarm}', [AlarmController::class, 'show']);
        Route::post('/alarms/{alarm}/acknowledge', [AlarmController::class, 'acknowledge']);
        Route::post('/alarms/{alarm}/resolve', [AlarmController::class, 'resolve']);

        // Livestreams
        Route::get('/livestreams', [LivestreamController::class, 'index']);
        Route::post('/livestreams', [LivestreamController::class, 'start']);
        Route::post('/livestreams/{livestream}/stop', [LivestreamController::class, 'stop']);
        Route::get('/livestreams/{livestream}/url', [LivestreamController::class, 'url']);
        Route::get('/livestreams/{livestream}/health', [LivestreamController::class, 'health']);
    });

    // Vault Operations
    Route::prefix('vaults')->group(function () {
        Route::post('/{vault}/open', [VaultController::class, 'open']);
        Route::post('/{vault}/close', [VaultController::class, 'close']);
        Route::get('/{vault}/sessions', [VaultController::class, 'sessions']);
    });

    // Working Times
    Route::apiResource('working-times', WorkingTimeController::class);

    // Maintenance
    Route::prefix('maintenance')->group(function () {
        Route::get('/', [MaintenanceController::class, 'index']);
        Route::post('/', [MaintenanceController::class, 'store']);
        Route::get('/upcoming', [MaintenanceController::class, 'upcoming']);
        Route::get('/overdue', [MaintenanceController::class, 'overdue']);
        Route::get('/{maintenance}', [MaintenanceController::class, 'show']);
        Route::put('/{maintenance}', [MaintenanceController::class, 'update']);
        Route::delete('/{maintenance}', [MaintenanceController::class, 'destroy']);
        Route::post('/{maintenance}/complete', [MaintenanceController::class, 'complete']);
        Route::get('/{maintenance}/logs', [MaintenanceController::class, 'logs']);
        Route::post('/{maintenance}/logs', [MaintenanceController::class, 'storeLog']);
    });

    // Audit Logs
    Route::prefix('audit-logs')->group(function () {
        Route::get('/', [AuditLogController::class, 'index']);
        Route::get('/user-activity', [AuditLogController::class, 'userActivity']);
        Route::get('/entity-history', [AuditLogController::class, 'entityHistory']);
        Route::get('/{auditLog}', [AuditLogController::class, 'show']);
    });
});
