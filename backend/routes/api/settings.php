<?php

use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\NotificationConfigController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'ensure.active'])->group(function () {

    // Settings management - admin only
    Route::middleware('role:Super Admin|Admin Pusat')->group(function () {
        Route::get('/settings', [SettingController::class, 'index']);
        Route::get('/settings/{key}', [SettingController::class, 'show']);
        Route::put('/settings/{key}', [SettingController::class, 'update']);
        Route::post('/settings', [SettingController::class, 'store']);
        Route::delete('/settings/{key}', [SettingController::class, 'destroy']);

        // IP Whitelist management
        Route::get('/settings/security/ip-whitelist', [SettingController::class, 'ipWhitelist']);
        Route::post('/settings/security/ip-whitelist', [SettingController::class, 'addIpWhitelist']);
        Route::delete('/settings/security/ip-whitelist/{id}', [SettingController::class, 'removeIpWhitelist']);
    });

    // Notification configurations
    Route::apiResource('notification-configs', NotificationConfigController::class);
    Route::patch('/notification-configs/{notificationConfig}/toggle', [NotificationConfigController::class, 'toggle']);
});
