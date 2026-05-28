<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CorrelationIdMiddleware;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SecureHeaders;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\LogApiRequest;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            CorrelationIdMiddleware::class,
            ForceJsonResponse::class,
            SecureHeaders::class,
        ]);

        $middleware->api(append: [
            LogApiRequest::class,
        ]);

        $middleware->alias([
            'device.auth' => \App\Http\Middleware\DeviceTokenAuth::class,
            'ip.whitelist' => \App\Http\Middleware\IpWhitelist::class,
            'branch.access' => \App\Http\Middleware\CheckBranchAccess::class,
            'active.user' => EnsureActiveUser::class,
            // Backwards-compatible alias used by existing route files.
            'ensure.active' => EnsureActiveUser::class,
            // Backwards-compatible alias used by existing route files.
            'device-token-auth' => \App\Http\Middleware\DeviceTokenAuth::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        $middleware->throttleApi('60,1');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Exception handling is done in App\Exceptions\Handler
    })
    ->create();
