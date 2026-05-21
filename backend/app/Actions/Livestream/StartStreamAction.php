<?php

namespace App\Actions\Livestream;

use App\DTOs\Livestream\StartStreamDTO;
use App\Models\LivestreamSession;
use App\Services\LivestreamService;

class StartStreamAction
{
    public function __construct(
        private readonly LivestreamService $livestreamService,
    ) {}

    public function execute(StartStreamDTO $dto): LivestreamSession
    {
        $result = $this->livestreamService->startStream($dto);

        return $result['session'];
    }
}
