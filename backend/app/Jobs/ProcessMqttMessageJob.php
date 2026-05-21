<?php

namespace App\Jobs;

use App\Services\MqttService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMqttMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly string $topic,
        public readonly string $payload,
        public readonly string $receivedAt,
    ) {
        $this->onQueue('mqtt');
    }

    public function handle(MqttService $mqttService): void
    {
        $decodedPayload = json_decode($this->payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Invalid JSON payload received from MQTT', [
                'topic' => $this->topic,
                'payload' => $this->payload,
                'error' => json_last_error_msg(),
            ]);

            throw new \RuntimeException("Invalid JSON payload: " . json_last_error_msg());
        }

        $mqttService->handleIncomingMessage(
            topic: $this->topic,
            payload: $decodedPayload,
            receivedAt: $this->receivedAt,
        );

        Log::info('MQTT message processed successfully', [
            'topic' => $this->topic,
            'received_at' => $this->receivedAt,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to process MQTT message', [
            'topic' => $this->topic,
            'payload' => $this->payload,
            'received_at' => $this->receivedAt,
            'error' => $exception->getMessage(),
        ]);
    }
}
