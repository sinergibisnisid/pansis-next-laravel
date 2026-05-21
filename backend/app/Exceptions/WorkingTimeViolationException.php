<?php

namespace App\Exceptions;

use Exception;

class WorkingTimeViolationException extends Exception
{
    public function __construct(
        public readonly string $branchId,
        public readonly string $vaultId,
        public readonly string $attemptedAt,
        string $message = 'Akses ditolak. Di luar jam kerja yang ditentukan.',
    ) {
        parent::__construct($message, 403);
    }

    public function getStatusCode(): int
    {
        return 403;
    }
}
