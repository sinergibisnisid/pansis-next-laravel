<?php

use App\Http\Controllers\Api\V1\DeviceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Device heartbeat - authenticated via device token
    Route::middleware('device-token-auth')->group(function () {
        Route::post('/devices/heartbeat', [DeviceController::class, 'heartbeat']);
    });

    // Device registration (public for initial setup, secured by registration token)
    Route::post('/devices/register', [DeviceController::class, 'register']);

    // Protected device routes
    Route::middleware(['auth:sanctum', 'ensure.active'])->group(function () {
        Route::apiResource('devices', DeviceController::class);

        Route::get('/devices/{device}/status', [DeviceController::class, 'status']);
        Route::post('/devices/{device}/restart', [DeviceController::class, 'restart']);
        Route::post('/devices/{device}/update-firmware', [DeviceController::class, 'updateFirmware']);
        Route::get('/devices/{device}/heartbeats', [DeviceController::class, 'heartbeats']);
        Route::post('/devices/{device}/regenerate-token', [DeviceController::class, 'regenerateToken']);
        Route::patch('/devices/{device}/toggle-active', [DeviceController::class, 'toggleActive']);
    });
});
