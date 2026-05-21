<?php

namespace App\Exceptions;

use Exception;

class MqttConnectionException extends Exception
{
    public function __construct(
        public readonly string $broker,
        public readonly string $reason,
        string $message = 'Koneksi MQTT gagal.',
    ) {
        parent::__construct($message, 503);
    }

    public function getStatusCode(): int
    {
        return 503;
    }
}
