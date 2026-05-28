<?php

use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\DeviceProvisioningController;
use App\Http\Controllers\Api\V1\DeviceSyncController;
use App\Http\Controllers\Api\V1\MqttAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─── Device provisioning (P1-12) ─────────────────────────────────────────
    // Public endpoint: device claims itself with a one-time code.
    Route::post('/devices/provision/claim', [DeviceProvisioningController::class, 'claim'])
        ->middleware('throttle:10,1');

    // ─── EMQX broker auth/ACL hooks (P1-12) ──────────────────────────────────
    // Called by EMQX, not by users. IP-allowlist via ip.whitelist middleware
    // when IP_WHITELIST_ENABLED is on.
    Route::post('/mqtt/auth', [MqttAuthController::class, 'authenticate'])
        ->middleware('throttle:600,1');
    Route::post('/mqtt/acl', [MqttAuthController::class, 'authorize'])
        ->middleware('throttle:600,1');

    // ─── Device → cloud (authenticated via X-Device-Token header) ────────────
    Route::middleware('device.auth')->group(function () {
        Route::post('/devices/heartbeat', [DeviceController::class, 'heartbeat']);

        // P1-11: Offline event buffer ingestion + stats.
        Route::post('/devices/sync/events', [DeviceSyncController::class, 'ingest']);
        Route::get('/devices/sync/stats', [DeviceSyncController::class, 'stats']);
    });

    // Public registration kept for backward compatibility with old controllers.
    Route::post('/devices/register', [DeviceController::class, 'register']);

    // ─── Admin / operator routes (Sanctum + permissions) ────────────────────
    Route::middleware(['auth:sanctum', 'active.user'])->group(function () {
        Route::apiResource('devices', DeviceController::class);

        Route::get('/devices/{device}/status', [DeviceController::class, 'status']);
        Route::post('/devices/{device}/restart', [DeviceController::class, 'restart']);
        Route::post('/devices/{device}/update-firmware', [DeviceController::class, 'updateFirmware']);
        Route::get('/devices/{device}/heartbeats', [DeviceController::class, 'heartbeats']);
        Route::post('/devices/{device}/regenerate-token', [DeviceController::class, 'regenerateToken']);
        Route::patch('/devices/{device}/toggle-active', [DeviceController::class, 'toggleActive']);

        // P1-12: Admin generates claim codes + rotates MQTT credentials.
        Route::post('/devices/provision/codes', [DeviceProvisioningController::class, 'generateCode'])
            ->middleware('permission:devices.register');
        Route::post('/devices/{device}/rotate-mqtt', [DeviceProvisioningController::class, 'rotateMqtt'])
            ->middleware('permission:devices.manage');

        // P2-25: Device provisioning management.
        Route::get('/devices/provision/codes', [DeviceProvisioningController::class, 'listCodes'])
            ->middleware('permission:devices.manage');
        Route::delete('/devices/provision/codes/{codeId}', [DeviceProvisioningController::class, 'revokeCode'])
            ->middleware('permission:devices.manage');
        Route::get('/devices/provision/provisioned', [DeviceProvisioningController::class, 'listProvisioned'])
            ->middleware('permission:devices.manage');
        Route::get('/devices/provision/statistics', [DeviceProvisioningController::class, 'statistics'])
            ->middleware('permission:devices.manage');
    });
});
