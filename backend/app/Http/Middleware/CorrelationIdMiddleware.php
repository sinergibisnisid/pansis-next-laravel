<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Assigns a correlation_id to every incoming HTTP request and:
 *   - binds it to the container as singleton 'correlation_id' so logging,
 *     queue jobs, MQTT publishes, and broadcast events can pick it up.
 *   - injects it into Log::shareContext() so all log lines automatically
 *     include the id without explicit calls.
 *   - echoes it back as X-Correlation-ID + X-Request-Id response headers
 *     so the caller (e.g. frontend) can quote the id when reporting an issue.
 *
 * The id is taken from the X-Correlation-ID / X-Request-Id request header if
 * present (so a frontend or upstream gateway can propagate it), otherwise we
 * generate a new ULID-like 16-char string.
 */
class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $cid = (string) (
            $request->header('X-Correlation-ID')
            ?? $request->header('X-Request-Id')
            ?? Str::random(16)
        );

        // Bind for the rest of the lifecycle so JsonStdoutLogger can read it.
        app()->instance('correlation_id', $cid);
        $request->attributes->set('correlation_id', $cid);

        // Share into Laravel logger context so every Log::* picks it up.
        if (method_exists(Log::class, 'shareContext')) {
            Log::shareContext(['correlation_id' => $cid]);
        }

        $response = $next($request);

        $response->headers->set('X-Correlation-ID', $cid);
        $response->headers->set('X-Request-Id', $cid);

        return $response;
    }
}
