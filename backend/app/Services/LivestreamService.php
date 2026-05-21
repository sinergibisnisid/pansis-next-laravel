<?php

namespace App\Services;

use App\DTOs\Livestream\StartStreamDTO;
use App\Enums\StreamStatus;
use App\Models\LivestreamSession;
use App\Repositories\Contracts\LivestreamSessionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LivestreamService
{
    public function __construct(
        private readonly LivestreamSessionRepositoryInterface $livestreamSessionRepository,
    ) {}

    public function startStream(StartStreamDTO $dto): array
    {
        $streamToken = $this->generateStreamToken();
        $streamPath = $dto->streamPath ?? "vault-{$dto->vaultId}-" . Str::random(8);

        $session = $this->livestreamSessionRepository->startSession([
            'device_id' => $dto->deviceId,
            'vault_id' => $dto->vaultId,
            'branch_id' => $dto->branchId,
            'user_id' => $dto->userId,
            'stream_path' => $streamPath,
            'stream_token' => hash('sha256', $streamToken),
            'quality' => $dto->quality,
            'status' => StreamStatus::Active->value,
            'started_at' => now(),
        ]);

        $baseUrl = config('services.mediamtx.api_url', 'http://localhost:9997');

        return [
            'session' => $session,
            'stream_token' => $streamToken,
            'stream_path' => $streamPath,
            'rtsp_url' => "rtsp://localhost:8554/{$streamPath}",
            'webrtc_url' => "{$baseUrl}/webrtc/{$streamPath}",
            'hls_url' => "{$baseUrl}/hls/{$streamPath}",
        ];
    }

    public function stopStream(string $sessionId): void
    {
        $session = $this->livestreamSessionRepository->findOrFail($sessionId);

        $this->livestreamSessionRepository->stopSession($sessionId);

        $session->update([
            'status' => StreamStatus::Stopped->value,
            'stopped_at' => now(),
            'duration_seconds' => now()->diffInSeconds($session->started_at),
        ]);
    }

    public function getStreamUrl(string $sessionId): ?string
    {
        $session = $this->livestreamSessionRepository->findOrFail($sessionId);

        if ($session->status !== StreamStatus::Active->value) {
            return null;
        }

        $baseUrl = config('services.mediamtx.api_url', 'http://localhost:9997');

        return "{$baseUrl}/webrtc/{$session->stream_path}";
    }

    public function generateStreamToken(): string
    {
        return Str::random(64);
    }

    public function checkStreamHealth(string $sessionId): array
    {
        $session = $this->livestreamSessionRepository->findOrFail($sessionId);
        $baseUrl = config('services.mediamtx.api_url', 'http://localhost:9997');

        try {
            $response = Http::timeout(5)->get("{$baseUrl}/v3/paths/get/{$session->stream_path}");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'session_id' => $sessionId,
                    'status' => 'active',
                    'stream_path' => $session->stream_path,
                    'readers' => $data['readers'] ?? 0,
                    'ready' => $data['ready'] ?? false,
                    'bytes_received' => $data['bytesReceived'] ?? 0,
                ];
            }

            // Stream not found in MediaMTX
            $session->update(['status' => StreamStatus::Error->value]);

            return [
                'session_id' => $sessionId,
                'status' => 'error',
                'stream_path' => $session->stream_path,
                'error' => 'Stream not found in MediaMTX',
            ];
        } catch (\Throwable $e) {
            Log::error("Stream health check failed: {$e->getMessage()}", [
                'session_id' => $sessionId,
            ]);

            return [
                'session_id' => $sessionId,
                'status' => 'unknown',
                'stream_path' => $session->stream_path,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getActiveStreams(?string $branchId = null): Collection
    {
        $sessions = $this->livestreamSessionRepository->getActiveSessions();

        if ($branchId) {
            return $sessions->where('branch_id', $branchId);
        }

        return $sessions;
    }

    public function getMediaMtxPaths(): array
    {
        $baseUrl = config('services.mediamtx.api_url', 'http://localhost:9997');

        try {
            $response = Http::timeout(5)->get("{$baseUrl}/v3/paths/list");

            if ($response->successful()) {
                return $response->json('items', []);
            }

            return [];
        } catch (\Throwable $e) {
            Log::error("MediaMTX API call failed: {$e->getMessage()}");
            return [];
        }
    }
}
