<?php

namespace App\Actions\Livestream;

use App\Services\LivestreamService;

class StopStreamAction
{
    public function __construct(
        private readonly LivestreamService $livestreamService,
    ) {}

    public function execute(string $sessionId): void
    {
        $this->livestreamService->stopStream($sessionId);
    }
}
