<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Livestream\StartStreamAction;
use App\Actions\Livestream\StopStreamAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Livestream\StartStreamRequest;
use App\Services\LivestreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LivestreamController extends Controller
{
    public function __construct(
        private readonly LivestreamService $livestreamService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id', 'vault_id', 'status', 'device_id']);
        $perPage = $request->integer('per_page', 15);

        $streams = $this->livestreamService->paginate($filters, $perPage);

        return $this->paginatedResponse($streams, 'Streams retrieved successfully');
    }

    public function show(string $id): JsonResponse
    {
        $stream = $this->livestreamService->findOrFail($id);
        $stream->load(['device', 'vault']);

        return $this->successResponse($stream, 'Stream retrieved successfully');
    }

    public function start(StartStreamRequest $request, StartStreamAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse($result['data'], 'Stream started successfully', 201);
    }

    public function stop(string $id, StopStreamAction $action): JsonResponse
    {
        $result = $action->execute($id);

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse($result['data'], 'Stream stopped successfully');
    }

    public function streamUrl(string $id): JsonResponse
    {
        $stream = $this->livestreamService->findOrFail($id);

        if ($stream->status !== 'active') {
            return $this->errorResponse('Stream is not active', 422);
        }

        $url = $this->livestreamService->getWebRtcUrl($stream);

        return $this->successResponse([
            'stream_id' => $stream->id,
            'url' => $url,
            'protocol' => 'webrtc',
            'expires_at' => now()->addMinutes(30)->toIso8601String(),
        ], 'Stream URL retrieved');
    }

    public function health(string $id): JsonResponse
    {
        $stream = $this->livestreamService->findOrFail($id);
        $health = $this->livestreamService->checkHealth($stream);

        return $this->successResponse([
            'stream_id' => $stream->id,
            'status' => $stream->status,
            'health' => $health,
        ], 'Stream health retrieved');
    }
}
