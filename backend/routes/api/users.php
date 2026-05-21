<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'ensure.active'])->group(function () {
    Route::apiResource('users', UserController::class);

    // Additional user routes
    Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->middleware('permission:manage-users');

    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('permission:manage-users');

    Route::get('/users/{user}/activity', [UserController::class, 'activity']);

    Route::post('/users/{user}/assign-roles', [UserController::class, 'assignRoles'])
        ->middleware('permission:manage-roles');

    Route::post('/users/{user}/assign-permissions', [UserController::class, 'assignPermissions'])
        ->middleware('permission:manage-roles');

    // Roles and Permissions
    Route::get('/roles', [UserController::class, 'roles']);
    Route::get('/permissions', [UserController::class, 'permissions']);
});
