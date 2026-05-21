<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceTokenAuth
{
    /**
     * Validate device authentication via X-Device-Token and X-Device-Serial headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Device-Token');
        $serial = $request->header('X-Device-Serial');

        if (!$token || !$serial) {
            return response()->json([
                'success' => false,
                'message' => 'Device authentication required. Missing X-Device-Token or X-Device-Serial header.',
            ], 401);
        }

        $device = Device::where('serial_number', $serial)
            ->where('device_token', $token)
            ->where('is_active', true)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid device credentials.',
            ], 401);
        }

        $request->merge(['authenticated_device' => $device]);

        return $next($request);
    }
}
