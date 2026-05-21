<?php

namespace App\Exceptions;

use Exception;

class VaultAccessDeniedException extends Exception
{
    public function __construct(
        public readonly string $reason,
        public readonly string $vaultId,
        public readonly string $userId,
        string $message = 'Akses vault ditolak.',
    ) {
        parent::__construct($message, 403);
    }

    public function getStatusCode(): int
    {
        return 403;
    }
}
