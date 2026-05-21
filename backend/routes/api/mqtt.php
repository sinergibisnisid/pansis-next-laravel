<?php

use App\Http\Controllers\Api\V1\MqttLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'ensure.active'])->group(function () {

    // MQTT log routes - admin only
    Route::middleware('role:Super Admin|Admin Pusat')->group(function () {
        Route::get('/mqtt-logs', [MqttLogController::class, 'index']);
        Route::get('/mqtt-logs/statistics', [MqttLogController::class, 'statistics']);
        Route::get('/mqtt-logs/{mqttLog}', [MqttLogController::class, 'show']);
        Route::get('/mqtt-logs/device/{device}', [MqttLogController::class, 'byDevice']);
        Route::get('/mqtt-logs/topic/{topic}', [MqttLogController::class, 'byTopic'])
            ->where('topic', '.*');
    });
});
