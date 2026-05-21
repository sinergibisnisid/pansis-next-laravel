<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    /**
     * Log API request details.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        // Only log in production and skip health-check endpoints
        if (!app()->environment('production')) {
            return $response;
        }

        if ($this->isHealthCheck($request)) {
            return $response;
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        Log::channel('api')->info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
            'response_status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
        ]);

        return $response;
    }

    /**
     * Determine if the request is a health check endpoint.
     */
    protected function isHealthCheck(Request $request): bool
    {
        $healthCheckPaths = [
            'api/health',
            'health',
        ];

        return in_array($request->path(), $healthCheckPaths);
    }
}
