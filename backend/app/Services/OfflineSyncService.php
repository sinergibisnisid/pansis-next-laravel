<?php

namespace App\Services;

use App\Enums\BufferedEventStatus;
use App\Models\DeviceEventBuffer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates replay of offline-buffered events.
 *
 * Flow:
 *   1. ingestBatch(): controller uploads N events at reconnect time. Each is
 *      stored in device_event_buffers with status=pending. Duplicates (same
 *      device_id + source_event_id) are silently ignored.
 *   2. processPending(): worker picks pending events in chronological order
 *      and dispatches them through the matching live handler.
 */
class OfflineSyncService
{
    /**
     * Maximum number of times a buffered event will be retried before being
     * marked as Failed.
     */
    public const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly MqttService $mqttService,
    ) {}

    /**
     * Ingest a batch of buffered events from a controller. Returns the count
     * of newly inserted rows (duplicates are skipped).
     *
     * Each item must have:
     *   - source_event_id (uuid generated on the controller)
     *   - topic (the MQTT topic this event would have been published on)
     *   - event_type (door_opened, fingerprint_scan, vault_open, …)
     *   - payload (associative array)
     *   - occurred_at (ISO8601 string)
     */
    public function ingestBatch(string $deviceId, array $events): int
    {
        $now = now();
        $inserted = 0;

        foreach ($events as $event) {
            // Required keys must be present, otherwise skip with a warning.
            if (
                empty($event['source_event_id'])
                || empty($event['topic'])
                || empty($event['event_type'])
                || empty($event['occurred_at'])
            ) {
                Log::warning('Offline event ignored: missing required keys', [
                    'device_id' => $deviceId,
                    'event' => $event,
                ]);
                continue;
            }

            // Dedup at the application layer to keep things idempotent even when
            // the unique constraint is bypassed (e.g. partial batches).
            $exists = DeviceEventBuffer::query()
                ->where('device_id', $deviceId)
                ->where('source_event_id', $event['source_event_id'])
                ->exists();

            if ($exists) {
                continue;
            }

            try {
                DeviceEventBuffer::create([
                    'device_id' => $deviceId,
                    'vault_id' => $event['vault_id'] ?? ($event['payload']['vault_id'] ?? null),
                    'source_event_id' => $event['source_event_id'],
                    'topic' => $event['topic'],
                    'event_type' => $event['event_type'],
                    'payload' => $event['payload'] ?? [],
                    'occurred_at' => $event['occurred_at'],
                    'uploaded_at' => $now,
                    'status' => BufferedEventStatus::Pending->value,
                    'attempts' => 0,
                ]);
                $inserted++;
            } catch (\Throwable $e) {
                Log::error('Failed to insert buffered event', [
                    'device_id' => $deviceId,
                    'source_event_id' => $event['source_event_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Offline event batch ingested', [
            'device_id' => $deviceId,
            'received' => count($events),
            'inserted' => $inserted,
        ]);

        return $inserted;
    }

    /**
     * Process up to $batchSize pending events, oldest occurred_at first.
     * Returns the number of events successfully processed.
     */
    public function processPending(int $batchSize = 100): int
    {
        $processed = 0;

        // Lock-free style: select pending IDs, then update each row individually
        // inside a small transaction so a long-running batch doesn't block writers.
        $pendingIds = DeviceEventBuffer::query()
            ->where('status', BufferedEventStatus::Pending->value)
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->orderBy('occurred_at')
            ->limit($batchSize)
            ->pluck('id');

        foreach ($pendingIds as $id) {
            $event = DeviceEventBuffer::find($id);
            if (!$event || $event->status !== BufferedEventStatus::Pending) {
                continue;
            }

            $this->processOne($event);

            if ($event->fresh()?->status === BufferedEventStatus::Processed) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Process a single buffered event.
     *
     * Routes it back through MqttService::handleIncomingMessage() so the same
     * pipeline used for live events handles it (door, button, vault, fingerprint…).
     */
    public function processOne(DeviceEventBuffer $event): void
    {
        $event->update([
            'status' => BufferedEventStatus::Processing->value,
            'attempts' => $event->attempts + 1,
        ]);

        try {
            DB::transaction(function () use ($event) {
                $payload = $event->payload ?? [];
                // Inject the original occurrence time so handlers can stamp
                // door_opened_at / etc. with the real time, not "now".
                $payload['occurred_at'] = $event->occurred_at->toIso8601String();

                $this->mqttService->handleIncomingMessage(
                    topic: $event->topic,
                    payload: json_encode($payload),
                );
            });

            $event->update([
                'status' => BufferedEventStatus::Processed->value,
                'processed_at' => now(),
                'last_error' => null,
            ]);
        } catch (\Throwable $e) {
            $finalState = $event->attempts + 1 >= self::MAX_ATTEMPTS
                ? BufferedEventStatus::Failed->value
                : BufferedEventStatus::Pending->value;

            $event->update([
                'status' => $finalState,
                'last_error' => substr($e->getMessage(), 0, 4000),
                'processed_at' => $finalState === BufferedEventStatus::Failed->value ? now() : null,
            ]);

            Log::error('Buffered event processing failed', [
                'event_id' => $event->id,
                'topic' => $event->topic,
                'attempts' => $event->attempts + 1,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark a buffered event as Skipped (e.g. operator decision: ignore replay).
     */
    public function skip(string $eventId, ?string $reason = null): void
    {
        $event = DeviceEventBuffer::findOrFail($eventId);
        $event->update([
            'status' => BufferedEventStatus::Skipped->value,
            'last_error' => $reason,
            'processed_at' => now(),
        ]);
    }

    /**
     * Stats useful for ops dashboards.
     */
    public function getStats(?string $deviceId = null): array
    {
        $query = DeviceEventBuffer::query();
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        return [
            'pending' => (clone $query)->where('status', BufferedEventStatus::Pending->value)->count(),
            'processing' => (clone $query)->where('status', BufferedEventStatus::Processing->value)->count(),
            'processed' => (clone $query)->where('status', BufferedEventStatus::Processed->value)->count(),
            'failed' => (clone $query)->where('status', BufferedEventStatus::Failed->value)->count(),
            'skipped' => (clone $query)->where('status', BufferedEventStatus::Skipped->value)->count(),
        ];
    }
}
