<?php

namespace App\Services;

use App\Enums\SnapshotTrigger;
use App\Models\Snapshot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SnapshotService
{
    public function captureSnapshot(
        string $vaultId,
        string $deviceId,
        ?string $userId,
        SnapshotTrigger $trigger
    ): void {
        // Dispatch snapshot capture job to queue
        dispatch(function () use ($vaultId, $deviceId, $userId, $trigger) {
            $vault = \App\Models\Vault::find($vaultId);
            $branchId = $vault?->branch_id ?? '';

            // In a real implementation, this would call the camera device API
            // to capture an image. For now, we dispatch the event.
            \Illuminate\Support\Facades\Event::dispatch('snapshot.capture.requested', [
                'vault_id' => $vaultId,
                'device_id' => $deviceId,
                'user_id' => $userId,
                'branch_id' => $branchId,
                'trigger' => $trigger->value,
            ]);
        })->onQueue('snapshots');
    }

    public function saveSnapshot(
        string $vaultId,
        string $deviceId,
        ?string $userId,
        string $branchId,
        SnapshotTrigger $trigger,
        string $imageData
    ): Snapshot {
        $filename = $this->generateFilename($vaultId, $trigger);
        $path = "snapshots/{$branchId}/{$vaultId}/" . now()->format('Y/m/d');

        // Decode base64 image data if needed
        $decodedImage = base64_decode($imageData, true);
        if ($decodedImage === false) {
            $decodedImage = $imageData;
        }

        // Store the image
        $fullPath = "{$path}/{$filename}";
        Storage::disk('local')->put($fullPath, $decodedImage);

        // Create snapshot record
        $snapshot = Snapshot::create([
            'vault_id' => $vaultId,
            'device_id' => $deviceId,
            'user_id' => $userId,
            'branch_id' => $branchId,
            'trigger' => $trigger,
            'file_path' => $fullPath,
            'file_size' => strlen($decodedImage),
            'mime_type' => 'image/jpeg',
            'captured_at' => now(),
        ]);

        return $snapshot;
    }

    public function getSnapshotsByVault(
        string $vaultId,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): Collection {
        $query = Snapshot::where('vault_id', $vaultId)
            ->orderBy('captured_at', 'desc');

        if ($dateFrom) {
            $query->where('captured_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('captured_at', '<=', $dateTo);
        }

        return $query->get();
    }

    public function deleteOldSnapshots(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);

        $oldSnapshots = Snapshot::where('captured_at', '<', $cutoffDate)->get();
        $deletedCount = 0;

        foreach ($oldSnapshots as $snapshot) {
            // Delete file from storage
            if ($snapshot->file_path && Storage::disk('local')->exists($snapshot->file_path)) {
                Storage::disk('local')->delete($snapshot->file_path);
            }

            $snapshot->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    private function generateFilename(string $vaultId, SnapshotTrigger $trigger): string
    {
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(8);

        return "{$trigger->value}_{$timestamp}_{$random}.jpg";
    }
}
