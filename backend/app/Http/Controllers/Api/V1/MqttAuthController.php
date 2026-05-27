<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DeviceProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints called by EMQX broker (HTTP auth/ACL hook) to authenticate
 * MQTT-publishing devices and authorize topic access.
 *
 * EMQX configuration (emqx.conf):
 *   auth.http.auth_req = http://backend/api/v1/mqtt/auth
 *   auth.http.acl_req  = http://backend/api/v1/mqtt/acl
 *
 * Both endpoints are POSTs and must respond:
 *   200 OK     → allowed
 *   403 Forbid → denied
 */
class MqttAuthController extends Controller
{
    public function __construct(
        private readonly DeviceProvisioningService $provisioningService,
    ) {}

    /**
     * POST /api/v1/mqtt/auth
     *
     * Body (from EMQX): { "username": "...", "password": "...", "clientid": "..." }
     */
    public function authenticate(Request $request): JsonResponse
    {
        $username = (string) $request->input('username', '');
        $password = (string) $request->input('password', '');

        if ($username === '' || $password === '') {
            return response()->json(['result' => 'deny'], 403);
        }

        $credential = $this->provisioningService->authenticateMqtt($username, $password);

        if (!$credential) {
            return response()->json(['result' => 'deny'], 403);
        }

        return response()->json([
            'result' => 'allow',
            'is_superuser' => false,
        ], 200);
    }

    /**
     * POST /api/v1/mqtt/acl
     *
     * Body (from EMQX): { "username": "...", "topic": "...", "action": "publish|subscribe" }
     */
    public function authorize(Request $request): JsonResponse
    {
        $username = (string) $request->input('username', '');
        $topic = (string) $request->input('topic', '');
        $action = (string) $request->input('action', '');

        if ($username === '' || $topic === '' || !in_array($action, ['publish', 'subscribe'], true)) {
            return response()->json(['result' => 'deny'], 403);
        }

        $credential = \App\Models\DeviceMqttCredential::where('mqtt_username', $username)
            ->where('is_active', true)
            ->first();

        if (!$credential || !$credential->isUsable()) {
            return response()->json(['result' => 'deny'], 403);
        }

        if (!$this->provisioningService->isTopicAllowed($credential, $topic, $action)) {
            return response()->json(['result' => 'deny'], 403);
        }

        return response()->json(['result' => 'allow'], 200);
    }
}
