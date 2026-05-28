<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RouterMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Controller monitoring router PoE
class RouterController extends Controller
{
    public function __construct(
        private readonly RouterMonitoringService $routerService,
    ) {}

    // List semua router
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'status', 'vpn_enabled', 'failover_enabled']);
        $perPage = $request->integer('per_page', 15);

        $routers = $this->routerService->list($filters, $perPage);

        return $this->paginatedResponse($routers, 'Routers retrieved successfully');
    }

    // Detail router
    public function show(string $id): JsonResponse
    {
        $router = $this->routerService->find($id);

        return $this->successResponse($router, 'Router retrieved successfully');
    }

    // Simpan/update spec router
    public function upsertSpec(Request $request, string $deviceId): JsonResponse
    {
        $data = $request->validate([
            'lan_ip' => 'nullable|ip',
            'wan_ip_primary' => 'nullable|ip',
            'wan_ip_secondary' => 'nullable|ip',
            'isp_primary' => 'nullable|string|max:100',
            'isp_secondary' => 'nullable|string|max:100',
            'vpn_enabled' => 'boolean',
            'vpn_type' => 'nullable|string|in:wireguard,openvpn,ipsec',
            'vpn_endpoint' => 'nullable|string|max:255',
            'failover_enabled' => 'boolean',
            'poe_enabled' => 'boolean',
            'poe_ports' => 'nullable|integer|min:0|max:48',
            'lan_ports' => 'nullable|integer|min:0|max:48',
            'metadata' => 'nullable|array',
        ]);

        $spec = $this->routerService->upsertSpec($deviceId, $data);

        return $this->successResponse($spec, 'Router spec saved successfully');
    }

    // Router yang sedang failover
    public function failoverActive(): JsonResponse
    {
        $routers = $this->routerService->getFailoverActive();

        return $this->successResponse($routers, 'Failover-active routers retrieved');
    }

    // Router dengan masalah VPN
    public function vpnIssues(): JsonResponse
    {
        $routers = $this->routerService->getVpnIssues();

        return $this->successResponse($routers, 'Routers with VPN issues retrieved');
    }

    // Ringkasan monitoring router
    public function summary(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');
        $summary = $this->routerService->getSummary($branchId);

        return $this->successResponse($summary, 'Router summary retrieved');
    }
}