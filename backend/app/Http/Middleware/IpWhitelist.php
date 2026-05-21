<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class IpWhitelist
{
    /**
     * Check if request IP is in the allowed list.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('security.ip_whitelist_enabled', false)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        // Check config-based whitelist
        $configWhitelist = config('security.ip_whitelist', []);

        if (in_array($clientIp, $configWhitelist)) {
            return $next($request);
        }

        // Check database-based whitelist
        $dbWhitelisted = DB::table('ip_whitelists')
            ->where('ip_address', $clientIp)
            ->where('is_active', true)
            ->exists();

        if ($dbWhitelisted) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Access denied. Your IP address is not whitelisted.',
        ], 403);
    }
}
