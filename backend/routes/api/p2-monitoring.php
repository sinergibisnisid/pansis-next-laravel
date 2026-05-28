<?php

use App\Http\Controllers\Api\V1\BranchMonitoringController;
use App\Http\Controllers\Api\V1\EmergencyController;
use App\Http\Controllers\Api\V1\HardwareCommandController;
use App\Http\Controllers\Api\V1\OccupancyController;
use App\Http\Controllers\Api\V1\RouterController;
use App\Http\Controllers\Api\V1\UpsController;
use App\Http\Controllers\Api\V1\NotificationConfigController;
use App\Http\Controllers\Api\V1\WorkingTimeController;
use Illuminate\Support\Facades\Route;

// Routes P2: router, ups, geo, emergency, occupancy, command queue, notif config
Route::prefix('v1')->middleware(['auth:sanctum', 'ensure.active'])->group(function () {

    // Router PoE
    Route::prefix('routers')->group(function () {
        Route::get('/', [RouterController::class, 'index']);
        Route::get('/summary', [RouterController::class, 'summary']);
        Route::get('/failover-active', [RouterController::class, 'failoverActive']);
        Route::get('/vpn-issues', [RouterController::class, 'vpnIssues']);
        Route::get('/{id}', [RouterController::class, 'show']);
        Route::put('/{deviceId}/spec', [RouterController::class, 'upsertSpec'])
            ->middleware('permission:devices.manage');
    });

    // UPS
    Route::prefix('ups')->group(function () {
        Route::get('/', [UpsController::class, 'index']);
        Route::get('/summary', [UpsController::class, 'summary']);
        Route::get('/on-battery', [UpsController::class, 'onBattery']);
        Route::get('/critical', [UpsController::class, 'critical']);
        Route::get('/battery-due', [UpsController::class, 'batteryDue']);
        Route::get('/{id}', [UpsController::class, 'show']);
        Route::put('/{deviceId}/spec', [UpsController::class, 'upsertSpec'])
            ->middleware('permission:devices.manage');
    });

    // Geo monitoring cabang (map view)
    Route::prefix('branches/monitoring')->group(function () {
        Route::get('/geo', [BranchMonitoringController::class, 'geoStatus']);
        Route::get('/health', [BranchMonitoringController::class, 'overallHealth']);
        Route::get('/with-alarms', [BranchMonitoringController::class, 'withAlarms']);
        Route::get('/{branchId}', [BranchMonitoringController::class, 'detail']);
    });

    // Cek jam kerja
    Route::post('/working-times/check', [WorkingTimeController::class, 'check']);

    // Emergency / tombol panik
    Route::prefix('emergencies')->group(function () {
        Route::get('/active', [EmergencyController::class, 'active']);
        Route::get('/history', [EmergencyController::class, 'history']);
        Route::get('/statistics', [EmergencyController::class, 'statistics']);
        Route::post('/trigger', [EmergencyController::class, 'trigger']);
        Route::post('/{id}/acknowledge', [EmergencyController::class, 'acknowledge']);
        Route::post('/{id}/resolve', [EmergencyController::class, 'resolve']);
    });

    // Tracking occupancy vault
    Route::prefix('occupancy')->group(function () {
        Route::get('/summary', [OccupancyController::class, 'summary']);
        Route::post('/entry', [OccupancyController::class, 'entry']);
        Route::post('/exit', [OccupancyController::class, 'exit']);
        Route::get('/vault/{vaultId}/status', [OccupancyController::class, 'status']);
        Route::get('/vault/{vaultId}/history', [OccupancyController::class, 'history']);
        Route::post('/vault/{vaultId}/exit-all', [OccupancyController::class, 'exitAll']);
    });

    // Antrian hardware command
    Route::prefix('hardware-commands')->group(function () {
        Route::get('/', [HardwareCommandController::class, 'index']);
        Route::get('/statistics', [HardwareCommandController::class, 'statistics']);
        Route::get('/pending', [HardwareCommandController::class, 'pending']);
        Route::get('/{id}', [HardwareCommandController::class, 'show']);
        Route::post('/dispatch', [HardwareCommandController::class, 'dispatch'])
            ->middleware('permission:vaults.control');
        Route::post('/{id}/cancel', [HardwareCommandController::class, 'cancel'])
            ->middleware('permission:vaults.control');
        Route::post('/{id}/retry', [HardwareCommandController::class, 'retry'])
            ->middleware('permission:vaults.control');
    });

    // Konfigurasi notifikasi (tambahan)
    Route::prefix('notification-configs')->group(function () {
        Route::get('/options', [NotificationConfigController::class, 'options']);
        Route::get('/by-branch', [NotificationConfigController::class, 'byBranch']);
        Route::post('/bulk', [NotificationConfigController::class, 'bulkStore'])
            ->middleware('permission:notifications.manage');
    });
});