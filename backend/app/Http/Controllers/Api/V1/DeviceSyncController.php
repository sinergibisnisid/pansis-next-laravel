<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SyncOfflineEventsJob;
use App\Services\OfflineSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Endpoints used by IoT controllers to upload events that were captured
 * locally while the device was offline.
 *
 * Auth: device.auth middleware (X-Device-Serial + X-Device-Token headers).
 */
class DeviceSyncController extends Controller
{
    public function __construct(
        private readonly OfflineSyncService $offlineSyncService,
    ) {}

    /**
     * POST /api/v1/devices/sync/events
     *
     * Body:
     * {
     *   "events": [
     *     {
     *       "source_event_id": "uuid",
     *       "topic": "door/{vault_id}/opened",
     *       "event_type": "door_opened",
     *       "vault_id": "uuid",
     *       "occurred_at": "2026-05-27T10:00:00Z",
     *       "payload": { "vault_id": "uuid", "device_id": "uuid", ... }
     *     },
     *     ...
     *   ]
     * }
     */
    public function ingest(Request $request): JsonResponse
    {
        $device = $request->get('authenticated_device');
        if (!$device) {
            return $this->errorResponse('Device authentication required', 401);
        }

        $validator = Validator::make($request->all(), [
            'events' => 'required|array|min:1|max:1000',
            'events.*.source_event_id' => 'required|uuid',
            'events.*.topic' => 'required|string|max:255',
            'events.*.event_type' => 'required|string|max:64',
            'events.*.occurred_at' => 'required|date',
            'events.*.payload' => 'sometimes|array',
            'events.*.vault_id' => 'sometimes|nullable|uuid',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $inserted = $this->offlineSyncService->ingestBatch(
            deviceId: $device->id,
            events: $request->input('events', []),
        );

        // Kick a sync job right away so events are replayed soon, not on next cron tick.
        SyncOfflineEventsJob::dispatch(batchSize: max($inserted, 50));

        return $this->successResponse(
            data: [
                'received' => count($request->input('events', [])),
                'inserted' => $inserted,
                'duplicates' => count($request->input('events', [])) - $inserted,
            ],
            message: 'Offline events ingested',
        );
    }

    /**
     * GET /api/v1/devices/sync/stats
     * Returns pending/processed counts for the calling device.
     */
    public function stats(Request $request): JsonResponse
    {
        $device = $request->get('authenticated_device');
        if (!$device) {
            return $this->errorResponse('Device authentication required', 401);
        }

        return $this->successResponse(
            data: $this->offlineSyncService->getStats($device->id),
        );
    }
}
