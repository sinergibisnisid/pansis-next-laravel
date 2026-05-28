<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DeviceType;
use App\Http\Controllers\Controller;
use App\Services\DeviceProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Two-step device provisioning endpoints.
 *
 *   POST /api/v1/devices/provision/codes      [auth:sanctum + permission devices.register]
 *       Admin generates a one-time claim code.
 *
 *   POST /api/v1/devices/provision/claim      [public, rate-limited]
 *       Device claims itself with the code, receives api_token + mqtt creds.
 *
 *   POST /api/v1/devices/{id}/rotate-mqtt     [auth:sanctum + permission devices.manage]
 *       Admin rotates a device's MQTT credentials.
 */
class DeviceProvisioningController extends Controller
{
    public function __construct(
        private readonly DeviceProvisioningService $provisioningService,
    ) {}

    /**
     * Admin generates a claim code. Plaintext code is returned ONCE.
     */
    public function generateCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|uuid|exists:branches,id',
            'vault_id' => 'nullable|uuid|exists:vaults,id',
            'expected_device_type' => 'required|string|in:' . implode(',', DeviceType::values()),
            'expected_device_name' => 'nullable|string|max:255',
            'ttl_minutes' => 'nullable|integer|min:1|max:1440',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $admin = $request->user();
        if (!$admin) {
            return $this->errorResponse('Unauthorized', 401);
        }

        $result = $this->provisioningService->generateClaimCode(
            branchId: $request->input('branch_id'),
            vaultId: $request->input('vault_id'),
            expectedType: DeviceType::from($request->input('expected_device_type')),
            expectedName: $request->input('expected_device_name'),
            admin: $admin,
            ttlMinutes: (int) $request->input('ttl_minutes', 60),
        );

        return $this->successResponse(
            data: [
                'claim_code' => $result['claim_code'],
                'claim_code_id' => $result['claim_code_id'],
                'code_suffix' => $result['code_suffix'],
                'expires_at' => $result['expires_at'],
                'warning' => 'Code is shown only once. Copy it now.',
            ],
            message: 'Claim code generated',
            code: 201,
        );
    }

    /**
     * Device side: claim itself with the code.
     *
     * No auth — this endpoint is how the device first proves it knows the
     * out-of-band claim code an admin gave it. Should be rate-limited.
     */
    public function claim(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'claim_code' => 'required|string|size:8',
            'serial_number' => 'required|string|max:128',
            'mac_address' => 'nullable|string|max:64',
            'firmware_version' => 'nullable|string|max:64',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $result = $this->provisioningService->claimDevice(
            code: strtoupper((string) $request->input('claim_code')),
            serialNumber: $request->input('serial_number'),
            macAddress: $request->input('mac_address'),
            ipAddress: $request->ip(),
            firmwareVersion: $request->input('firmware_version'),
        );

        return $this->successResponse(
            data: [
                'device_id' => $result['device']->id,
                'serial_number' => $result['device']->serial_number,
                'api_token' => $result['api_token'],
                'mqtt' => [
                    'username' => $result['mqtt_username'],
                    'password' => $result['mqtt_password'],
                    'host' => config('mqtt.host'),
                    'port' => config('mqtt.port'),
                    'tls_port' => config('mqtt.tls_port'),
                    'use_tls' => (bool) config('mqtt.use_tls'),
                ],
                'acl' => [
                    'publish' => $result['publish_acl'],
                    'subscribe' => $result['subscribe_acl'],
                ],
                'warning' => 'api_token and mqtt.password are shown only once. Store them securely.',
            ],
            message: 'Device provisioned successfully',
            code: 201,
        );
    }

    /**
     * Admin rotates a device's MQTT credentials.
     */
    public function rotateMqtt(Request $request, string $deviceId): JsonResponse
    {
        $device = \App\Models\Device::findOrFail($deviceId);

        $result = $this->provisioningService->rotateMqttCredentials($device);

        return $this->successResponse(
            data: [
                'mqtt_username' => $result['credential']->mqtt_username,
                'mqtt_password' => $result['mqtt_password'],
                'rotated_at' => $result['credential']->created_at,
                'warning' => 'mqtt_password is shown only once.',
            ],
            message: 'MQTT credentials rotated',
        );
    }

    // List semua claim code (aktif, expired, terpakai)
    public function listCodes(Request $request): JsonResponse
    {
        $query = \App\Models\DeviceClaimCode::query()
            ->with(['branch', 'creator']);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->whereNull('used_at')
                    ->where('expires_at', '>', now());
            } elseif ($status === 'used') {
                $query->whereNotNull('used_at');
            } elseif ($status === 'expired') {
                $query->whereNull('used_at')
                    ->where('expires_at', '<=', now());
            }
        }

        $perPage = $request->integer('per_page', 15);
        $codes = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->paginatedResponse($codes, 'Claim codes retrieved');
    }

    // Revoke claim code yang belum terpakai
    public function revokeCode(string $codeId): JsonResponse
    {
        $code = \App\Models\DeviceClaimCode::findOrFail($codeId);

        if ($code->used_at) {
            return $this->errorResponse('Code has already been used', 422);
        }

        if ($code->isExpired()) {
            return $this->errorResponse('Code has already expired', 422);
        }

        $code->update(['expires_at' => now()]);

        return $this->successResponse($code->fresh(), 'Claim code revoked');
    }

    // List device yang sudah provisioned + status MQTT
    public function listProvisioned(Request $request): JsonResponse
    {
        $query = \App\Models\Device::query()
            ->whereNotNull('device_token')
            ->with(['branch', 'vault', 'mqttCredential']);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = $request->integer('per_page', 15);
        $devices = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->paginatedResponse($devices, 'Provisioned devices retrieved');
    }

    // Statistik provisioning
    public function statistics(): JsonResponse
    {
        $totalCodes = \App\Models\DeviceClaimCode::count();
        $activeCodes = \App\Models\DeviceClaimCode::whereNull('used_at')
            ->where('expires_at', '>', now())
            ->count();
        $usedCodes = \App\Models\DeviceClaimCode::whereNotNull('used_at')->count();
        $expiredCodes = \App\Models\DeviceClaimCode::whereNull('used_at')
            ->where('expires_at', '<=', now())
            ->count();

        $totalProvisioned = \App\Models\Device::whereNotNull('device_token')->count();
        $withMqtt = \App\Models\DeviceMqttCredential::where('is_active', true)->count();

        return $this->successResponse([
            'claim_codes' => [
                'total' => $totalCodes,
                'active' => $activeCodes,
                'used' => $usedCodes,
                'expired' => $expiredCodes,
            ],
            'devices' => [
                'total_provisioned' => $totalProvisioned,
                'with_active_mqtt' => $withMqtt,
            ],
        ], 'Provisioning statistics retrieved');
    }
}
