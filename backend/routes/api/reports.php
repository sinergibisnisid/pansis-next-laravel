<?php

use App\Http\Controllers\Api\V1\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'ensure.active'])->group(function () {

    Route::apiResource('reports', ReportController::class);

    Route::post('/reports/{report}/generate', [ReportController::class, 'generate']);
    Route::get('/reports/{report}/download', [ReportController::class, 'download']);
    Route::get('/reports/{report}/status', [ReportController::class, 'status']);

    // Scheduled reports
    Route::get('/reports-scheduled', [ReportController::class, 'scheduled']);
    Route::post('/reports-scheduled', [ReportController::class, 'createScheduled']);
    Route::put('/reports-scheduled/{report}', [ReportController::class, 'updateScheduled']);
    Route::delete('/reports-scheduled/{report}', [ReportController::class, 'deleteScheduled']);

    // Report types and templates
    Route::get('/report-types', [ReportController::class, 'types']);
    Route::get('/report-templates', [ReportController::class, 'templates']);
});
