<?php

namespace App\Exceptions;

use Exception;

class DeviceAuthenticationException extends Exception
{
    public function __construct(
        public readonly string $serialNumber,
        public readonly string $reason,
        string $message = 'Autentikasi perangkat gagal.',
    ) {
        parent::__construct($message, 401);
    }

    public function getStatusCode(): int
    {
        return 401;
    }
}
