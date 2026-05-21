<?php

namespace App\Jobs;

use App\Enums\SnapshotTrigger;
use App\Models\Device;
use App\Services\SnapshotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CaptureSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [5, 15, 30];

    public function __construct(
        public readonly string $vaultId,
        public readonly string $deviceId,
        public readonly ?string $userId,
        public readonly string $branchId,
        public readonly SnapshotTrigger $trigger,
    ) {
        $this->onQueue('snapshots');
    }

    public function handle(SnapshotService $snapshotService): void
    {
        $device = Device::findOrFail($this->deviceId);

        $snapshotUrl = "http://{$device->ip_address}/api/snapshot";

        $response = Http::timeout(30)->get($snapshotUrl);

        if (!$response->successful()) {
            Log::error('Failed to capture snapshot from camera', [
                'device_id' => $this->deviceId,
                'vault_id' => $this->vaultId,
                'status_code' => $response->status(),
            ]);

            throw new \RuntimeException("Failed to capture snapshot: HTTP {$response->status()}");
        }

        $imageData = $response->body();

        $snapshotService->saveSnapshot(
            vaultId: $this->vaultId,
            deviceId: $this->deviceId,
            userId: $this->userId,
            branchId: $this->branchId,
            trigger: $this->trigger,
            imageData: $imageData,
        );

        Log::info('Snapshot captured and saved successfully', [
            'vault_id' => $this->vaultId,
            'device_id' => $this->deviceId,
            'trigger' => $this->trigger->value,
        ]);
    }
}
