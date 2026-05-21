<?php

use Illuminate\Support\Facades\Route;

// Health check (no auth)
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'PANSIN ACCESS API is running',
        'data' => [
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ],
    ]);
});

// Include route modules
require __DIR__ . '/api/auth.php';
require __DIR__ . '/api/users.php';
require __DIR__ . '/api/monitoring.php';
require __DIR__ . '/api/devices.php';
require __DIR__ . '/api/reports.php';
require __DIR__ . '/api/mqtt.php';
require __DIR__ . '/api/settings.php';
