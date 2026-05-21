<?php

namespace App\Exceptions;

use Exception;

class SessionTimeoutException extends Exception
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $vaultId,
        public readonly int $duration,
        string $message = 'Sesi vault telah habis waktu.',
    ) {
        parent::__construct($message, 408);
    }

    public function getStatusCode(): int
    {
        return 408;
    }
}
